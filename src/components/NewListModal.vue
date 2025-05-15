<template>
  <div class="modal fade" id="newListModal" ref="modalRef" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content bg-white shadow-sm border">
        <div class="modal-header py-3">
          <h5 class="modal-title">Create New List</h5>
          <button type="button" class="btn-close" @click="hide"></button>
        </div>
        <div class="modal-body py-4">
          <input
            type="text"
            class="form-control"
            v-model="listName"
            placeholder="Enter list name"
            @keyup.enter="createList"
            ref="listInput"
          >
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button 
            type="button" 
            class="btn btn-primary" 
            @click="createList"
            :disabled="!listName.trim()"
          >Create List</button>
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
const listName = ref('')
const modalRef = ref(null)
const listInput = ref(null)

onMounted(() => {
  modal.value = new Modal(modalRef.value, {
    backdrop: true
  })
})

const hide = () => {
  modal.value?.hide()
  listName.value = ''
}

const createList = () => {
  if (listName.value.trim()) {
    const teamspaceId = window.activeTeamspace?.id
    const projectId = window.activeProject?.id
    
    if (!teamspaceId || !projectId) {
      console.error('Missing teamspace or project reference')
      return
    }

    const project = workspaceStore.getProject(teamspaceId, projectId)
    if (!project) {
      console.error('Project not found')
      return
    }

    const newId = Math.max(0, ...project.lists.map(l => l.id), 0) + 1
    
    const newList = {
      id: newId,
      name: listName.value.trim(),
      tasks: []
    }
    
    workspaceStore.addList(teamspaceId, projectId, newList)
    hide()
  }
}

// Expose methods to parent components
defineExpose({
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