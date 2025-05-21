<template>
  <div v-if="showModal" class="modal-backdrop fade show"></div>
  <div v-if="showModal" class="modal fade show d-block" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Task Details</h5>
          <button type="button" class="btn-close" aria-label="Close" @click="hide"></button>
        </div>
        <div class="modal-body">
          <div v-if="isLoading" class="d-flex justify-content-center my-4">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
          </div>
          
          <div v-else-if="error" class="alert alert-danger" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            {{ error }}
          </div>
          
          <form v-else @submit.prevent="saveChanges">
            <!-- Task Name -->
            <div class="mb-3">
              <label for="taskName" class="form-label">Task Name</label>
              <input 
                type="text" 
                class="form-control" 
                id="taskName" 
                v-model="taskData.name"
                placeholder="Task name"
              >
            </div>

            <!-- Description -->
            <div class="mb-3">
              <label for="taskDescription" class="form-label">Description</label>
              <textarea 
                class="form-control" 
                id="taskDescription" 
                v-model="taskData.description" 
                placeholder="Task description"
                rows="3"
              ></textarea>
            </div>
            
            <!-- Assignee -->
            <div class="mb-3">
              <label for="taskAssignee" class="form-label">Assignee</label>
              <select class="form-select" id="taskAssignee" v-model="taskData.assignee">
                <option :value="null">Unassigned</option>
                <option v-for="user in userList" :key="user.id" :value="user">
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
                v-model="taskData.dueDate"
              >
              <small v-if="taskData.formattedDate" class="text-muted">
                Formatted: {{ taskData.formattedDate }}
              </small>
            </div>

            <!-- Priority -->
            <div class="mb-3">
              <label for="taskPriority" class="form-label">Priority</label>
              <select class="form-select" id="taskPriority" v-model="taskData.priority">
                <option value="low">Low</option>
                <option value="normal">Normal</option>
                <option value="high">High</option>
              </select>
            </div>

            <!-- Status -->
            <div class="mb-3">
              <label for="taskStatus" class="form-label">Status</label>
              <select class="form-select" id="taskStatus" v-model="taskData.status">
                <option value="todo">To Do</option>
                <option value="in progress">In Progress</option>
                <option value="completed">Completed</option>
              </select>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button 
            type="button" 
            class="btn btn-danger me-auto" 
            @click="deleteTask"
            :disabled="isLoading || isSaving || isDeleting"
          >
            <span v-if="isDeleting" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
            {{ isDeleting ? 'Deleting...' : 'Delete Task' }}
          </button>
          <button 
            type="button" 
            class="btn btn-primary" 
            @click="saveChanges"
            :disabled="isSaving || isLoading || isDeleting"
          >
            <span v-if="isSaving" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
            {{ isSaving ? 'Saving...' : 'Save Changes' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useWorkspaceStore } from '../stores/workspaceStore';
import { useTaskStore } from '../stores/taskStore';
import { useToast } from '../composables/useToast';

const workspaceStore = useWorkspaceStore();
const taskStore = useTaskStore();
const toast = useToast();

// State
const showModal = ref(false);
const isLoading = ref(false);
const isSaving = ref(false);
const isDeleting = ref(false);
const error = ref(null);
const taskId = ref(null);
const originalTask = ref(null);
const taskData = ref({
  id: '',
  name: '',
  title: '',
  description: '',
  assignee: null,
  dueDate: '',
  priority: 'normal',
  status: 'todo',
  formattedDate: ''
});

// User list - hardcoded for now, typically this would come from an API
const userList = ref([
  { id: '1', name: 'John Doe' },
  { id: '2', name: 'Jane Smith' },
  { id: '3', name: 'Mike Johnson' }
]);

// Check if there are changes in the form
const hasChanges = computed(() => {
  if (!originalTask.value) return false;
  
  // Compare properties
  return (
    taskData.value.name !== originalTask.value.name ||
    taskData.value.description !== originalTask.value.description ||
    (taskData.value.assignee?.id !== originalTask.value.assignee?.id) ||
    taskData.value.dueDate !== originalTask.value.dueDate ||
    taskData.value.priority !== originalTask.value.priority ||
    taskData.value.status !== originalTask.value.status
  );
});

// Method to show the modal with a specific task ID
const show = async (id) => {
  taskId.value = id;
  showModal.value = true;
  
  try {
    await loadTaskDetails();
  } catch (err) {
    console.error('Error loading task details:', err);
    error.value = err.message || 'Failed to load task details';
    toast.showToast(error.value, 'danger');
  }
};

// Load task details from the API
const loadTaskDetails = async () => {
  if (!taskId.value) {
    error.value = 'No task ID provided';
    return;
  }
  
  isLoading.value = true;
  error.value = null;
  
  try {
    const task = await taskStore.fetchTaskById(taskId.value);
    
    // Store original task for comparison
    originalTask.value = { ...task };
    
    // Update form data
    taskData.value = { ...task };
    
  } catch (err) {
    console.error('Error loading task details:', err);
    error.value = err.message || 'Failed to load task details';
    toast.showToast(error.value, 'danger');
  } finally {
    isLoading.value = false;
  }
};

// Method to hide the modal
const hide = () => {
  // Check for unsaved changes
  if (hasChanges.value) {
    if (!confirm('You have unsaved changes. Are you sure you want to close?')) {
      return;
    }
  }
  
  showModal.value = false;
  taskId.value = null;
  originalTask.value = null;
  error.value = null;
  
  // Reset form
  taskData.value = {
    id: '',
    name: '',
    title: '',
    description: '',
    assignee: null,
    dueDate: '',
    priority: 'normal',
    status: 'todo'
  };
};

// Save changes to the task
const saveChanges = async () => {
  if (!taskId.value) {
    error.value = 'No task ID provided';
    return;
  }
  
  // Check if there are changes
  if (!hasChanges.value) {
    toast.showToast('No changes to save', 'info');
    hide();
    return;
  }
  
  isSaving.value = true;
  error.value = null;
  
  try {
    // Prepare update data (only include changed fields)
    const updateData = {};
    
    if (taskData.value.name !== originalTask.value.name) {
      updateData.name = taskData.value.name;
    }
    
    if (taskData.value.description !== originalTask.value.description) {
      updateData.description = taskData.value.description;
    }
    
    // Handle assignee changes
    if ((taskData.value.assignee?.id !== originalTask.value.assignee?.id)) {
      updateData.assignee = taskData.value.assignee;
    }
    
    if (taskData.value.dueDate !== originalTask.value.dueDate) {
      updateData.dueDate = taskData.value.dueDate;
    }
    
    if (taskData.value.priority !== originalTask.value.priority) {
      updateData.priority = taskData.value.priority;
    }
    
    if (taskData.value.status !== originalTask.value.status) {
      updateData.status = taskData.value.status;
    }
    
    // Call API to update the task
    const result = await taskStore.updateTask(taskId.value, updateData);
    
    toast.showToast('Task updated successfully', 'success');
    
    // Dispatch a custom event so the list view can refresh
    window.dispatchEvent(new CustomEvent('taskUpdated', { detail: result }));
    
    // Close the modal
    hide();
    
  } catch (err) {
    console.error('Error saving task changes:', err);
    error.value = err.message || 'Failed to save task changes';
    toast.showToast(error.value, 'danger');
  } finally {
    isSaving.value = false;
  }
};

// Helper function to format dates
const formatDate = (dateString) => {
  if (!dateString) return '';
  
  try {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('en-GB', { 
      day: '2-digit',
      month: '2-digit',
      year: '2-digit'
    }).format(date);
  } catch (e) {
    return dateString;
  }
};

// Clean up when component is unmounted
watch(showModal, (newValue) => {
  if (!newValue) {
    // Reset data when modal is closed
    taskId.value = null;
    originalTask.value = null;
    error.value = null;
  }
});

// Expose methods to the template
defineExpose({
  show,
  hide
});

// Confirm task deletion
const confirmDelete = () => {
  if (!taskId.value) {
    error.value = 'No task ID provided';
    return;
  }
  
  // Show confirmation dialog
  if (confirm(`Are you sure you want to delete this task: "${taskData.value.name}"?`)) {
    deleteTask();
  }
};

// Delete the task
const deleteTask = async () => {
  if (!taskId.value) {
    error.value = 'No task ID provided';
    return;
  }
  
  isDeleting.value = true;
  error.value = null;
  
  try {
    // Call API to delete the task
    await taskStore.deleteTask(taskId.value);
    
    toast.showToast('Task deleted successfully', 'success');
    
    // Dispatch a custom event so the list view can refresh
    window.dispatchEvent(new CustomEvent('taskDeleted', { detail: taskId.value }));
    
    // Close the modal
    hide();
    
  } catch (err) {
    console.error('Error deleting task:', err);
    error.value = err.message || 'Failed to delete task';
    toast.showToast(error.value, 'danger');
  } finally {
    isDeleting.value = false;
  }
};
</script>

<style scoped>
.modal-backdrop {
  background-color: rgba(0, 0, 0, 0.5);
}

.modal {
  display: block;
}

.form-label {
  font-weight: 500;
  color: #424242;
  font-size: 0.875rem;
}

.modal-content {
  border: none;
  border-radius: 8px;
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.modal-header {
  border-bottom: 1px solid rgba(0, 0, 0, 0.1);
  background-color: #f8f9fa;
  border-radius: 8px 8px 0 0;
}

.modal-footer {
  border-top: 1px solid rgba(0, 0, 0, 0.1);
  background-color: #f8f9fa;
  border-radius: 0 0 8px 8px;
}

.btn-primary {
  background-color: #0d6efd;
  border-color: #0d6efd;
}

.btn-primary:hover {
  background-color: #0b5ed7;
  border-color: #0a58ca;
}

.form-control, .form-select {
  border-color: #ced4da;
  font-size: 0.875rem;
}

.form-control:focus, .form-select:focus {
  border-color: #86b7fe;
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
</style> 