<template>
  <div class="sidebar bg-white border-end" style="width: 250px; height: calc(100vh - 48px); position: fixed; left: 0; top: 48px;">
    <!-- User Profile Section -->
    <div class="p-2 border-bottom">
      <div class="d-flex align-items-center">
        <div class="rounded-circle bg-secondary" style="width: 32px; height: 32px;"></div>
        <h6 class="mb-0 ms-2 app-text">Haris Hassan</h6>
      </div>
    </div>

    <!-- Navigation Section -->
    <div class="p-2 border-bottom">
      <div class="nav-item mb-1">
        <router-link 
          to="/" 
          class="nav-link d-flex align-items-center text-dark text-decoration-none py-1 px-2 rounded app-text"
          :class="{ 'active-item': selectedItem.type === 'home' }"
          @click="handleHomeClick"
        >
          <i class="bi bi-house-door me-2"></i>
          <span>Home</span>
        </router-link>
      </div>
      <div class="nav-item">
        <router-link 
          to="/inbox" 
          class="nav-link d-flex align-items-center text-dark text-decoration-none py-1 px-2 rounded app-text"
          :class="{ 'active-item': selectedItem.type === 'inbox' }"
          @click="handleInboxClick"
        >
          <i class="bi bi-inbox me-2"></i>
          <span>Inbox</span>
        </router-link>
      </div>
    </div>

    <!-- Workspace Navigation -->
    <div class="p-2">
      <!-- Main Accordion for Teamspaces -->
      <div class="accordion" id="teamspacesAccordion">
        <!-- Teamspaces -->
        <div v-for="teamspace in teamspaces" :key="teamspace.id" class="accordion-item border-0 mb-2">
          <h2 class="accordion-header">
            <button 
              class="accordion-button collapsed py-2 app-text" 
              :class="{ 'active-item': selectedItem.type === 'teamspace' && selectedItem.id === teamspace.id }"
              type="button" 
              @click="handleTeamspaceClick(teamspace)"
            >
              <div class="d-flex justify-content-between align-items-center w-100 me-2">
                <div class="d-flex align-items-center">
                  <i class="bi bi-building me-2 text-secondary"></i>
                  <span>{{ teamspace.name }}</span>
                </div>
                <button class="btn btn-link text-dark p-0" @click.stop="showNewProjectModal(teamspace, $event)">
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
                          <i class="bi bi-folder me-2 text-secondary"></i>
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
                    :data-bs-parent="'#projectsAccordion' + teamspace.id"
                  >
                    <div class="accordion-body p-0">
                      <!-- Lists -->
                      <div v-for="list in project.lists" :key="list.id" class="ms-4 mb-2">
                        <router-link 
                          :to="{ name: 'list', params: { id: list.id }}" 
                          class="d-flex justify-content-between align-items-center text-dark text-decoration-none py-1 app-text px-2"
                          :class="{ 'active-item': selectedItem.type === 'list' && selectedItem.id === list.id }"
                          @click="handleListClick(list)"
                        >
                          <div class="d-flex align-items-center">
                            <i class="bi bi-list-ul me-2 text-secondary"></i>
                            <span>{{ list.name }}</span>
                          </div>
                          <button class="btn btn-link text-dark p-0 ms-2" @click.stop="showNewTaskModal(list)">
                            <i class="bi bi-plus"></i>
                          </button>
                        </router-link>
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
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useNavigationStore } from '../stores/navigationStore'
import { useWorkspaceStore } from '../stores/workspaceStore'
import { useRouter, useRoute } from 'vue-router'
import * as bootstrap from 'bootstrap'

const navigationStore = useNavigationStore()
const workspaceStore = useWorkspaceStore()
const router = useRouter()
const route = useRoute()
const collapseInstances = ref({})
const selectedItem = ref({
  type: null,
  id: null
})

// Make teamspaces reactive through computed
const teamspaces = computed(() => workspaceStore.teamspaces)

onMounted(() => {
  initializeCollapses()
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

const handleTeamspaceClick = (teamspace) => {
  selectedItem.value = {
    type: 'teamspace',
    id: teamspace.id
  }
  toggleTeamspace(teamspace.id)
  navigationStore.setTeamspace(teamspace)
}

const handleProjectClick = (teamspaceId, project) => {
  selectedItem.value = {
    type: 'project',
    id: project.id
  }
  toggleProject(teamspaceId, project.id)
  navigationStore.setProject(project)
}

const handleListClick = (list) => {
  selectedItem.value = {
    type: 'list',
    id: list.id
  }
  navigationStore.setList(list)
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

  // Log the context
  console.log(`Opening list modal for: ${currentTeamspace.name}/Project ${currentProject.name}`)
  
  window.activeProject = currentProject
  window.activeTeamspace = currentTeamspace
  
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
  // TODO: Implement task creation
  console.log('Create new task in list:', list.name)
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
  background-color: transparent;
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
}

/* Add consistent indentation for each level */
.accordion-item {
  margin-bottom: 0.5rem;
  border: none !important;
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
  color: #6366f1;
}

.bi-folder {
  color: #8b5cf6;
}

.bi-list-ul {
  color: #a855f7;
}

/* When item is active */
.active-item .bi {
  color: inherit;
}
</style> 