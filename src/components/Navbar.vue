<template>
  <nav class="navbar navbar-expand-lg fixed-top py-1" style="background-color: #2A216A;">
    <div class="container-fluid px-3">
      <!-- Left side - Brand -->
      <div class="d-flex align-items-center">
        <span class="navbar-brand text-white mb-0 h5">Click Flow</span>
      </div>

      <!-- Center - Search Bar -->
      <div class="d-flex align-items-center flex-grow-1 justify-content-center mx-4">
        <div class="input-group" style="max-width: 400px;">
          <input 
            type="text" 
            class="form-control search-input border-0" 
            placeholder="Search..."
            v-model="searchQuery"
            @input="handleSearch"
          >
          <button 
            class="btn search-button border-0 d-flex align-items-center"
            @click="handleSearch"
          >
            <i class="bi bi-search text-white"></i>
          </button>
        </div>
      </div>

      <!-- Right side - User Dropdown -->
      <div class="d-flex align-items-center">
        <div class="dropdown">
          <button 
            class="btn btn-link text-white p-1 dropdown-toggle d-flex align-items-center gap-2" 
            type="button" 
            id="userDropdown"
            aria-expanded="false"
            style="text-decoration: none;"
            ref="dropdownButton"
            @click="toggleDropdown"
          >
            <div class="d-flex align-items-center">
              <img
                v-if="profilePictureUrl"
                :src="profilePictureUrl"
                alt="Profile"
                class="rounded-circle"
                style="width: 28px; height: 28px; object-fit: cover"
              />
              <div
                v-else
                class="rounded-circle bg-white d-flex align-items-center justify-content-center"
                style="width: 28px; height: 28px;"
              >
                <i class="bi bi-person text-primary" style="font-size: 1rem;"></i>
              </div>
              <span class="ms-2 d-none d-md-inline">{{ userName }}</span>
            </div>
          </button>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
            <li>
              <div class="dropdown-header">
                <div class="d-flex align-items-center gap-2">
                  <img
                    v-if="profilePictureUrl"
                    :src="profilePictureUrl"
                    alt="Profile"
                    class="rounded-circle"
                    style="width: 32px; height: 32px; object-fit: cover"
                  />
                  <div
                    v-else
                    class="rounded-circle bg-secondary d-flex align-items-center justify-content-center"
                    style="width: 32px; height: 32px;"
                  >
                    <i class="bi bi-person text-white"></i>
                  </div>
                  <div>
                    <div class="fw-semibold">{{ userName }}</div>
                    <div class="text-muted small">{{ userEmail }}</div>
                  </div>
                </div>
              </div>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="#" @click="handleProfile"><i class="bi bi-person me-2"></i>Profile</a></li>
            <li><a class="dropdown-item" href="#" @click="handleSettings"><i class="bi bi-gear me-2"></i>Settings</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="#" @click="handleLogout"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
          </ul>
        </div>
      </div>
    </div>
  </nav>

  <!-- Profile Drawer -->
  <ProfileDrawer 
    :is-open="isProfileDrawerOpen"
    @close="closeProfileDrawer"
  />
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { Dropdown } from 'bootstrap'
import 'bootstrap/dist/css/bootstrap.min.css'
import 'bootstrap/dist/js/bootstrap.bundle.min.js'
import { useWorkspaceStore } from '../stores/workspaceStore'
import ProfileDrawer from './ProfileDrawer.vue'

const router = useRouter()
const workspaceStore = useWorkspaceStore()
const dropdownButton = ref(null)
let dropdownInstance = null
const isProfileDrawerOpen = ref(false)

// State
const searchQuery = ref('')
const userName = computed(() => {
  const userData = localStorage.getItem('authUser')
  if (userData) {
    try {
      const auth = JSON.parse(userData)
      // Handle different user data structures
      if (auth.full_name) {
        return auth.full_name
      } else if (auth.user?.full_name) {
        return auth.user.full_name
      } else if (auth.name) {
        return auth.name
      }
    } catch (error) {
      console.error('Error parsing user data:', error)
    }
  }
  return 'User'
})

const userEmail = computed(() => {
  const userData = localStorage.getItem('authUser')
  if (userData) {
    try {
      const auth = JSON.parse(userData)
      // Handle different user data structures
      if (auth.email) {
        return auth.email
      } else if (auth.user?.email) {
        return auth.user.email
      }
    } catch (error) {
      console.error('Error parsing user data:', error)
    }
  }
  return ''
})

const profilePictureUrl = computed(() => {
  const userData = localStorage.getItem('authUser')
  if (userData) {
    try {
      const auth = JSON.parse(userData)
      // Handle different user data structures
      if (auth.profile_picture_url) {
        return auth.profile_picture_url
      } else if (auth.user?.profile_picture_url) {
        return auth.user.profile_picture_url
      } else if (auth.avatar) {
        return auth.avatar
      }
    } catch (error) {
      console.error('Error parsing user data:', error)
    }
  }
  return null
})
const authToken = computed(() => {
  const userData = localStorage.getItem('authUser')
  if (userData) {
    const auth = JSON.parse(userData)
    return auth.token || ''
  }
  return ''
})
// Methods
const handleSearch = () => {
  // TODO: Implement search functionality
  console.log('Searching for:', searchQuery.value)
}

const handleProfile = () => {
  if (dropdownInstance) {
    dropdownInstance.hide()
  }
  isProfileDrawerOpen.value = true
}

const closeProfileDrawer = () => {
  isProfileDrawerOpen.value = false
}

const handleSettings = () => {
  router.push('/settings')
}

const handleLogout = () => {
  // Clear workspace data from store
  workspaceStore.clearWorkspaces()
  
  // Remove auth data from localStorage
  localStorage.removeItem('authUser')
  localStorage.removeItem('has_workspace')
  localStorage.removeItem('authToken')
  
  // Redirect to auth page
  router.push('/auth')
}

const toggleDropdown = () => {
  if (dropdownInstance) {
    dropdownInstance.toggle()
  }
}

onMounted(() => {
  // Initialize Bootstrap dropdown
  if (dropdownButton.value) {
    dropdownInstance = new Dropdown(dropdownButton.value)
  }
})
</script>

<style scoped>
.navbar {
  height: 48px;
  z-index: 1030;
}

.navbar-brand {
  font-size: 1rem;
  font-weight: 500;
}

.input-group {
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
  border-radius: 4px;
  overflow: hidden;
  height: 30px;
}

.search-input {
  height: 30px;
  padding: 0.25rem 0.75rem;
  font-size: 0.875rem;
  background-color: #605990;
  color: white;
}

.search-input::placeholder {
  color: rgba(255, 255, 255, 0.7);
}

.search-input:focus {
  background-color: #605990;
  color: white;
  box-shadow: none;
}

.search-button {
  height: 30px;
  padding: 0 0.75rem;
  background-color: #605990;
}

.search-button:hover {
  background-color: #6c64a1;
}

.dropdown-toggle::after {
  margin-left: 0.5rem;
}

.bi-person-circle {
  font-size: 1.4rem;
}

.bi-search {
  font-size: 0.875rem;
}

.dropdown-menu {
  margin-top: 0.5rem;
  min-width: 240px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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
</style> 