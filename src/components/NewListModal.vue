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
        <div class="modal-footer py-3">
          <button type="button" class="btn btn-link text-dark" @click="hide">Cancel</button>
          <button type="button" class="btn btn-primary px-4" @click="createList">Create</button>
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
  const project = window.activeProject
  const teamspace = window.activeTeamspace

  if (listName.value.trim() && project && teamspace) {
    // Get the correct project from the correct teamspace
    const currentTeamspace = workspaceStore.teamspaces.find(t => t.id === teamspace.id)
    if (!currentTeamspace) {
      console.error('Teamspace not found:', teamspace.name)
      return
    }

    const currentProject = currentTeamspace.projects.find(p => p.id === project.id)
    if (!currentProject) {
      console.error('Project not found in teamspace:', teamspace.name)
      return
    }

    const newId = Math.max(0, ...(currentProject.lists?.map(l => l.id) || [0])) + 1
    
    const newList = {
      id: newId,
      name: listName.value.trim(),
      tasks: []
    }
    
    // Log where the list is being created
    console.log(`Creating list "${newList.name}" in: ${teamspace.name}/Project ${currentProject.name}`)
    
    workspaceStore.addList(currentProject, newList)
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