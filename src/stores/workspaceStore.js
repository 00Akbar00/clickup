import { defineStore } from 'pinia'
import { workspaceData } from '../data/workspace'
import { ref } from 'vue'

export const useWorkspaceStore = defineStore('workspace', {
  state: () => ({
    workspaces: [
      // Default workspace for testing
      {
        id: 1,
        name: 'My Workspace',
        description: 'Default workspace',
        teamspaces: [],
        createdAt: new Date().toISOString()
      }
    ],
    currentWorkspace: null,
    teamspaces: [],
    loading: false,
    error: null
  }),

  getters: {
    currentWorkspaceTeamspaces: (state) => state.currentWorkspace?.teamspaces || state.teamspaces
  },

  actions: {
    // Initialize store
    initializeStore() {
      // Set first workspace as current if none selected
      if (!this.currentWorkspace && this.workspaces.length > 0) {
        this.setCurrentWorkspace(this.workspaces[0])
      }
    },

    // Existing teamspace actions
    addTeamspace(teamspace) {
      // Add teamspace to current workspace
      if (this.currentWorkspace) {
        if (!teamspace.workspaceId) {
          teamspace.workspaceId = this.currentWorkspace.id
        }
        
        // Initialize arrays if they don't exist
        if (!this.currentWorkspace.teamspaces) {
          this.currentWorkspace.teamspaces = []
        }
        
        // Add only to the currentWorkspace.teamspaces array
        this.currentWorkspace.teamspaces.push(teamspace)
        
        // Update the workspace in the workspaces array
        const workspaceIndex = this.workspaces.findIndex(w => w.id === this.currentWorkspace.id)
        if (workspaceIndex !== -1) {
          this.workspaces[workspaceIndex] = { ...this.currentWorkspace }
        }

        // Update the teamspaces reference to point to current workspace's teamspaces
        this.teamspaces = this.currentWorkspace.teamspaces
      }
    },
    
    // New workspace actions
    setCurrentWorkspace(workspace) {
      this.currentWorkspace = workspace
      // Load teamspaces for this workspace
      this.teamspaces = workspace.teamspaces || []
      console.log('Switched to workspace:', workspace.name, 'with teamspaces:', this.teamspaces)
    },

    async createWorkspace(workspaceData) {
      try {
        this.loading = true
        // Generate a temporary ID (in real app, this would come from the backend)
        const newWorkspace = {
          id: Math.max(0, ...this.workspaces.map(w => w.id)) + 1,
          name: workspaceData.name,
          description: workspaceData.description,
          teamspaces: [],
          createdAt: new Date().toISOString()
        }
        
        this.workspaces.push(newWorkspace)
        
        // If this is the first workspace, set it as current
        if (!this.currentWorkspace) {
          this.setCurrentWorkspace(newWorkspace)
        }
        
        return newWorkspace
      } catch (error) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    // Helper function to find teamspace and its components
    findTeamspaceComponents(teamspaceId) {
      const teamspace = this.teamspaces.find(t => t.id === teamspaceId)
      if (!teamspace) return null
      return { teamspace }
    },

    findProjectComponents(teamspaceId, projectId) {
      const result = this.findTeamspaceComponents(teamspaceId)
      if (!result) return null
      
      const project = result.teamspace.projects?.find(p => p.id === projectId)
      if (!project) return null
      
      return { ...result, project }
    },

    findListComponents(teamspaceId, projectId, listId) {
      const result = this.findProjectComponents(teamspaceId, projectId)
      if (!result) return null
      
      const list = result.project.lists?.find(l => l.id === listId)
      if (!list) return null
      
      return { ...result, list }
    },

    // Actions
    addProject(teamspaceId, project) {
      const result = this.findTeamspaceComponents(teamspaceId)
      if (!result) {
        console.error('Teamspace not found')
        return
      }

      if (!result.teamspace.projects) {
        result.teamspace.projects = []
      }

      project.lists = []
      result.teamspace.projects.push(project)
      console.log(`Added project "${project.name}" to teamspace: ${result.teamspace.name}`)
    },

    addList(teamspaceId, projectId, list) {
      const result = this.findProjectComponents(teamspaceId, projectId)
      if (!result) {
        console.error('Project or teamspace not found')
        return
      }

      if (!result.project.lists) {
        result.project.lists = []
      }

      list.tasks = []
      result.project.lists.push(list)
      console.log(`Added list "${list.name}" to: ${result.teamspace.name}/Project ${result.project.name}`)
    },

    addTask(teamspaceId, projectId, listId, task) {
      const result = this.findListComponents(teamspaceId, projectId, listId)
      if (!result) {
        console.error('List, project, or teamspace not found')
        return
      }

      if (!result.list.tasks) {
        result.list.tasks = []
      }

      result.list.tasks.push(task)
      console.log(`Added task "${task.name}" to: ${result.teamspace.name}/Project ${result.project.name}/List ${result.list.name}`)
    },

    updateTaskStatus(teamspaceId, projectId, listId, taskId, newStatus) {
      const list = this.getList(teamspaceId, projectId, listId)
      if (list && list.tasks) {
        const task = list.tasks.find(t => t.id === taskId)
        if (task) {
          task.status = newStatus
        }
      }
    },

    // Getters
    getTeamspace(teamspaceId) {
      return this.teamspaces.find(t => t.id === teamspaceId)
    },

    getProject(teamspaceId, projectId) {
      const result = this.findProjectComponents(teamspaceId, projectId)
      return result?.project
    },

    getList(teamspaceId, projectId, listId) {
      const result = this.findListComponents(teamspaceId, projectId, listId)
      return result?.list
    }
  }
}) 