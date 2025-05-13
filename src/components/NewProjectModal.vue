<template>
  <div class="modal fade" id="newProjectModal" ref="modalRef" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content bg-white shadow-sm border">
        <div class="modal-header py-3">
          <h5 class="modal-title">Create New Project</h5>
          <button type="button" class="btn-close" @click="hide"></button>
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
        <div class="modal-footer py-3">
          <button type="button" class="btn btn-link text-dark" @click="hide">Cancel</button>
          <button type="button" class="btn btn-primary px-4" @click="createProject">Create</button>
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
const modal = ref(null)
const projectName = ref('')
const modalRef = ref(null)
const projectInput = ref(null)

onMounted(() => {
  modal.value = new Modal(modalRef.value, {
    backdrop: true
  })
})

const hide = () => {
  modal.value?.hide()
  projectName.value = ''
}

const createProject = () => {
  const teamspace = window.activeTeamspace
  if (projectName.value.trim() && teamspace) {
    const newId = Math.max(0, ...teamspace.projects.map(p => p.id)) + 1
    
    const newProject = {
      id: newId,
      name: projectName.value.trim(),
      lists: []
    }
    
    workspaceStore.addProject(teamspace, newProject)
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
  font-size: 1.25rem;
}

.form-control {
  padding: 0.75rem 1rem;
  font-size: 1rem;
}

.form-control:focus {
  box-shadow: 0 0 0 0.25rem rgba(13,110,253,.15);
}

.btn {
  font-size: 1rem;
  padding: 0.5rem 1rem;
}

.modal-header, .modal-footer {
  border-color: rgba(0,0,0,.1);
}
</style> 