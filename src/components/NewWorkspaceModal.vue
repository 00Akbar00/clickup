<template>
  <div v-if="showModal" class="modal fade show d-block" id="newWorkspaceModal" tabindex="-1" aria-labelledby="newWorkspaceModalLabel" aria-hidden="false">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="newWorkspaceModalLabel">Create New Workspace</h5>
          <button type="button" class="btn-close" @click="hide" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="handleSubmit">
            <div class="mb-3">
              <label for="workspaceName" class="form-label">Workspace Name</label>
              <input
                type="text"
                class="form-control"
                id="workspaceName"
                v-model="workspaceName"
                placeholder="Enter workspace name"
                required
              >
            </div>
            <div class="mb-3">
              <label for="workspaceDescription" class="form-label">Description (Optional)</label>
              <textarea
                class="form-control"
                id="workspaceDescription"
                v-model="workspaceDescription"
                rows="3"
                placeholder="Enter workspace description"
              ></textarea>
            </div>
            <div class="mb-3">
              <label for="workspaceLogo" class="form-label">Logo (Optional)</label>
              <div class="d-flex align-items-center gap-2">
                <div 
                  v-if="logoPreview" 
                  class="logo-preview rounded d-flex align-items-center justify-content-center"
                >
                  <img :src="logoPreview" alt="Logo Preview" class="img-fluid" />
                </div>
                <div v-else class="logo-placeholder rounded d-flex align-items-center justify-content-center">
                  <i class="bi bi-building text-muted"></i>
                </div>
                <div class="flex-grow-1">
                  <input
                    type="file"
                    class="form-control"
                    id="workspaceLogo"
                    accept="image/*"
                    @change="handleLogoChange"
                  >
                </div>
              </div>
            </div>
            <div class="form-text mb-3">
              A workspace can contain multiple teamspaces and helps you organize your work at the highest level.
            </div>
            <div v-if="errorMessage" class="alert alert-danger mb-3">
              {{ errorMessage }}
            </div>
            <button type="submit" class="btn btn-primary w-100" :disabled="!workspaceName.trim() || isLoading">
              <span v-if="isLoading" class="spinner-border spinner-border-sm me-2" role="status"></span>
              Create Workspace
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
  <div v-if="showModal" class="modal-backdrop fade show"></div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue'
import { useWorkspaceStore } from '../stores/workspaceStore'
import { useRouter } from 'vue-router'
import axios from 'axios'

const workspaceStore = useWorkspaceStore()
const workspaceName = ref('')
const workspaceDescription = ref('')
const workspaceLogo = ref('')
const logoPreview = ref('')
const isLoading = ref(false)
const errorMessage = ref('')
const showModal = ref(false)

// Show/hide methods
const show = () => {
  showModal.value = true
  resetForm()
}

const hide = () => {
  showModal.value = false
  resetForm()
}

const resetForm = () => {
  workspaceName.value = ''
  workspaceDescription.value = ''
  workspaceLogo.value = ''
  logoPreview.value = ''
  errorMessage.value = ''
}

const handleLogoChange = (event) => {
  const file = event.target.files[0]
  if (file) {
    const reader = new FileReader()
    reader.onload = e => {
      logoPreview.value = e.target.result
      workspaceLogo.value = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

const handleSubmit = async () => {
  try {
    isLoading.value = true
    errorMessage.value = ''
    
    await workspaceStore.createWorkspace({
      name: workspaceName.value,
      description: workspaceDescription.value,
      logo: workspaceLogo.value
    })
    hide()
    
  } catch (error) {
    errorMessage.value = error.response?.data?.message || error.message || 'Failed to create workspace'
    
    console.error(`Failed to create workspace: ${errorMessage.value}`)
  } finally {
    isLoading.value = false
  }
}

// Expose methods to parent components
defineExpose({
  show,
  hide
})
</script>

<style scoped>
.modal-body {
  padding: 1.5rem;
}

.form-label {
  font-weight: 500;
}

.form-text {
  font-size: 0.875rem;
}

.logo-preview, 
.logo-placeholder {
  width: 48px;
  height: 48px;
  background-color: #f8f9fa;
  border: 1px dashed #ccc;
  overflow: hidden;
}

.logo-preview img {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
}

.logo-placeholder i {
  font-size: 1.5rem;
}
</style> 