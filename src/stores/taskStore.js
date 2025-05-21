import { defineStore } from 'pinia'
import axios from '../utils/axios'
import { useTeamspaceStore } from './teamspaceStore'

export const useTaskStore = defineStore('task', {
  state: () => ({
    loading: false,
    error: null
  }),

  actions: {
    // Helper method to normalize task data
    normalizeTask(task) {
      const normalizedTask = { ...task }
      
      // Ensure ID is consistent
      if (!normalizedTask.id) {
        if (normalizedTask._id) {
          normalizedTask.id = normalizedTask._id
        } else if (normalizedTask.task_id) {
          normalizedTask.id = normalizedTask.task_id
        }
      }
      
      // Ensure fields exist and are consistently named
      if (normalizedTask.title && !normalizedTask.name) {
        normalizedTask.name = normalizedTask.title
      } else if (normalizedTask.name && !normalizedTask.title) {
        normalizedTask.title = normalizedTask.name
      }
      
      // Ensure status is in expected format
      if (normalizedTask.status) {
        // Convert 'inprogress' to 'in progress' for consistency in UI
        if (normalizedTask.status === 'inprogress') {
          normalizedTask.status = 'in progress'
        }
      } else {
        normalizedTask.status = 'todo' // Default status
      }
      
      // Parse due date if it's in DD-MM-YYYY format (API) to YYYY-MM-DD (UI)
      if (normalizedTask.due_date && typeof normalizedTask.due_date === 'string' && normalizedTask.due_date.includes('-')) {
        const parts = normalizedTask.due_date.split('-')
        if (parts.length === 3 && parts[0].length === 2 && parts[1].length === 2 && parts[2].length === 4) {
          // Looks like DD-MM-YYYY format from API
          normalizedTask.dueDate = `${parts[2]}-${parts[1]}-${parts[0]}`
          
          // Also add a formatted date for display (DD/MM/YY)
          normalizedTask.formattedDate = `${parts[0]}/${parts[1]}/${parts[2].substring(2)}`
        } else if (parts.length === 3) {
          // Try to handle other formats
          normalizedTask.dueDate = normalizedTask.due_date
          
          // Attempt to create a formatted date for display (DD/MM/YY)
          try {
            const date = new Date(normalizedTask.due_date)
            const day = date.getDate().toString().padStart(2, '0')
            const month = (date.getMonth() + 1).toString().padStart(2, '0')
            const year = date.getFullYear().toString().substring(2)
            normalizedTask.formattedDate = `${day}/${month}/${year}`
          } catch (e) {
            // ignore date formatting error
          }
        } else {
          normalizedTask.dueDate = normalizedTask.due_date
        }
      }
      
      // Ensure comments is initialized as an array if not present
      if (!normalizedTask.comments) {
        normalizedTask.comments = []
      }
      
      // Ensure assignee is handled correctly
      if (normalizedTask.assignee && typeof normalizedTask.assignee === 'object') {
        // Keep the assignee object as is
      } else if (normalizedTask.assignee && typeof normalizedTask.assignee === 'string') {
        // If assignee is just a string ID, convert to object format
        normalizedTask.assignee = { id: normalizedTask.assignee, name: 'Unknown User' }
      } else {
        // Initialize empty assignee
        normalizedTask.assignee = null
      }
      
      return normalizedTask
    },

    // Find a list in the teamspace store that contains a specific task
    findListWithTask(taskId) {
      const teamspaceStore = useTeamspaceStore()
      
      for (const teamspace of teamspaceStore.teamspaces) {
        if (!teamspace.projects) continue
        
        for (const project of teamspace.projects) {
          if (!project.lists) continue
          
          for (const list of project.lists) {
            if (!list.tasks) continue
            
            const taskIndex = list.tasks.findIndex(t => 
              t.id === taskId || 
              t._id === taskId || 
              t.task_id === taskId
            )
            
            if (taskIndex !== -1) {
              return {
                teamspace,
                project,
                list,
                taskIndex
              }
            }
          }
        }
      }
      
      return null
    },

    // Fetch tasks for a specific list
    async fetchTasksForList(listId) {
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
        
        // Make API call to get tasks for list
        const response = await axios.get(`/lists/${listId}/tasks`, { headers })
        
        if (response.status !== 200) {
          throw new Error(`Server returned status ${response.status}`)
        }
        
        // Handle different response formats
        let tasks = []
        
        if (response.data) {
          if (Array.isArray(response.data)) {
            tasks = response.data
          } else if (response.data.tasks && Array.isArray(response.data.tasks)) {
            tasks = response.data.tasks
          } else if (response.data.data && Array.isArray(response.data.data)) {
            tasks = response.data.data
          } else if (response.data.id || response.data._id || response.data.task_id) {
            tasks = [response.data]
          } else {
            // Last resort: try to find any arrays in the response
            for (const key in response.data) {
              if (Array.isArray(response.data[key])) {
                tasks = response.data[key]
                break
              }
            }
          }
        }
        
        // Normalize tasks data
        const normalizedTasks = tasks.map(task => this.normalizeTask(task))
        
        // Update the store with fetched tasks
        const teamspaceStore = useTeamspaceStore()
        
        // Find and update the list in all teamspaces
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
              list.tasks = normalizedTasks
              break
            }
          }
        }
        
        return normalizedTasks
      } catch (error) {
        // Extract meaningful error message
        let errorMessage = `Failed to fetch tasks for list: ${listId}`
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

    // Create a new task using the API
    async createTask(listId, task) {
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
        
        // Prepare task data according to API expectations
        const taskData = {
          title: task.name || task.title,
          description: task.description || '',
          due_date: task.dueDate || task.due_date || null
        }
        
        // Add optional fields if present
        if (task.priority) {
          taskData.priority = task.priority
        }
        
        if (task.status) {
          // Convert 'in progress' to 'inprogress' for API compatibility
          if (task.status === 'in progress') {
            taskData.status = 'inprogress'
          } else {
            taskData.status = task.status
          }
        }
        
        // Make API call to create task
        const response = await axios.post(`/tasks/${listId}`, taskData, { headers })
        
        // Extract the created task data
        let createdTask = null
        if (response.data.task) {
          createdTask = response.data.task
        } else if (response.data.data) {
          createdTask = response.data.data
        } else {
          createdTask = response.data
        }
        
        // Normalize created task
        const normalizedTask = this.normalizeTask(createdTask)
        
        // Find the list and add the task to it
        const teamspaceStore = useTeamspaceStore()
        let listFound = false
        
        // Search in all teamspaces
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
              // Initialize tasks array if needed
              if (!list.tasks) {
                list.tasks = []
              }
              
              // Check if task already exists
              const existingIndex = list.tasks.findIndex(t => 
                t.id === normalizedTask.id || 
                t._id === normalizedTask.id || 
                t.task_id === normalizedTask.id
              )
              
              if (existingIndex !== -1) {
                // Update existing task
                list.tasks[existingIndex] = normalizedTask
              } else {
                // Add the task to the list
                list.tasks.push(normalizedTask)
              }
              
              listFound = true
              break
            }
          }
          
          if (listFound) break
        }
        
        if (!listFound) {
          // Try to refresh tasks to get the updated data
          try {
            await this.fetchTasksForList(listId)
          } catch (refreshError) {
            // Ignore refresh error
          }
        }
        
        return normalizedTask
      } catch (error) {
        // Extract meaningful error message
        let errorMessage = 'Failed to create task'
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

    // Fetch task details by ID
    async fetchTaskById(taskId) {
      if (!taskId) {
        throw new Error('No task ID provided')
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
        
        // Make API call to get task details
        const response = await axios.get(`/tasks/${taskId}`, { headers })
        
        if (response.status !== 200) {
          throw new Error(`Server returned status ${response.status}`)
        }
        
        // Extract task data from response
        let taskData = null
        
        if (response.data) {
          if (response.data.task) {
            taskData = response.data.task
          } else if (response.data.data) {
            taskData = response.data.data
          } else if (response.data.id || response.data._id || response.data.task_id) {
            // Response itself appears to be the task
            taskData = response.data
          }
        }
        
        if (!taskData) {
          throw new Error('Could not extract task data from API response')
        }
        
        // Normalize task data
        const normalizedTask = this.normalizeTask(taskData)
        
        // Update the task in all relevant lists
        const result = this.findListWithTask(taskId)
        if (result) {
          result.list.tasks[result.taskIndex] = normalizedTask
        }
        
        return normalizedTask
      } catch (error) {
        // Extract meaningful error message
        let errorMessage = `Failed to fetch task with ID: ${taskId}`
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

    // Update task details
    async updateTask(taskId, updatedData) {
      if (!taskId) {
        throw new Error('No task ID provided')
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
        const updateData = {}
        
        // The API expects 'title' instead of 'name' for the task name
        if (updatedData.name) {
          updateData.title = updatedData.name
        }
        
        // Handle description if present
        if (updatedData.description !== undefined) {
          updateData.description = updatedData.description
        }
        
        // Format due date if present (from YYYY-MM-DD to DD-MM-YYYY)
        if (updatedData.dueDate) {
          const parts = updatedData.dueDate.split('-')
          if (parts.length === 3) {
            updateData.due_date = `${parts[2]}-${parts[1]}-${parts[0]}`
          }
        }
        
        // Convert UI status format to API format if needed
        if (updatedData.status) {
          if (updatedData.status === 'in progress') {
            updateData.status = 'inprogress'
          } else {
            updateData.status = updatedData.status
          }
        }
        
        // Convert assignee to ID if needed
        if (updatedData.assignee && typeof updatedData.assignee === 'object') {
          updateData.assignee_id = updatedData.assignee.id
        }
        
        // Include priority if provided
        if (updatedData.priority) {
          updateData.priority = updatedData.priority
        }
        
        // Make the API call to update the task using PUT method
        const response = await axios.put(`/tasks/${taskId}`, updateData, { headers })
        
        // Get the updated task data
        const updatedTask = await this.fetchTaskById(taskId)
        
        // Update the task in all relevant lists
        const teamspaceStore = useTeamspaceStore()
        
        for (const teamspace of teamspaceStore.teamspaces) {
          if (!teamspace.projects) continue
          
          for (const project of teamspace.projects) {
            if (!project.lists) continue
            
            for (const list of project.lists) {
              if (!list.tasks) continue
              
              const taskIndex = list.tasks.findIndex(t => 
                t.id === taskId || 
                t._id === taskId || 
                t.task_id === taskId
              )
              
              if (taskIndex !== -1) {
                // Replace the task with updated version
                list.tasks[taskIndex] = updatedTask
              }
            }
          }
        }
        
        return updatedTask
      } catch (error) {
        // Extract meaningful error message
        let errorMessage = `Failed to update task with ID: ${taskId}`
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

    // Update task status immediately and then via API
    updateTaskStatus(teamspaceId, projectId, listId, taskId, newStatus) {
      // Update locally first for immediate feedback
      const teamspaceStore = useTeamspaceStore()
      const list = teamspaceStore.teamspaces
        .find(t => t.id === teamspaceId)
        ?.projects?.find(p => p.id === projectId)
        ?.lists?.find(l => l.id === listId)
      
      if (list && list.tasks) {
        const task = list.tasks.find(t => t.id === taskId)
        if (task) {
          task.status = newStatus
        }
      }
      
      // Then update via API asynchronously
      this.updateTaskStatusViaApi(taskId, newStatus, listId)
        .catch(error => {})
    },

    // Update task status via API
    async updateTaskStatusViaApi(taskId, newStatus, listId) {
      if (!taskId) {
        throw new Error('No task ID provided')
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
        
        // Convert 'in progress' to 'inprogress' for API compatibility
        const apiStatus = newStatus === 'in progress' ? 'inprogress' : newStatus
        
        // Make API call to update task status
        const response = await axios.patch(`/tasks/${taskId}/status`, {
          status: apiStatus
        }, { headers })
        
        // Try to refresh the tasks for the list
        if (listId) {
          try {
            await this.fetchTasksForList(listId)
          } catch (refreshError) {
            // Ignore refresh error
          }
        }
        
        return response.data
      } catch (error) {
        // Extract meaningful error message
        let errorMessage = 'Failed to update task status'
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

    // Delete a task
    async deleteTask(taskId) {
      if (!taskId) {
        throw new Error('No task ID provided')
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
        
        // Make the API call to delete the task
        const response = await axios.delete(`/tasks/${taskId}`, { headers })
        
        // Remove the task from all lists in the store
        const teamspaceStore = useTeamspaceStore()
        
        for (const teamspace of teamspaceStore.teamspaces) {
          if (!teamspace.projects) continue
          
          for (const project of teamspace.projects) {
            if (!project.lists) continue
            
            for (const list of project.lists) {
              if (!list.tasks) continue
              
              const taskIndex = list.tasks.findIndex(t => 
                t.id === taskId || 
                t._id === taskId || 
                t.task_id === taskId
              )
              
              if (taskIndex !== -1) {
                // Remove the task from the array
                list.tasks.splice(taskIndex, 1)
              }
            }
          }
        }
        
        return response.data
      } catch (error) {
        // Extract meaningful error message
        let errorMessage = `Failed to delete task with ID: ${taskId}`
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

    // Add a task to a specific list (synchronous, local only)
    addTask(teamspaceId, projectId, listId, task) {
      const teamspaceStore = useTeamspaceStore()
      const teamspace = teamspaceStore.getTeamspaceLocal(teamspaceId)
      
      if (!teamspace || !teamspace.projects) return
      
      const project = teamspace.projects.find(p => 
        p.id === projectId || 
        p._id === projectId || 
        p.project_id === projectId
      )
      
      if (!project || !project.lists) return
      
      const list = project.lists.find(l => 
        l.id === listId || 
        l._id === listId || 
        l.list_id === listId
      )
      
      if (!list) return
      
      if (!list.tasks) {
        list.tasks = []
      }
      
      list.tasks.push(task)
    }
  }
}) 