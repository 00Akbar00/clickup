<template>
  <div v-if="showModal" class="modal fade show d-block" tabindex="-1" aria-hidden="false">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Create New Project</h5>
          <button type="button" class="btn-close" @click="hide" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="projectName" class="form-label">Project Name *</label>
            <input 
              type="text" 
              class="form-control" 
              id="projectName"
              v-model="projectName"
              placeholder="Enter project name"
              @keyup.enter="createProject"
              :disabled="isLoading"
            >
          </div>
          
          <div class="mb-3">
            <label for="projectDescription" class="form-label">Description</label>
            <textarea
              class="form-control"
              id="projectDescription"
              v-model="projectDescription"
              placeholder="Describe this project's purpose"
              rows="2"
              :disabled="isLoading"
            ></textarea>
          </div>
          
          <div class="mb-3">
            <label class="form-label d-block">Visibility</label>
            <div class="form-check form-check-inline">
              <input 
                class="form-check-input" 
                type="radio" 
                name="visibility" 
                id="visibilityPublic" 
                value="public" 
                v-model="visibility"
                :disabled="isLoading"
              >
              <label class="form-check-label" for="visibilityPublic">Public</label>
            </div>
            <div class="form-check form-check-inline">
              <input 
                class="form-check-input" 
                type="radio" 
                name="visibility" 
                id="visibilityPrivate" 
                value="private" 
                v-model="visibility"
                :disabled="isLoading"
              >
              <label class="form-check-label" for="visibilityPrivate">Private</label>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button 
            type="button" 
            class="btn btn-primary" 
            @click="createProject" 
            :disabled="!projectName.trim() || isLoading"
          >
            <span v-if="isLoading" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
            {{ isLoading ? 'Creating...' : 'Create Project' }}
          </button>
        </div>
      </div>
    </div>
  </div>
  <div v-if="showModal" class="modal-backdrop fade show"></div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useWorkspaceStore } from '../stores/workspaceStore'
import { useProjectStore } from '../stores/projectStore'
import { useTeamspaceStore } from '../stores/teamspaceStore'
import { createToast } from 'mosha-vue-toastify'

const workspaceStore = useWorkspaceStore()
const projectStore = useProjectStore()
const teamspaceStore = useTeamspaceStore()

const showModal = ref(false)
const projectName = ref('')
const projectDescription = ref('')
const visibility = ref('public')
const isLoading = ref(false)

// Modal display functions
const show = () => {
  showModal.value = true
  projectName.value = '' // Reset form
  projectDescription.value = ''
  visibility.value = 'public'
}

const hide = () => {
  showModal.value = false
  projectName.value = '' // Reset form
  projectDescription.value = ''
  visibility.value = 'public'
}

const createProject = async () => {
  if (!projectName.value.trim()) return
  
  const teamspaceId = window.activeTeamspace?.id
  if (!teamspaceId) {
    createToast('No active team selected', {
      position: 'top-right',
      type: 'danger',
      timeout: 3000
    })
    return
  }
  
  isLoading.value = true
  
  try {
    // Create a loading toast
    const loadingToast = createToast('Creating project...', {
      position: 'top-right',
      type: 'info',
      showIcon: true,
      swipeClose: false,
      timeout: -1
    })
    
    const newProject = {
      name: projectName.value.trim(),
      description: projectDescription.value.trim(),
      visibility: visibility.value
    }
    
    // Create the project using projectStore instead of workspaceStore
    await projectStore.addProject(teamspaceId, newProject)
    
    // Refresh the projects list from the API using teamspaceStore
    await teamspaceStore.fetchTeamspaceProjects(teamspaceId)
    
    // Remove loading toast and show success toast
    loadingToast.close()
    createToast('Project created successfully!', {
      position: 'top-right',
      type: 'success',
      timeout: 3000
    })
    
    hide() // Close the modal
  } catch (error) {
    console.error('Failed to create project:', error)
    createToast(`Failed to create project: ${error.message || 'Unknown error'}`, {
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
  hide,
  show,
  createProject
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