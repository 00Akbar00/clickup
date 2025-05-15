<template>
  <div class="sidebar bg-light border-end" :class="{ 'collapsed': isCollapsed }" :style="sidebarStyle">
    <!-- User Profile Section -->
    <div class="p-2 border-bottom d-flex justify-content-between align-items-center">
      <!-- <h1>{{userName}}</h1> -->
      <div class="d-flex align-items-center overflow-hidden">
        <!-- <div class="rounded-circle bg-secondary flex-shrink-0" style="width: 32px; height: 32px;"></div> -->
        <img

v-if="profilePictureUrl"

:src="profilePictureUrl"

alt="Profile"

class="rounded-circle"

style="width: 32px; height: 32px; object-fit: cover;"

/>

<div

v-else

class="rounded-circle bg-secondary"

style="width: 32px; height: 32px;"

></div>
        <h6 class="mb-0 ms-2 app-text sidebar-text text-truncate">{{ userName }}</h6>
      </div>
      <button class="btn btn-link text-dark p-0 collapse-btn flex-shrink-0" @click="toggleSidebar">
        <i class="bi" :class="isCollapsed ? 'bi-chevron-right' : 'bi-chevron-left'"></i>
      </button>
    </div>

    <!-- Navigation Section -->
    <div class="p-2 border-bottom">
      <div class="nav-item mb-1">
        <router-link 
          to="/" 
          class="nav-link d-flex align-items-center text-dark text-decoration-none py-1 px-2 rounded app-text"
          :class="{ 'active-item': selectedItem.type === 'home', 'justify-content-center': isCollapsed }"
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
          :class="{ 'active-item': selectedItem.type === 'inbox', 'justify-content-center': isCollapsed }"
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
          :class="{ 'active-item': selectedItem.type === 'everything', 'justify-content-center': isCollapsed }"
          @click="handleEverythingClick"
        >
          <i class="bi bi-grid-3x3-gap" :class="isCollapsed ? '' : 'me-2'"></i>
          <span class="sidebar-text">Everything</span>
        </router-link>
      </div>

      <!-- Main Accordion for Teamspaces -->
      <div class="accordion" id="teamspacesAccordion">
        <!-- Teamspaces -->
        <div v-for="teamspace in teamspaces" :key="teamspace.id" class="accordion-item border-0 mb-2">
          <h2 class="accordion-header">
            <button 
              class="accordion-button collapsed py-2 app-text" 
              :class="{ 
                'active-item': selectedItem.type === 'teamspace' && selectedItem.id === teamspace.id,
                'justify-content-center': isCollapsed
              }"
              type="button" 
              @click="handleTeamspaceClick(teamspace)"
            >
              <div class="d-flex justify-content-between align-items-center w-100 me-2">
                <div class="d-flex align-items-center">
                  <i class="bi bi-building" :class="isCollapsed ? '' : 'me-2'"></i>
                  <span class="sidebar-text">{{ teamspace.name }}</span>
                </div>
                <button class="btn btn-link text-dark p-0 sidebar-text" @click.stop="showNewProjectModal(teamspace, $event)">
                  <i class="bi bi-plus"></i>
                </button>
              </div>
            </button>
          </h2>
          <div 
            :id="'teamspaceCollapse' + teamspace.id" 
            class="accordion-collapse collapse" 
            data-bs-parent="#teamspacesAccordion">
            <div class="accordion-body p-0">
              <!-- Projects Accordion -->
              <div class="accordion" :id="'projectsAccordion' + teamspace.id">
                <!-- Projects -->
                <div v-for="project in teamspace.projects" :key="project.id" class="accordion-item border-0 ms-3 mb-2">
                  <h2 class="accordion-header">
                    <button 
                      class="accordion-button collapsed py-2 app-text" 
                      :class="{ 'active-item': selectedItem.type === 'project' && selectedItem.id === project.id }"
                      type="button" 
                      @click="handleProjectClick(teamspace.id, project)"
                    >
                      <div class="d-flex justify-content-between align-items-center w-100 me-2">
                        <div class="d-flex align-items-center">
                          <i class="bi bi-folder me-2"></i>
                          <span>{{ project.name }}</span>
                        </div>
                        <button class="btn btn-link text-dark p-0" @click.stop="showNewListModal(project, $event, teamspace)">
                          <i class="bi bi-plus"></i>
                        </button>
                      </div>
                    </button>
                  </h2>
                  <div 
                    :id="'projectCollapse' + project.id" 
                    class="accordion-collapse collapse" 
                    :data-bs-parent="'#projectsAccordion' + teamspace.id">
                    <div class="accordion-body p-0">
                      <!-- Lists -->
                      <div v-for="list in project.lists" :key="list.id" class="ms-4 mb-2">
                        <div 
                          class="d-flex justify-content-between align-items-center text-dark text-decoration-none py-1 app-text px-2"
                          :class="{ 'active-item': selectedItem.type === 'list' && selectedItem.id === list.id }"
                          @click="handleListClick(list, project, teamspace)"
                        >
                          <div class="d-flex align-items-center">
                            <i class="bi bi-list-ul me-2"></i>
                            <span>{{ list.name }}</span>
                          </div>
                          <button class="btn btn-link text-dark p-0 ms-2" @click.stop="showNewTaskModal(list)">
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
import { ref, onMounted, computed, watch } from 'vue'
import { useNavigationStore } from '../stores/navigationStore'
import { useWorkspaceStore } from '../stores/workspaceStore'
import { useRouter, useRoute } from 'vue-router'
import * as bootstrap from 'bootstrap'
import { useSidebarStore } from '../stores/sidebarStore'

const navigationStore = useNavigationStore()
const workspaceStore = useWorkspaceStore()
const router = useRouter()
const route = useRoute()
const collapseInstances = ref({})
const selectedItem = ref({
  type: null,
  id: null
})
const userName = ref('')
const profilePictureUrl = ref('')
const sidebarStore = useSidebarStore()

const isCollapsed = computed(() => sidebarStore.isCollapsed)

const sidebarStyle = computed(() => ({
  width: isCollapsed.value ? '60px' : '250px',
  height: 'calc(100vh - 48px)',
  position: 'fixed',
  left: '0',
  top: '48px'
}))

// Make teamspaces reactive through computed
const teamspaces = computed(() => workspaceStore.teamspaces)

onMounted(() => {
console.log("hi")

  initializeCollapses()
  // const user = JSON.parse(localStorage.getItem('authUser'))
  // if (user && user.fullName) {
  //   userName.value = user.fullName
  //   console.log(userName.value)
  // }
const user = JSON.parse(localStorage.getItem('authUser'))

if (user && user.full_name) {

userName.value = user.full_name
// profile_picture_url
profilePictureUrl.value = user.profile_picture_url || ''

// console.log("hi")
console.log('lksfmlmd',userName.value)

}
})

const handleHomeClick = () => {
  selectedItem.value = {
    type: 'home',
    id: null
  }
  navigationStore.clearActiveItems()
}

const handleInboxClick = () => {
  selectedItem.value = {
    type: 'inbox',
    id: null
  }
  navigationStore.clearActiveItems()
}

const handleEverythingClick = () => {
  selectedItem.value = {
    type: 'everything',
    id: null
  }
  navigationStore.clearActiveItems()
}

const handleTeamspaceClick = (teamspace) => {
  selectedItem.value = {
    type: 'teamspace',
    id: teamspace.id
  }
  toggleTeamspace(teamspace.id)
  navigationStore.setTeamspace(teamspace)
  router.push(`/teamspace/${teamspace.id}`)
}

const handleProjectClick = (teamspaceId, project) => {
  selectedItem.value = {
    type: 'project',
    id: project.id
  }
  toggleProject(teamspaceId, project.id)
  navigationStore.setProject(project)
  router.push(`/project/${project.id}`)
}

const isTeamspaceExpanded = (teamspaceId) => {
  const instance = collapseInstances.value['teamspace' + teamspaceId]
  if (instance) {
    return instance._element.classList.contains('show')
  }
  return false
}

const isProjectExpanded = (projectId) => {
  const instance = collapseInstances.value['project' + projectId]
  if (instance) {
    return instance._element.classList.contains('show')
  }
  return false
}

const expandTeamspace = (teamspaceId) => {
  const instance = collapseInstances.value['teamspace' + teamspaceId]
  if (instance && !isTeamspaceExpanded(teamspaceId)) {
    instance.show()
  }
}

const expandProject = (teamspaceId, projectId) => {
  const instance = collapseInstances.value['project' + projectId]
  if (instance && !isProjectExpanded(projectId)) {
    instance.show()
  }
}

const handleListClick = (list, project, teamspace) => {
  selectedItem.value = {
    type: 'list',
    id: list.id
  }
  
  // Set the full navigation context
  navigationStore.setTeamspace(teamspace)
  navigationStore.setProject(project)
  navigationStore.setList(list)
  
  // Ensure parent accordions are expanded
  expandTeamspace(teamspace.id)
  expandProject(teamspace.id, project.id)
  
  router.push(`/list/${list.id}`)
}

const showNewProjectModal = (teamspace, event) => {
  event.stopPropagation()
  window.activeTeamspace = teamspace
  const modal = document.querySelector('#newProjectModal')
  if (modal) {
    const projectModal = bootstrap.Modal.getInstance(modal)
    if (projectModal) {
      projectModal.show()
    } else {
      const newModal = new bootstrap.Modal(modal)
      newModal.show()
    }
  }
}

const showNewListModal = (project, event, teamspace) => {
  event.stopPropagation()
  
  // Find the current teamspace and project reference from the store
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

  // Set the active teamspace and project in the navigation store
  navigationStore.setTeamspace(currentTeamspace)
  navigationStore.setProject(currentProject)
  
  // Set the window variables for the modal
  window.activeProject = currentProject
  window.activeTeamspace = currentTeamspace
  
  // Log the context
  console.log(`Opening list modal for: ${currentTeamspace.name}/Project ${currentProject.name}`)
  
  const modal = document.querySelector('#newListModal')
  if (modal) {
    const listModal = bootstrap.Modal.getInstance(modal)
    if (listModal) {
      listModal.show()
    } else {
      const newModal = new bootstrap.Modal(modal)
      newModal.show()
    }
  }
}

const showNewTaskModal = (list) => {
  // Find the current teamspace and project reference from the store
  const currentTeamspace = workspaceStore.teamspaces.find(t => {
    return t.projects.some(p => p.lists.some(l => l.id === list.id))
  })
  
  if (!currentTeamspace) {
    console.error('Teamspace not found for list:', list.name)
    return
  }

  const currentProject = currentTeamspace.projects.find(p => 
    p.lists.some(l => l.id === list.id)
  )
  
  if (!currentProject) {
    console.error('Project not found for list:', list.name)
    return
  }

  // Set the active teamspace and project in the navigation store
  navigationStore.setTeamspace(currentTeamspace)
  navigationStore.setProject(currentProject)
  
  // Set the window variables for the modal
  window.activeList = list
  window.activeProject = currentProject
  window.activeTeamspace = currentTeamspace
  
  // Log the context
  console.log(`Opening task modal for: ${currentTeamspace.name}/Project ${currentProject.name}/List ${list.name}`)
  
  const modal = document.querySelector('#newTaskModal')
  if (modal) {
    const taskModal = bootstrap.Modal.getInstance(modal)
    if (taskModal) {
      taskModal.show()
    } else {
      const newModal = new bootstrap.Modal(modal)
      newModal.show()
    }
  }
}

const showNewTeamspaceModal = () => {
  const modal = document.querySelector('#newTeamspaceModal')
  if (modal) {
    const teamspaceModal = bootstrap.Modal.getInstance(modal)
    if (teamspaceModal) {
      teamspaceModal.show()
    } else {
      const newModal = new bootstrap.Modal(modal)
      newModal.show()
    }
  }
}

const showInviteMembersModal = () => {
  const modal = document.querySelector('#inviteMembersModal')
  if (modal) {
    const inviteModal = bootstrap.Modal.getInstance(modal)
    if (inviteModal) {
      inviteModal.show()
    } else {
      const newModal = new bootstrap.Modal(modal)
      newModal.show()
    }
  }
}

// Watch route changes to update selected item
watch(
  () => route.name,
  (newRoute) => {
    if (newRoute === 'home') {
      selectedItem.value = { type: 'home', id: null }
    } else if (newRoute === 'inbox') {
      selectedItem.value = { type: 'inbox', id: null }
    }
  }
)

// Watch for changes in teamspaces to initialize new collapse instances
watch(teamspaces, () => {
  initializeCollapses()
}, { deep: true })

const initializeCollapses = () => {
  // Clear existing instances to prevent memory leaks
  Object.values(collapseInstances.value).forEach(instance => {
    if (instance && typeof instance.dispose === 'function') {
      instance.dispose()
    }
  })
  collapseInstances.value = {}

  // Initialize new instances
  teamspaces.value.forEach(teamspace => {
    const teamspaceEl = document.getElementById('teamspaceCollapse' + teamspace.id)
    if (teamspaceEl) {
      collapseInstances.value['teamspace' + teamspace.id] = new bootstrap.Collapse(teamspaceEl, {
        toggle: false
      })
    }

    if (teamspace.projects) {
      teamspace.projects.forEach(project => {
        const projectEl = document.getElementById('projectCollapse' + project.id)
        if (projectEl) {
          collapseInstances.value['project' + project.id] = new bootstrap.Collapse(projectEl, {
            toggle: false
          })
        }
      })
    }
  })
}

const toggleTeamspace = (teamspaceId) => {
  const instance = collapseInstances.value['teamspace' + teamspaceId]
  if (instance) {
    instance.toggle()
  }
}

const toggleProject = (teamspaceId, projectId) => {
  const instance = collapseInstances.value['project' + projectId]
  if (instance) {
    instance.toggle()
  }
}

const toggleSidebar = () => {
  sidebarStore.toggleSidebar()
  // Dispatch event for BreadcrumbNav to update its state
  window.dispatchEvent(new CustomEvent('sidebar-toggle', { 
    detail: { isCollapsed: sidebarStore.isCollapsed } 
  }))
}
</script>

<style>
/* Global styles - not scoped to allow reuse */
:root {
  --app-font-size: 0.875rem;
  --app-active-color: #E5E6FF;
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
  border: none !important;
  box-shadow: none !important;
  background-color: transparent !important;
  border-radius: 4px !important;
}

.accordion-button:not(.collapsed)::after {
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

/* Add connecting lines styling */
.accordion-body {
  position: relative;
}

/* Ensure all items align properly */
.accordion-button > div,
.router-link-active,
router-link {
  width: 100%;
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

.accordion-button.active-item::after {
  background-image: var(--bs-accordion-btn-icon) !important;
}

/* Remove default accordion button background */
.accordion-button:not(.collapsed),
.accordion-button {
  background-color: transparent;
  border: none !important;
  border-radius: 4px !important;
}

.accordion-button:focus {
  box-shadow: none;
  border: none !important;
  background-color: transparent;
  border-radius: 4px !important;
}

/* Ensure text and icons stay dark when selected */
.active-item span,
.active-item button,
.active-item i {
  color: #212529 !important;
}

/* Add padding to router links */
router-link {
  padding: 0.4rem 0.5rem;
  margin: 0 -0.25rem;
}

.bi {
  font-size: 1rem;
}

.bi-building {
  color: inherit;
}

.bi-folder {
  color: inherit;
}

.bi-list-ul {
  color: inherit;
}

/* When item is active */
.active-item .bi {
  color: inherit;
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

/* Override any white backgrounds */
.accordion-item, 
.accordion-header,
.accordion-collapse {
  background-color: transparent !important;
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
</style> 