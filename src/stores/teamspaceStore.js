import { defineStore } from 'pinia'
import axios from '../utils/axios'
import { useProjectStore } from './projectStore'

export const useTeamspaceStore = defineStore('teamspace', {
  state: () => ({
    teamspaces: [],
    loading: false,
    error: null
  }),

  actions: {
    async loadTeamspacesForWorkspace(workspaceId) {
      try {
        console.log('loadTeamspacesForWorkspace called with workspaceId:', workspaceId)
        this.loading = true
        this.error = null
        
        // Get auth token from localStorage
        const userData = localStorage.getItem('authUser')
        let token = null
        if (userData) {
          try {
            const auth = JSON.parse(userData)
            token = auth.token || localStorage.getItem('authToken')
          } catch (e) {
            console.error('Error parsing authUser:', e)
          }
        }
        
        if (!token) {
          token = localStorage.getItem('authToken')
        }
        
        // Ensure token has Bearer prefix
        const bearerToken = token?.startsWith('Bearer ') ? token : `Bearer ${token}`
        
        // Build request headers with Bearer token
        const headers = {
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        }
        
        // Only add Authorization header if token exists
        if (token) {
          headers['Authorization'] = bearerToken
          console.log('Using authentication token for API request')
        } else {
          console.warn('No authentication token found for API request')
        }
        
        console.log('Request headers:', headers)
        
        // Use the API endpoint to fetch teamspaces for this workspace
        console.log('Making API call to /teams/workspace/' + workspaceId)
        const teamsResponse = await axios.get(`/teams/workspace/${workspaceId}`, { headers })
        console.log('API response received:', teamsResponse.status, teamsResponse.statusText)
        console.log('API response data:', teamsResponse.data)
        
        // Handle different response formats
        let teamspaces = []
        
        if (teamsResponse.data) {
          if (Array.isArray(teamsResponse.data)) {
            teamspaces = teamsResponse.data
            console.log('Response is an array')
          } else if (teamsResponse.data.teams && Array.isArray(teamsResponse.data.teams)) {
            teamspaces = teamsResponse.data.teams
            console.log('Response has teams array property')
          } else if (teamsResponse.data.data && Array.isArray(teamsResponse.data.data)) {
            teamspaces = teamsResponse.data.data
            console.log('Response has data array property')
          }
        }
        
        console.log('Extracted teamspaces count:', teamspaces.length)
        
        // Normalize teamspaces to ensure consistent ID field and initialize projects array
        const normalizedTeamspaces = teamspaces.map(team => {
          const normalizedTeam = { ...team }
          
          // Ensure consistent ID field
          if (!normalizedTeam.id) {
            if (normalizedTeam._id) {
              normalizedTeam.id = normalizedTeam._id
            } else if (normalizedTeam.team_id) {
              normalizedTeam.id = normalizedTeam.team_id
            }
          }
          
          // Ensure projects is initialized as an array
          if (!normalizedTeam.projects) {
            normalizedTeam.projects = []
          }
          
          return normalizedTeam
        })
        
        // Update the store with the normalized teamspaces
        this.teamspaces = normalizedTeamspaces
        console.log('Updated teamspaces store with', this.teamspaces.length, 'teamspaces')
        
        return this.teamspaces
      } catch (error) {
        console.error('Error in loadTeamspacesForWorkspace:', error)
        console.error('Error details:', error.response?.status, error.response?.statusText)
        console.error('Error data:', error.response?.data)
        
        // Fallback to empty array
        this.teamspaces = []
        
        // Extract meaningful error message
        let errorMessage = `Failed to fetch teamspaces for workspace: ${workspaceId}`
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

    // Set teamspaces directly
    setTeamspaces(teamspaces) {
      this.teamspaces = teamspaces.map(team => {
        const normalizedTeam = { ...team }
        
        // Ensure consistent ID field
        if (!normalizedTeam.id) {
          if (normalizedTeam._id) {
            normalizedTeam.id = normalizedTeam._id
          } else if (normalizedTeam.team_id) {
            normalizedTeam.id = normalizedTeam.team_id
          }
        }
        
        // Ensure projects is initialized as an array
        if (!normalizedTeam.projects) {
          normalizedTeam.projects = []
        }
        
        return normalizedTeam
      })
    },

    // Clear teamspace data
    clearTeamspaces() {
      this.teamspaces = []
      this.error = null
    },

    // Add a new teamspace
    async addTeamspace(teamspace, workspaceId) {
      if (!workspaceId) {
        throw new Error('No workspace ID provided')
      }
      
      // Ensure teamspace has the workspace ID
      if (!teamspace.workspaceId) {
        teamspace.workspaceId = workspaceId
      }
        
      try {
        this.loading = true
        
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
          'Content-Type': 'application/json',
          'Authorization': token.startsWith('Bearer ') ? token : `Bearer ${token}`
        }
        
        // Make API call to create teamspace using the new endpoint
        const response = await axios.post(
          `/teams/create/${workspaceId}`,
          {
            name: teamspace.name,
            description: teamspace.description || 'Team created from ClickFlow',
            visibility: teamspace.visibility || 'public',
            color_code: teamspace.color_code || '#' + Math.floor(Math.random()*16777215).toString(16)
          },
          { headers }
        )
        
        // Extract the created teamspace data
        let createdTeamspace = null
        if (response.data.team) {
          createdTeamspace = response.data.team
        } else if (response.data.data) {
          createdTeamspace = response.data.data
        } else if (response.data.teamspace) {
          createdTeamspace = response.data.teamspace
        } else {
          createdTeamspace = response.data
        }
        
        // Ensure ID is consistent
        if (!createdTeamspace.id && createdTeamspace._id) {
          createdTeamspace.id = createdTeamspace._id
        }
        
        // Initialize projects array if needed
        if (!createdTeamspace.projects) {
          createdTeamspace.projects = []
        }
        
        // Add to the teamspaces array
        this.teamspaces.push(createdTeamspace)
        
        return createdTeamspace
      } catch (error) {
        // Extract meaningful error message
        let errorMessage = 'Failed to create teamspace'
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

    // Fetch details for a specific teamspace/team by ID
    async fetchTeamspaceDetails(teamspaceId) {
      if (!teamspaceId) {
        throw new Error('No teamspace ID provided')
      }
      
      try {
        this.loading = true
        this.error = null
        
        // Make the API call to get teamspace by ID
        const response = await axios.get(`/teams/${teamspaceId}`)
        
        // Handle different response formats
        let fetchedTeamspace = null
        
        if (response.data) {
          if (response.data.team) {
            fetchedTeamspace = response.data.team
          } else if (response.data.data) {
            fetchedTeamspace = response.data.data
          } else if (typeof response.data === 'object' && (response.data.id || response.data._id || response.data.team_id)) {
            // Response is the teamspace object itself
            fetchedTeamspace = response.data
          }
        }
        
        if (!fetchedTeamspace) {
          throw new Error('Invalid teamspace data received from API')
        }
        
        // Normalize the teamspace data
        const normalizedTeamspace = { ...fetchedTeamspace }
        
        // Ensure ID is consistent
        if (!normalizedTeamspace.id) {
          if (normalizedTeamspace._id) {
            normalizedTeamspace.id = normalizedTeamspace._id
          } else if (normalizedTeamspace.team_id) {
            normalizedTeamspace.id = normalizedTeamspace.team_id
          }
        }
        
        // Ensure projects is initialized as an array if not present
        if (!normalizedTeamspace.projects) {
          normalizedTeamspace.projects = []
        }
        
        // Update the teamspace in the store
        const teamspaceIndex = this.teamspaces.findIndex(t => 
          t.id === teamspaceId || 
          t._id === teamspaceId || 
          t.team_id === teamspaceId
        )
        
        if (teamspaceIndex !== -1) {
          // Preserve existing projects if not included in the API response
          if (normalizedTeamspace.projects.length === 0 && this.teamspaces[teamspaceIndex].projects?.length > 0) {
            normalizedTeamspace.projects = this.teamspaces[teamspaceIndex].projects
          }
          
          // Update the teamspace with the new data
          this.teamspaces[teamspaceIndex] = normalizedTeamspace
        } else {
          // If teamspace wasn't found in the array, add it
          this.teamspaces.push(normalizedTeamspace)
        }
        
        return normalizedTeamspace
      } catch (error) {
        // Extract meaningful error message
        let errorMessage = `Failed to fetch teamspace with ID: ${teamspaceId}`
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

    // Synchronous getter for local teamspace data (no API call)
    getTeamspaceLocal(teamspaceId) {
      if (!teamspaceId) return null
      
      return this.teamspaces.find(t => 
        t.id === teamspaceId || 
        t._id === teamspaceId || 
        t.team_id === teamspaceId
      )
    },

    // Get teamspace with API fallback
    async getTeamspace(teamspaceId) {
      if (!teamspaceId) {
        return null
      }
      
      try {
        // First try to get fresh data from the API
        return await this.fetchTeamspaceDetails(teamspaceId)
      } catch (error) {
        // Fallback to local data
        return this.getTeamspaceLocal(teamspaceId)
      }
    },

    // Fetch projects for a specific teamspace/team
    async fetchTeamspaceProjects(teamspaceId) {
      if (!teamspaceId) {
        throw new Error('No teamspace ID provided')
      }
      
      try {
        this.loading = true
        this.error = null
        
        // Make the API call to get projects for teamspace
        const response = await axios.get(`/teams/${teamspaceId}/projects`)
        
        // Handle different response formats
        let projects = []
        
        if (response.data) {
          if (Array.isArray(response.data)) {
            projects = response.data
          } else if (response.data.projects && Array.isArray(response.data.projects)) {
            projects = response.data.projects
          } else if (response.data.data && Array.isArray(response.data.data)) {
            projects = response.data.data
          }
        }
        
        // Get the project store to normalize projects
        const projectStore = useProjectStore()
        
        // Normalize projects data using the project store's method
        const normalizedProjects = projects.map(project => projectStore.normalizeProject(project))
        
        // Update the projects in the teamspace
        const teamspace = this.getTeamspaceLocal(teamspaceId)
        if (teamspace) {
          teamspace.projects = normalizedProjects
        }
        
        return normalizedProjects
      } catch (error) {
        // Extract meaningful error message
        let errorMessage = `Failed to fetch projects for teamspace: ${teamspaceId}`
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

    // Method to update teamspace properties
    async updateTeamspace(updatedTeamspace) {
      try {
        if (!updatedTeamspace || !updatedTeamspace.id) {
          throw new Error('Invalid teamspace data')
        }

        this.loading = true
        
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
        
        // Extract only the properties that need to be updated
        const updateData = {
          name: updatedTeamspace.name,
          description: updatedTeamspace.description || 'Team updated in ClickFlow',
          visibility: updatedTeamspace.is_private ? 'private' : 'public',
          color_code: updatedTeamspace.color_code
        }
        
        // Get the teamspace ID
        const teamspaceId = updatedTeamspace.id || updatedTeamspace._id || updatedTeamspace.team_id
        
        try {
          // Make API call to update teamspace using PUT method
          const response = await axios.put(
            `/teams/${teamspaceId}`,
            updateData,
            { headers }
          )
          
          // Extract the updated teamspace from the response
          let updatedTeamspaceData = response.data
          if (response.data.team) {
            updatedTeamspaceData = response.data.team
          } else if (response.data.data) {
            updatedTeamspaceData = response.data.data
          }
          
          // Ensure ID is consistent
          if (!updatedTeamspaceData.id) {
            if (updatedTeamspaceData._id) {
              updatedTeamspaceData.id = updatedTeamspaceData._id
            } else if (updatedTeamspaceData.team_id) {
              updatedTeamspaceData.id = updatedTeamspaceData.team_id
            }
          }
          
          // Update teamspace in store
          const teamspaceIndex = this.teamspaces.findIndex(t => 
            t.id === teamspaceId || 
            t._id === teamspaceId || 
            t.team_id === teamspaceId
          )
          
          if (teamspaceIndex !== -1) {
            // Update existing teamspace while preserving its projects
            const projects = this.teamspaces[teamspaceIndex].projects || []
            this.teamspaces[teamspaceIndex] = {
              ...this.teamspaces[teamspaceIndex],
              ...updatedTeamspaceData,
              projects // Ensure projects are preserved
            }
          }
          
          return updatedTeamspaceData
        } catch (error) {
          // Even without a successful API call, update the UI
          // This provides instant feedback in the demo
          
          // Update teamspace in store
          const teamspaceIndex = this.teamspaces.findIndex(t => 
            t.id === teamspaceId || 
            t._id === teamspaceId || 
            t.team_id === teamspaceId
          )
          
          if (teamspaceIndex !== -1) {
            // Update existing teamspace while preserving its projects
            const projects = this.teamspaces[teamspaceIndex].projects || []
            this.teamspaces[teamspaceIndex] = {
              ...this.teamspaces[teamspaceIndex],
              ...updatedTeamspace,
              projects // Ensure projects are preserved
            }
          }
          
          // Extract meaningful error message
          let errorMessage = 'Failed to update teamspace'
          if (error.response?.data?.message) {
            errorMessage = error.response.data.message
          } else if (error.response?.data?.error) {
            errorMessage = error.response.data.error
          } else if (error.message) {
            errorMessage = error.message
          }
          
          this.error = errorMessage
          
          // Rethrow the error for the caller to handle
          throw error
        }
      } catch (error) {
        throw error
      } finally {
        this.loading = false
      }
    },

    // Method to delete a teamspace
    async deleteTeamspace(teamspaceId) {
      try {
        if (!teamspaceId) {
          throw new Error('Invalid teamspace ID')
        }

        this.loading = true
        
        // Get auth token from localStorage
        const userData = localStorage.getItem('authUser')
        let token = null
        if (userData) {
          try {
            const auth = JSON.parse(userData)
            token = auth.token || localStorage.getItem('authToken')
          } catch (e) {
            // Ignore parsing error
          }
        }
        
        if (!token) {
          token = localStorage.getItem('authToken')
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
        
        // Make API call to delete teamspace using DELETE method
        const response = await axios.delete(`/teams/${teamspaceId}`, { headers })
        
        // Remove teamspace from store
        const teamspaceIndex = this.teamspaces.findIndex(t => 
          t.id === teamspaceId || 
          t._id === teamspaceId || 
          t.team_id === teamspaceId
        )
        
        if (teamspaceIndex !== -1) {
          // Remove the teamspace from array
          this.teamspaces.splice(teamspaceIndex, 1)
        }
        
        return response.data
      } catch (error) {
        // Extract meaningful error message
        let errorMessage = 'Failed to delete teamspace'
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
    
    // Helper function to find a project within a teamspace
    findProject(teamspaceId, projectId) {
      const teamspace = this.getTeamspaceLocal(teamspaceId)
      if (!teamspace || !teamspace.projects) return null
      
      return teamspace.projects.find(p => 
        p.id === projectId || 
        p._id === projectId || 
        p.project_id === projectId
      )
    },
    
    // Update a project in a teamspace
    updateProject(teamspaceId, project) {
      if (!project || !project.id) return
      
      const teamspace = this.getTeamspaceLocal(teamspaceId)
      if (!teamspace || !teamspace.projects) return
      
      const projectIndex = teamspace.projects.findIndex(p => 
        p.id === project.id || 
        p._id === project.id || 
        p.project_id === project.id
      )
      
      if (projectIndex !== -1) {
        teamspace.projects[projectIndex] = project
      } else {
        teamspace.projects.push(project)
      }
    }
  }
}) 