<template>
  <div class="modal fade" id="newWorkspaceModal" tabindex="-1" aria-labelledby="newWorkspaceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="newWorkspaceModalLabel">Create New Workspace</h5>
          <button type="button" class="btn-close" @click="closeModal" aria-label="Close"></button>
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
            <div class="form-text mb-3">
              A workspace can contain multiple teamspaces and helps you organize your work at the highest level.
            </div>
            <button type="submit" class="btn btn-primary w-100" :disabled="!workspaceName.trim()">
              Create Workspace
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useWorkspaceStore } from '../stores/workspaceStore'
import * as bootstrap from 'bootstrap'

const workspaceStore = useWorkspaceStore()
const workspaceName = ref('')
const workspaceDescription = ref('')
const modalRef = ref(null)
let modalInstance = null

onMounted(() => {
  modalRef.value = document.querySelector('#newWorkspaceModal')
  if (modalRef.value) {
    modalInstance = new bootstrap.Modal(modalRef.value)
  }
})

const closeModal = () => {
  if (modalInstance) {
    modalInstance.hide()
    workspaceName.value = ''
    workspaceDescription.value = ''
  }
}

const handleSubmit = async () => {
  try {
    await workspaceStore.createWorkspace({
      name: workspaceName.value.trim(),
      description: workspaceDescription.value.trim()
    })
    closeModal()
  } catch (error) {
    console.error('Error creating workspace:', error)
  }
}
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
</style> 