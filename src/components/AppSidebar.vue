<script setup>
import { useWorkspaceStore } from '../stores/workspaceStore';
import { useRouter } from 'vue-router';
import { inject, ref, computed } from 'vue';
import { useNavigationStore } from '../stores/navigationStore';

const workspaceStore = useWorkspaceStore();
const navigationStore = useNavigationStore();
const router = useRouter();
const modals = inject('modals');

// Get current workspace
const currentWorkspace = computed(() => workspaceStore.currentWorkspace);
const teamspaces = computed(() => workspaceStore.teamspaces);

// Method to handle list click
const handleListClick = async (teamId, projectId, listId) => {
  try {
    // Fetch the list details from the API
    const listDetails = await workspaceStore.fetchListDetails(listId);
    // Navigate to the list view with all necessary parameters
    router.push({
      name: 'list-view', // Adjust this to match your route name
      params: {
        teamId: teamId,
        projectId: projectId, 
        listId: listId
      }
    });
  } catch (error) {
    console.error('Error fetching list details:', error);
    alert('Error loading list details. Please try again.');
  }
};

// Method to handle add task icon click
const handleAddTaskClick = async (e, teamId, projectId, listId) => {
  // Stop event propagation to prevent navigating to the list
  e.stopPropagation();
  
  try {
    // Fetch the list details first to make sure we have the latest data
    const listDetails = await workspaceStore.fetchListDetails(listId);
    // Set global references for the modal
    window.activeList = listDetails;
    window.activeTeamspace = workspaceStore.getTeamspaceLocal(teamId);
    window.activeProject = workspaceStore.getProjectLocal(teamId, projectId);
    
    // Show the task creation modal
    modals.showNewTask();
  } catch (error) {
    console.error('Error preparing for task creation:', error);
    alert('Error preparing task creation. Please try again.');
  }
};

// Track expanded teamspaces/projects
const expandedTeamspaces = ref({});
const expandedProjects = ref({});

// Toggle teamspace expand/collapse
const toggleTeamspace = (teamspaceId) => {
  expandedTeamspaces.value[teamspaceId] = !expandedTeamspaces.value[teamspaceId];
};

// Toggle project expand/collapse
const toggleProject = (projectId) => {
  expandedProjects.value[projectId] = !expandedProjects.value[projectId];
};

// Check if teamspace is expanded
const isTeamspaceExpanded = (teamspaceId) => {
  return !!expandedTeamspaces.value[teamspaceId];
};

// Check if project is expanded
const isProjectExpanded = (projectId) => {
  return !!expandedProjects.value[projectId];
};
</script>

<template>
  <div class="sidebar">
    <div class="sidebar-section">
      <h6 class="sidebar-heading">WORKSPACE</h6>
      <div class="workspace-name" v-if="currentWorkspace">
        {{ currentWorkspace.name }}
      </div>
    </div>
    
    <div class="sidebar-section">
      <div class="sidebar-item" @click="router.push({ name: 'inbox' })">
        <i class="bi bi-inbox"></i>
        <span>Inbox</span>
      </div>
      
      <div class="sidebar-item" @click="router.push({ name: 'everything' })">
        <i class="bi bi-collection"></i>
        <span>Everything</span>
      </div>
    </div>
    
    <div class="sidebar-section">
      <h6 class="sidebar-heading">TEAMSPACES</h6>
      
      <div v-for="teamspace in teamspaces" :key="teamspace.id" class="teamspace-section">
        <!-- Teamspace Header -->
        <div class="teamspace-header" @click="toggleTeamspace(teamspace.id)">
          <div class="d-flex align-items-center">
            <i class="bi" :class="isTeamspaceExpanded(teamspace.id) ? 'bi-caret-down-fill' : 'bi-caret-right-fill'"></i>
            <i class="bi bi-people sidebar-icon me-2"></i>
            <span class="teamspace-name">{{ teamspace.name }}</span>
          </div>
        </div>
        
        <!-- Teamspace Content (Projects) -->
        <div v-if="isTeamspaceExpanded(teamspace.id)" class="teamspace-content">
          <div v-for="project in teamspace.projects" :key="project.id" class="project-section">
            <!-- Project Header -->
            <div class="project-header" @click="toggleProject(project.id)">
              <div class="d-flex align-items-center">
                <i class="bi" :class="isProjectExpanded(project.id) ? 'bi-caret-down-fill' : 'bi-caret-right-fill'"></i>
                <i class="bi bi-kanban sidebar-icon me-2"></i>
                <span class="project-name">{{ project.name }}</span>
              </div>
            </div>
            
            <!-- Project Content (Lists) -->
            <div v-if="isProjectExpanded(project.id)" class="project-content">
              <!-- Lists -->
              <div 
                v-for="list in project.lists" 
                :key="list.id" 
                class="list-item d-flex justify-content-between align-items-center" 
                @click="handleListClick(teamspace.id, project.id, list.id)"
              >
                <span class="text-truncate">{{ list.name }}</span>
                <i 
                  class="bi bi-plus-circle add-task-icon" 
                  @click="handleAddTaskClick($event, teamspace.id, project.id, list.id)"
                  title="Add task to this list"
                ></i>
              </div>
              
              <!-- Empty state for lists -->
              <div v-if="!project.lists || project.lists.length === 0" class="empty-lists">
                No lists in this project
              </div>
            </div>
          </div>
          
          <!-- Empty state for projects -->
          <div v-if="!teamspace.projects || teamspace.projects.length === 0" class="empty-projects">
            No projects in this teamspace
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.sidebar {
  height: 100%;
  background-color: #f8f9fa;
  padding: 1rem;
  overflow-y: auto;
}

.sidebar-section {
  margin-bottom: 1.5rem;
}

.sidebar-heading {
  font-size: 0.7rem;
  font-weight: 600;
  color: #6c757d;
  margin-bottom: 0.5rem;
  letter-spacing: 0.05rem;
}

.sidebar-item {
  display: flex;
  align-items: center;
  padding: 0.5rem 0.75rem;
  border-radius: 4px;
  transition: background-color 0.2s;
  cursor: pointer;
  margin-bottom: 0.25rem;
}

.sidebar-item:hover {
  background-color: rgba(0, 0, 0, 0.05);
}

.sidebar-item i {
  margin-right: 0.75rem;
  font-size: 1rem;
}

.workspace-name {
  font-weight: 500;
  margin-bottom: 0.5rem;
}

.teamspace-header, .project-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0.25rem;
  cursor: pointer;
  border-radius: 4px;
  transition: background-color 0.2s;
}

.teamspace-header:hover, .project-header:hover {
  background-color: rgba(0, 0, 0, 0.05);
}

.teamspace-name, .project-name {
  margin-left: 0.5rem;
  font-weight: 500;
}

.teamspace-content {
  padding-left: 1rem;
}

.project-content {
  padding-left: 1.5rem;
}

.list-item {
  padding: 6px 12px;
  cursor: pointer;
  border-radius: 4px;
  transition: background-color 0.2s;
  font-size: 0.9rem;
}

.list-item:hover {
  background-color: rgba(0, 0, 0, 0.05);
}

.add-task-icon {
  opacity: 0.6;
  font-size: 0.9rem;
  transition: opacity 0.2s, transform 0.2s;
}

.add-task-icon:hover {
  opacity: 1;
  transform: scale(1.2);
}

.empty-lists, .empty-projects {
  font-size: 0.8rem;
  color: #6c757d;
  padding: 0.5rem;
  font-style: italic;
}

.sidebar-icon {
  font-size: 14px;
  color: #6c757d;
}
</style> 