<template>
  <nav class="breadcrumb-nav px-4 border-bottom">
    <!-- Sidebar Toggle Button -->
    <button 
      v-if="showToggle" 
      class="btn btn-link text-dark p-0 me-3 toggle-btn"
      @click="toggleSidebar"
    >
      <i class="bi bi-list"></i>
    </button>

    <!-- Home page -->
    <template v-if="route.name === 'home'">
      <div class="breadcrumb-item">
        <i class="bi bi-house-door me-2"></i>
        <span>Home</span>
      </div>
    </template>

    <!-- Inbox page -->
    <template v-else-if="isInboxPage">
      <div class="breadcrumb-item">
        <i class="bi bi-inbox me-2"></i>
        <span>Inbox</span>
      </div>
      <div class="ms-4 inbox-tabs">
        <button 
          v-for="tab in inboxTabs" 
          :key="tab.id"
          class="tab-button"
          :class="{ active: inboxStore.activeTab === tab.id }"
          @click="handleTabChange(tab)"
        >
          {{ tab.label }}
        </button>
      </div>
    </template>

    <!-- Teamspace/Project/List pages -->
    <template v-else>
      <div class="breadcrumb-item" v-if="activeTeamspace">
        <i class="bi bi-building me-2"></i>
        <router-link :to="{ name: 'teamspace', params: { id: activeTeamspace.id }}">
          {{ activeTeamspace.name }}
        </router-link>
      </div>

      <div class="breadcrumb-item ms-3" v-if="activeProject">
        <i class="bi bi-folder me-2"></i>
        <router-link :to="{ name: 'project', params: { id: activeProject.id }}">
          {{ activeProject.name }}
        </router-link>
      </div>

      <div class="breadcrumb-item ms-3" v-if="activeList">
        <i class="bi bi-list-ul me-2"></i>
        <router-link :to="{ name: 'list', params: { id: activeList.id }}">
          {{ activeList.name }}
        </router-link>
      </div>
    </template>
  </nav>
</template>

<script setup>
import { ref, onMounted, computed, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import { useNavigationStore } from '../stores/navigationStore'
import { useInboxStore } from '../stores/inboxStore'
import { useSidebarStore } from '../stores/sidebarStore'

const route = useRoute()
const navigationStore = useNavigationStore()
const inboxStore = useInboxStore()
const sidebarStore = useSidebarStore()

const showToggle = computed(() => sidebarStore.isCollapsed)

// Listen for sidebar collapse/expand events to sync state
const handleSidebarToggle = (event) => {
  sidebarStore.setSidebarState(event.detail.isCollapsed)
}

onMounted(() => {
  window.addEventListener('sidebar-toggle', handleSidebarToggle)
})

onUnmounted(() => {
  window.removeEventListener('sidebar-toggle', handleSidebarToggle)
})

const toggleSidebar = () => {
  sidebarStore.setSidebarState(false) // Always expand when clicking breadcrumb button
}

// Computed properties for active items
const activeTeamspace = computed(() => navigationStore.activeTeamspace)
const activeProject = computed(() => navigationStore.activeProject)
const activeList = computed(() => navigationStore.activeList)

// Inbox related
const isInboxPage = computed(() => route.name === 'inbox')

const inboxTabs = [
  {
    id: 'Important',
    label: 'Important',
    description: 'High priority tasks and notifications'
  },
  {
    id: 'Updates',
    label: 'Updates',
    description: 'Recent changes and activity in your workspace'
  },
  {
    id: 'Messages',
    label: 'Messages',
    description: 'Direct messages and mentions'
  }
]

const handleTabChange = (tab) => {
  inboxStore.setActiveTab(tab.id)
}

const handleItemClick = (item) => {
  switch (item.type) {
    case 'teamspace':
      navigationStore.setTeamspace(item)
      break
    case 'project':
      navigationStore.setProject(item)
      break
    case 'list':
      navigationStore.setList(item)
      break
  }
}
</script>

<style scoped>
.breadcrumb-nav {
  height: 48px;
  display: flex;
  align-items: center;
  background-color: white;
  transition: margin-left 0.3s ease;
}

.breadcrumb {
  --bs-breadcrumb-divider: '/';
  padding: 0.25rem 0;
}

.breadcrumb-item {
  display: flex;
  align-items: center;
  font-weight: 500;
}

.breadcrumb-item a {
  color: inherit;
  text-decoration: none;
}

.breadcrumb-item a:hover {
  opacity: 0.8;
}

.breadcrumb-item + .breadcrumb-item::before {
  font-size: 0.75rem;
  color: #6c757d;
  padding: 0 0.75rem;
}

.small {
  font-size: 0.875rem;
}

.cursor-pointer {
  cursor: pointer;
}

.cursor-pointer:hover {
  opacity: 0.8;
}

/* Inbox Tabs Styles */
.inbox-tabs {
  display: flex;
  gap: 1rem;
}

.tab-button {
  border: none;
  background: none;
  padding: 0.5rem 1rem;
  cursor: pointer;
  transition: all 0.2s;
  position: relative;
  color: #6c757d;
}

.tab-button:hover {
  color: #2c3e50;
}

.tab-button.active {
  color: #2c3e50;
  font-weight: 500;
}

.tab-button.active::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 2px;
  background-color: #2c3e50;
}

.toggle-btn {
  display: flex;
  align-items: center;
  opacity: 0.6;
  transition: opacity 0.2s;
  cursor: pointer;
}

.toggle-btn:hover {
  opacity: 1;
}

.toggle-btn i {
  font-size: 1.25rem;
}
</style> 