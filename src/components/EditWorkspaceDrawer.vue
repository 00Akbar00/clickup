<!-- EditWorkspace Drawer Component -->
<template>
  <div>
    <!-- Backdrop -->
    <div 
      v-if="isDrawerOpen" 
      class="drawer-backdrop"
      @click="close"
    ></div>

    <!-- Drawer -->
    <div 
      class="drawer" 
      :class="{ 'drawer-open': isDrawerOpen }"
    >
      <div class="drawer-header border-bottom">
        <h5 class="mb-0">Edit Workspace</h5>
        <button class="btn btn-link text-dark p-0" @click="close">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>

      <div class="drawer-body">
        <!-- Workspace Info -->
        <div class="text-center mb-4">
          <div class="position-relative d-inline-block">
            <img
              v-if="logoPreview"
              :src="logoPreview"
              alt="Workspace Logo"
              class="rounded workspace-logo"
            />
            <div
              v-else
              class="rounded bg-secondary d-flex align-items-center justify-content-center workspace-logo"
            >
              <i class="bi bi-building text-white" style="font-size: 2rem;"></i>
            </div>
            <label 
              class="btn btn-light btn-sm rounded-circle position-absolute bottom-0 end-0 p-1 edit-button"
              role="button"
            >
              <i class="bi bi-pencil"></i>
              <input 
                type="file" 
                class="d-none" 
                accept="image/*"
                @change="handleLogoChange"
              >
            </label>
          </div>
        </div>

        <!-- Workspace Fields -->
        <div class="workspace-fields mb-4">
          <!-- Name Field -->
          <div class="workspace-field mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <label class="form-label mb-0">Workspace Name</label>
              <button 
                v-if="!isEditingName"
                class="btn btn-link text-primary p-0" 
                @click="startEditingName"
              >
                <i class="bi bi-pencil-square"></i>
              </button>
            </div>
            <div class="d-flex gap-2">
              <input 
                type="text" 
                class="form-control"
                v-model="form.name"
                :readonly="!isEditingName"
                :class="{ 'form-control-plaintext': !isEditingName }"
                placeholder="Enter workspace name"
              >
              <div v-if="isEditingName" class="edit-actions">
                <button class="btn btn-primary btn-sm" @click="saveNameChanges">
                  <i class="bi bi-check-lg"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- Description Field -->
          <div class="workspace-field mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <label class="form-label mb-0">Description</label>
              <button 
                v-if="!isEditingDescription"
                class="btn btn-link text-primary p-0" 
                @click="startEditingDescription"
              >
                <i class="bi bi-pencil-square"></i>
              </button>
            </div>
            <div class="d-flex gap-2">
              <textarea 
                class="form-control"
                v-model="form.description"
                :readonly="!isEditingDescription"
                :class="{ 'form-control-plaintext': !isEditingDescription }"
                placeholder="Enter workspace description"
                rows="3"
              ></textarea>
              <div v-if="isEditingDescription" class="edit-actions">
                <button class="btn btn-primary btn-sm" @click="saveDescriptionChanges">
                  <i class="bi bi-check-lg"></i>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Update and Delete Buttons -->
        <div class="border-top pt-3 d-flex justify-content-between">
          <button 
            class="btn btn-danger" 
            @click="confirmDeleteWorkspace" 
            :disabled="isUpdating || isDeleting"
          >
            <span v-if="isDeleting" class="spinner-border spinner-border-sm me-2" role="status"></span>
            <i class="bi bi-trash me-1"></i> Delete Workspace
          </button>
          <button 
            class="btn btn-primary" 
            @click="updateWorkspace" 
            :disabled="isUpdating || isDeleting"
          >
            <span v-if="isUpdating" class="spinner-border spinner-border-sm me-2" role="status"></span>
            Update Workspace
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useWorkspaceStore } from '../stores/workspaceStore'
import axios from 'axios'

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  workspace: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['close', 'updated', 'deleted'])
const workspaceStore = useWorkspaceStore()

// Internal state for drawer open status 
const isDrawerOpen = ref(false)

// Expose isOpen as a computed property with getter and setter
defineExpose({
  get isOpen() {
    return isDrawerOpen.value
  },
  set isOpen(value) {
    isDrawerOpen.value = value
  },
  get workspace() {
    return currentWorkspace.value
  },
  set workspace(value) {
    currentWorkspace.value = value
    if (value) {
      form.value.name = value.name || ''
      form.value.description = value.description || ''
      logoPreview.value = value.logo || value.logo_url || ''
    }
  }
})

// Watch for changes to the isOpen prop
watch(() => props.isOpen, (newValue) => {
  isDrawerOpen.value = newValue
}, { immediate: true })

// Edit states
const isEditingName = ref(false)
const isEditingDescription = ref(false)
const originalName = ref('')
const originalDescription = ref('')
const isUpdating = ref(false)
const isDeleting = ref(false)

// Workspace state
const currentWorkspace = ref({...props.workspace})

// Form state
const form = ref({
  name: '',
  description: '',
  logo: ''
})

const logoPreview = ref('')

// Initialize the form with workspace data when the component is first loaded
watch(() => props.workspace, (newWorkspace) => {
  if (newWorkspace) {
    currentWorkspace.value = {...newWorkspace}
    form.value.name = newWorkspace.name || ''
    form.value.description = newWorkspace.description || ''
    logoPreview.value = newWorkspace.logo || newWorkspace.logo_url || ''
  }
}, { immediate: true, deep: true })

// Methods
const close = () => {
  // Reset any ongoing edits
  cancelNameEdit()
  cancelDescriptionEdit()
  isDrawerOpen.value = false
  emit('close')
}

const startEditingName = () => {
  originalName.value = form.value.name
  isEditingName.value = true
}

const startEditingDescription = () => {
  originalDescription.value = form.value.description
  isEditingDescription.value = true
}

const saveNameChanges = () => {
  isEditingName.value = false
}

const saveDescriptionChanges = () => {
  isEditingDescription.value = false
}

const cancelNameEdit = () => {
  form.value.name = originalName.value
  isEditingName.value = false
}

const cancelDescriptionEdit = () => {
  form.value.description = originalDescription.value
  isEditingDescription.value = false
}

const handleLogoChange = (event) => {
  const file = event.target.files[0]
  if (file) {
    const reader = new FileReader()
    reader.onload = (e) => {
      logoPreview.value = e.target.result
      form.value.logo = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

const updateWorkspace = async () => {
  if (!currentWorkspace.value || !currentWorkspace.value.id) {
    console.error('No workspace selected to update')
    return
  }

  try {
    isUpdating.value = true

    // Get auth token from localStorage
    const userData = localStorage.getItem('authUser')
    let token = null
    if (userData) {
      const auth = JSON.parse(userData)
      token = auth.token || localStorage.getItem('authToken')
    }
    
    if (!token) {
      console.error('Authentication token not found')
      return
    }
    
    // Ensure token has Bearer prefix
    const bearerToken = token.startsWith('Bearer ') ? token : `Bearer ${token}`
    
    // Build request headers with Bearer token
    const headers = {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'Authorization': bearerToken
    }
    
    // Prepare update data
    const updateData = {
      name: form.value.name,
      description: form.value.description,
      logo: form.value.logo
    }
    
    // Make API call to update workspace
    const workspaceId = currentWorkspace.value.id || currentWorkspace.value._id || currentWorkspace.value.workspace_id
    const url = `http://localhost/api/workspaces/${workspaceId}`
    
    const response = await axios.put(url, updateData, { headers })
    
    // Update store data
    if (workspaceStore.updateWorkspaceData) {
      await workspaceStore.updateWorkspaceData(workspaceId, updateData)
    } else {
      // Fallback: refetch workspace data
      await workspaceStore.fetchWorkspaces()
    }
    emit('updated', response.data)
    close()
  } catch (error) {
    console.error('Error updating workspace:', error)
    console.error('Failed to update workspace:', error.response?.data?.message || error.message || 'Unknown error')
  } finally {
    isUpdating.value = false
  }
}

const confirmDeleteWorkspace = () => {
  // Show confirmation dialog before deletion
  if (confirm(`Are you sure you want to delete the workspace "${currentWorkspace.value.name}"? This action cannot be undone.`)) {
    deleteWorkspace()
  }
}

const deleteWorkspace = async () => {
  if (!currentWorkspace.value || !currentWorkspace.value.id) {
    console.error('No workspace selected to delete')
    return
  }

  try {
    isDeleting.value = true

    // Get auth token from localStorage
    const userData = localStorage.getItem('authUser')
    let token = null
    if (userData) {
      const auth = JSON.parse(userData)
      token = auth.token || localStorage.getItem('authToken')
    }
    
    if (!token) {
      console.error('Authentication token not found')
      return
    }
    
    // Ensure token has Bearer prefix
    const bearerToken = token.startsWith('Bearer ') ? token : `Bearer ${token}`
    
    // Build request headers with Bearer token
    const headers = {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'Authorization': bearerToken
    }
    
    // Get workspace ID
    const workspaceId = currentWorkspace.value.id || currentWorkspace.value._id || currentWorkspace.value.workspace_id
    
    // TODO: Implement the actual API call to delete the workspace
    // For now, this is a placeholder for the API call
    // const url = `http://localhost/api/workspaces/${workspaceId}`
    // await axios.delete(url, { headers })
    
    // Simulate API call with a delay for now
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    // Update store after successful deletion
    if (workspaceStore.removeWorkspace) {
      await workspaceStore.removeWorkspace(workspaceId)
    } else {
      // Fallback: refetch workspace data
      await workspaceStore.fetchWorkspaces()
    }
    // Emit event to notify parent component
    emit('deleted', workspaceId)
    
    // Close the drawer
    close()
  } catch (error) {
    console.error('Error deleting workspace:', error)
    console.error('Failed to delete workspace:', error.response?.data?.message || error.message || 'Unknown error')
  } finally {
    isDeleting.value = false
  }
}

// Define component name
defineOptions({
  name: 'EditWorkspaceDrawer'
})
</script>

<style scoped>
.drawer-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background-color: rgba(0, 0, 0, 0.2);
  backdrop-filter: blur(1px);
  z-index: 1040;
}

.drawer {
  position: fixed;
  top: 0;
  left: -400px;
  width: 400px;
  height: 100vh;
  background-color: white;
  box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
  transition: left 0.3s ease;
  z-index: 1050;
}

.drawer-open {
  left: 0;
}

.drawer-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
}

.drawer-body {
  padding: 1.5rem;
  overflow-y: auto;
  height: calc(100vh - 60px);
}

.workspace-logo {
  width: 120px;
  height: 120px;
  object-fit: cover;
}

.edit-button {
  cursor: pointer;
}

.edit-button:hover {
  background-color: #e9ecef !important;
}

.workspace-field {
  background-color: #f8f9fa;
  padding: 1rem;
  border-radius: 8px;
}

.form-control-plaintext {
  background-color: transparent;
  border: none;
  padding-left: 0;
  padding-right: 0;
}

.form-control-plaintext:focus {
  outline: none;
}

.edit-actions {
  display: flex;
  gap: 0.5rem;
}

.edit-actions .btn {
  width: 32px;
  height: 32px;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

@media (max-width: 576px) {
  .drawer {
    width: 100%;
    left: -100%;
  }
}
</style> 