<script setup>
import Sidebar from './components/Sidebar.vue'
import Navbar from './components/Navbar.vue'
import BreadcrumbNav from './components/BreadcrumbNav.vue'
import NewProjectModal from './components/NewProjectModal.vue'
import NewListModal from './components/NewListModal.vue'
import NewTaskModal from './components/NewTaskModal.vue'
import NewTeamspaceModal from './components/NewTeamspaceModal.vue'
import NewWorkspaceModal from './components/NewWorkspaceModal.vue'
import InviteMembersModal from './components/InviteMembersModal.vue'
import TaskDetailsModal from './components/TaskDetailsModal.vue'
import EditWorkspaceDrawer from './components/EditWorkspaceDrawer.vue'
import MembersDrawer from './components/MembersDrawer.vue'
import { useRoute, useRouter } from 'vue-router'
import { ref, onMounted, onUnmounted, computed, provide } from 'vue'
import { useSidebarStore } from './stores/sidebarStore'
import { useWorkspaceStore } from './stores/workspaceStore'
import { extractInvitationParams, processPendingInvitation } from './utils/inviteHandler'

const route = useRoute()
const router = useRouter()
const isSidebarCollapsed = ref(false)
const sidebarStore = useSidebarStore()
const workspaceStore = useWorkspaceStore()

// Create refs for all modal components
const newProjectModalRef = ref(null)
const newListModalRef = ref(null)
const newTaskModalRef = ref(null)
const newTeamspaceModalRef = ref(null)
const newWorkspaceModalRef = ref(null)
const inviteModalRef = ref(null)
const taskDetailsModalRef = ref(null)
const editWorkspaceDrawerRef = ref(null)
const membersDrawerRef = ref(null)

// Create a global modals object to provide to all components
const modals = {
  showNewProject: () => newProjectModalRef.value?.show(),
  showNewList: () => newListModalRef.value?.show(),
  showNewTask: () => newTaskModalRef.value?.show(),
  showNewTeamspace: () => newTeamspaceModalRef.value?.show(),
  showNewWorkspace: () => newWorkspaceModalRef.value?.show(),
  showInviteMembers: () => inviteModalRef.value?.show(),
  showTaskDetails: (taskId) => taskDetailsModalRef.value?.show(taskId),
  showEditWorkspace: (workspace) => {
    if (editWorkspaceDrawerRef.value) {
      editWorkspaceDrawerRef.value.workspace = workspace;
      editWorkspaceDrawerRef.value.isOpen = true;
    } else {
      console.error('editWorkspaceDrawerRef is not available');
    }
  },
  showMembers: (workspace) => {
    if (membersDrawerRef.value) {
      membersDrawerRef.value.workspace = workspace;
      membersDrawerRef.value.isOpen = true;
    } else {
      console.error('membersDrawerRef is not available');
    }
  }
}

// Function to handle workspace invitation URLs
const handleWorkspaceInvitation = async () => {
  // Check if current URL is an invitation link
  const currentUrl = window.location.href
  const inviteParams = extractInvitationParams(currentUrl)
  
  if (inviteParams) {
    console.log('Detected workspace invitation URL:', inviteParams)
    
    // Store invitation details for post-auth processing
    localStorage.setItem('pendingInvitation', JSON.stringify(inviteParams))
    
    // Check if user is authenticated
    const isAuthenticated = !!localStorage.getItem('authUser') || 
                           !!localStorage.getItem('authToken')
    
    if (isAuthenticated) {
      // User is logged in, process invitation right away
      try {
        await workspaceStore.acceptWorkspaceInvitation(inviteParams.token)
        localStorage.removeItem('pendingInvitation')
        router.push({ name: 'home' })
      } catch (error) {
        console.error('Failed to process invitation:', error)
      }
    } else {
      // User is not logged in, redirect to auth page
      router.push({ name: 'auth' })
    }
    return true
  }
  
  // If we're not handling an invite URL, check for stored invites
  return await processPendingInvitation()
}

// Provide the modals object to all child components
provide('modals', modals)

const handleSidebarToggle = (event) => {
  isSidebarCollapsed.value = event.detail.isCollapsed
}

onMounted(async () => {
  window.addEventListener('sidebar-toggle', handleSidebarToggle)
  
  // Check for and handle workspace invitations
  await handleWorkspaceInvitation()
})

onUnmounted(() => {
  window.removeEventListener('sidebar-toggle', handleSidebarToggle)
})

const mainContentStyle = computed(() => ({
  marginLeft: sidebarStore.isCollapsed ? '60px' : '250px',
  transition: 'margin-left 0.3s ease',
  width: `calc(100% - ${sidebarStore.isCollapsed ? '60px' : '250px'})`,
  position: 'fixed',
  right: 0
}))
</script>

<template>
  <div class="app-container" :class="{ 'auth-page': route.name === 'auth' || route.name === 'forgot-password' || route.name === 'reset-password' || route.name === 'create-workspace' }">
    <template v-if="route.name !== 'auth' && route.name !== 'forgot-password' && route.name !== 'reset-password' && route.name !== 'create-workspace'">
      <Navbar />
      <Sidebar />
      <main :style="mainContentStyle">
        <BreadcrumbNav />
        <div class="content-area">
          <router-view></router-view>
        </div>
      </main>
      <!-- Modals at root level -->
      <NewProjectModal ref="newProjectModalRef" />
      <NewListModal ref="newListModalRef" />
      <NewTaskModal ref="newTaskModalRef" />
      <NewTeamspaceModal ref="newTeamspaceModalRef" />
      <NewWorkspaceModal ref="newWorkspaceModalRef" />
      <InviteMembersModal ref="inviteModalRef" />
      <TaskDetailsModal ref="taskDetailsModalRef" />
      <EditWorkspaceDrawer 
        ref="editWorkspaceDrawerRef"
        @close="() => {}"
      />
      <MembersDrawer 
        ref="membersDrawerRef"
        @close="() => {}"
      />
    </template>
    <template v-else>
      <router-view></router-view>
    </template>
  </div>
</template>

<style>
@import 'bootstrap/dist/css/bootstrap.min.css';
@import 'bootstrap-icons/font/bootstrap-icons.css';

.app-container {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  padding-top: 48px; /* Match navbar height */
}

.app-container.auth-page {
  padding-top: 0;
  display: block;
}

main {
  flex: 1;
  position: relative;
  background-color: #fff;
  transition: margin-left 0.3s ease;
}

.content-area {
  padding: 1.5rem;
  background-color: white;
  min-height: calc(100vh - 96px); /* Subtract navbar + breadcrumb height */
  width: 100%;
}

/* Set white background for all route components */
.content-area > *:not(.auth-page) {
  background-color: white;
  border-radius: 8px;
  width: 100%;
}

/* Global modal styles */
.modal-backdrop {
  --bs-backdrop-bg: #000000;
  --bs-backdrop-opacity: 0.2;
  backdrop-filter: blur(1px);
}

.modal-backdrop.show {
  opacity: var(--bs-backdrop-opacity);
}

.modal {
  z-index: 2000;
}

.modal-dialog {
  z-index: 2010;
}

.modal-content {
  box-shadow: 0 0.5rem 2rem rgba(0, 0, 0, 0.15);
}
</style>
