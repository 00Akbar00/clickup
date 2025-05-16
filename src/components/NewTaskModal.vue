<template>
  <div class="modal fade" id="newTaskModal" ref="modalRef" tabindex="-1" data-bs-backdrop="static" @keydown="handleKeydown">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content bg-white shadow-sm border">
        <div class="modal-header py-3">
          <h5 class="modal-title">Create New Task</h5>
          <button type="button" class="btn-close" @click="hide" aria-label="Close"></button>
        </div>
        <div class="modal-body py-4">
          <div class="mb-3">
            <label class="form-label">Task Name</label>
            <input
              type="text"
              class="form-control"
              v-model="taskName"
              placeholder="Enter task name"
              ref="taskInput"
            >
          </div>
          <div class="mb-3">
            <label class="form-label">Assigned To</label>
            <input
              type="text"
              class="form-control"
              v-model="assignedTo"
              placeholder="Enter assignee name"
            >
          </div>
          <div class="mb-3">
            <label class="form-label">Due Date</label>
            <input
              type="date"
              class="form-control"
              v-model="dueDate"
            >
          </div>
          <div class="mb-3">
            <label class="form-label">Priority</label>
            <select class="form-select" v-model="priority">
              <option value="high">High</option>
              <option value="normal">Normal</option>
              <option value="low">Low</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Status</label>
            <select class="form-select" v-model="status">
              <option value="todo">To Do</option>
              <option value="in progress">In Progress</option>
              <option value="completed">Completed</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button 
            type="button" 
            class="btn btn-primary w-100" 
            @click="createTask"
            :disabled="!taskName.trim()"
          >Create Task</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Modal } from 'bootstrap'
import { useWorkspaceStore } from '../stores/workspaceStore'
import { useNavigationStore } from '../stores/navigationStore'

const workspaceStore = useWorkspaceStore()
const navigationStore = useNavigationStore()
const modalRef = ref(null)
const taskInput = ref(null)
let modalInstance = null

// Form fields
const taskName = ref('')
const assignedTo = ref('')
const dueDate = ref('')
const priority = ref('normal')
const status = ref('todo')

onMounted(() => {
  modalInstance = new Modal(modalRef.value)
})

const hide = () => {
  modalInstance?.hide()
  resetForm()
}

const handleKeydown = (event) => {
  if (event.key === 'Escape') {
    hide()
  }
}

const resetForm = () => {
  taskName.value = ''
  assignedTo.value = ''
  dueDate.value = ''
  priority.value = 'normal'
  status.value = 'todo'
}

const createTask = () => {
  if (!taskName.value.trim()) return

  const teamspaceId = window.activeTeamspace?.id
  const projectId = window.activeProject?.id
  const listId = window.activeList?.id
  
  if (!teamspaceId || !projectId || !listId) {
    console.error('Missing teamspace, project, or list reference')
    return
  }

  const list = workspaceStore.getList(teamspaceId, projectId, listId)
  if (!list) {
    console.error('List not found')
    return
  }

  const newId = Math.max(0, ...list.tasks.map(t => t.id), 0) + 1
  
  const newTask = {
    id: newId,
    name: taskName.value.trim(),
    assignee: assignedTo.value.trim(),
    dueDate: dueDate.value,
    priority: priority.value,
    status: status.value
  }
  
  workspaceStore.addTask(teamspaceId, projectId, listId, newTask)
  hide()
}

// Expose methods to parent components
defineExpose({
  hide,
  createTask
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