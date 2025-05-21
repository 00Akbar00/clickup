import { defineStore } from 'pinia'
import axios from '../utils/axios'
import { useTeamspaceStore } from './teamspaceStore'
import { useListStore } from './listStore'

export const useProjectStore = defineStore('project', {
  state: () => ({
    loading: false,
    error: null
  }),

  actions: {
    // Helper method to normalize project data
    normalizeProject(project) {
      const normalizedProject = { ...project }
      
      // Ensure ID is consistent
      if (!normalizedProject.id) {
        if (normalizedProject._id) {
          normalizedProject.id = normalizedProject._id
        } else if (normalizedProject.project_id) {
          normalizedProject.id = normalizedProject.project_id
        }
      }
      
      // Ensure lists is initialized as an array if not present
      if (!normalizedProject.lists) {
        normalizedProject.lists = []
      }
      
      return normalizedProject
    },

    // Method to find a project by ID in any teamspace
    getProjectById(projectId) {
      if (!projectId) return null
      
      const teamspaceStore = useTeamspaceStore()
      
      // Search in all teamspaces to find the project
      for (const teamspace of teamspaceStore.teamspaces) {
        if (!teamspace.projects) continue
        
        const project = teamspace.projects.find(p => 
          p.id === projectId || 
          p._id === projectId || 
          p.project_id === projectId
        )
        
        if (project) {
          return project
        }
      }
      
      return null
    },

    // Add a new project to a teamspace
    async addProject(teamspaceId, project) {
      if (!teamspaceId) {
        throw new Error('No teamspace ID provided')
      }
      
      try {
        this.loading = true
        this.error = null
        
        // Make API call to create project with correct endpoint
        const response = await axios.post(`/projects/${teamspaceId}`, {
          name: project.name,
          description: project.description || 'Project created in ClickFlow',
          color_code: project.color_code || '#' + Math.floor(Math.random()*16777215).toString(16),
          visibility: project.visibility || 'public'
        })
        
        // Extract the created project data
        let createdProject = null
        if (response.data.project) {
          createdProject = response.data.project
        } else if (response.data.data) {
          createdProject = response.data.data
        } else {
          createdProject = response.data
        }
        
        // Normalize the project data
        const normalizedProject = this.normalizeProject(createdProject)
        
        // Update the project in the teamspace store
        const teamspaceStore = useTeamspaceStore()
        teamspaceStore.updateProject(teamspaceId, normalizedProject)
        
        return normalizedProject
      } catch (error) {
        // Extract meaningful error message
        let errorMessage = 'Failed to create project'
        if (error.response?.data?.message) {
          errorMessage = error.response.data.message
        } else if (error.response?.data?.error) {
          errorMessage = error.response.data.error
        } else if (error.message) {
          errorMessage = error.message
        }
        
        this.error = errorMessage
        throw error
      } finally {
        this.loading = false
      }
    },

    // Fetch details for a specific project by ID
    async fetchProjectDetails(projectId) {
      if (!projectId) {
        throw new Error('No project ID provided')
      }
      
      try {
        this.loading = true
        this.error = null
        
        // Get auth token from localStorage
        const userData = localStorage.getItem('authUser')
        let token = null
        if (userData) {
          const auth = JSON.parse(userData)
          token = auth.token || localStorage.getItem('authToken')
        }
        
        if (!token) {
          throw new Error('Authentication token not found')
        }
        
        // Build request headers with Bearer token
        const headers = {
          'Accept': 'application/json',
          'Authorization': token.startsWith('Bearer ') ? token : `Bearer ${token}`
        }
        
        // Make the API call to get project by ID
        const response = await axios.get(`/projects/${projectId}`, { headers })
        
        // Handle different response formats
        let fetchedProject = null
        
        if (response.data) {
          if (response.data.project) {
            fetchedProject = response.data.project
          } else if (response.data.data) {
            fetchedProject = response.data.data
          } else if (typeof response.data === 'object' && (response.data.id || response.data._id || response.data.project_id)) {
            // Response is the project object itself
            fetchedProject = response.data
          }
        }
        
        if (!fetchedProject) {
          throw new Error('Invalid project data received from API')
        }
        
        // Normalize the project data
        const normalizedProject = this.normalizeProject(fetchedProject)
        
        // Fetch the lists for this project
        try {
          const listStore = useListStore()
          const lists = await listStore.fetchProjectLists(projectId)
          normalizedProject.lists = lists
        } catch (listError) {
          normalizedProject.lists = normalizedProject.lists || []
        }
        
        // Update project in teamspace store if team_id is available
        if (normalizedProject.team_id) {
          const teamspaceStore = useTeamspaceStore()
          teamspaceStore.updateProject(normalizedProject.team_id, normalizedProject)
        }
        
        return normalizedProject
      } catch (error) {
        // Extract meaningful error message
        let errorMessage = `Failed to fetch project with ID: ${projectId}`
        if (error.response?.data?.message) {
          errorMessage = error.response.data.message
        } else if (error.response?.data?.error) {
          errorMessage = error.response.data.error
        } else if (error.message) {
          errorMessage = error.message
        }
        
        this.error = errorMessage
        throw error
      } finally {
        this.loading = false
      }
    },

    // Get a project by ID (first tries API, then falls back to local data)
    async getProject(teamspaceId, projectId) {
      try {
        // First try to get fresh data from the API
        return await this.fetchProjectDetails(projectId)
      } catch (error) {
        // Special handling for 500 errors which might still have resulted in successful creation
        if (error.response?.status === 500) {
          // Try to refresh the project list
          try {
            const listStore = useListStore()
            await listStore.fetchProjectLists(projectId)
          } catch (refreshError) {
            // Ignore refresh error
          }
        }
        
        // Fallback to local data from teamspace store
        const teamspaceStore = useTeamspaceStore()
        return teamspaceStore.findProject(teamspaceId, projectId)
      }
    },

    // Update a project
    async updateProject(projectId, updatedData) {
      if (!projectId) {
        throw new Error('No project ID provided')
      }
      
      try {
        this.loading = true
        this.error = null
        
        // Get auth token from localStorage
        const userData = localStorage.getItem('authUser')
        let token = null
        if (userData) {
          const auth = JSON.parse(userData)
          token = auth.token || localStorage.getItem('authToken')
        }
        
        if (!token) {
          throw new Error('Authentication token not found')
        }
        
        // Ensure token has Bearer prefix
        const bearerToken = token.startsWith('Bearer ') ? token : `Bearer ${token}`
        
        // Build request headers with Bearer token
        const headers = {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'Authorization': bearerToken
        }
        
        // Prepare the update data
        const updateData = {
          name: updatedData.name,
          description: updatedData.description || '',
          color_code: updatedData.color_code || '',
          visibility: updatedData.visibility || 'public'
        }
        
        // Make API call to update project
        const response = await axios.put(`/projects/${projectId}`, updateData, { headers })
        
        // Get the updated project data
        const updatedProject = await this.fetchProjectDetails(projectId)
        
        // Update the project in teamspace store
        if (updatedProject.team_id) {
          const teamspaceStore = useTeamspaceStore()
          teamspaceStore.updateProject(updatedProject.team_id, updatedProject)
        }
        
        return updatedProject
      } catch (error) {
        // Extract meaningful error message
        let errorMessage = `Failed to update project with ID: ${projectId}`
        if (error.response?.data?.message) {
          errorMessage = error.response.data.message
        } else if (error.response?.data?.error) {
          errorMessage = error.response.data.error
        } else if (error.message) {
          errorMessage = error.message
        }
        
        this.error = errorMessage
        throw error
      } finally {
        this.loading = false
      }
    },

    // Delete a project
    async deleteProject(projectId) {
      if (!projectId) {
        throw new Error('No project ID provided')
      }
      
      try {
        this.loading = true
        this.error = null
        
        // Get auth token from localStorage
        const userData = localStorage.getItem('authUser')
        let token = null
        if (userData) {
          const auth = JSON.parse(userData)
          token = auth.token || localStorage.getItem('authToken')
        }
        
        if (!token) {
          throw new Error('Authentication token not found')
        }
        
        // Ensure token has Bearer prefix
        const bearerToken = token.startsWith('Bearer ') ? token : `Bearer ${token}`
        
        // Build request headers with Bearer token
        const headers = {
          'Accept': 'application/json',
          'Authorization': bearerToken
        }
        
        // Get project details first to know which teamspace it belongs to
        let teamspaceId = null
        try {
          const project = await this.fetchProjectDetails(projectId)
          teamspaceId = project.team_id
        } catch (error) {
          // If we can't get project details, continue with delete anyway
        }
        
        // Make API call to delete project
        const response = await axios.delete(`/projects/${projectId}`, { headers })
        
        // If we know the teamspace, remove the project from it
        if (teamspaceId) {
          const teamspaceStore = useTeamspaceStore()
          const teamspace = teamspaceStore.getTeamspaceLocal(teamspaceId)
          
          if (teamspace && teamspace.projects) {
            const projectIndex = teamspace.projects.findIndex(p => 
              p.id === projectId || 
              p._id === projectId || 
              p.project_id === projectId
            )
            
            if (projectIndex !== -1) {
              teamspace.projects.splice(projectIndex, 1)
            }
          }
        }
        
        return response.data
      } catch (error) {
        // Extract meaningful error message
        let errorMessage = `Failed to delete project with ID: ${projectId}`
        if (error.response?.data?.message) {
          errorMessage = error.response.data.message
        } else if (error.response?.data?.error) {
          errorMessage = error.response.data.error
        } else if (error.message) {
          errorMessage = error.message
        }
        
        this.error = errorMessage
        throw error
      } finally {
        this.loading = false
      }
    }
  }
}) 