<template>
  <div v-if="showModal" class="modal-backdrop fade show"></div>
  <div v-if="showModal" class="modal fade show d-block" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add New Task</h5>
          <button type="button" class="btn-close" aria-label="Close" @click="hide"></button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="createTask">
            <!-- Task Name -->
            <div class="mb-3">
              <label for="taskName" class="form-label">Task Name</label>
              <input 
                type="text" 
                class="form-control" 
                id="taskName" 
                v-model="taskName" 
                placeholder="Enter task name"
                required
                ref="taskNameInput"
              >
            </div>

            <!-- Description -->
            <div class="mb-3">
              <label for="taskDescription" class="form-label">Description</label>
              <textarea 
                class="form-control" 
                id="taskDescription" 
                v-model="taskDescription" 
                placeholder="Enter task description (optional)"
                rows="3"
              ></textarea>
            </div>

            <!-- Assignee -->
            <div class="mb-3">
              <label for="taskAssignee" class="form-label">Assignee</label>
              <select class="form-select" id="taskAssignee" v-model="assigneeId">
                <option value="">Unassigned</option>
                <option v-for="user in userList" :key="user.id" :value="user.id">
                  {{ user.name }}
                </option>
              </select>
            </div>

            <!-- Due Date -->
            <div class="mb-3">
              <label for="taskDueDate" class="form-label">Due Date</label>
              <input 
                type="date" 
                class="form-control" 
                id="taskDueDate" 
                v-model="taskDueDate"
              >
              <small class="form-text text-muted">Will be formatted as DD/MM/YY</small>
            </div>

            <!-- Priority -->
            <div class="mb-3">
              <label for="taskPriority" class="form-label">Priority</label>
              <select class="form-select" id="taskPriority" v-model="taskPriority">
                <option value="low">Low</option>
                <option value="normal">Normal</option>
                <option value="high">High</option>
              </select>
            </div>

            <!-- Status -->
            <div class="mb-3">
              <label for="taskStatus" class="form-label">Status</label>
              <select class="form-select" id="taskStatus" v-model="taskStatus">
                <option value="todo">To Do</option>
                <option value="in progress">In Progress</option>
                <option value="completed">Completed</option>
              </select>
            </div>

            <!-- Error Message -->
            <div v-if="errorMessage" class="alert alert-danger" role="alert">
              {{ errorMessage }}
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button 
            type="button" 
            class="btn btn-primary" 
            @click="createTask"
            :disabled="!taskName || isLoading"
          >
            <span v-if="isLoading" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
            {{ isLoading ? 'Creating...' : 'Create Task' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useWorkspaceStore } from '../stores/workspaceStore'
import { useTaskStore } from '../stores/taskStore'
import { useToast } from '../composables/useToast'

const workspaceStore = useWorkspaceStore()
const taskStore = useTaskStore()
const toast = useToast()

// Reactive state
const showModal = ref(false)
const taskName = ref('')
const taskDescription = ref('')
const taskDueDate = ref('')
const taskPriority = ref('normal')
const taskStatus = ref('todo')
const isLoading = ref(false)
const errorMessage = ref('')
const taskNameInput = ref(null)
const assigneeId = ref('')

// User list - hardcoded for now, typically this would come from an API
const userList = ref([
  { id: '1', name: 'John Doe' },
  { id: '2', name: 'Jane Smith' },
  { id: '3', name: 'Mike Johnson' }
])

// Method to show the modal
const show = () => {
  showModal.value = true
  resetForm()
  
  // Focus the name input after the modal is shown
  setTimeout(() => {
    if (taskNameInput.value) {
      taskNameInput.value.focus()
    }
  }, 100)
}

// Method to hide the modal
const hide = () => {
  showModal.value = false
  resetForm()
}

// Reset the form
const resetForm = () => {
  taskName.value = ''
  taskDescription.value = ''
  taskDueDate.value = ''
  taskPriority.value = 'normal'
  taskStatus.value = 'todo'
  errorMessage.value = ''
  assigneeId.value = ''
}

// Create a new task
const createTask = async () => {
  if (!taskName.value.trim()) {
    errorMessage.value = 'Task name is required'
    return
  }
  
  // Get list information from the window object (set by ListView component)
  const list = window.activeList
  if (!list) {
    errorMessage.value = 'No list selected'
    toast.showToast('No list selected', 'danger')
    return
  }
  
  // Get the list ID
  const listId = list.id || list._id || list.list_id
  if (!listId) {
    errorMessage.value = 'Invalid list ID'
    toast.showToast('Invalid list ID', 'danger')
    return
  }
  
  try {
    isLoading.value = true
    errorMessage.value = ''
    
    // Format the due date if it exists (from YYYY-MM-DD to DD-MM-YYYY)
    let formattedDueDate = null
    if (taskDueDate.value) {
      // Parse the date from input format (YYYY-MM-DD)
      const dateParts = taskDueDate.value.split('-')
      if (dateParts.length === 3) {
        // Rearrange to DD-MM-YYYY format
        formattedDueDate = `${dateParts[2]}-${dateParts[1]}-${dateParts[0]}`
      } else {
        formattedDueDate = taskDueDate.value // Keep original if parsing fails
      }
    }
    
    // Prepare assignee if selected
    let assigneeData = null
    if (assigneeId.value) {
      const selectedUser = userList.value.find(u => u.id === assigneeId.value)
      if (selectedUser) {
        assigneeData = {
          id: selectedUser.id,
          name: selectedUser.name
        }
      }
    }
    
    // Create a new task object
    const task = {
      name: taskName.value.trim(),
      title: taskName.value.trim(), // API expects title instead of name
      description: taskDescription.value.trim(),
      due_date: formattedDueDate, // Formatted date for API
      priority: taskPriority.value,
      status: taskStatus.value,
      assignee: assigneeData
    }
  
    // Show loading toast
    toast.showToast('Creating task...', 'info')
    
    // Call the API to create the task
    const createdTask = await taskStore.createTask(listId, task)
    
    console.log('Task created successfully:', createdTask)
    
    // Show success toast
    toast.showToast(`Task "${taskName.value}" created successfully`, 'success')
    
    // Dispatch custom event to notify listeners (like ListView) that a task was created
    window.dispatchEvent(new CustomEvent('taskCreated', { detail: createdTask }))
    
    // Hide the modal
    hide()
    
  } catch (error) {
    console.error('Error creating task:', error)
    errorMessage.value = error.message || 'Failed to create task'
    toast.showToast(errorMessage.value, 'danger')
  } finally {
    isLoading.value = false
  }
}

// Expose methods to the template
defineExpose({
  show,
  hide
})
</script>

<style scoped>
.modal-backdrop {
  background-color: rgba(0, 0, 0, 0.5);
}

.modal {
  display: block;
}
</style> 