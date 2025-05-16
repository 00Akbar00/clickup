<template>
  <div class="list-view">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="app-text-lg mb-0">{{ list?.name }}</h2>
      <button class="btn btn-primary" @click="showNewTaskModal">
        <i class="bi bi-plus"></i> Add Task
      </button>
    </div>

    <div class="tasks-container">
      <!-- Header Row -->
      <div class="task-row header app-text">
        <div class="task-name">Task Name</div>
        <div class="task-assignee">Assignee</div>
        <div class="task-due-date">Due Date</div>
        <div class="task-priority">Priority</div>
        <div class="task-status">Status</div>
        <div class="task-comments">Comments</div>
      </div>

      <!-- Task Rows -->
      <div v-if="list?.tasks?.length" class="task-list">
        <div v-for="task in list.tasks" :key="task.id" class="task-row app-text">
          <div class="task-name">{{ task.name }}</div>
          <div class="task-assignee">
            <i class="bi bi-person me-1"></i>
            {{ task.assignee || 'Unassigned' }}
          </div>
          <div class="task-due-date">
            <i class="bi bi-calendar me-1"></i>
            {{ task.dueDate || 'No date' }}
          </div>
          <div class="task-priority">
            <span>
              <i class="bi bi-flag me-1"></i>
              {{ task.priority || 'None' }}
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
              >
                {{ task.status === 'todo' ? 'To Do' : 
                   task.status === 'in progress' ? 'In Progress' : 
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
                     :class="{ active: task.status === 'in progress' }">
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
          <div class="task-comments">
            <i class="bi bi-chat me-1"></i>
            {{ task.comments?.length || 0 }}
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="empty-state app-text text-center py-5">
        <i class="bi bi-list-task fs-1 mb-3 d-block"></i>
        <p class="mb-0">No tasks yet. Click the "Add Task" button to create one.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, watch } from 'vue'
import { useWorkspaceStore } from '../stores/workspaceStore'
import { useNavigationStore } from '../stores/navigationStore'
import { useRoute } from 'vue-router'
import * as bootstrap from 'bootstrap'

const workspaceStore = useWorkspaceStore()
const navigationStore = useNavigationStore()
const route = useRoute()

// Find the list based on route params
const list = computed(() => {
  const teamspace = workspaceStore.teamspaces.find(
    t => t.name === decodeURIComponent(route.params.teamspaceName)
  );
  if (!teamspace) return null;

  const project = teamspace.projects.find(
    p => p.name === decodeURIComponent(route.params.projectName)
  );
  if (!project) return null;

  const foundList = project.lists.find(
    l => l.name === decodeURIComponent(route.params.listName)
  );
  
  if (foundList) {
    navigationStore.setTeamspace(teamspace);
    navigationStore.setProject(project);
    navigationStore.setList(foundList);
  }
  
  return foundList;
});

// Watch for route changes to ensure list updates
watch(
  () => route.params.listName,
  (newName, oldName) => {
    if (newName !== oldName) {
      // Force recompute the list when route changes
      list.value;
    }
  }
);

const showNewTaskModal = () => {
  // Set the active list in the window object using the current list
  window.activeList = list.value;
  window.activeProject = navigationStore.activeProject;
  window.activeTeamspace = navigationStore.activeTeamspace;
  
  const modal = document.querySelector('#newTaskModal');
  if (modal) {
    const modalInstance = bootstrap.Modal.getOrCreateInstance(modal);
    modalInstance.show();
  }
};

const updateTaskStatus = (task, newStatus) => {
  if (task.status === newStatus) return;
  
  const teamspaceId = window.activeTeamspace?.id;
  const projectId = window.activeProject?.id;
  const listId = list.value?.id;
  
  if (!teamspaceId || !projectId || !listId) {
    console.error('Missing context information');
    return;
  }

  workspaceStore.updateTaskStatus(teamspaceId, projectId, listId, task.id, newStatus);
};

const getStatusClass = (status) => {
  switch (status) {
    case 'completed':
      return 'bg-success';
    case 'in progress':
      return 'bg-primary';
    default:
      return 'bg-secondary';
  }
};
</script>

<style scoped>
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
}

.tasks-container::-webkit-scrollbar {
  display: none;  /* Chrome, Safari, Opera */
}

.task-list {
  padding-bottom: 1rem; /* Add padding at the bottom */
}

.task-row {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1fr;
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
  cursor: pointer;
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
.task-comments {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.empty-state {
  color: #666;
}

.empty-state i {
  color: #ccc;
}

.status-btn {
  color: white;
  border: none;
  padding: 0.25rem 0.75rem;
  font-size: 0.75rem;
}

.status-btn:hover,
.status-btn:focus {
  color: white;
  opacity: 0.9;
}

.bg-success {
  background-color: #28a745;
}

.bg-primary {
  background-color: var(--app-primary-color);
}

.bg-secondary {
  background-color: #6c757d;
}

.dropdown-menu {
  min-width: 120px;
  padding: 0.25rem;
  border-radius: 4px;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
}

.dropdown-item {
  padding: 0.375rem 0.75rem;
  font-size: 0.875rem;
  border-radius: 3px;
  cursor: pointer;
}

.dropdown-item:hover {
  background-color: #f8f9fa;
}

.dropdown-item.active {
  background-color: var(--app-active-color);
  color: inherit;
}
</style> 