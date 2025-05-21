<template>
  <div class="list-view">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="app-text-lg mb-0">{{ list?.name }}</h2>
      
      <div class="d-flex gap-2">
        <button 
          class="btn btn-outline-secondary btn-sm" 
          @click="refreshTasks" 
          title="Refresh tasks"
          :disabled="isLoading"
        >
          <i class="bi" :class="isLoading ? 'bi-arrow-repeat spin' : 'bi-arrow-repeat'"></i> 
          Refresh
        </button>
        <button class="btn btn-primary" @click="showNewTaskModal">
          <i class="bi bi-plus"></i> Add Task
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading && !list" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="mt-2">Loading list data...</p>
    </div>

    <!-- Error State -->
    <!-- <div v-else-if="error" class="alert alert-danger" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-2"></i>
      {{ error }}
      <button class="btn btn-sm btn-outline-danger ms-2" @click="refreshTasks">Retry</button>
    </div> -->

    <div v-else class="tasks-container">
      <!-- Header Row -->
      <div class="task-row header app-text">
        <div class="task-name">Task Name</div>
        <div class="task-assignee">Assignee</div>
        <div class="task-due-date">Due Date</div>
        <div class="task-priority">Priority</div>
        <div class="task-status">Status</div>
        <div class="task-comments">Comments</div>
        <div class="task-actions">Actions</div>
      </div>

      <!-- Task Rows -->
      <div v-if="list?.tasks?.length" class="task-list">
        <div 
          v-for="task in list.tasks" 
          :key="task.id" 
          class="task-row app-text"
        >
          <div class="task-name">{{ task.name || task.title }}</div>
          <div class="task-assignee">
            <i class="bi bi-person me-1"></i>
            <span v-if="task.assignee">{{ task.assignee.name || task.assignee }}</span>
            <span v-else>Unassigned</span>
          </div>
          <div class="task-due-date">
            <i class="bi bi-calendar me-1"></i>
            <span v-if="task.due_date || task.dueDate">{{ formatDueDate(task.due_date || task.dueDate) }}</span>
            <span v-else>No date</span>
          </div>
          <div class="task-priority">
            <span>
              <i class="bi bi-flag me-1"></i>
              <span v-if="task.priority" :class="getPriorityClass(task.priority)">
                {{ task.priority.charAt(0).toUpperCase() + task.priority.slice(1) }}
              </span>
              <span v-else>Normal</span>
            </span>
          </div>
          <div class="task-status">
            <div class="dropdown">
              <button 
                class="btn btn-sm status-btn dropdown-toggle" 
                :class="getStatusClass(task.status)"
                type="button" 
                data-bs-toggle="dropdown" 
                aria-expanded="false"
                @click.stop
              >
                {{ task.status === 'todo' ? 'To Do' : 
                   task.status === 'in progress' || task.status === 'inprogress' ? 'In Progress' : 
                   'Completed' }}
              </button>
              <ul class="dropdown-menu">
                <li>
                  <a class="dropdown-item" 
                     @click="updateTaskStatus(task, 'todo')"
                     :class="{ active: task.status === 'todo' }">
                    To Do
                  </a>
                </li>
                <li>
                  <a class="dropdown-item" 
                     @click="updateTaskStatus(task, 'in progress')"
                     :class="{ active: task.status === 'in progress' || task.status === 'inprogress' }">
                    In Progress
                  </a>
                </li>
                <li>
                  <a class="dropdown-item" 
                     @click="updateTaskStatus(task, 'completed')"
                     :class="{ active: task.status === 'completed' }">
                    Completed
                  </a>
                </li>
              </ul>
            </div>
          </div>
          <!-- <div class="task-comments">
            <i class="bi bi-chat me-1"></i>
            {{ task.comments?.length || 0 }}
          </div> -->
          <div>
    <!-- 🟡 COMMENT ICON (clickable) -->
    <div class="task-comments" @click="showCommentsModal = true" style="cursor: pointer;">
      <i class="bi bi-chat me-1"></i>
      {{ task.comments?.length || 0 }}
    </div>

    <!-- ✅ MODAL -->
    <div v-if="showCommentsModal" class="custom-modal-overlay" @click.self="closeModal">
      <div class="custom-modal">
        <div class="modal-header d-flex justify-content-between align-items-center">
          <h5 class="modal-title">Comments</h5>
          <button class="btn-close" @click="closeModal"></button>
        </div>
        <div class="modal-body">
          <Comments :taskId="task.id" />
        </div>
      </div>
    </div>
  </div>
          <div class="task-actions">
            <button 
              class="btn btn-sm btn-icon" 
              @click="openTaskDetails(task)" 
              title="View task details"
            >
              <i class="bi bi-three-dots"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- Task Loading Overlay -->
      <div v-if="isLoading && list" class="tasks-loading-overlay">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else-if="!list?.tasks?.length" class="empty-state app-text text-center py-5">
        <i class="bi bi-list-task fs-1 mb-3 d-block"></i>
        <p class="mb-0">No tasks yet. Click the "Add Task" button to create one.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, inject, onUnmounted } from 'vue'
import { useWorkspaceStore } from '../stores/workspaceStore'
import { useTeamspaceStore } from '../stores/teamspaceStore'
import { useProjectStore } from '../stores/projectStore'
import { useListStore } from '../stores/listStore'
import { useTaskStore } from '../stores/taskStore'
import { useNavigationStore } from '../stores/navigationStore'
import { useRoute } from 'vue-router'
import * as bootstrap from 'bootstrap'
import { useToast } from '../composables/useToast'


import Comments from './Comments.vue'

const props = defineProps({ task: Object })
const showComments = ref(false)

const workspaceStore = useWorkspaceStore()
const teamspaceStore = useTeamspaceStore()
const projectStore = useProjectStore()
const listStore = useListStore()
const taskStore = useTaskStore()
const navigationStore = useNavigationStore()
const route = useRoute()
const toast = useToast()
const modals = inject('modals')

const isLoading = ref(false)
const error = ref(null)
const list = ref(null)
const teamspaceId = ref(null)
const projectId = ref(null)
const listId = ref(null)


const showCommentsModal = ref(false)

const closeModal = () => {
  showCommentsModal.value = false
}

// Open task details modal when clicking on a task
const openTaskDetails = (task) => {
  if (!task || !task.id) {
    toast.showToast('Cannot open task: Invalid task data', 'danger')
    return
  }
  
  modals.showTaskDetails(task.id)
}

// Format due date to DD/MM/YY
const formatDueDate = (dateString) => {
  if (!dateString) return ''
  
  // If it's already in DD-MM-YYYY format (from API)
  if (dateString.match(/^\d{2}-\d{2}-\d{4}$/)) {
    const parts = dateString.split('-')
    return `${parts[0]}/${parts[1]}/${parts[2].substring(2)}`
  }
  
  // If it's in YYYY-MM-DD format (from date input)
  if (dateString.match(/^\d{4}-\d{2}-\d{2}$/)) {
    const parts = dateString.split('-')
    return `${parts[2]}/${parts[1]}/${parts[0].substring(2)}`
  }
  
  // If it's a date string (like "2023-04-15T00:00:00.000Z")
  if (dateString.includes('T')) {
    try {
      const date = new Date(dateString)
      const day = date.getDate().toString().padStart(2, '0')
      const month = (date.getMonth() + 1).toString().padStart(2, '0')
      const year = date.getFullYear().toString().substring(2)
      return `${day}/${month}/${year}`
    } catch (e) {
      console.error('Error parsing date:', e)
    }
  }
  
  // If it's neither, return as is
  return dateString
}

// Find the list based on route params (for initial rendering)
const findList = async () => {
  try {
    isLoading.value = true
    error.value = null
    
    // Check if we're using the ID-based route
    if (route.name === 'list-view' && route.params.teamId && route.params.projectId && route.params.listId) {
      // Use the direct IDs from the route
      teamspaceId.value = route.params.teamId
      projectId.value = route.params.projectId 
      listId.value = route.params.listId
      
      // Fetch list details directly using the ID
      try {
        const fetchedList = await listStore.fetchListDetails(listId.value)
        list.value = fetchedList
        
        // Try to find corresponding teamspace and project
        const teamspace = teamspaceStore.getTeamspaceLocal(teamspaceId.value)
        if (teamspace) {
          navigationStore.setTeamspace(teamspace)
          
          const project = teamspace.projects?.find(p => 
            p.id === projectId.value || 
            p._id === projectId.value || 
            p.project_id === projectId.value
          )
          
          if (project) {
            navigationStore.setProject(project)
          }
        }
        
        // Set the list in navigation store
        navigationStore.setList(fetchedList)
        
        // Fetch tasks for the list
        await fetchListTasks()
        return
      } catch (error) {
        console.error('Error fetching list details by ID:', error)
        toast.showToast('Error fetching list details', 'danger')
      }
    }
    
    // Fall back to name-based lookup if ID-based lookup fails or we're using the name-based route
    // Get the current teamspace, project and list names from the route
    const teamspaceName = decodeURIComponent(route.params.teamspaceName)
    const projectName = decodeURIComponent(route.params.projectName)
    const listName = decodeURIComponent(route.params.listName)
    
    // Find the teamspace by name
    const teamspace = teamspaceStore.teamspaces.find(t => t.name === teamspaceName)
    if (!teamspace) {
      throw new Error(`Teamspace "${teamspaceName}" not found`)
    }
    
    // Set the active teamspace in the navigation store
    navigationStore.setTeamspace(teamspace)
    teamspaceId.value = teamspace.id || teamspace._id || teamspace.team_id
    
    // Find the project by name
    const project = teamspace.projects.find(p => p.name === projectName)
    if (!project) {
      throw new Error(`Project "${projectName}" not found in teamspace "${teamspaceName}"`)
    }
    
    // Set the active project in the navigation store
    navigationStore.setProject(project)
    projectId.value = project.id || project._id || project.project_id
    
    // Find the list by name
    const foundList = project.lists?.find(l => l.name === listName)
    if (!foundList) {
      throw new Error(`List "${listName}" not found in project "${projectName}"`)
    }
    
    // Set the list and fetch tasks
    list.value = foundList
    navigationStore.setList(foundList)
    listId.value = foundList.id || foundList._id || foundList.list_id
    
    // Fetch the list details and tasks from the API
    await fetchListTasks()
  } catch (err) {
    console.error('Error finding list:', err)
    error.value = err.message || 'Error loading list'
    toast.showToast(error.value, 'danger')
  } finally {
    isLoading.value = false
  }
}

// Fetch tasks for the current list
const fetchListTasks = async () => {
  if (!listId.value) {
    console.error('Cannot fetch tasks: No list ID available')
    return
  }
  
  try {
    isLoading.value = true
    error.value = null
    
    // Show loading toast for better UX
    toast.showToast(`Loading tasks for "${list.value?.name}"...`, 'info')
    
    // Fetch tasks from the API
    const tasks = await taskStore.fetchTasksForList(listId.value)
    
    // The fetchTasksForList method already updates the tasks in the store,
    // but we'll refresh our local reference just to be sure
    if (list.value) {
      // Get the updated list from the store
      const updatedList = listStore.getListById(listId.value)
      if (updatedList) {
        list.value = updatedList
      }
    }
    
    // Show appropriate toast based on number of tasks
    if (tasks.length > 0) {
      toast.showToast(`Loaded ${tasks.length} tasks for list: ${list.value?.name}`, 'success')
    } else {
      toast.showToast(`No tasks found in list: ${list.value?.name}`, 'info') 
    }
  } catch (err) {
    console.error('Error fetching tasks:', err)
    error.value = err.message || 'Error loading tasks'
    toast.showToast(error.value, 'danger')
  } finally {
    isLoading.value = false
  }
}

// Refresh tasks manually
const refreshTasks = async () => {
  await fetchListTasks()
}

// Watch for route changes to update the list
watch(
  () => route.params,
  () => {
    findList()
  },
  { deep: true }
)

onMounted(() => {
  findList()
  
  // Add event listener for task updates
  window.addEventListener('taskUpdated', () => refreshTasks())
  
  // Add event listener for task deletion
  window.addEventListener('taskDeleted', () => refreshTasks())
  
  // Clean up on unmount
  onUnmounted(() => {
    window.removeEventListener('taskUpdated', () => refreshTasks())
    window.removeEventListener('taskDeleted', () => refreshTasks())
  })
})

const showNewTaskModal = () => {
  // Ensure we have a valid list
  if (!list.value || !list.value.id) {
    toast.showToast('Cannot create a task: Invalid list', 'danger');
    return;
  }

  // Set the active list in the window object using the current list
  window.activeList = list.value;
  
  // Also set teamspace and project if available
  if (teamspaceId.value) {
    window.activeTeamspace = teamspaceStore.getTeamspaceLocal(teamspaceId.value);
  }
  
  if (projectId.value) {
    window.activeProject = projectStore.getProjectById(projectId.value);
  }
  
  // Show the modal
  modals.showNewTask();
  
  // Add event listener to refresh tasks after modal is closed
  const refreshAfterClose = () => {
    // Check if the task count changed by refetching tasks
    fetchListTasks();
    
    // Remove the event listener
    window.removeEventListener('taskCreated', refreshAfterClose);
  };
  
  // Add listener for custom event that will be dispatched after task creation
  window.addEventListener('taskCreated', refreshAfterClose);
};

const updateTaskStatus = (task, newStatus) => {
  if (task.status === newStatus) return;
  
  if (!teamspaceId.value || !projectId.value || !listId.value) {
    console.error('Missing context information');
    return;
  }

  taskStore.updateTaskStatus(teamspaceId.value, projectId.value, listId.value, task.id, newStatus);
};

const getStatusClass = (status) => {
  switch (status) {
    case 'completed':
      return 'bg-success';
    case 'in progress':
    case 'inprogress':
      return 'bg-primary';
    default:
      return 'bg-secondary';
  }
};

// Helper function to get priority class
const getPriorityClass = (priority) => {
  switch (priority.toLowerCase()) {
    case 'high':
      return 'text-danger';
    case 'low':
      return 'text-success';
    default:
      return 'text-primary';
  }
};
</script>

<style scoped>
.custom-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.4);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
}
.custom-modal {
  background: white;
  border-radius: 10px;
  width: 90%;
  max-width: 600px;
  max-height: 80vh;
  overflow-y: auto;
  padding: 1rem;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
}
.list-view {
  height: calc(100vh - 96px); /* Subtract navbar + breadcrumb height */
  display: flex;
  flex-direction: column;
  overflow: hidden; /* Prevent main content scrolling */
}

.tasks-container {
  flex-grow: 1;
  overflow-y: auto;
  border-radius: 8px;
  margin-top: 1rem;
  background: white;
  min-height: 0;
  scrollbar-width: none;  /* Firefox */
  -ms-overflow-style: none;  /* IE and Edge */
  position: relative; /* For loading overlay */
}

.tasks-container::-webkit-scrollbar {
  display: none;  /* Chrome, Safari, Opera */
}

.task-list {
  padding-bottom: 1rem; /* Add padding at the bottom */
}

.task-row {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr 1fr 0.5fr 0.5fr;
  gap: 1rem;
  padding: 0.75rem 1rem;
  align-items: center;
}

.task-row:not(.header) {
  border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.task-row:last-child {
  border-bottom: none;
}

.task-row:not(.header):hover {
  background-color: #f8f9fa;
}

.task-row.header {
  font-size: 0.75rem;
  color: #666;
  font-weight: 500;
  background: white;
  border-bottom: 1px solid #dee2e6;
  position: sticky;
  top: 0;
  z-index: 1;
}

.task-name,
.task-assignee,
.task-due-date,
.task-priority,
.task-status,
.task-comments,
.task-actions {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.empty-state {
  color: #6c757d;
}

.status-btn {
  font-size: 0.75rem;
  padding: 0.25rem 0.5rem;
  color: white;
}

.tasks-loading-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.8);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 10;
}

.spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.btn-icon {
  background: transparent;
  border: none;
  color: #6c757d;
  padding: 0.25rem;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.btn-icon:hover {
  background-color: rgba(0, 0, 0, 0.05);
  color: #343a40;
}

.btn-icon i {
  font-size: 1.2rem;
}
</style> 