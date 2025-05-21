<template>
  <div v-if="showModal" class="modal fade show d-block" id="newListModal" tabindex="-1" aria-hidden="false">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Create New List</h5>
          <button type="button" class="btn-close" @click="hide" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="listName" class="form-label">List Name</label>
            <input
              type="text"
              class="form-control"
              id="listName"
              v-model="listName"
              placeholder="Enter list name"
              @keyup.enter="createList"
              :disabled="isLoading"
            >
          </div>
          <div class="mb-3">
            <label for="listDescription" class="form-label">Description (optional)</label>
            <textarea
              class="form-control"
              id="listDescription"
              v-model="listDescription"
              placeholder="Enter list description"
              rows="3"
              :disabled="isLoading"
            ></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button 
            type="button" 
            class="btn btn-primary" 
            @click="createList" 
            :disabled="!listName.trim() || isLoading"
          >
            <span v-if="isLoading" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
            {{ isLoading ? 'Creating...' : 'Create List' }}
          </button>
        </div>
      </div>
    </div>
  </div>
  <div v-if="showModal" class="modal-backdrop fade show"></div>
</template>

<script setup>
import { ref } from 'vue'
import { useWorkspaceStore } from '../stores/workspaceStore'
import { useNavigationStore } from '../stores/navigationStore'
import { useListStore } from '../stores/listStore'
import { useTeamspaceStore } from '../stores/teamspaceStore'
import { useProjectStore } from '../stores/projectStore'
import { createToast } from 'mosha-vue-toastify'

const workspaceStore = useWorkspaceStore()
const navigationStore = useNavigationStore()
const listStore = useListStore()
const teamspaceStore = useTeamspaceStore()
const projectStore = useProjectStore()

const listName = ref('')
const listDescription = ref('')
const isLoading = ref(false)
const showModal = ref(false)

const show = () => {
  showModal.value = true
  listName.value = '' // Reset form
  listDescription.value = '' // Reset description
}

const hide = () => {
  showModal.value = false
  listName.value = '' // Reset form
  listDescription.value = '' // Reset description
}

const createList = async () => {
  if (!listName.value.trim()) return

  const teamspaceId = window.activeTeamspace?.id
  const projectId = window.activeProject?.id
  
  if (!teamspaceId || !projectId) {
    createToast('Missing teamspace or project reference', {
      position: 'top-right',
      type: 'danger',
      timeout: 3000
    })
    return
  }

  isLoading.value = true
  
  try {
    // Create a loading toast
    const loadingToast = createToast('Creating list...', {
      position: 'top-right',
      type: 'info',
      showIcon: true,
      swipeClose: false,
      timeout: -1
    })
    
    // Prepare the list data
    const listData = {
      name: listName.value.trim(),
      description: listDescription.value.trim() || undefined
    }
    
    console.log('Creating list with data:', listData, 'for project:', projectId)
    
    // Call the API to create the list using listStore instead of workspaceStore
    const newList = await listStore.createList(projectId, listData)
    
    // Update navigation if needed
    if (newList) {
      // Refresh the project lists to ensure UI is updated with the new list
      try {
        await listStore.fetchProjectLists(projectId)
        console.log('Successfully refreshed project lists after creating new list')
      } catch (refreshError) {
        console.warn('Failed to refresh lists after creation:', refreshError)
      }
      
      const teamspace = teamspaceStore.getTeamspaceLocal(teamspaceId)
      const project = teamspace?.projects?.find(p => 
        p.id === projectId || 
        p._id === projectId || 
        p.project_id === projectId
      )
      
      if (teamspace && project) {
        navigationStore.setTeamspace(teamspace)
        navigationStore.setProject(project)
        navigationStore.setList(newList)
      }
      
      // Remove loading toast and show success toast
      loadingToast.close()
      createToast('List created successfully!', {
        position: 'top-right',
        type: 'success',
        timeout: 3000
      })
      
      hide() // Close the modal
    } else {
      // Handle case where newList is undefined
      loadingToast.close()
      createToast('List may have been created but no data was returned', {
        position: 'top-right',
        type: 'warning',
        timeout: 5000
      })
      
      // Try to refresh the project lists
      try {
        await listStore.fetchProjectLists(projectId)
        console.log('Successfully refreshed project lists after uncertain creation')
        hide() // Close the modal
      } catch (refreshError) {
        console.error('Could not refresh lists after creation attempt:', refreshError)
      }
    }
  } catch (error) {
    console.error('Error creating list:', error)
    
    // Get detailed error information for debugging
    let errorInfo = {
      message: error.message || 'Unknown error'
    }
    
    if (error.response) {
      errorInfo.status = error.response.status
      errorInfo.data = error.response.data
      errorInfo.headers = error.response.headers
    }
    
    console.error('Detailed error info:', errorInfo)
    
    let errorMessage = 'Failed to create list'
    
    // Try to extract useful error information
    if (error.response) {
      if (error.response.data?.message) {
        errorMessage = error.response.data.message
      } else if (error.response.data?.error) {
        errorMessage = error.response.data.error
      } else if (error.response.status === 500) {
        errorMessage = 'Server error occurred. Please check if the list was created despite this error.'
        
        // Try to refresh project lists to check if the list was created despite the error
        try {
          await listStore.fetchProjectLists(projectId)
          console.log('Refreshed project lists after 500 error')
        } catch (refreshError) {
          console.warn('Could not refresh lists after error:', refreshError)
        }
      }
    } else if (error.message) {
      errorMessage = error.message
    }
    
    createToast(`Failed to create list: ${errorMessage}`, {
      position: 'top-right',
      type: 'danger',
      timeout: 5000
    })
  } finally {
    isLoading.value = false
  }
}

// Expose methods to parent components
defineExpose({
  show,
  hide,
  createList
})
</script>

<style scoped>
.modal {
  z-index: 2000 !important;
}

.modal-dialog {
  max-width: 500px;
}

.modal-content {
  border-radius: 8px;
  box-shadow: 0 0.5rem 2rem rgba(0, 0, 0, 0.15) !important;
}

.modal-title {
  font-size: var(--app-font-size-lg);
}

.form-control {
  padding: 0.5rem 1rem;
  font-size: var(--app-font-size-base);
  border-radius: 6px;
  border: 1.5px solid rgba(0, 0, 0, 0.1);
  box-shadow: none;
}

.form-control:focus {
  border-color: var(--app-primary-color);
  box-shadow: 0 0 8px rgba(84, 62, 208, 0.25);
}

.btn {
  font-size: var(--app-font-size-base);
  padding: 0.5rem 1rem;
}

.modal-header {
  border-bottom: none;
}

.modal-footer {
  border-color: rgba(0,0,0,.1);
}
</style> 