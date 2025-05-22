import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from '../utils/axios'

// Import other stores
import { useTeamspaceStore } from './teamspaceStore'
import { useProjectStore } from './projectStore'
import { useListStore } from './listStore'
import { useTaskStore } from './taskStore'

export const useWorkspaceStore = defineStore('workspace', {
  state: () => ({
    workspaces: [], // Empty array to store workspaces from API
    currentWorkspace: null,
    loading: false,
    error: null
  }),

  getters: {
    // Add a getter to access teamspaces from the teamspace store
    currentWorkspaceTeamspaces() {
      const teamspaceStore = useTeamspaceStore()
      return teamspaceStore.teamspaces
    }
  },

  actions: {
    // Initialize store
    async initializeStore() {
      try {
        // Check for pending invitation
        const pendingInvitation = localStorage.getItem('pendingInvitation')
        if (pendingInvitation) {
          const { token } = JSON.parse(pendingInvitation)
          // Process invitation if user is authenticated
          if (localStorage.getItem('authToken') || localStorage.getItem('authUser')) {
            await this.acceptWorkspaceInvitation(token)
            localStorage.removeItem('pendingInvitation')
          }
        }
        
        // Fetch workspaces from API
        await this.fetchWorkspaces()
        
        // Process pending invitation if available
        if (pendingInvitation) {
          try {
            const { token } = JSON.parse(pendingInvitation)
            // Process invitation if user is authenticated
            if (localStorage.getItem('authToken') || localStorage.getItem('authUser')) {
              console.log('Processing pending invitation during initialization')
              await this.acceptWorkspaceInvitation(token)
              localStorage.removeItem('pendingInvitation')
              
              // Refresh workspaces after accepting invitation
              await this.fetchWorkspaces()
            }
          } catch (inviteError) {
            console.error('Error processing pending invitation:', inviteError)
          }
        }
        
        // Set first workspace as current if none selected and workspaces exist
        if (!this.currentWorkspace && this.workspaces.length > 0) {
          await this.setCurrentWorkspace(this.workspaces[0])
        }
      } catch (error) {
        this.error = 'Failed to initialize workspaces'
      }
    },

    // Fetch workspaces from API
    async fetchWorkspaces() {
      try {
        this.loading = true
        this.error = null
        
        // Clear any existing workspaces before fetching from API
        this.workspaces = []
        
        // Get workspaces from the API
        const response = await axios.get('/workspaces')
        
        // Handle different API response formats
        let workspaces = []
        
        if (response.data) {
          if (Array.isArray(response.data)) {
            workspaces = response.data
            console.log(responnse)
          } else if (response.data.workspaces && Array.isArray(response.data.workspaces)) {
            workspaces = response.data.workspaces
          } else if (response.data.data && Array.isArray(response.data.data)) {
            workspaces = response.data.data
          } else if (response.data.success && response.data.workspaces && Array.isArray(response.data.workspaces)) {
            workspaces = response.data.workspaces
          } else if (response.data.id || response.data._id || response.data.workspace_id) {
            workspaces = [response.data]
          } else {
            workspaces = []
          }
        }
        
        // Normalize workspaces
        this.workspaces = workspaces.map(workspace => {
          // Create a normalized copy of the workspace
          const normalizedWorkspace = { ...workspace }
          
          // Ensure ID is consistent
          if (!normalizedWorkspace.id) {
            if (normalizedWorkspace._id) {
              normalizedWorkspace.id = normalizedWorkspace._id
            } else if (normalizedWorkspace.workspace_id) {
              normalizedWorkspace.id = normalizedWorkspace.workspace_id
            }
          }
          
          // Ensure teamspaces is an array
          if (!normalizedWorkspace.teamspaces) {
            normalizedWorkspace.teamspaces = []
          }
          
          // Add logo_url property if needed
          if (normalizedWorkspace.logo && !normalizedWorkspace.logo_url) {
            normalizedWorkspace.logo_url = normalizedWorkspace.logo
          }
          
          return normalizedWorkspace
        })
        
        // Update current workspace if it no longer exists in the fetched workspaces
        if (this.currentWorkspace) {
          // Get all possible IDs from current workspace
          const currentWorkspaceIds = [
            this.currentWorkspace.id,
            this.currentWorkspace._id,
            this.currentWorkspace.workspace_id
          ].filter(Boolean) // Remove null/undefined values
          
          // Check if current workspace exists in the fetched workspaces
          const currentWorkspaceExists = this.workspaces.some(
            w => currentWorkspaceIds.includes(w.id) || 
                 currentWorkspaceIds.includes(w._id) || 
                 currentWorkspaceIds.includes(w.workspace_id)
          )
          
          if (!currentWorkspaceExists && this.workspaces.length > 0) {
            // Reset current workspace to the first available one
            await this.setCurrentWorkspace(this.workspaces[0])
          } else if (!currentWorkspaceExists) {
            // No workspaces available, reset current workspace
            this.currentWorkspace = null
            // Clear teamspaces in the teamspace store
            const teamspaceStore = useTeamspaceStore()
            teamspaceStore.clearTeamspaces()
          }
        }
        
        return this.workspaces
      } catch (error) {
        // Extract meaningful error message
        let errorMessage = 'Failed to fetch workspaces'
        if (error.response?.data?.message) {
          errorMessage = error.response.data.message
        } else if (error.response?.data?.error) {
          errorMessage = error.response.data.error
        } else if (error.message) {
          errorMessage = error.message
        }
        
        this.error = errorMessage
        this.workspaces = []
        throw error
      } finally {
        this.loading = false
      }
    },

    // Set current workspace
    async setCurrentWorkspace(workspace) {
      if (!workspace) {
        throw new Error('No workspace object provided')
      }
      
      console.log('setCurrentWorkspace called with workspace:', workspace.name, workspace.id)
      
      // Normalize the workspace object to ensure it has an id property
      let workspaceToSet = { ...workspace }
      
      // Check for alternative ID fields in the workspace object
      if (!workspaceToSet.id) {
        if (workspaceToSet._id) {
          workspaceToSet.id = workspaceToSet._id
        } else if (workspaceToSet.workspace_id) {
          workspaceToSet.id = workspaceToSet.workspace_id
        }
      }
      
      // Check if workspace has an ID now
      if (!workspaceToSet.id) {
        throw new Error('Invalid workspace object (no id)')
      }
      
      try {
        this.loading = true
        this.error = null
        
        console.log('Fetching workspace details for ID:', workspaceToSet.id)
        
        // First, fetch the workspace details by ID to ensure we have the latest workspace data
        const fetchedWorkspace = await this.fetchWorkspaceById(workspaceToSet.id)
        
        console.log('Fetched workspace details:', fetchedWorkspace.name, fetchedWorkspace.id)
        
        // Set current workspace with the data from API
        this.currentWorkspace = fetchedWorkspace
        
        // Get teamspace store to load the teamspaces for this workspace
        const teamspaceStore = useTeamspaceStore()
        console.log('Loading teamspaces for workspace:', fetchedWorkspace.id)
        await teamspaceStore.loadTeamspacesForWorkspace(fetchedWorkspace.id)
        console.log('Loaded teamspaces count:', teamspaceStore.teamspaces.length)
        
        // Set the has_workspace flag in localStorage
        localStorage.setItem('has_workspace', 'true')
        
        return this.currentWorkspace
      } catch (error) {
        console.error('Error in setCurrentWorkspace:', error)
        // Fallback to using the provided workspace if API call fails
        this.currentWorkspace = workspaceToSet
        
        // Set teamspaces in teamspace store
        const teamspaceStore = useTeamspaceStore()
        console.log('Error occurred, setting teamspaces from workspace object:', workspaceToSet.teamspaces?.length || 0)
        teamspaceStore.setTeamspaces(workspaceToSet.teamspaces || [])
        
        return workspaceToSet
      } finally {
        this.loading = false
      }
    },

    // Fetch a specific workspace by ID from the API
    async fetchWorkspaceById(workspaceId) {
      if (!workspaceId) {
        throw new Error('No workspace ID provided')
      }
      
      try {
        // Make the API call to get workspace by ID
        const response = await axios.get(`/workspaces?workspace_id=${workspaceId}`)
        // Handle different response formats
        let fetchedWorkspace = null
        
        if (response.data && typeof response.data === 'object') {
          // Case 1: Response contains a single workspace object
          if (response.data.workspace) {
            fetchedWorkspace = response.data.workspace
          } 
          // Case 2: Response contains a data property with the workspace
          else if (response.data.data) {
            fetchedWorkspace = response.data.data
          } 
          // Case 3: Response contains a workspaces array - find our workspace by ID
          else if (response.data.workspaces && Array.isArray(response.data.workspaces)) {
            fetchedWorkspace = response.data.workspaces.find(w => 
              w.id === workspaceId || 
              w._id === workspaceId || 
              w.workspace_id === workspaceId
            )
          }
          // Case 4: Response is an array - find our workspace by ID
          else if (Array.isArray(response.data)) {
            fetchedWorkspace = response.data.find(w => 
              w.id === workspaceId || 
              w._id === workspaceId || 
              w.workspace_id === workspaceId
            )
            
            // If not found by exact ID match, take the first item if available
            if (!fetchedWorkspace && response.data.length > 0) {
              fetchedWorkspace = response.data[0]
            }
          } 
          // Case 5: Response is the workspace object itself
          else if (response.data.id || response.data._id || response.data.workspace_id) {
            fetchedWorkspace = response.data
          }
        }
        
        // Ensure the workspace has the expected properties
        if (fetchedWorkspace) {
          // Create a normalized workspace object
          const normalizedWorkspace = { ...fetchedWorkspace }
          
          // Ensure ID is consistent - check multiple possible ID field names
          if (!normalizedWorkspace.id) {
            if (normalizedWorkspace._id) {
              normalizedWorkspace.id = normalizedWorkspace._id
            } else if (normalizedWorkspace.workspace_id) {
              normalizedWorkspace.id = normalizedWorkspace.workspace_id
            }
          }
          
          // Ensure teamspaces is an array
          if (!normalizedWorkspace.teamspaces) {
            normalizedWorkspace.teamspaces = []
          }
          
          // Add logo_url property if needed
          if (normalizedWorkspace.logo && !normalizedWorkspace.logo_url) {
            normalizedWorkspace.logo_url = normalizedWorkspace.logo
          }
          
          return normalizedWorkspace
        }
        
        throw new Error('Could not parse workspace data from response')
      } catch (error) {
        // Extract meaningful error message
        let errorMessage = `Failed to fetch workspace with ID: ${workspaceId}`
        if (error.response?.data?.message) {
          errorMessage = error.response.data.message
        } else if (error.response?.data?.error) {
          errorMessage = error.response.data.error
        } else if (error.message) {
          errorMessage = error.message
        }
        
        this.error = errorMessage
        throw error
      }
    },

    // Create a new workspace
    async createWorkspace(workspaceData) {
      try {
        this.loading = true
        this.error = null
        
        // Create FormData object for proper multipart/form-data submission
        const formData = new FormData()
        formData.append('name', workspaceData.name)
        
        // Only add optional fields if they exist
        if (workspaceData.description) {
          formData.append('description', workspaceData.description)
        }
        
        if (workspaceData.logo) {
          // Convert base64 to blob if it's a base64 string
          if (typeof workspaceData.logo === 'string' && workspaceData.logo.startsWith('data:')) {
            const response = await fetch(workspaceData.logo)
            const blob = await response.blob()
            formData.append('logo', blob, 'workspace-logo.png')
          } else {
            formData.append('logo', workspaceData.logo)
          }
        }
        
        // Make the API request with the correct Content-Type header
        const response = await axios.post('/workspaces', formData, {
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'multipart/form-data'
          }
        })

        // Extract the ID of the newly created workspace, checking multiple possible ID fields
        let newWorkspaceId = null;
        
        if (response.data) {
          // Try to find the workspace ID in the response
          if (response.data.workspace && response.data.workspace.workspace_id) {
            newWorkspaceId = response.data.workspace.workspace_id;
          } else if (response.data.id) {
            newWorkspaceId = response.data.id;
          } else if (response.data._id) {
            newWorkspaceId = response.data._id;
          } else if (response.data.workspace_id) {
            newWorkspaceId = response.data.workspace_id;
          } else if (response.data.workspace?.id) {
            newWorkspaceId = response.data.workspace.id;
          } else if (response.data.workspace?._id) {
            newWorkspaceId = response.data.workspace._id;
          } else if (response.data.data?.id) {
            newWorkspaceId = response.data.data.id;
          } else if (response.data.data?._id) {
            newWorkspaceId = response.data.data._id;
          } else if (response.data.data?.workspace_id) {
            newWorkspaceId = response.data.data.workspace_id;
          }
        }
        
        if (!newWorkspaceId) {
          // If we still don't have an ID, check if the response data itself might be the workspace
          const directData = response.data;
          if (directData && typeof directData === 'object') {
            // Create a normalized workspace with an ID
            const createdWorkspace = { ...directData };
            
            // Try to use any available ID field
            if (!createdWorkspace.id) {
              if (createdWorkspace._id) {
                createdWorkspace.id = createdWorkspace._id;
              } else if (createdWorkspace.workspace_id) {
                createdWorkspace.id = createdWorkspace.workspace_id;
              }
            }
            
            // If we now have an ID, set this as current workspace
            if (createdWorkspace.id) {
              // Initialize teamspaces if needed
              if (!createdWorkspace.teamspaces) {
                createdWorkspace.teamspaces = [];
              }
              
              // Add to workspaces list
              const exists = this.workspaces.some(w => 
                w.id === createdWorkspace.id || 
                w._id === createdWorkspace.id ||
                w.workspace_id === createdWorkspace.id
              );
              
              if (!exists) {
                this.workspaces.push(createdWorkspace);
              }
              
              // Set as current workspace
              await this.setCurrentWorkspace(createdWorkspace);

              // Set the has_workspace flag in localStorage
              localStorage.setItem('has_workspace', 'true');
              
              return createdWorkspace;
            }
          }
          
          // Fallback to fetching all workspaces
          await this.fetchWorkspaces();
        } else {
          // Fetch the newly created workspace by ID
          try {
            const newWorkspace = await this.fetchWorkspaceById(newWorkspaceId);
            
            // Ensure the workspace is in our workspaces list
            const exists = this.workspaces.some(w => 
              w.id === newWorkspace.id || 
              w._id === newWorkspace.id ||
              w.workspace_id === newWorkspace.id
            );
            
            if (!exists) {
              this.workspaces.push(newWorkspace);
            }
            
            // Set as current workspace
            await this.setCurrentWorkspace(newWorkspace);
            
            return newWorkspace;
          } catch (fetchError) {
            // Fallback to fetching all workspaces
            await this.fetchWorkspaces();
          }
        }
        
        // If we couldn't fetch by ID, find the workspace in the updated list
        const createdWorkspace = this.workspaces.find(w => w.name === workspaceData.name);
        if (createdWorkspace) {
          await this.setCurrentWorkspace(createdWorkspace);
          return createdWorkspace;
        }
        
        // If we still can't find it, use the first workspace
        if (this.workspaces.length > 0) {
          await this.setCurrentWorkspace(this.workspaces[0]);
          return this.workspaces[0];
        }
        
        throw new Error('Could not find the created workspace');
      } catch (error) {
        // Extract meaningful error message
        let errorMessage = 'Failed to create workspace';
        if (error.response?.data?.message) {
          errorMessage = error.response.data.message;
        } else if (error.response?.data?.error) {
          errorMessage = error.response.data.error;
        } else if (error.message) {
          errorMessage = error.message;
        }
        
        this.error = errorMessage;
        throw error;
      } finally {
        this.loading = false;
      }
    },

    // Method to update workspace properties
    async updateWorkspaceData(workspaceId, updatedData) {
      try {
        if (!workspaceId) {
          throw new Error('No workspace ID provided');
        }

        this.loading = true;
        this.error = null;
        
        // Get auth token from localStorage
        const userData = localStorage.getItem('authUser');
        let token = null;
        if (userData) {
          const auth = JSON.parse(userData);
          token = auth.token || localStorage.getItem('authToken');
        }
        
        if (!token) {
          throw new Error('Authentication token not found');
        }
        
        // Ensure token has Bearer prefix
        const bearerToken = token.startsWith('Bearer ') ? token : `Bearer ${token}`;
        
        // Build request headers with Bearer token
        const headers = {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'Authorization': bearerToken
        };
        
        // Prepare update data
        const updateData = {
          name: updatedData.name,
          description: updatedData.description || '',
          logo: updatedData.logo || ''
        };
        
        // Make API call to update workspace
        const response = await axios.put(`/api/workspaces/${workspaceId}`, updateData, { headers });
        
        // Extract the updated workspace from the response
        let updatedWorkspace = response.data;
        if (response.data.workspace) {
          updatedWorkspace = response.data.workspace;
        } else if (response.data.data) {
          updatedWorkspace = response.data.data;
        }
        
        // Ensure ID is consistent
        if (!updatedWorkspace.id) {
          if (updatedWorkspace._id) {
            updatedWorkspace.id = updatedWorkspace._id;
          } else if (updatedWorkspace.workspace_id) {
            updatedWorkspace.id = updatedWorkspace.workspace_id;
          }
        }
        
        // Update workspace in store
        const workspaceIndex = this.workspaces.findIndex(w => 
          w.id === workspaceId || 
          w._id === workspaceId || 
          w.workspace_id === workspaceId
        );
        
        if (workspaceIndex !== -1) {
          // Get teamspaces to preserve them
          const teamspaceStore = useTeamspaceStore();
          const teamspaces = teamspaceStore.teamspaces;
          
          // Update existing workspace while preserving its teamspaces
          this.workspaces[workspaceIndex] = {
            ...this.workspaces[workspaceIndex],
            ...updatedWorkspace,
            teamspaces // Ensure teamspaces are preserved
          };
          
          // If this is the current workspace, update it too
          if (this.currentWorkspace && 
              (this.currentWorkspace.id === workspaceId || 
               this.currentWorkspace._id === workspaceId || 
               this.currentWorkspace.workspace_id === workspaceId)) {
            this.currentWorkspace = {
              ...this.currentWorkspace,
              ...updatedWorkspace,
              teamspaces // Preserve teamspaces
            };
          }
        }
        
        return updatedWorkspace;
      } catch (error) {
        // Extract meaningful error message
        let errorMessage = 'Failed to update workspace';
        if (error.response?.data?.message) {
          errorMessage = error.response.data.message;
        } else if (error.response?.data?.error) {
          errorMessage = error.response.data.error;
        } else if (error.message) {
          errorMessage = error.message;
        }
        
        this.error = errorMessage;
        throw error;
      } finally {
        this.loading = false;
      }
    },

    // Accept workspace invitation
    async acceptWorkspaceInvitation(inviteToken) {
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
        
        // Make API call to join workspace
        const response = await axios.post(
          `/invites/join/${inviteToken}`,
          {}, // Empty body as per Postman collection
          { headers }
        )
        
        // Set flag indicating user has a workspace
        localStorage.setItem('has_workspace', 'true')
        
        // Refresh workspace list to include the newly joined workspace
        await this.fetchWorkspaces()
        
        return response.data
      } catch (error) {
        // Extract meaningful error message
        let errorMessage = 'Failed to accept workspace invitation'
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

    // Clear workspace data (used during logout or when needed)
    clearWorkspaces() {
      this.workspaces = []
      this.currentWorkspace = null
      this.error = null
      
      // Clear data in other stores
      const teamspaceStore = useTeamspaceStore()
      teamspaceStore.clearTeamspaces()
    }
  }
}) 