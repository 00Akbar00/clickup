<script setup>
import Sidebar from './components/Sidebar.vue'
import Navbar from './components/Navbar.vue'
import BreadcrumbNav from './components/BreadcrumbNav.vue'
import NewProjectModal from './components/NewProjectModal.vue'
import NewListModal from './components/NewListModal.vue'
import NewTaskModal from './components/NewTaskModal.vue'
import NewTeamspaceModal from './components/NewTeamspaceModal.vue'
import InviteMembersModal from './components/InviteMembersModal.vue'
import { useRoute } from 'vue-router'
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { useSidebarStore } from './stores/sidebarStore'

const route = useRoute()
const isSidebarCollapsed = ref(false)
const sidebarStore = useSidebarStore()

const handleSidebarToggle = (event) => {
  isSidebarCollapsed.value = event.detail.isCollapsed
}

onMounted(() => {
  window.addEventListener('sidebar-toggle', handleSidebarToggle)
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
  <div class="app-container" :class="{ 'auth-page': route.name === 'auth' || route.name === 'forgot-password' || route.name === 'reset-password'   }">
    <template v-if="route.name !== 'auth' && route.name !== 'forgot-password' && route.name !== 'reset-password'">
      <Navbar />
      <Sidebar />
      <main :style="mainContentStyle">
        <BreadcrumbNav />
        <div class="content-area">
          <router-view></router-view>
        </div>
      </main>
      <!-- Modals at root level -->
      <NewProjectModal ref="newProjectModal" />
      <NewListModal ref="newListModal" />
      <NewTaskModal />
      <NewTeamspaceModal />
      <InviteMembersModal />
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
