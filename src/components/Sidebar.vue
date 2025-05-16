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
              v-if="currentWorkspace?.logo_url"
              :src="currentWorkspace.logo_url"
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
          <li class="dropdown-header py-1">
            <small class="text-muted text-uppercase">Workspaces</small>
          </li>
          <li v-for="workspace in workspaces" :key="workspace.id">
            <a class="dropdown-item d-flex align-items-center justify-content-between" href="#" @click="switchWorkspace(workspace)">
              <div class="d-flex align-items-center">
                <div class="me-2">
                  <img
                    v-if="workspace.logo_url"
                    :src="workspace.logo_url"
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
              <span v-if="workspace.id === currentWorkspaceId" class="active-workspace-indicator">
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
              @click="handleTeamspaceClick(teamspace)"
            >
              <div
                class="d-flex justify-content-between align-items-center w-100 me-2"
              >
                <div class="d-flex align-items-center">
                  <i
                    class="bi bi-building"
                    :class="isCollapsed ? '' : 'me-2'"
                  ></i>
                  <span class="sidebar-text">{{ teamspace.name }}</span>
                </div>
                <button
                  class="btn btn-link text-dark p-0 sidebar-text"
                  @click.stop="showNewProjectModal(teamspace, $event)"
                >
                  <i class="bi bi-plus"></i>
                </button>
              </div>
            </button>
          </h2>
          <div
            :id="'teamspaceCollapse' + teamspace.id"
            class="accordion-collapse collapse"
            data-bs-parent="#teamspacesAccordion"
          >
            <div class="accordion-body p-0">
              <!-- Projects Accordion -->
              <div class="accordion" :id="'projectsAccordion' + teamspace.id">
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
                      @click="handleProjectClick(teamspace.id, project)"
                    >
                      <div
                        class="d-flex justify-content-between align-items-center w-100 me-2"
                      >
                        <div class="d-flex align-items-center">
                          <i class="bi bi-folder me-2"></i>
                          <span>{{ project.name }}</span>
                        </div>
                        <button
                          class="btn btn-link text-dark p-0"
                          @click.stop="
                            showNewListModal(project, $event, teamspace)
                          "
                        >
                          <i class="bi bi-plus"></i>
                        </button>
                      </div>
                    </button>
                  </h2>
                  <div
                    :id="'projectCollapse' + project.id"
                    class="accordion-collapse collapse"
                    :data-bs-parent="'#projectsAccordion' + teamspace.id"
                  >
                    <div class="accordion-body p-0">
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
                          @click="handleListClick(list, project, teamspace)"
                        >
                          <div class="d-flex align-items-center">
                            <i class="bi bi-list-ul me-2"></i>
                            <span>{{ list.name }}</span>
                          </div>
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
        <!-- New Teamspace Button -->
        <div class="nav-item mb-2">
          <button
            class="nav-link d-flex align-items-center text-dark text-decoration-none py-1 px-2 rounded app-text w-100"
            :class="{ 'justify-content-center': isCollapsed }"
            @click="showNewTeamspaceModal"
          >
            <i class="bi bi-plus" :class="isCollapsed ? '' : 'me-2'"></i>
            <span class="sidebar-text">New Teamspace</span>
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
import { ref, onMounted, computed, watch, nextTick } from "vue";
import { useNavigationStore } from "../stores/navigationStore";
import { useWorkspaceStore } from "../stores/workspaceStore";
import { useRouter, useRoute } from "vue-router";
import { Dropdown } from "bootstrap";
import * as bootstrap from "bootstrap";
import { useSidebarStore } from "../stores/sidebarStore";

const navigationStore = useNavigationStore();
const workspaceStore = useWorkspaceStore();
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

// Add workspace computed properties
const workspaces = computed(() => workspaceStore.workspaces);
const currentWorkspaceId = computed(() => workspaceStore.currentWorkspace?.id);

// Add computed property for current workspace name
const currentWorkspaceName = computed(() => workspaceStore.currentWorkspace?.name || 'No workspace selected');

onMounted(() => {
  // Initialize workspaces
  workspaceStore.initializeStore();

  // Initialize collapses
  initializeCollapses();

  // Get user data
  const user = JSON.parse(localStorage.getItem("authUser"));
  if (user && user.full_name) {
    userName.value = user.full_name;
    userEmail.value = user.email || '';
    profilePictureUrl.value = user.profile_picture_url || "";
  }

  // Initialize user dropdown
  if (sidebarDropdownButton.value) {
    userDropdownInstance = new Dropdown(sidebarDropdownButton.value);
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

const handleTeamspaceClick = (teamspace) => {
  selectedItem.value = {
    type: "teamspace",
    id: teamspace.id,
  };
  toggleTeamspace(teamspace.id);
  navigationStore.setTeamspace(teamspace);
  router.push(`/${encodeURIComponent(teamspace.name)}`);
};

const handleProjectClick = (teamspaceId, project) => {
  selectedItem.value = {
    type: "project",
    id: project.id,
  };
  toggleProject(teamspaceId, project.id);
  
  // Find the teamspace
  const teamspace = workspaceStore.teamspaces.find(t => t.id === teamspaceId);
  if (!teamspace) return;
  
  navigationStore.setTeamspace(teamspace);
  navigationStore.setProject(project);
  router.push(`/${encodeURIComponent(teamspace.name)}/${encodeURIComponent(project.name)}`);
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
  const instance = collapseInstances.value["teamspace" + teamspaceId];
  if (instance && !isTeamspaceExpanded(teamspaceId)) {
    instance.show();
  }
};

const expandProject = (teamspaceId, projectId) => {
  const instance = collapseInstances.value["project" + projectId];
  if (instance && !isProjectExpanded(projectId)) {
    instance.show();
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
  const modal = document.querySelector("#newProjectModal");
  if (modal) {
    const projectModal = bootstrap.Modal.getInstance(modal);
    if (projectModal) {
      projectModal.show();
    } else {
      const newModal = new bootstrap.Modal(modal);
      newModal.show();
    }
  }
};

const showNewListModal = (project, event, teamspace) => {
  event.stopPropagation();

  // Find the current teamspace and project reference from the store
  const currentTeamspace = workspaceStore.teamspaces.find(
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

  const modal = document.querySelector("#newListModal");
  if (modal) {
    const modalInstance = bootstrap.Modal.getOrCreateInstance(modal);
    modalInstance.show();
  }
};

const showNewTaskModal = (list, project, teamspace) => {
  // Find the current teamspace and project reference from the store
  const currentTeamspace = workspaceStore.teamspaces.find(
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

  const currentList = currentProject.lists.find(
    (l) => l.id === list.id
  );
  if (!currentList) {
    console.error("List not found in project:", project.name);
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

  const modal = document.querySelector("#newTaskModal");
  if (modal) {
    const modalInstance = bootstrap.Modal.getOrCreateInstance(modal);
    modalInstance.show();
  }
};

const showNewTeamspaceModal = () => {
  const modal = document.querySelector("#newTeamspaceModal");
  if (modal) {
    const teamspaceModal = bootstrap.Modal.getInstance(modal);
    if (teamspaceModal) {
      teamspaceModal.show();
    } else {
      const newModal = new bootstrap.Modal(modal);
      newModal.show();
    }
  }
};

const showInviteMembersModal = () => {
  const modal = document.querySelector("#inviteMembersModal");
  if (modal) {
    const inviteModal = bootstrap.Modal.getInstance(modal);
    if (inviteModal) {
      inviteModal.show();
    } else {
      const newModal = new bootstrap.Modal(modal);
      newModal.show();
    }
  }
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
      const teamspace = workspaceStore.teamspaces.find(
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

// Watch for changes in teamspaces to initialize new collapse instances
watch(
  () => workspaceStore.currentWorkspaceTeamspaces,
  () => {
    // Wait for DOM to update before initializing collapses
    nextTick(() => {
      initializeCollapses();
    });
  },
  { deep: true }
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
      const teamspaceEl = document.getElementById(
        "teamspaceCollapse" + teamspace.id
      );
      if (teamspaceEl) {
        collapseInstances.value["teamspace" + teamspace.id] =
          new bootstrap.Collapse(teamspaceEl, {
            toggle: false,
          });
      }

      if (teamspace.projects) {
        teamspace.projects.forEach((project) => {
          const projectEl = document.getElementById(
            "projectCollapse" + project.id
          );
          if (projectEl) {
            collapseInstances.value["project" + project.id] =
              new bootstrap.Collapse(projectEl, {
                toggle: false,
              });
          }
        });
      }
    });
  }, 100);
};

const toggleTeamspace = (teamspaceId) => {
  const instance = collapseInstances.value["teamspace" + teamspaceId];
  if (instance) {
    instance.toggle();
  }
};

const toggleProject = (teamspaceId, projectId) => {
  const instance = collapseInstances.value["project" + projectId];
  if (instance) {
    instance.toggle();
  }
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

// Add workspace methods
const switchWorkspace = (workspace) => {
  workspaceStore.setCurrentWorkspace(workspace);
  // Reset navigation state
  navigationStore.clearActiveItems();
  // Close the dropdown
  if (userDropdownInstance) {
    userDropdownInstance.hide();
  }
  // Navigate to home
  router.push('/');
};

const showNewWorkspaceModal = () => {
  const modal = document.querySelector("#newWorkspaceModal");
  if (modal) {
    const workspaceModal = bootstrap.Modal.getInstance(modal);
    if (workspaceModal) {
      workspaceModal.show();
    } else {
      const newModal = new bootstrap.Modal(modal);
      newModal.show();
    }
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
  border-radius: 4px !important;
  filter: none;
  width: 0.8rem;
  height: 0.8rem;
  background-size: 0.8rem;
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
</style>
