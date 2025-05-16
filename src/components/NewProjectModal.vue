<template>
  <div class="modal fade" id="newProjectModal" ref="modalRef" tabindex="-1" data-bs-backdrop="static" @keydown="handleKeydown">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content bg-white shadow-sm border">
        <div class="modal-header py-3">
          <h5 class="modal-title">Create New Project</h5>
          <button type="button" class="btn-close" @click="hide" aria-label="Close"></button>
        </div>
        <div class="modal-body py-4">
          <input
            type="text"
            class="form-control"
            v-model="projectName"
            placeholder="Enter project name"
            @keyup.enter="createProject"
            ref="projectInput"
          >
        </div>
        <div class="modal-footer">
          <button 
            type="button" 
            class="btn btn-primary w-100" 
            @click="createProject"
            :disabled="!projectName.trim()"
          >Create Project</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Modal } from 'bootstrap'
import { useWorkspaceStore } from '../stores/workspaceStore'

const workspaceStore = useWorkspaceStore()
const modalRef = ref(null)
const projectName = ref('')
const projectInput = ref(null)
let modalInstance = null

onMounted(() => {
  // Initialize modal instance
  modalInstance = new Modal(modalRef.value)
})

const hide = () => {
  modalInstance?.hide()
  projectName.value = ''
}

const createProject = () => {
  if (projectName.value.trim()) {
    const newId = Math.max(0, ...workspaceStore.teamspaces.flatMap(t => t.projects?.map(p => p.id) || []), 0) + 1
    
    const newProject = {
      id: newId,
      name: projectName.value.trim(),
      lists: []
    }
    
    const teamspaceId = window.activeTeamspace?.id
    if (teamspaceId) {
      workspaceStore.addProject(teamspaceId, newProject)
      modalInstance?.hide()
      projectName.value = ''
    } else {
      console.error('No active teamspace found')
    }
  }
}

const handleKeydown = (event) => {
  if (event.key === 'Escape') {
    hide()
  }
}

// Expose methods to parent components
defineExpose({
  hide,
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