<template>
  <div v-if="showModal" class="modal fade show d-block" id="newTeamspaceModal" tabindex="-1" aria-hidden="false">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Create New Team</h5>
          <button type="button" class="btn-close" @click="hide" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="teamspaceName" class="form-label">Team Name *</label>
            <input 
              type="text" 
              class="form-control" 
              id="teamspaceName" 
              v-model="teamspaceName"
              placeholder="Enter team name"
              @keyup.enter="createTeamspace"
              :disabled="isLoading"
            >
          </div>
          
          <div class="mb-3">
            <label for="teamDescription" class="form-label">Description</label>
            <textarea
              class="form-control"
              id="teamDescription"
              v-model="teamDescription"
              placeholder="Describe this team's purpose"
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
            @click="createTeamspace" 
            :disabled="!teamspaceName.trim() || isLoading"
          >
            <span v-if="isLoading" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
            {{ isLoading ? 'Creating...' : 'Create Team' }}
          </button>
        </div>
      </div>
    </div>
  </div>
  <div v-if="showModal" class="modal-backdrop fade show"></div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useWorkspaceStore } from '../stores/workspaceStore'
import { useTeamspaceStore } from '../stores/teamspaceStore'
import { createToast } from 'mosha-vue-toastify'

const workspaceStore = useWorkspaceStore()
const teamspaceStore = useTeamspaceStore()

const showModal = ref(false)
const teamspaceName = ref('')
const teamDescription = ref('')
const visibility = ref('public')
const isLoading = ref(false)

// Modal display functions
const show = () => {
  showModal.value = true
  teamspaceName.value = '' // Reset form
  teamDescription.value = ''
  visibility.value = 'public'
}

const hide = () => {
  showModal.value = false
  // Reset form
  teamspaceName.value = ''
  teamDescription.value = ''
  visibility.value = 'public'
}

const createTeamspace = async () => {
  if (!teamspaceName.value.trim()) return
  
  isLoading.value = true
  
  try {
    const workspaceId = workspaceStore.currentWorkspace?.id
    
    if (!workspaceId) {
      throw new Error('No active workspace found')
    }
    
    const newTeamspace = {
      name: teamspaceName.value.trim(),
      description: teamDescription.value.trim(),
      visibility: visibility.value,
      projects: []
    }
    
    await teamspaceStore.addTeamspace(newTeamspace, workspaceId)
    
    createToast('Team created successfully!', {
      position: 'top-right',
      type: 'success',
      timeout: 3000
    })
    
    hide() // Close the modal
  } catch (error) {
    console.error('Failed to create team:', error.message || 'Unknown error')
    
    createToast(`Failed to create team: ${error.message || 'Unknown error'}`, {
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
  createTeamspace
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

.form-label {
  font-weight: 500;
  color: #444;
  font-size: var(--app-font-size-base);
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