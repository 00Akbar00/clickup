<template>
  <div
    class="sidebar bg-light border-end"
    :class="{ collapsed: isCollapsed }"
    :style="sidebarStyle"
  >
    <!-- User Profile Section -->
    <div
      class="p-2 border-bottom d-flex justify-content-between align-items-center"
    >
      <div class="dropdown flex-grow-1">
        <button 
          class="btn btn-link text-dark p-0 d-flex align-items-center w-100" 
          type="button" 
          id="sidebarUserDropdown"
          aria-expanded="false"
          style="text-decoration: none;"
          ref="sidebarDropdownButton"
          @click="toggleUserDropdown"
        >
          <div class="d-flex align-items-center overflow-hidden">
            <img
              v-if="currentWorkspace?.logo_url || currentWorkspace?.logo"
              :src="getWorkspaceLogo(currentWorkspace)"
              alt="Workspace"
              class="rounded-circle"
              style="width: 32px; height: 32px; object-fit: cover"
            />
            <div
              v-else
              class="rounded-circle bg-secondary d-flex align-items-center justify-content-center"
              style="width: 32px; height: 32px"
            >
              <i class="bi bi-building text-white"></i>
            </div>
            <h6 class="mb-0 ms-2 app-text sidebar-text text-truncate">
              {{ currentWorkspaceName }}
            </h6>
          </div>
        </button>
        <ul class="dropdown-menu w-100" aria-labelledby="sidebarUserDropdown">
          <!-- Workspaces Section -->
          <li class="dropdown-header py-1 d-flex align-items-center justify-content-between">
            <small class="text-muted text-uppercase">Workspaces</small>
            <button 
              class="btn btn-sm btn-link p-0 text-muted" 
              @click="refreshWorkspaces" 
              title="Refresh workspaces"
            >
              <i class="bi" :class="{'bi-arrow-clockwise': !isWorkspacesLoading, 'bi-arrow-clockwise spin': isWorkspacesLoading}"></i>
            </button>
          </li>
          
          <!-- Edit and Manage Members Options -->
          <li v-if="currentWorkspace">
            <a class="dropdown-item d-flex align-items-center" href="#" @click.prevent="editCurrentWorkspace">
              <i class="bi bi-pencil-square me-2"></i>
              Edit Workspace
            </a>
          </li>
          <li v-if="currentWorkspace">
            <a class="dropdown-item d-flex align-items-center" href="#" @click.prevent="showMembersDrawer">
              <i class="bi bi-people me-2"></i>
              Manage Members
            </a>
          </li>
          <li v-if="currentWorkspace"><hr class="dropdown-divider my-1"></li>
          
          <!-- Loading indicator -->
          <!-- <li v-if="isWorkspacesLoading && workspaces.length === 0" class="dropdown-item text-center">
            <div class="d-flex align-items-center justify-content-center py-2">
              <div class="spinner-border spinner-border-sm text-primary me-2"></div>
              <span>Loading workspaces...</span>
            </div>
          </li> -->
          
          <!-- Error message -->
          <li v-else-if="workspaceError && workspaces.length === 0" class="dropdown-item">
            <div class="alert alert-danger py-2 mb-0">
              <small>{{ workspaceError }}</small>
              <button class="btn btn-sm btn-outline-danger mt-1 w-100" @click="refreshWorkspaces">
                Try Again
              </button>
            </div>
          </li>
          
          <!-- Workspaces list -->
          <li v-for="workspace in workspaces" :key="workspace.id || workspace._id || workspace.workspace_id || index">
            <a class="dropdown-item d-flex align-items-center justify-content-between" href="#" @click="switchWorkspace(workspace)">
              <div class="d-flex align-items-center">
                <div class="me-2">
                  <img
                    v-if="workspace.logo_url || workspace.logo"
                    :src="getWorkspaceLogo(workspace)"
                    alt="Workspace"
                    class="rounded-circle"
                    style="width: 24px; height: 24px; object-fit: cover"
                  />
                  <div
                    v-else
                    class="rounded-circle bg-secondary d-flex align-items-center justify-content-center"
                    style="width: 24px; height: 24px"
                  >
                    <i class="bi bi-building text-white" style="font-size: 0.875rem"></i>
                  </div>
                </div>
                <span>{{ workspace.name }}</span>
              </div>
              <span v-if="isActiveWorkspace(workspace)" class="active-workspace-indicator">
                <i class="bi bi-check2"></i>
              </span>
            </a>
          </li>
          <li>
            <a class="dropdown-item d-flex align-items-center new-workspace-item" href="#" @click="showNewWorkspaceModal">
              <i class="bi bi-plus-circle me-2"></i>
              Create New Workspace
            </a>
          </li>
          <li v-if="currentWorkspace">
            <a class="dropdown-item d-flex align-items-center text-danger" href="#" @click="leaveWorkspace">
              <i class="bi bi-box-arrow-left me-2"></i>
              Leave Workspace
            </a>
          </li>
        </ul>
      </div>
      <button
        class="btn btn-link text-dark p-0 collapse-btn flex-shrink-0"
        @click="toggleSidebar"
      >
        <i
          class="bi"
          :class="isCollapsed ? 'bi-chevron-right' : 'bi-chevron-left'"
        ></i>
      </button>
    </div>

    <!-- Navigation Section -->
    <div class="p-2 border-bottom">
      <div class="nav-item mb-1">
        <router-link
          to="/"
          class="nav-link d-flex align-items-center text-dark text-decoration-none py-1 px-2 rounded app-text"
          :class="{
            'active-item': selectedItem.type === 'home',
            'justify-content-center': isCollapsed,
          }"
          @click="handleHomeClick"
        >
          <i class="bi bi-house-door" :class="isCollapsed ? '' : 'me-2'"></i>
          <span class="sidebar-text">Home</span>
        </router-link>
      </div>
      <div class="nav-item">
        <router-link
          to="/inbox"
          class="nav-link d-flex align-items-center text-dark text-decoration-none py-1 px-2 rounded app-text"
          :class="{
            'active-item': selectedItem.type === 'inbox',
            'justify-content-center': isCollapsed,
          }"
          @click="handleInboxClick"
        >
          <i class="bi bi-inbox" :class="isCollapsed ? '' : 'me-2'"></i>
          <span class="sidebar-text">Inbox</span>
        </router-link>
      </div>
    </div>

    <!-- Workspace Navigation -->
    <div class="workspace-section p-2">
      <!-- Everything Overview -->
      <div class="nav-item mb-3">
        <router-link
          to="/everything"
          class="nav-link d-flex align-items-center text-dark text-decoration-none py-1 px-2 rounded app-text"
          :class="{
            'active-item': selectedItem.type === 'everything',
            'justify-content-center': isCollapsed,
          }"
          @click="handleEverythingClick"
        >
          <i class="bi bi-grid-3x3-gap" :class="isCollapsed ? '' : 'me-2'"></i>
          <span class="sidebar-text">Everything</span>
        </router-link>
      </div>

      <!-- Teams Header with Refresh Button -->
      <div class="teams-header d-flex justify-content-between align-items-center mb-2 px-2">
        <h6 class="text-muted mb-0" style="font-size: 0.8rem;">TEAMS</h6>
        <button 
          class="btn btn-sm btn-link p-0 text-muted" 
          @click="refreshTeams" 
          title="Refresh teams"
          v-if="!isCollapsed"
        >
          <i class="bi" :class="{'bi-arrow-clockwise': !teamspaceStore.loading, 'bi-arrow-clockwise spin': teamspaceStore.loading}"></i>
        </button>
      </div>

      <!-- Main Accordion for Teamspaces -->
      <div class="accordion" id="teamspacesAccordion">
        <!-- Teamspaces -->
        <div
          v-for="teamspace in teamspaces"
          :key="teamspace.id"
          class="accordion-item border-0 mb-2"
        >
          <h2 class="accordion-header">
            <button
              class="accordion-button collapsed py-2 app-text"
              :class="{
                'active-item':
                  selectedItem.type === 'teamspace' &&
                  selectedItem.id === teamspace.id,
                'justify-content-center': isCollapsed,
              }"
              type="button"
              data-bs-toggle="none"
              @click="handleTeamspaceClickNoToggle(teamspace)"
            >
              <div
                class="d-flex justify-content-between align-items-center w-100"
              >
                <div class="d-flex align-items-center">
                  <!-- Replace color indicator circle with fixed icon -->
                  <i class="bi bi-people me-2 sidebar-team-icon"></i>
                  <span>{{ teamspace.name }}</span>
                </div>
                <div class="d-flex align-items-center">
                  <!-- Loading spinner for teamspace -->
                  <div v-if="loadingTeamspaceId === getTeamspaceId(teamspace)" class="spinner-border spinner-border-sm sidebar-spinner me-2"></div>
                  
                  <!-- Buttons shown when not loading -->
                  <template v-else>
                    <!-- Team options menu (three dots) -->
                    <div class="dropdown d-inline-block me-2">
                      <button
                        class="btn btn-link text-dark p-0 sidebar-text"
                        @click.stop="toggleTeamOptions(getTeamspaceId(teamspace), $event)"
                        title="Team options"
                      >
                        <i class="bi bi-three-dots"></i>
                      </button>
                      <div :id="`teamOptionsMenu${getTeamspaceId(teamspace)}`" class="dropdown-menu dropdown-menu-end team-options-menu">
                        <button class="dropdown-item" @click.stop="changeTeamName(teamspace, $event)">
                          <i class="bi bi-pencil me-2"></i>
                          Rename Team
                        </button>
                        <button class="dropdown-item" @click.stop="toggleTeamPrivacy(teamspace, $event)">
                          <i class="bi" :class="[teamspace.is_private ? 'bi-lock-fill' : 'bi-unlock-fill', 'me-2']"></i>
                          {{ teamspace.is_private ? 'Make Public' : 'Make Private' }}
                        </button>
                        <div class="dropdown-divider"></div>
                        <button class="dropdown-item text-danger" @click.stop="deleteTeam(teamspace, $event)">
                          <i class="bi bi-trash me-2"></i>
                          Delete Team
                        </button>
                      </div>
                    </div>
                    
                    <!-- New project button -->
                    <button
                      class="btn btn-link text-dark p-0 sidebar-text"
                      @click.stop="showNewProjectModal(teamspace, $event)"
                      title="New project"
                    >
                      <i class="bi bi-plus"></i>
                    </button>
                    
                    <!-- Down arrow -->
                    <i 
                      class="bi bi-chevron-down ms-2 sidebar-dropdown-arrow" 
                      @click.stop="toggleTeamspaceDropdown(teamspace, $event)"
                    ></i>
                  </template>
                </div>
              </div>
            </button>
          </h2>
          <div
            :id="`teamspaceCollapse${getTeamspaceId(teamspace)}`"
            class="accordion-collapse collapse"
          >
            <div class="accordion-body p-0">
              <!-- Projects Accordion -->
              <div class="accordion" :id="`projectsAccordion${getTeamspaceId(teamspace)}`">
                <!-- Loading indicator for projects -->
                <div 
                  v-if="loadingTeamspaceId === getTeamspaceId(teamspace)" 
                  class="text-center py-2"
                >
                  <div class="spinner-border spinner-border-sm sidebar-spinner me-2"></div>
                  <span class="small">Loading projects...</span>
                </div>
                <div v-else-if="teamspace.projects.length === 0" class="text-center text-muted py-2">
                  <small>No projects found</small>
                </div>

                <!-- Projects -->
                <div
                  v-for="project in teamspace.projects"
                  :key="project.id"
                  class="accordion-item border-0 ms-3 mb-2"
                >
                  <h2 class="accordion-header">
                    <button
                      class="accordion-button collapsed py-2 app-text"
                      :class="{
                        'active-item':
                          selectedItem.type === 'project' &&
                          selectedItem.id === project.id,
                      }"
                      type="button"
                      data-bs-toggle="none"
                      @click="handleProjectClickNoToggle(getTeamspaceId(teamspace), project)"
                    >
                      <div
                        class="d-flex justify-content-between align-items-center w-100 me-2"
                      >
                        <div class="d-flex align-items-center">
                          <i class="bi bi-folder me-2"></i>
                          <span>{{ project.name }}</span>
                        </div>
                        <div class="d-flex align-items-center">
                          <!-- Loading spinner for project -->
                          <div
                            v-if="loadingProjectId === getProjectId(project) || loadingListsProjectId === getProjectId(project)" 
                            class="spinner-border spinner-border-sm sidebar-spinner"></div>
                          
                          <!-- Button shown when not loading -->
                          <template v-else>
                            <!-- Project options menu (three dots) -->
                            <div class="dropdown d-inline-block me-2">
                              <button
                                class="btn btn-link text-dark p-0 sidebar-text"
                                @click.stop="toggleProjectOptions(getProjectId(project), $event)"
                                title="Project options"
                              >
                                <i class="bi bi-three-dots"></i>
                              </button>
                              <div :id="`projectOptionsMenu${getProjectId(project)}`" class="dropdown-menu dropdown-menu-end project-options-menu">
                                <button class="dropdown-item" @click.stop="changeProjectName(teamspace, project, $event)">
                                  <i class="bi bi-pencil me-2"></i>
                                  Rename Project
                                </button>
                                <div class="dropdown-divider"></div>
                                <button class="dropdown-item text-danger" @click.stop="deleteProject(teamspace, project, $event)">
                                  <i class="bi bi-trash me-2"></i>
                                  Delete Project
                                </button>
                              </div>
                            </div>
                            
                            <!-- New list button -->
                            <button
                              class="btn btn-link text-dark p-0"
                              @click.stop="
                                showNewListModal(project, $event, teamspace)
                              "
                              title="New list"
                            >
                              <i class="bi bi-plus"></i>
                            </button>
                            
                            <!-- Down arrow -->
                            <i class="bi bi-chevron-down ms-2 sidebar-dropdown-arrow"
                              @click.stop="toggleProjectDropdown(teamspace, project, $event)"
                            ></i>
                          </template>
                        </div>
                      </div>
                    </button>
                  </h2>
                  <div
                    :id="`projectCollapse${getProjectId(project)}`"
                    class="accordion-collapse collapse"
                  >
                    <div class="accordion-body p-0">
                      <!-- Loading indicator for lists -->
                      <div 
                        v-if="loadingProjectId === getProjectId(project) || loadingListsProjectId === getProjectId(project)" 
                        class="text-center py-2"
                      >
                        <div class="spinner-border spinner-border-sm sidebar-spinner me-2"></div>
                        <span class="small">{{ loadingProjectId === getProjectId(project) ? 'Loading project details...' : 'Loading lists...' }}</span>
                      </div>
                      <div v-else-if="!project.lists || project.lists.length === 0" class="text-center text-muted py-2">
                        <small>No lists found</small>
                      </div>
                      
                      <!-- Lists -->
                      <div
                        v-for="list in project.lists"
                        :key="list.id"
                        class="ms-4 mb-2"
                      >
                        <div
                          class="d-flex justify-content-between align-items-center text-dark text-decoration-none py-1 app-text px-2"
                          :class="{
                            'active-item':
                              selectedItem.type === 'list' &&
                              selectedItem.id === list.id,
                          }"
                        >
                          <div class="d-flex align-items-center" @click="handleListClick(list, project, teamspace)">
                            <i class="bi bi-list-ul me-2"></i>
                            <span>{{ list.name }}</span>
                          </div>
                          <!-- Loading spinner for list -->
                          <div v-if="loadingTaskListId === list.id" class="spinner-border spinner-border-sm sidebar-spinner"></div>
                          
                          <!-- Buttons shown when not loading -->
                          <div v-else class="d-flex align-items-center">
                            <!-- List options menu (three dots) -->
                            <div class="dropdown d-inline-block me-2">
                              <button
                                class="btn btn-link text-dark p-0 sidebar-text"
                                @click.stop="toggleListOptions(list.id, $event)"
                                title="List options"
                              >
                                <i class="bi bi-three-dots"></i>
                              </button>
                              <div :id="`listOptionsMenu${list.id}`" class="dropdown-menu dropdown-menu-end list-options-menu">
                                <button class="dropdown-item" @click.stop="changeListName(list, project, teamspace, $event)">
                                  <i class="bi bi-pencil me-2"></i>
                                  Rename List
                                </button>
                                <div class="dropdown-divider"></div>
                                <button class="dropdown-item text-danger" @click.stop="deleteList(list, project, teamspace, $event)">
                                  <i class="bi bi-trash me-2"></i>
                                  Delete List
                                </button>
                              </div>
                            </div>
                            
                            <!-- Add task button -->
                            <button
                              class="btn btn-link text-dark p-0 ms-2"
                              @click.stop="showNewTaskModal(list, project, teamspace)"
                            >
                              <i class="bi bi-plus"></i>
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Loading indicator for teamspaces -->
        <div v-if="workspaceStore.loading && teamspaces.length === 0" class="text-center py-3">
          <div class="spinner-border spinner-border-sm sidebar-spinner me-2"></div>
          <span>Loading teams...</span>
        </div>
        
        <!-- New Teamspace Button -->
        <div class="nav-item mb-2">
          <button
            class="nav-link d-flex align-items-center text-dark text-decoration-none py-1 px-2 rounded app-text w-100"
            :class="{ 'justify-content-center': isCollapsed }"
            @click="showNewTeamspaceModal"
          >
            <i class="bi bi-plus" :class="isCollapsed ? '' : 'me-2'"></i>
            <span class="sidebar-text">New Team</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Invite Members Button -->
    <div class="invite-section border-top p-2">
      <div class="nav-item" :style="{ marginLeft: isCollapsed ? '0' : '25%' }">
        <button
          class="nav-link d-flex align-items-center justify-content-center text-dark text-decoration-none py-1 px-4 rounded app-text"
          @click="showInviteMembersModal"
        >
          <i class="bi bi-person-plus" :class="isCollapsed ? '' : 'me-2'"></i>
          <span class="sidebar-text">Invite</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch, nextTick, inject } from "vue";
import { useNavigationStore } from "../stores/navigationStore";
import { useWorkspaceStore } from "../stores/workspaceStore";
import { useTeamspaceStore } from "../stores/teamspaceStore";
import { useProjectStore } from "../stores/projectStore";
import { useListStore } from "../stores/listStore";
import { useRouter, useRoute } from "vue-router";
import { Dropdown } from "bootstrap";
import * as bootstrap from "bootstrap";
import { useSidebarStore } from "../stores/sidebarStore";
import { createToast } from 'mosha-vue-toastify';

const navigationStore = useNavigationStore();
const workspaceStore = useWorkspaceStore();
const teamspaceStore = useTeamspaceStore();
const projectStore = useProjectStore();
const listStore = useListStore();
const router = useRouter();
const route = useRoute();
const collapseInstances = ref({});
const selectedItem = ref({
  type: null,
  id: null,
});
const userName = ref("");
const userEmail = ref("");
const profilePictureUrl = ref("");
const sidebarStore = useSidebarStore();
const sidebarDropdownButton = ref(null);
let userDropdownInstance = null;
const loadingTeamspaceId = ref(null);
const teamspacesWithLoadedProjects = ref(new Set());
const projectsWithLoadedLists = ref(new Set());
const loadingProjectId = ref(null);
const loadingListsProjectId = ref(null);
const loadingTaskListId = ref(null);
const teamOptionsInstances = ref({});
const projectOptionsInstances = ref({});
const listOptionsInstances = ref({});

// Get modals from the parent component
const modals = inject('modals', {
  showNewTeamspace: () => console.warn('modals not provided'),
  showNewProject: () => console.warn('modals not provided'),
  showNewList: () => console.warn('modals not provided'),
  showNewWorkspace: () => console.warn('modals not provided'),
  showInviteMembers: () => console.warn('modals not provided'),
  showEditWorkspace: () => console.warn('modals not provided'),
  showMembers: () => console.warn('modals not provided'),
  showNewTask: () => console.warn('modals not provided')
});

const isCollapsed = computed(() => sidebarStore.isCollapsed);

const sidebarStyle = computed(() => ({
  width: isCollapsed.value ? "60px" : "250px",
  height: "calc(100vh - 48px)",
  position: "fixed",
  left: "0",
  top: "48px",
}));

// Make teamspaces reactive through computed
const teamspaces = computed(() => workspaceStore.currentWorkspaceTeamspaces);

// Add workspace computed properties with loading and error states
const workspaces = computed(() => workspaceStore.workspaces);
const currentWorkspace = computed(() => workspaceStore.currentWorkspace);
const currentWorkspaceId = computed(() => {
  const workspace = workspaceStore.currentWorkspace;
  if (!workspace) return null;
  return workspace.id || workspace._id || workspace.workspace_id;
});
const isWorkspacesLoading = computed(() => workspaceStore.loading);
const workspaceError = computed(() => workspaceStore.error);

// Add computed property for current workspace name
const currentWorkspaceName = computed(() => workspaceStore.currentWorkspace?.name || 'No workspace selected');

// Function to edit current workspace
const editCurrentWorkspace = () => {
  // Close the dropdown
  if (userDropdownInstance) {
    userDropdownInstance.hide();
  }
  
  // Show the edit workspace modal
  if (modals.showEditWorkspace) {
    modals.showEditWorkspace(currentWorkspace.value);
  } else {
    console.warn('Edit workspace modal not provided');
    createToast('Edit workspace feature is coming soon', {
      position: 'top-right',
      type: 'info',
      timeout: 3000
    });
  }
};

onMounted(async () => {
  try {
    // Reset all loading states to ensure a clean start
    loadingTeamspaceId.value = null;
    loadingProjectId.value = null;
    loadingListsProjectId.value = null;
    loadingTaskListId.value = null;
    
    // Reset tracked teamspaces
    teamspacesWithLoadedProjects.value.clear();
    
    // Always refresh workspaces from API first
    await workspaceStore.fetchWorkspaces()
    
    // Initialize workspaces - this will use the fetched workspaces
    await workspaceStore.initializeStore()
    
    // If we have a current workspace, ensure we load its teamspaces
    if (workspaceStore.currentWorkspace) {
      // This will fetch the teamspaces using the new API endpoint
      await workspaceStore.setCurrentWorkspace(workspaceStore.currentWorkspace)
    }
    
    // Initialize collapses
    initializeCollapses()

  // Initialize user dropdown
  if (sidebarDropdownButton.value) {
      userDropdownInstance = new Dropdown(sidebarDropdownButton.value)
    }
  } catch (error) {
    console.error('Error initializing sidebar:', error)
    createToast('Error loading workspace data', {
      position: 'top-right',
      type: 'danger',
      timeout: 5000
    })
  }
});

// Watch for changes in teamspaces to reinitialize collapse instances
watch(teamspaces, () => {
  nextTick(() => {
    initializeCollapses();
  });
}, { deep: true });

// Also watch for changes in the current workspace to reset loaded teamspaces
watch(currentWorkspaceId, async () => {
  console.log('Current workspace changed, resetting loaded teamspaces tracking');
  teamspacesWithLoadedProjects.value.clear();
  projectsWithLoadedLists.value.clear();
  
  // Attempt to refresh teamspaces when workspace changes
  if (currentWorkspaceId.value) {
    try {
      const teamspaceStore = useTeamspaceStore()
      await teamspaceStore.loadTeamspacesForWorkspace(currentWorkspaceId.value)
    } catch (error) {
      console.error('Error loading teamspaces for workspace:', error)
      createToast('Error loading teams. Please try refreshing.', {
        position: 'top-right',
        type: 'warning',
        timeout: 5000
      })
    }
  }
});

const handleHomeClick = () => {
  selectedItem.value = {
    type: "home",
    id: null,
  };
  navigationStore.clearActiveItems();
};

const handleInboxClick = () => {
  selectedItem.value = {
    type: "inbox",
    id: null,
  };
  navigationStore.clearActiveItems();
};

const handleEverythingClick = () => {
  selectedItem.value = {
    type: "everything",
    id: null,
  };
  navigationStore.clearActiveItems();
};

const handleTeamspaceClickNoToggle = (teamspace) => {
  selectedItem.value = {
    type: "teamspace",
    id: teamspace.id,
  };
  
  const teamspaceId = getTeamspaceId(teamspace);
  
  // If the teamspace is loading, do nothing to prevent toggling
  if (loadingTeamspaceId.value === teamspaceId) {
    return;
  }
  
  // Set teamspace in navigation store and navigate
  navigationStore.setTeamspace(teamspace);
  router.push(`/${encodeURIComponent(teamspace.name)}`);
};

const toggleTeamspaceDropdown = (teamspace, event) => {
  // Ensure event propagation is stopped and default behavior prevented
  event.stopPropagation();
  event.preventDefault();
  
  const teamspaceId = getTeamspaceId(teamspace);
  
  const teamspaceCollapseId = `teamspaceCollapse${teamspaceId}`;
  const teamspaceCollapseElement = document.getElementById(teamspaceCollapseId);
  
  // Check if teamspace is already expanded
  const isExpanded = teamspaceCollapseElement && teamspaceCollapseElement.classList.contains('show');
  // If already expanded, just collapse it without fetching data
  if (isExpanded) {
    toggleTeamspace(teamspaceId);
    return;
  }
  loadingTeamspaceId.value = teamspaceId;
  
  // Set safety timeouts
  safetyResetLoadingStates();
  
  // No toast needed, the spinner in the UI is enough
  
  // First fetch detailed teamspace information from API
  teamspaceStore.fetchTeamspaceDetails(teamspaceId)
    .then(fetchedTeamspace => {
      
      // Now fetch projects for this teamspace
      return teamspaceStore.fetchTeamspaceProjects(teamspaceId)
        .then(projects => {
          // Add this teamspace to the set of those with loaded projects
          teamspacesWithLoadedProjects.value.add(teamspaceId);
          
          // Continue with UI updates and navigation
          toggleTeamspace(teamspaceId);
          
          // Update navigation store with the teamspace that now has the projects
          const updatedTeamspace = teamspaceStore.getTeamspaceLocal(teamspaceId);
          navigationStore.setTeamspace(updatedTeamspace);
          
          return { teamspace: updatedTeamspace, projects };
        })
        .catch(projectsError => {
          console.error('Error loading projects:', projectsError);
          // Still continue with what we have (teamspace details but no projects)
          
          // Still toggle the teamspace and navigate, but with the limited data we have
          toggleTeamspace(teamspaceId);
          navigationStore.setTeamspace(fetchedTeamspace);
          
          return { teamspace: fetchedTeamspace, projects: [] };
        });
    })
    .catch(error => {
      console.error('Error loading teamspace details:', error);
      // Still toggle the teamspace and navigate, but with the limited data we have
      toggleTeamspace(teamspaceId);
      navigationStore.setTeamspace(teamspace);
    })
    .finally(() => {
      // Clear loading state
      loadingTeamspaceId.value = null;
    });
};

// Original handleTeamspaceClick for backward compatibility
const handleTeamspaceClick = (teamspace) => {
  handleTeamspaceClickNoToggle(teamspace);
  toggleTeamspaceDropdown(teamspace, { stopPropagation: () => {} });
};

const handleProjectClickNoToggle = (teamspaceId, project) => {
  // If project is currently loading, do nothing
  const projectId = getProjectId(project);
  if (loadingProjectId.value === projectId || loadingListsProjectId.value === projectId) {
    console.log('Project is currently loading, ignoring click');
    return;
  }

  selectedItem.value = {
    type: "project",
    id: project.id,
  };
  
  // Get the teamspace to pass to the navigation store
  const teamspace = teamspaceStore.getTeamspaceLocal(teamspaceId);
  
  // Set project and teamspace in navigation store
  if (teamspace) {
    navigationStore.setTeamspace(teamspace);
    navigationStore.setProject(project);
    
    // Navigate to the project page
    router.push(`/${encodeURIComponent(teamspace.name)}/${encodeURIComponent(project.name)}`);
  }
};

// Safety function to reset loading states after a timeout
const safetyResetLoadingStates = () => {
  // Set safety timeout to automatically clear loading states after 10 seconds
  const SAFETY_TIMEOUT = 10000; // 10 seconds
  
  if (loadingProjectId.value) {
    setTimeout(() => {
      if (loadingProjectId.value) {
        console.warn(`Safety timeout: Clearing stuck loadingProjectId: ${loadingProjectId.value}`);
        loadingProjectId.value = null;
      }
    }, SAFETY_TIMEOUT);
  }
  
  if (loadingListsProjectId.value) {
    setTimeout(() => {
      if (loadingListsProjectId.value) {
        console.warn(`Safety timeout: Clearing stuck loadingListsProjectId: ${loadingListsProjectId.value}`);
        loadingListsProjectId.value = null;
      }
    }, SAFETY_TIMEOUT);
  }
  
  if (loadingTeamspaceId.value) {
    setTimeout(() => {
      if (loadingTeamspaceId.value) {
        console.warn(`Safety timeout: Clearing stuck loadingTeamspaceId: ${loadingTeamspaceId.value}`);
        loadingTeamspaceId.value = null;
      }
    }, SAFETY_TIMEOUT);
  }
  
  if (loadingTaskListId.value) {
    setTimeout(() => {
      if (loadingTaskListId.value) {
        console.warn(`Safety timeout: Clearing stuck loadingTaskListId: ${loadingTaskListId.value}`);
        loadingTaskListId.value = null;
      }
    }, SAFETY_TIMEOUT);
  }
};

// Add to each loading method
const toggleProjectDropdown = (teamspace, project, event) => {
  // Ensure event propagation is stopped and default behavior prevented
  event.stopPropagation();
  event.preventDefault();
  const teamspaceId = getTeamspaceId(teamspace);
  const projectId = getProjectId(project);
  
  const projectCollapseId = `projectCollapse${projectId}`;
  const projectCollapseElement = document.getElementById(projectCollapseId);
  
  // Check if project is already expanded
  const isExpanded = projectCollapseElement && projectCollapseElement.classList.contains('show');
  // If already expanded, just collapse it without fetching data
  if (isExpanded) {
    toggleProject(teamspaceId, projectId);
    return;
  }
  
  // Otherwise proceed with loading data and expanding
  loadingProjectId.value = projectId;
  
  // Set safety timeouts
  safetyResetLoadingStates();
  
  // No toast needed, the spinner in the UI is enough
  
  // Fetch detailed project information from API
  console.log('Starting project details fetch from toggleProjectDropdown');
  projectStore.fetchProjectDetails(projectId)
    .then(project => {
      console.log('Project details loaded successfully from toggleProjectDropdown:', project);
      
      // Also fetch lists for this project
      loadingListsProjectId.value = projectId;
      
      console.log('Starting lists fetch from toggleProjectDropdown');
      return listStore.fetchProjectLists(projectId)
        .then(lists => {
          console.log(`Loaded ${lists.length} lists for project ${projectId} from toggleProjectDropdown`);
          
          // Add this project to the set of those with loaded lists
          projectsWithLoadedLists.value.add(projectId);
          
          // Continue with UI updates and navigation
          // We're going to call toggleProject to expand the UI, but we've already loaded the data,
          // so loadingListsProjectId will prevent toggleProject from fetching again
          console.log('Expanding project UI after data loaded from toggleProjectDropdown');
          toggleProject(teamspaceId, projectId);
          
          // Get updated references
          const teamspace = teamspaceStore.getTeamspaceLocal(teamspaceId);
          const updatedProject = teamspace?.projects.find(p => 
            p.id === projectId || 
            p._id === projectId || 
            p.project_id === projectId
          );
          
          if (teamspace && updatedProject) {
            // Update navigation store
            navigationStore.setTeamspace(teamspace);
            navigationStore.setProject(updatedProject);
          } else {
            // If we can't find the updated references, use what we have
            navigationStore.setProject(project);
          }
          
          return { project, lists };
        })
        .catch(listsError => {
          console.warn(`Could not load lists for project ${projectId} from toggleProjectDropdown:`, listsError);
          
          // Still proceed with project details
          // Add this project to the set of those with loaded lists
          projectsWithLoadedLists.value.add(projectId);
          
          // Continue with UI updates and navigation
          console.log('Expanding project UI after lists fetch error from toggleProjectDropdown');
          toggleProject(teamspaceId, projectId);
          
          // Get updated references
          const teamspace = teamspaceStore.getTeamspaceLocal(teamspaceId);
          const updatedProject = teamspace?.projects.find(p => 
            p.id === projectId || 
            p._id === projectId || 
            p.project_id === projectId
          );
          
          if (teamspace && updatedProject) {
            navigationStore.setTeamspace(teamspace);
            navigationStore.setProject(updatedProject);
          } else {
            navigationStore.setProject(project);
          }
          
          return { project, lists: [] };
        })
        .finally(() => {
          // Always clear the lists loading state
          console.log('Clearing loadingListsProjectId in toggleProjectDropdown');
          loadingListsProjectId.value = null;
        });
    })
    .catch(error => {
      console.error('Error loading project details from toggleProjectDropdown:', error);
      // Continue with UI updates and navigation, but with limited data
      console.log('Expanding project UI after project details error from toggleProjectDropdown');
      toggleProject(teamspaceId, projectId);
      
      // Get updated references, with what we have
      navigationStore.setProject(project);
    })
    .finally(() => {
      // Always clear the project loading state
      console.log('Clearing loadingProjectId in toggleProjectDropdown');
      loadingProjectId.value = null;
    });
};

// Original handleProjectClick for backward compatibility
const handleProjectClick = (teamspaceId, project) => {
  handleProjectClickNoToggle(teamspaceId, project);
  toggleProjectDropdown(teamspaceStore.getTeamspaceLocal(teamspaceId), project, { stopPropagation: () => {} });
};

const isTeamspaceExpanded = (teamspaceId) => {
  const instance = collapseInstances.value["teamspace" + teamspaceId];
  if (instance) {
    return instance._element.classList.contains("show");
  }
  return false;
};

const isProjectExpanded = (projectId) => {
  const instance = collapseInstances.value["project" + projectId];
  if (instance) {
    return instance._element.classList.contains("show");
  }
  return false;
};

const expandTeamspace = (teamspaceId) => {
  const teamspaceCollapseId = `teamspaceCollapse${teamspaceId}`;
  const teamspaceCollapseElement = document.getElementById(teamspaceCollapseId);
  
  if (teamspaceCollapseElement) {
    // Ensure we have a collapse instance
    let bsCollapse = bootstrap.Collapse.getInstance(teamspaceCollapseElement);
    
    if (!bsCollapse) {
      // Create new instance if not exists
      collapseInstances.value[teamspaceCollapseId] = new bootstrap.Collapse(teamspaceCollapseElement, {
        toggle: false
      });
      bsCollapse = collapseInstances.value[teamspaceCollapseId];
    }
    
    // Only expand if not already expanded
    if (!teamspaceCollapseElement.classList.contains('show')) {
      bsCollapse.show();
      
      // Load projects if not already loaded
      if (!teamspacesWithLoadedProjects.value.has(teamspaceId)) {
        const teamspace = teamspaceStore.getTeamspaceLocal(teamspaceId);
        if (teamspace) {
          fetchTeamspaceProjects(teamspaceId);
        }
      }
    }
  }
};

const expandProject = (teamspaceId, projectId) => {
  // First expand the parent teamspace
  expandTeamspace(teamspaceId);
  
  // Then expand the project
  const projectCollapseId = `projectCollapse${projectId}`;
  const projectCollapseElement = document.getElementById(projectCollapseId);
  
  if (projectCollapseElement) {
    // Ensure we have a collapse instance
    let bsCollapse = bootstrap.Collapse.getInstance(projectCollapseElement);
    
    if (!bsCollapse) {
      // Create new instance if not exists
      collapseInstances.value[projectCollapseId] = new bootstrap.Collapse(projectCollapseElement, {
        toggle: false
      });
      bsCollapse = collapseInstances.value[projectCollapseId];
    }
    
    // Only expand if not already expanded
    if (!projectCollapseElement.classList.contains('show')) {
      bsCollapse.show();
      
      // Only load lists if we're not already loading and they haven't been loaded yet
      if (!loadingProjectId.value && !loadingListsProjectId.value && !projectsWithLoadedLists.value.has(projectId)) {
        console.log(`Expanding project ${projectId} from route navigation - need to fetch data`);
        fetchProjectDetails(teamspaceId, projectId);
      } else if (projectsWithLoadedLists.value.has(projectId)) {
        console.log(`Expanding project ${projectId} from route navigation - data already loaded`);
      } else {
        console.log(`Expanding project ${projectId} from route navigation - fetch already in progress`);
      }
    }
  }
};

const handleListClick = (list, project, teamspace) => {
  selectedItem.value = {
    type: "list",
    id: list.id,
  };

  // Set the full navigation context
  navigationStore.setTeamspace(teamspace);
  navigationStore.setProject(project);
  navigationStore.setList(list);

  // Ensure parent accordions are expanded
  expandTeamspace(teamspace.id);
  expandProject(teamspace.id, project.id);

  router.push(`/${encodeURIComponent(teamspace.name)}/${encodeURIComponent(project.name)}/${encodeURIComponent(list.name)}`);
};

const showNewProjectModal = (teamspace, event) => {
  event.stopPropagation();
  window.activeTeamspace = teamspace;
  modals.showNewProject();
};

const showNewListModal = (project, event, teamspace) => {
  event.stopPropagation();

  // Find the current teamspace and project reference from the store
  const currentTeamspace = teamspaceStore.teamspaces.find(
    (t) => t.id === teamspace.id
  );
  if (!currentTeamspace) {
    console.error("Teamspace not found:", teamspace.name);
    return;
  }

  const currentProject = currentTeamspace.projects.find(
    (p) => p.id === project.id
  );
  if (!currentProject) {
    console.error("Project not found in teamspace:", teamspace.name);
    return;
  }

  // Set the active teamspace and project in the navigation store
  navigationStore.setTeamspace(currentTeamspace);
  navigationStore.setProject(currentProject);

  // Set the window variables for the modal
  window.activeProject = currentProject;
  window.activeTeamspace = currentTeamspace;

  modals.showNewList();
};

const showNewTaskModal = (list, project, teamspace) => {
  // Set loading state
  loadingTaskListId.value = list.id;

  // Find the current teamspace and project reference from the store
  const currentTeamspace = teamspaceStore.teamspaces.find(
    (t) => t.id === teamspace.id
  );
  if (!currentTeamspace) {
    console.error("Teamspace not found:", teamspace.name);
    loadingTaskListId.value = null;
    return;
  }

  const currentProject = currentTeamspace.projects.find(
    (p) => p.id === project.id
  );
  if (!currentProject) {
    console.error("Project not found in teamspace:", teamspace.name);
    loadingTaskListId.value = null;
    return;
  }

  const currentList = currentProject.lists.find(
    (l) => l.id === list.id
  );
  if (!currentList) {
    console.error("List not found in project:", project.name);
    loadingTaskListId.value = null;
    return;
  }

  // Set the active list, project, and teamspace in the navigation store
  navigationStore.setTeamspace(currentTeamspace);
  navigationStore.setProject(currentProject);
  navigationStore.setList(currentList);

  // Set the window variables for the modal
  window.activeList = currentList;
  window.activeProject = currentProject;
  window.activeTeamspace = currentTeamspace;

  // Use the modals object to show the new task modal
  modals.showNewTask();
  
  // Clear loading state after modal is shown
  setTimeout(() => {
    loadingTaskListId.value = null;
  }, 100);
};

const showNewTeamspaceModal = () => {
  modals.showNewTeamspace();
  
  if (userDropdownInstance) {
    userDropdownInstance.hide();
  }
};

const showInviteMembersModal = () => {
  modals.showInviteMembers();
};

const showMembersDrawer = () => {
  // Close the user dropdown
  if (userDropdownInstance) {
    userDropdownInstance.hide();
  }
  
  // Show the members drawer with the current workspace
  modals.showMembers(currentWorkspace.value);
};

// Watch route changes to update selected item and expand appropriate accordions
watch(
  () => route.path,
  async (newPath) => {
    const teamspaceName = route.params.teamspaceName;
    const projectName = route.params.projectName;
    const listName = route.params.listName;

    if (newPath === '/') {
      selectedItem.value = { type: 'home', id: null };
    } else if (newPath === '/inbox') {
      selectedItem.value = { type: 'inbox', id: null };
    } else if (newPath === '/everything') {
      selectedItem.value = { type: 'everything', id: null };
    } else if (teamspaceName) {
      const teamspace = teamspaceStore.teamspaces.find(
        t => t.name === decodeURIComponent(teamspaceName)
      );
      if (teamspace) {
        selectedItem.value = { type: 'teamspace', id: teamspace.id };
        expandTeamspace(teamspace.id);

        if (projectName) {
          const project = teamspace.projects.find(
            p => p.name === decodeURIComponent(projectName)
          );
          if (project) {
            selectedItem.value = { type: 'project', id: project.id };
            expandProject(teamspace.id, project.id);

            if (listName) {
              const list = project.lists.find(
                l => l.name === decodeURIComponent(listName)
              );
              if (list) {
                selectedItem.value = { type: 'list', id: list.id };
              }
            }
          }
        }
      }
    }
  },
  { immediate: true }
);

const initializeCollapses = () => {
  // Clear existing instances to prevent memory leaks
  Object.values(collapseInstances.value).forEach((instance) => {
    if (instance && typeof instance.dispose === "function") {
      instance.dispose();
    }
  });
  collapseInstances.value = {};

  // Initialize new instances after a short delay to ensure DOM is ready
  setTimeout(() => {
    teamspaces.value.forEach((teamspace) => {
      const teamspaceId = getTeamspaceId(teamspace);
      const teamspaceCollapseId = `teamspaceCollapse${teamspaceId}`;
      const teamspaceEl = document.getElementById(teamspaceCollapseId);
      
      if (teamspaceEl) {
        collapseInstances.value[teamspaceCollapseId] = new bootstrap.Collapse(teamspaceEl, {
            toggle: false,
          });
      }

      if (teamspace.projects) {
        teamspace.projects.forEach((project) => {
          const projectId = getProjectId(project);
          const projectCollapseId = `projectCollapse${projectId}`;
          const projectEl = document.getElementById(projectCollapseId);
          
          if (projectEl) {
            collapseInstances.value[projectCollapseId] = new bootstrap.Collapse(projectEl, {
                toggle: false,
              });
          }
        });
      }
    });
  }, 100);
};

const toggleTeamspace = (teamspaceId) => {
  const teamspaceCollapseId = `teamspaceCollapse${teamspaceId}`;
  const teamspaceCollapseElement = document.getElementById(teamspaceCollapseId);
  
  if (teamspaceCollapseElement) {
    let bsCollapse = bootstrap.Collapse.getInstance(teamspaceCollapseElement);
    
    if (!bsCollapse) {
      // Initialize a new collapse instance
      collapseInstances.value[teamspaceCollapseId] = new bootstrap.Collapse(teamspaceCollapseElement, {
        toggle: false
      });
      
      bsCollapse = collapseInstances.value[teamspaceCollapseId];
    }
    
    // Check current state and toggle it
    const isExpanded = teamspaceCollapseElement.classList.contains('show');
    
    if (isExpanded) {
      bsCollapse.hide();
    } else {
      bsCollapse.show();
      
      // Check if the teamspace is being expanded and projects haven't been loaded yet
      if (!teamspacesWithLoadedProjects.value.has(teamspaceId)) {
        fetchTeamspaceProjects(teamspaceId);
      }
    }
  } else {
    console.error('Could not find collapse element with ID:', teamspaceCollapseId);
  }
};

const toggleProject = (teamspaceId, projectId) => {
  const projectCollapseId = `projectCollapse${projectId}`;
  const projectCollapseElement = document.getElementById(projectCollapseId);
  
  if (projectCollapseElement) {
    let bsCollapse = bootstrap.Collapse.getInstance(projectCollapseElement);
    
    if (!bsCollapse) {
      // Initialize a new collapse instance
      collapseInstances.value[projectCollapseId] = new bootstrap.Collapse(projectCollapseElement, {
        toggle: false
      });
      
      bsCollapse = collapseInstances.value[projectCollapseId];
    }
    
    // Check current state and toggle it
    const isExpanded = projectCollapseElement.classList.contains('show');
    
    if (isExpanded) {
      bsCollapse.hide();
    } else {
      bsCollapse.show();
      
      // Check if we're being called from toggleProjectDropdown by checking if lists are already loading
      // Only try to fetch data if we're not already loading lists (to avoid duplicate calls)
      if (!loadingListsProjectId.value && !loadingProjectId.value) {
        // Check if the project is being expanded and lists haven't been loaded yet
        if (!projectsWithLoadedLists.value.has(projectId)) {
          console.log('Need to load project lists from toggleProject');
          fetchProjectDetails(teamspaceId, projectId);
        } else {
          // Even if we've loaded the project before, refresh the lists
          // This ensures the lists are always up to date
          console.log('Project was already loaded, refreshing lists from toggleProject');
          loadingListsProjectId.value = projectId;
          
          // Set safety timeouts
          safetyResetLoadingStates();
          
          projectStore.fetchProjectLists(projectId)
            .then(lists => {
              console.log(`Refreshed ${lists.length} lists for project ${projectId}`);
            })
            .catch(error => {
              console.warn(`Error refreshing lists for project ${projectId}:`, error);
            })
            .finally(() => {
              // Ensure loading state is reset
              loadingListsProjectId.value = null;
            });
        }
      } else {
        console.log('Skipping data fetch in toggleProject since fetch is already in progress');
      }
    }
  } else {
    console.error('Could not find collapse element with ID:', projectCollapseId);
  }
};

const fetchTeamspaceProjects = (teamspaceId) => {
  // Set loading state
  loadingTeamspaceId.value = teamspaceId;
  
  
  // Fetch projects from API
  teamspaceStore.fetchTeamspaceProjects(teamspaceId)
    .then(projects => {
      // Mark this teamspace as having its projects loaded
      teamspacesWithLoadedProjects.value.add(teamspaceId);
    })
    .catch(error => {
      console.error('Error loading teamspace projects:', error);
      
      // Remove toast notification
      console.error(`Error loading projects: ${error.message || 'Unknown error'}`);
    })
    .finally(() => {
      // Clear loading state
      loadingTeamspaceId.value = null;
    });
};

const fetchProjectDetails = (teamspaceId, projectId) => {
  // If we're already loading this project or its lists, don't start another fetch
  if (loadingProjectId.value === projectId || loadingListsProjectId.value === projectId) {
    console.log(`Already fetching data for project ${projectId}, skipping duplicate fetch`);
    return;
  }
  
  // Set loading state
  loadingProjectId.value = projectId;
  
  // Set safety timeouts
  safetyResetLoadingStates();
  
  console.log(`Fetching details for project ${projectId} from fetchProjectDetails`);
  
  // Fetch project details from API
  projectStore.fetchProjectDetails(projectId)
    .then(project => {
      console.log(`Loaded details for project ${projectId} from fetchProjectDetails`, project);
      
      // Mark this project as having its details loaded
      // projectsWithLoadedLists.value.add(projectId);
      
      // Now fetch lists for this project
      loadingListsProjectId.value = projectId;
      return listStore.fetchProjectLists(projectId)
        .then(lists => {
          console.log(`Loaded ${lists.length} lists for project ${projectId} from fetchProjectDetails`);
          // Once we have the lists, mark the project as fully loaded
          projectsWithLoadedLists.value.add(projectId);
          return { project, lists };
        })
        .catch(listsError => {
          console.warn(`Could not load lists for project ${projectId} from fetchProjectDetails:`, listsError);
          return { project, lists: [] };
        })
        .finally(() => {
          // Always clear the lists loading state
          console.log('Clearing loadingListsProjectId in fetchProjectDetails');
          loadingListsProjectId.value = null;
        });
    })
    .catch(error => {
      console.error('Error loading project details from fetchProjectDetails:', error);
      
      // Remove toast notification
      console.error(`Error loading project details: ${error.message || 'Unknown error'}`);
    })
    .finally(() => {
      // Always clear the project loading state
      console.log('Clearing loadingProjectId in fetchProjectDetails');
      loadingProjectId.value = null;
    });
};

const toggleSidebar = () => {
  sidebarStore.toggleSidebar();
  // Dispatch event for BreadcrumbNav to update its state
  window.dispatchEvent(
    new CustomEvent("sidebar-toggle", {
      detail: { isCollapsed: sidebarStore.isCollapsed },
    })
  );
};

const toggleUserDropdown = () => {
  if (userDropdownInstance) {
    userDropdownInstance.toggle();
  }
};

// Function to refresh workspaces list
const refreshWorkspaces = async () => {
  try {
    createToast('Refreshing workspaces...', {
      position: 'top-right',
      type: 'info',
      timeout: 2000
    });
    
    await workspaceStore.fetchWorkspaces();
    
    if (workspaceStore.workspaces.length === 0) {
      createToast('No workspaces found. Create a new one.', {
        position: 'top-right',
        type: 'info',
        timeout: 4000
      });
    } else {
      createToast(`Loaded ${workspaceStore.workspaces.length} workspaces`, {
        position: 'top-right',
        type: 'success',
        timeout: 3000
      });
      
      // If no current workspace is set but workspaces exist, set the first one
      if (!workspaceStore.currentWorkspace && workspaceStore.workspaces.length > 0) {
        await workspaceStore.setCurrentWorkspace(workspaceStore.workspaces[0]);
      }
      
      // If workspace selected but no teamspaces, try to load them
      if (workspaceStore.currentWorkspace && teamspaceStore.teamspaces.length === 0) {
        try {
          await teamspaceStore.loadTeamspacesForWorkspace(workspaceStore.currentWorkspace.id);
        } catch (error) {
          console.error('Error loading teamspaces after workspace refresh:', error);
        }
      }
    }
  } catch (error) {
    console.error('Error refreshing workspaces:', error);
    createToast('Failed to refresh workspaces', {
      position: 'top-right',
      type: 'error',
      timeout: 4000
    });
  }
};

// Method to switch to a different workspace
const switchWorkspace = async (workspace) => {
  if (!workspace || !workspace.id) return;
  
  // Close dropdown
  if (userDropdownInstance) {
    userDropdownInstance.hide();
  }
  
  try {
    // Show loading toast
    createToast('Switching workspace...', {
      position: 'top-right',
      type: 'info',
      timeout: 2000
    });
    
    // Set new current workspace
    await workspaceStore.setCurrentWorkspace(workspace);
    
    // Clear navigation state
    navigationStore.clearActiveItems();
    
    // Navigate to the home page
    router.push('/');
    
    // If no teamspaces were loaded, try again directly
    if (teamspaceStore.teamspaces.length === 0) {
      console.log('No teamspaces loaded, trying direct loading');
      try {
        await teamspaceStore.loadTeamspacesForWorkspace(workspace.id);
      } catch (error) {
        console.error('Error loading teamspaces directly:', error);
      }
    }
    
    createToast(`Switched to "${workspace.name}"`, {
      position: 'top-right',
      type: 'success',
      timeout: 3000
    });
  } catch (error) {
    console.error('Error switching workspace:', error);
    createToast('Failed to switch workspace', {
      position: 'top-right',
      type: 'error',
      timeout: 4000
    });
  }
};

// Method to show the new workspace modal
const showNewWorkspaceModal = () => {
  modals.showNewWorkspace();
  
  if (userDropdownInstance) {
    userDropdownInstance.hide();
  }
};

const isActiveWorkspace = (workspace) => {
  const currentId = currentWorkspaceId.value || currentWorkspace.value?._id || currentWorkspace.value?.workspace_id;
  const workspaceId = workspace.id || workspace._id || workspace.workspace_id;
  return workspaceId === currentId;
};

// Add a refreshProjects method
const refreshProjects = (teamspaceId, event) => {
  if (event) {
    event.stopPropagation();
  }
  
  if (!teamspaceId) return;
  
  // Remove from loaded projects set to force a refresh
  teamspacesWithLoadedProjects.value.delete(teamspaceId);
  
  // Set loading state
  loadingTeamspaceId.value = teamspaceId;
  
  // Fetch projects from API
  teamspaceStore.fetchTeamspaceProjects(teamspaceId)
    .then(projects => {
      // Mark this teamspace as having its projects loaded
      teamspacesWithLoadedProjects.value.add(teamspaceId);
    })
    .catch(error => {
      console.error(`Error refreshing projects: ${error.message || 'Unknown error'}`);
    })
    .finally(() => {
      // Clear loading state
      loadingTeamspaceId.value = null;
    });
};

const refreshProjectDetails = (teamspaceId, projectId, event) => {
  if (event) {
    event.stopPropagation();
  }
  
  if (!teamspaceId || !projectId) return;
  
  // Remove from loaded projects set to force a refresh
  projectsWithLoadedLists.value.delete(projectId);
  
  // Set loading state
  loadingProjectId.value = projectId;
  
  // Fetch project details from API
  projectStore.fetchProjectDetails(projectId)
    .then(project => {
      // Mark this project as having its details loaded
      projectsWithLoadedLists.value.add(projectId);
      
      // Continue with UI updates and navigation
      toggleProject(teamspaceId, projectId);
      
      // Get updated references
      const teamspace = teamspaceStore.getTeamspaceLocal(teamspaceId);
      const updatedProject = teamspace?.projects.find(p => 
        p.id === projectId || 
        p._id === projectId || 
        p.project_id === projectId
      );
      
      if (teamspace && updatedProject) {
        // Update navigation store
        navigationStore.setTeamspace(teamspace);
        navigationStore.setProject(updatedProject);
        
        // Navigate to project page
        router.push(`/${encodeURIComponent(teamspace.name)}/${encodeURIComponent(updatedProject.name)}`);
    } else {
        // If we can't find the updated references, use what we have
        navigationStore.setProject(project);
        router.push(`/${encodeURIComponent(teamspace.name)}/${encodeURIComponent(project.name)}`);
      }
    })
    .catch(error => {
      console.error('Error refreshing project details:', error);
      
      // Close the loading toast
      console.error(`Error refreshing project details: ${error.message || 'Unknown error'}`);
    })
    .finally(() => {
      // Clear loading state
      loadingProjectId.value = null;
    });
};

// Helper functions to get consistent IDs
const getTeamspaceId = (teamspace) => {
  return teamspace.id || teamspace._id || teamspace.team_id;
};

const getProjectId = (project) => {
  return project.id || project._id || project.project_id;
};

const refreshLists = async (teamspaceId, projectId, event) => {
  if (event) {
    event.stopPropagation();
  }
  
  if (!teamspaceId || !projectId) return;
  
  // Set loading state
  loadingListsProjectId.value = projectId;
  
  // Fetch lists from API
  try {
    console.log(`Manually refreshing lists for project ${projectId}`);
    const lists = await projectStore.fetchProjectLists(projectId);
    console.log(`Refreshed ${lists.length} lists for project ${projectId}`);
    
    // Close the loading toast
    console.log(`${lists.length} lists loaded successfully`);
  } catch (error) {
    console.error('Error refreshing lists:', error);
    
    // Close the loading toast
    console.error(`Error refreshing lists: ${error.message || 'Unknown error'}`);
  } finally {
    // Clear loading state
    loadingListsProjectId.value = null;
  }
};

// Method to toggle the team options menu
const toggleTeamOptions = (teamspaceId, event) => {
  if (event) {
    event.stopPropagation();
  }
  
  const menuId = `teamOptionsMenu${teamspaceId}`;
  const menuElement = document.getElementById(menuId);
  
  if (menuElement) {
    // Check if we already have a dropdown instance
    let dropdownInstance = bootstrap.Dropdown.getInstance(menuElement);
    
    if (!dropdownInstance) {
      // Create a new dropdown instance
      const toggleButton = event.currentTarget;
      dropdownInstance = new bootstrap.Dropdown(toggleButton);
      teamOptionsInstances.value[menuId] = dropdownInstance;
    }
    
    // Toggle the dropdown
    if (menuElement.classList.contains('show')) {
      dropdownInstance.hide();
    } else {
      dropdownInstance.show();
    }
  }
};

// Method to change team name
const changeTeamName = async (teamspace, event) => {
  if (event) {
    event.stopPropagation();
  }
  
  // Hide dropdown menu
  const menuId = `teamOptionsMenu${getTeamspaceId(teamspace)}`;
  const menuElement = document.getElementById(menuId);
  const dropdownInstance = bootstrap.Dropdown.getInstance(menuElement);
  if (dropdownInstance) {
    dropdownInstance.hide();
  }
  
  // Prompt user for new name
  const newName = prompt('Enter new team name:', teamspace.name);
  
  // Check if user canceled or entered an empty name
  if (!newName || newName.trim() === '' || newName === teamspace.name) {
    return;
  }
  
  try {
    // Update the teamspace with new name
    const updatedTeamspace = { 
      ...teamspace, 
      name: newName.trim() 
    };
    
    // Use the store method to update via API
    const result = await teamspaceStore.updateTeamspace(updatedTeamspace);
    console.log('Update teamspace result:', result);
    console.log('Team name updated successfully');
  } catch (error) {
    console.error('Error updating team name:', error);
    console.error(`Failed to update team name: ${error.message || 'Unknown error'}`);
  }
};

// Method to toggle team privacy
const toggleTeamPrivacy = async (teamspace, event) => {
  if (event) {
    event.stopPropagation();
  }
  
  // Hide dropdown menu
  const menuId = `teamOptionsMenu${getTeamspaceId(teamspace)}`;
  const menuElement = document.getElementById(menuId);
  const dropdownInstance = bootstrap.Dropdown.getInstance(menuElement);
  if (dropdownInstance) {
    dropdownInstance.hide();
  }
  
  // Toggle privacy status
  const newPrivacyStatus = !teamspace.is_private;
  const statusText = newPrivacyStatus ? 'private' : 'public';
  
  try {
    // Update the teamspace with new privacy setting
    const updatedTeamspace = {
      ...teamspace,
      is_private: newPrivacyStatus,
      visibility: newPrivacyStatus ? 'private' : 'public'
    };
    
    // Use the store method to update via API
    const result = await teamspaceStore.updateTeamspace(updatedTeamspace);
    console.log('Update teamspace result:', result);
    console.log('Team is now', statusText);
  } catch (error) {
    console.error('Error updating team privacy:', error);
    console.error(`Failed to update team privacy: ${error.message || 'Unknown error'}`);
  }
};

// Method to delete a team
const deleteTeam = async (teamspace, event) => {
  if (event) {
    event.stopPropagation();
  }
  
  // Hide dropdown menu
  const menuId = `teamOptionsMenu${getTeamspaceId(teamspace)}`;
  const menuElement = document.getElementById(menuId);
  const dropdownInstance = bootstrap.Dropdown.getInstance(menuElement);
  if (dropdownInstance) {
    dropdownInstance.hide();
  }
  const teamspaceId = getTeamspaceId(teamspace);
  try {
    // Check if the teamspaceStore has the deleteTeamspace method
    if (typeof teamspaceStore.deleteTeamspace !== 'function') {
      console.error('teamspaceStore.deleteTeamspace is not a function', teamspaceStore);
      throw new Error('Delete team functionality is not available');
    }
    
    // Call API to delete the teamspace
    console.log('Calling teamspaceStore.deleteTeamspace with ID:', teamspaceId);
    const result = await teamspaceStore.deleteTeamspace(teamspaceId);
    console.log('Delete teamspace result:', result);
    console.log('Team deleted successfully');
    
    // Navigate to home if we're on a page related to this team
    if (selectedItem.value.type === 'teamspace' && selectedItem.value.id === teamspace.id) {
      router.push('/');
      navigationStore.clearActiveItems();
    }
  } catch (error) {
    console.error('Error deleting team:', error);
    console.error(`Failed to delete team: ${error.message || 'Unknown error'}`);
    
    // Implement local fallback to update UI
    // If API call fails, at least update UI to provide instant feedback
    try {
      // Remove the team from the local UI
      const teamspaceIndex = teamspaceStore.teamspaces.findIndex(t => 
        t.id === teamspaceId || 
        t._id === teamspaceId || 
        t.team_id === teamspaceId
      );
      
      if (teamspaceIndex !== -1) {
        console.log('Removing team from UI as fallback');
        teamspaceStore.teamspaces.splice(teamspaceIndex, 1);
        
        // Navigate to home
        router.push('/');
        selectedItem.value = { type: 'home', id: null };
        navigationStore.clearActiveItems();
      }
    } catch (fallbackError) {
      console.error('Error in local UI fallback:', fallbackError);
    }
  }
};

// Helper function to get workspace logo from different possible fields
const getWorkspaceLogo = (workspace) => {
  if (!workspace) return null;
  
  if (workspace.logo_url) {
    return workspace.logo_url;
  } else if (workspace.logo) {
    // If logo is a full URL or base64 data
    if (typeof workspace.logo === 'string' && 
        (workspace.logo.startsWith('http') || workspace.logo.startsWith('data:'))) {
      return workspace.logo;
    }
    // If it might be a relative path
    return `http://localhost/storage/${workspace.logo}`;
  }
  
  return null;
};

// Add a refreshTeams method
const refreshTeams = async () => {
  if (!currentWorkspaceId.value) {
    createToast('No workspace selected', {
      position: 'top-right',
      type: 'warning',
      timeout: 3000
    });
    return;
  }
  
  try {
    createToast('Refreshing teams...', {
      position: 'top-right',
      type: 'info',
      timeout: 2000
    });
    
    await teamspaceStore.loadTeamspacesForWorkspace(currentWorkspaceId.value);
    
    if (teamspaceStore.teamspaces.length === 0) {
      createToast('No teams found. Try creating a new team.', {
        position: 'top-right',
        type: 'info',
        timeout: 4000
      });
    } else {
      createToast(`Loaded ${teamspaceStore.teamspaces.length} teams`, {
        position: 'top-right',
        type: 'success',
        timeout: 3000
      });
    }
  } catch (error) {
    console.error('Error refreshing teams:', error);
    createToast('Failed to refresh teams. Please try again.', {
      position: 'top-right',
      type: 'error',
      timeout: 4000
    });
  }
};

// Method to toggle the project options menu
const toggleProjectOptions = (projectId, event) => {
  if (event) {
    event.stopPropagation();
  }
  
  const menuId = `projectOptionsMenu${projectId}`;
  const menuElement = document.getElementById(menuId);
  
  if (menuElement) {
    // Check if we already have a dropdown instance
    let dropdownInstance = bootstrap.Dropdown.getInstance(menuElement);
    
    if (!dropdownInstance) {
      // Create a new dropdown instance
      const toggleButton = event.currentTarget;
      dropdownInstance = new bootstrap.Dropdown(toggleButton);
      projectOptionsInstances.value[menuId] = dropdownInstance;
    }
    
    // Toggle the dropdown
    if (menuElement.classList.contains('show')) {
      dropdownInstance.hide();
    } else {
      dropdownInstance.show();
    }
  }
};

// Method to change project name
const changeProjectName = async (teamspace, project, event) => {
  if (event) {
    event.stopPropagation();
  }
  
  console.log('changeProjectName method called with project:', project);
  
  // Hide dropdown menu
  const menuId = `projectOptionsMenu${getProjectId(project)}`;
  const menuElement = document.getElementById(menuId);
  const dropdownInstance = bootstrap.Dropdown.getInstance(menuElement);
  if (dropdownInstance) {
    dropdownInstance.hide();
  }
  
  // Prompt user for new name
  const newName = prompt('Enter new project name:', project.name);
  
  // Check if user canceled or entered an empty name
  if (!newName || newName.trim() === '' || newName === project.name) {
    return;
  }
  
  console.log('Changing project name to:', newName);
  
  try {
    // Create a loading toast
    const loadingToast = createToast('Updating project...', {
      position: 'top-right',
      type: 'info',
      showIcon: true,
      swipeClose: false,
      timeout: -1
    });
    
    // Update the project with new name
    const updatedProject = { 
      ...project, 
      name: newName.trim() 
    };
    
    console.log('Calling projectStore.updateProject with:', updatedProject);
    
    // Use the store method to update via API
    const result = await projectStore.updateProject(getProjectId(project), updatedProject);
    
    loadingToast.close();
    console.log('Project name updated successfully');
    
    createToast('Project renamed successfully!', {
      position: 'top-right',
      type: 'success',
      timeout: 3000
    });
  } catch (error) {
    console.error('Error updating project name:', error);
    
    createToast(`Failed to update project name: ${error.message || 'Unknown error'}`, {
      position: 'top-right',
      type: 'danger',
      timeout: 5000
    });
  }
};

// Method to delete a project
const deleteProject = async (teamspace, project, event) => {
  if (event) {
    event.stopPropagation();
  }
  
  console.log('deleteProject method called with project:', project);
  
  // Hide dropdown menu
  const menuId = `projectOptionsMenu${getProjectId(project)}`;
  const menuElement = document.getElementById(menuId);
  const dropdownInstance = bootstrap.Dropdown.getInstance(menuElement);
  if (dropdownInstance) {
    dropdownInstance.hide();
  }
  
  // Confirm deletion
  if (!confirm(`Are you sure you want to delete the project "${project.name}"? This action cannot be undone.`)) {
    console.log('User cancelled project deletion');
    return;
  }
  
  const projectId = getProjectId(project);
  console.log('Deleting project with ID:', projectId);
  
  try {
    // Create a loading toast
    const loadingToast = createToast('Deleting project...', {
      position: 'top-right',
      type: 'info',
      showIcon: true,
      swipeClose: false,
      timeout: -1
    });
    
    // Call API to delete the project
    console.log('Calling projectStore.deleteProject with ID:', projectId);
    await projectStore.deleteProject(projectId);
    
    loadingToast.close();
    console.log('Project deleted successfully');
    
    createToast('Project deleted successfully!', {
      position: 'top-right',
      type: 'success',
      timeout: 3000
    });
    
    // Navigate to teamspace page if we're on a page related to this project
    if (selectedItem.value.type === 'project' && selectedItem.value.id === project.id) {
      router.push(`/${encodeURIComponent(teamspace.name)}`);
      navigationStore.clearProject();
      navigationStore.clearList();
    }
  } catch (error) {
    console.error('Error deleting project:', error);
    
    createToast(`Failed to delete project: ${error.message || 'Unknown error'}`, {
      position: 'top-right',
      type: 'danger',
      timeout: 5000
    });
  }
};

// Debug method to help troubleshoot API calls
const debugFetchStatus = () => {
  console.log({
    loadingProjectId: loadingProjectId.value,
    loadingListsProjectId: loadingListsProjectId.value,
    loadingTeamspaceId: loadingTeamspaceId.value,
    loadingTaskListId: loadingTaskListId.value,
    projectsWithLoadedLists: Array.from(projectsWithLoadedLists.value),
    teamspacesWithLoadedProjects: Array.from(teamspacesWithLoadedProjects.value)
  });
};

// Expose debug method to window for console access
if (typeof window !== 'undefined') {
  window.debugFetchStatus = debugFetchStatus;
}

// Method to toggle the list options menu
const toggleListOptions = (listId, event) => {
  if (event) {
    event.stopPropagation();
  }
  
  const menuId = `listOptionsMenu${listId}`;
  const menuElement = document.getElementById(menuId);
  
  if (menuElement) {
    // Check if we already have a dropdown instance
    let dropdownInstance = bootstrap.Dropdown.getInstance(menuElement);
    
    if (!dropdownInstance) {
      // Create a new dropdown instance
      const toggleButton = event.currentTarget;
      dropdownInstance = new bootstrap.Dropdown(toggleButton);
      listOptionsInstances.value[menuId] = dropdownInstance;
    }
    
    // Toggle the dropdown
    if (menuElement.classList.contains('show')) {
      dropdownInstance.hide();
    } else {
      dropdownInstance.show();
    }
  }
};

// Method to change list name
const changeListName = async (list, project, teamspace, event) => {
  if (event) {
    event.stopPropagation();
  }
  
  console.log('changeListName method called with list:', list);
  
  // Hide dropdown menu
  const menuId = `listOptionsMenu${list.id}`;
  const menuElement = document.getElementById(menuId);
  const dropdownInstance = bootstrap.Dropdown.getInstance(menuElement);
  if (dropdownInstance) {
    dropdownInstance.hide();
  }
  
  // Prompt user for new name
  const newName = prompt('Enter new list name:', list.name);
  
  // Check if user canceled or entered an empty name
  if (!newName || newName.trim() === '' || newName === list.name) {
    return;
  }
  
  console.log('Changing list name to:', newName);
  
  try {
    // Create a loading toast
    const loadingToast = createToast('Updating list...', {
      position: 'top-right',
      type: 'info',
      showIcon: true,
      swipeClose: false,
      timeout: -1
    });
    
    // Update the list with new name
    const updatedListData = { 
      name: newName.trim(),
      description: list.description || ''
    };
    
    console.log('Calling listStore.updateList with:', list.id, updatedListData);
    
    // Use the store method to update via API
    const result = await listStore.updateList(list.id, updatedListData);
    
    loadingToast.close();
    console.log('List name updated successfully');
    
    createToast('List renamed successfully!', {
      position: 'top-right',
      type: 'success',
      timeout: 3000
    });
  } catch (error) {
    console.error('Error updating list name:', error);
    
    createToast(`Failed to update list name: ${error.message || 'Unknown error'}`, {
      position: 'top-right',
      type: 'danger',
      timeout: 5000
    });
  }
};

// Method to delete a list
const deleteList = async (list, project, teamspace, event) => {
  if (event) {
    event.stopPropagation();
  }
  
  console.log('deleteList method called with list:', list);
  
  // Hide dropdown menu
  const menuId = `listOptionsMenu${list.id}`;
  const menuElement = document.getElementById(menuId);
  const dropdownInstance = bootstrap.Dropdown.getInstance(menuElement);
  if (dropdownInstance) {
    dropdownInstance.hide();
  }
  
  // Confirm deletion
  if (!confirm(`Are you sure you want to delete the list "${list.name}"? This action cannot be undone.`)) {
    console.log('User cancelled list deletion');
    return;
  }
  
  const listId = list.id;
  console.log('Deleting list with ID:', listId);
  
  try {
    // Create a loading toast
    const loadingToast = createToast('Deleting list...', {
      position: 'top-right',
      type: 'info',
      showIcon: true,
      swipeClose: false,
      timeout: -1
    });
    
    // Call API to delete the list
    console.log('Calling listStore.deleteList with ID:', listId);
    await listStore.deleteList(listId);
    
    loadingToast.close();
    console.log('List deleted successfully');
    
    createToast('List deleted successfully!', {
      position: 'top-right',
      type: 'success',
      timeout: 3000
    });
    
    // Navigate to project page if we're on a page related to this list
    if (selectedItem.value.type === 'list' && selectedItem.value.id === list.id) {
      router.push(`/${encodeURIComponent(teamspace.name)}/${encodeURIComponent(project.name)}`);
      navigationStore.clearList();
    }
  } catch (error) {
    console.error('Error deleting list:', error);
    
    createToast(`Failed to delete list: ${error.message || 'Unknown error'}`, {
      position: 'top-right',
      type: 'danger',
      timeout: 5000
    });
  }
};
</script>

<style>
/* Global styles - not scoped to allow reuse */
:root {
  --app-font-size: 0.875rem;
  --app-active-color: #e5e6ff;
}

.app-text-size {
  font-size: var(--app-font-size) !important;
}

/* Scoped styles */
.small {
  font-size: var(--app-font-size);
}

.accordion-button {
  padding: 0.4rem 0.75rem;
  border: none !important;
  border-radius: 4px !important;
  background-color: transparent !important;
}

.accordion-button::after {
  display: none !important; /* Hide the dropdown arrow */
}

.accordion-button:not(.collapsed) {
  box-shadow: none !important;
  background-color: transparent !important;
  border-radius: 4px !important;
}

.btn-link {
  text-decoration: none;
}

.btn-link:hover {
  opacity: 0.8;
}

/* Remove existing accordion-body padding */
.accordion-body {
  padding: 0;
  background-color: transparent !important;
}

/* Add consistent indentation for each level */
.accordion-item {
  margin-bottom: 0.5rem;
  border: none !important;
  background-color: transparent !important;
}

/* Teamspace level - no left margin */
#teamspacesAccordion > .accordion-item {
  margin-left: 0;
}

/* Project level - indent from teamspace */
#teamspacesAccordion .accordion-body .accordion-item {
  margin-left: 1rem;
  padding-left: 0.5rem;
}

/* List level - indent from project */
#teamspacesAccordion .accordion-body .accordion-body > div {
  margin-left: 1.25rem;
  padding-left: 0.5rem;
  border-left: 1px solid #e9ecef;
}

/* Style for active/selected items */
.active-item {
  background-color: var(--app-active-color) !important;
  border-radius: 4px !important;
}

/* Override accordion button styles when active */
.accordion-button.active-item {
  background-color: var(--app-active-color) !important;
  box-shadow: none !important;
  border-radius: 4px !important;
}

/* Ensure text and icons stay dark when selected */
.active-item span,
.active-item button,
.active-item i {
  color: #212529 !important;
}

.bi {
  font-size: 1rem;
}

.workspace-section {
  height: calc(100% - 260px);
  overflow-y: auto;
}

.invite-section {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  background: var(--bs-light);
}

.sidebar {
  transition: width 0.3s ease;
  overflow-x: hidden;
}

.sidebar.collapsed {
  width: 60px !important;
}

.sidebar.collapsed .sidebar-text {
  display: none;
}

.sidebar.collapsed .workspace-section {
  height: calc(100% - 100px);
}

.sidebar.collapsed .invite-section {
  display: flex;
  justify-content: center;
  padding: 0.5rem !important;
}

.sidebar.collapsed .accordion-button {
  padding: 0.5rem !important;
  justify-content: center;
}

.sidebar.collapsed .accordion-button::after,
.sidebar.collapsed .accordion-body,
.sidebar.collapsed .btn-link {
  display: none !important;
}

.sidebar.collapsed .nav-link {
  padding: 0.5rem !important;
  justify-content: center;
}

.sidebar.collapsed .bi {
  margin: 0 !important;
  font-size: 1.2rem;
}

.collapse-btn {
  opacity: 0.6;
  transition: opacity 0.2s;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
}

.collapse-btn:hover {
  opacity: 1;
}

.collapse-btn i {
  font-size: 1rem;
}

.dropdown-menu {
  margin-top: 0.5rem;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  border-radius: 4px;
}

.dropdown-header {
  padding: 0.75rem 1rem;
}

.dropdown-item {
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
}

.dropdown-item i {
  font-size: 1rem;
}

.dropdown-divider {
  margin: 0.5rem 0;
}

.sidebar.collapsed .dropdown-menu {
  position: fixed !important;
  left: 60px !important;
  top: 48px !important;
  width: 240px !important;
}

.active-workspace-indicator {
  color: var(--app-primary-color);
}

.dropdown-item:hover {
  background-color: var(--app-active-color);
}

.dropdown-item.active,
.dropdown-item:active {
  background-color: var(--app-active-color);
  color: inherit;
}

.new-workspace-item {
  color: var(--app-primary-color) !important;
}

.new-workspace-item:hover {
  color: inherit !important;
}

/* Add spin animation for refresh icon */
@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.spin {
  animation: spin 1s linear infinite;
}

/* Team options menu styles */
.team-options-menu,
.project-options-menu,
.list-options-menu {
  min-width: 200px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  border-radius: 4px;
  padding: 0.5rem 0;
  z-index: 1050;
}

.team-options-menu .dropdown-item,
.project-options-menu .dropdown-item,
.list-options-menu .dropdown-item {
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
}

.team-options-menu .dropdown-item:hover,
.project-options-menu .dropdown-item:hover,
.list-options-menu .dropdown-item:hover {
  background-color: var(--app-active-color);
}

.dropdown-menu.team-options-menu,
.dropdown-menu.project-options-menu,
.dropdown-menu.list-options-menu {
  margin-top: 0;
}

.team-options-menu .dropdown-item.text-danger:hover,
.project-options-menu .dropdown-item.text-danger:hover,
.list-options-menu .dropdown-item.text-danger:hover {
  background-color: rgba(220, 53, 69, 0.1);
}

.team-options-menu .dropdown-divider,
.project-options-menu .dropdown-divider,
.list-options-menu .dropdown-divider {
  margin: 0.25rem 0;
}

/* Color picker modal styles */
.color-option {
  transition: transform 0.2s, box-shadow 0.2s;
}

.color-option:hover {
  transform: scale(1.1);
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

/* Add custom spinner style for sidebar */
.sidebar-spinner {
  width: 1rem;
  height: 1rem;
  color: #6c757d;
}

/* Dropdown arrow styling */
.sidebar-dropdown-arrow {
  font-size: 0.75rem;
  color: #000000;
  transition: transform 0.2s ease;
}

.accordion-button:not(.collapsed) .sidebar-dropdown-arrow {
  transform: rotate(180deg);
}

/* Position the arrow slightly better in the header */
.accordion-header .sidebar-dropdown-arrow {
  opacity: 1;
}

.accordion-header:hover .sidebar-dropdown-arrow {
  opacity: 1;
}

/* Make three dots icon appear lighter/thinner */
.bi-three-dots {
  font-size: 0.9rem;
  opacity: 0.8;
}

.sidebar-team-icon {
  color: #6c757d;
  font-size: 16px;
}
</style>
