import { defineStore } from 'pinia'
import axios from '../utils/axios'
import { useTeamspaceStore } from './teamspaceStore'
import { useTaskStore } from './taskStore'

export const useListStore = defineStore('list', {
  state: () => ({
    loading: false,
    error: null
  }),

  actions: {
    // Helper method to normalize list data
    normalizeList(list) {
      const normalizedList = { ...list }
      
      // Ensure ID is consistent
      if (!normalizedList.id) {
        if (normalizedList._id) {
          normalizedList.id = normalizedList._id
        } else if (normalizedList.list_id) {
          normalizedList.id = normalizedList.list_id
        }
      }
      
      // Ensure tasks is initialized as an array if not present
      if (!normalizedList.tasks) {
        normalizedList.tasks = []
      }
      
      return normalizedList
    },

    // Method to find a list by ID in any teamspace/project
    getListById(listId) {
      if (!listId) return null
      
      const teamspaceStore = useTeamspaceStore()
      
      // Search in all teamspaces and projects to find the list
      for (const teamspace of teamspaceStore.teamspaces) {
        if (!teamspace.projects) continue
        
        for (const project of teamspace.projects) {
          if (!project.lists) continue
          
          const list = project.lists.find(l => 
            l.id === listId || 
            l._id === listId || 
            l.list_id === listId
          )
          
          if (list) {
            return list
          }
        }
      }
      
      return null
    },

    // Fetch lists for a specific project
    async fetchProjectLists(projectId) {
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
        
        // Make the API call to get lists for project
        const response = await axios.get(`/projects/${projectId}/lists`, { headers })
        
        // Handle different response formats
        let lists = []
        
        if (response.data) {
          if (Array.isArray(response.data)) {
            lists = response.data
          } else if (response.data.lists && Array.isArray(response.data.lists)) {
            lists = response.data.lists
          } else if (response.data.data && Array.isArray(response.data.data)) {
            lists = response.data.data
          }
        }
        
        // Normalize lists data
        const normalizedLists = lists.map(list => this.normalizeList(list))
        
        // Find and update the project in all teamspaces
        const teamspaceStore = useTeamspaceStore()
        
        // Look through all teamspaces to find the project and update its lists
        for (const teamspace of teamspaceStore.teamspaces) {
          if (!teamspace.projects) continue
          
          const project = teamspace.projects.find(p => 
            p.id === projectId || 
            p._id === projectId || 
            p.project_id === projectId
          )
          
          if (project) {
            project.lists = normalizedLists
          }
        }
        
        return normalizedLists
      } catch (error) {
        // Extract meaningful error message
        let errorMessage = `Failed to fetch lists for project: ${projectId}`
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

    // Create a new list using the API
    async createList(projectId, list) {
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
        
        // Make API call to create list with correct endpoint format from Postman collection
        const response = await axios.post(`/lists/${projectId}`, {
          name: list.name,
          description: list.description || 'List created in ClickFlow'
        }, { headers })
        
        // Extract the created list data
        let createdList = null
        if (response.data.list) {
          createdList = response.data.list
        } else if (response.data.data) {
          createdList = response.data.data
        } else {
          createdList = response.data
        }
        
        // Normalize the list data
        const normalizedList = this.normalizeList(createdList)
        
        // Find the project and add the list to it
        let projectFound = false
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
            // Initialize lists array if needed
            if (!project.lists) {
              project.lists = []
            }
            
            // Check if list already exists
            const existingIndex = project.lists.findIndex(l => 
              l.id === normalizedList.id || 
              l._id === normalizedList.id || 
              l.list_id === normalizedList.id
            )
            
            if (existingIndex !== -1) {
              // Update existing list
              project.lists[existingIndex] = normalizedList
            } else {
              // Add the list to the project
              project.lists.push(normalizedList)
            }
            
            projectFound = true
            break
          }
        }
        
        if (!projectFound) {
          // Try to refresh projects to get the updated data
          try {
            await this.fetchProjectLists(projectId)
          } catch (refreshError) {
            // Ignore refresh error
          }
        }
        
        return normalizedList
      } catch (error) {
        // Extract meaningful error message
        let errorMessage = 'Failed to create list'
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

    // Fetch a single list by ID
    async fetchListDetails(listId) {
      if (!listId) {
        throw new Error('No list ID provided')
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
        
        // Make the API call to get list by ID
        const response = await axios.get(`/lists/${listId}`, { headers })
        
        // Handle different response formats
        let fetchedList = null
        
        if (response.data) {
          if (response.data.list) {
            fetchedList = response.data.list
          } else if (response.data.data) {
            fetchedList = response.data.data
          } else if (typeof response.data === 'object' && (response.data.id || response.data._id || response.data.list_id)) {
            // Response is the list object itself
            fetchedList = response.data
          }
        }
        
        if (!fetchedList) {
          throw new Error('Invalid list data received from API')
        }
        
        // Normalize the list data
        const normalizedList = this.normalizeList(fetchedList)
        
        // Fetch tasks for this list
        try {
          const taskStore = useTaskStore()
          const tasks = await taskStore.fetchTasksForList(listId)
          normalizedList.tasks = tasks
        } catch (error) {
          // Tasks will remain as empty array from normalizeList
        }
        
        // Find the project that contains this list
        const teamspaceStore = useTeamspaceStore()
        let foundProjectId = null
        
        // Look through all teamspaces to find the list and update it
        for (const teamspace of teamspaceStore.teamspaces) {
          if (!teamspace.projects) continue
          
          for (const project of teamspace.projects) {
            if (!project.lists) continue
            
            const listIndex = project.lists.findIndex(l => 
              l.id === listId || 
              l._id === listId || 
              l.list_id === listId
            )
            
            if (listIndex !== -1) {
              // Update the list in the project
              project.lists[listIndex] = normalizedList
              foundProjectId = project.id || project._id || project.project_id
              break
            }
          }
          
          if (foundProjectId) break
        }
        
        // If we couldn't find the list in any project but the list has a project_id field
        if (!foundProjectId && normalizedList.project_id) {
          const projectId = normalizedList.project_id
          
          for (const teamspace of teamspaceStore.teamspaces) {
            if (!teamspace.projects) continue
            
            const project = teamspace.projects.find(p => 
              p.id === projectId || 
              p._id === projectId || 
              p.project_id === projectId
            )
            
            if (project) {
              // Initialize lists array if needed
              if (!project.lists) {
                project.lists = []
              }
              
              // Check if list already exists in the project
              const existingListIndex = project.lists.findIndex(l => 
                l.id === listId || 
                l._id === listId || 
                l.list_id === listId
              )
              
              if (existingListIndex !== -1) {
                // Update existing list
                project.lists[existingListIndex] = normalizedList
              } else {
                // Add as a new list
                project.lists.push(normalizedList)
              }
              
              foundProjectId = projectId
              break
            }
          }
        }
        
        return normalizedList
      } catch (error) {
        // Extract meaningful error message
        let errorMessage = `Failed to fetch list with ID: ${listId}`
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

    // Get a list by ID with API call
    async getList(teamspaceId, projectId, listId) {
      try {
        // First try to get fresh data from the API
        return await this.fetchListDetails(listId)
      } catch (error) {
        // Fallback to local data
        return this.getListLocal(teamspaceId, projectId, listId)
      }
    },

    // Synchronous version that doesn't make API calls
    getListLocal(teamspaceId, projectId, listId) {
      const teamspaceStore = useTeamspaceStore()
      const teamspace = teamspaceStore.getTeamspaceLocal(teamspaceId)
      
      if (!teamspace || !teamspace.projects) return null
      
      const project = teamspace.projects.find(p => 
        p.id === projectId || 
        p._id === projectId || 
        p.project_id === projectId
      )
      
      if (!project || !project.lists) return null
      
      return project.lists.find(l => 
        l.id === listId || 
        l._id === listId || 
        l.list_id === listId
      )
    },

    // Update a list
    async updateList(listId, updatedData) {
      if (!listId) {
        throw new Error('No list ID provided')
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
          description: updatedData.description || ''
        }
        
        // Make API call to update list
        const response = await axios.put(`/lists/${listId}`, updateData, { headers })
        
        // Get the updated list data
        const updatedList = await this.fetchListDetails(listId)
        
        return updatedList
      } catch (error) {
        // Extract meaningful error message
        let errorMessage = `Failed to update list with ID: ${listId}`
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

    // Delete a list
    async deleteList(listId) {
      if (!listId) {
        throw new Error('No list ID provided')
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
        
        // Make API call to delete list
        const response = await axios.delete(`/lists/${listId}`, { headers })
        
        // Remove the list from all projects in teamspaces
        const teamspaceStore = useTeamspaceStore()
        
        for (const teamspace of teamspaceStore.teamspaces) {
          if (!teamspace.projects) continue
          
          for (const project of teamspace.projects) {
            if (!project.lists) continue
            
            const listIndex = project.lists.findIndex(l => 
              l.id === listId || 
              l._id === listId || 
              l.list_id === listId
            )
            
            if (listIndex !== -1) {
              // Remove the list from the project
              project.lists.splice(listIndex, 1)
            }
          }
        }
        
        return response.data
      } catch (error) {
        // Extract meaningful error message
        let errorMessage = `Failed to delete list with ID: ${listId}`
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