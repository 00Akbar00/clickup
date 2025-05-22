<!-- MembersModal Component -->
<template>
  <div>
    <!-- Backdrop -->
    <div 
      v-if="isDrawerOpen" 
      class="modal-backdrop"
      @click="close"
    ></div>

    <!-- Modal Slab -->
    <div 
      class="members-modal" 
      :class="{ 'modal-open': isDrawerOpen }"
    >
      <div class="modal-header border-bottom">
        <h5 class="mb-0">Workspace Members</h5>
        <button class="btn btn-link text-dark p-0" @click="close">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>

      <div class="modal-body">
        <!-- Workspace Info -->
        <div class="d-flex align-items-center mb-4">
          <div class="position-relative me-3">
            <img
              v-if="currentWorkspace && (currentWorkspace.logo_url || currentWorkspace.logo)"
              :src="getWorkspaceLogo(currentWorkspace)"
              alt="Workspace Logo"
              class="rounded workspace-logo-sm"
            />
            <div
              v-else
              class="rounded bg-secondary d-flex align-items-center justify-content-center workspace-logo-sm"
            >
              <i class="bi bi-building text-white" style="font-size: 1.5rem;"></i>
            </div>
          </div>
          <div>
            <h6 class="mb-1">{{ currentWorkspace?.name || 'Workspace' }}</h6>
            <small class="text-muted">{{ members.length }} {{ members.length === 1 ? 'member' : 'members' }}</small>
          </div>
        </div>

        <!-- Members List Section -->
        <div class="members-section mb-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0">Members</h6>
            <button class="btn btn-sm btn-outline-primary" @click="showInviteMembersModal">
              <i class="bi bi-person-plus me-1"></i> Invite
            </button>
          </div>

          <!-- Loading State -->
          <div v-if="isLoading" class="text-center py-4">
            <div class="spinner-border spinner-border-sm text-primary me-2"></div>
            <span>Loading members...</span>
          </div>

          <!-- Error State -->
          <div v-else-if="error" class="alert alert-danger py-2">
            <p class="mb-1">{{ error }}</p>
            <button class="btn btn-sm btn-outline-danger mt-2" @click="fetchMembers">
              Try Again
            </button>
          </div>

          <!-- Empty State -->
          <div v-else-if="members.length === 0" class="text-center py-4 text-muted">
            <i class="bi bi-people mb-2" style="font-size: 2rem;"></i>
            <p>No members found</p>
            <button class="btn btn-sm btn-outline-primary" @click="showInviteMembersModal">
              Invite Members
            </button>
          </div>

          <!-- Members List -->
          <div v-else class="list-group members-list">
            <div v-for="member in members" :key="member.id || member.user_id || member.workspace_member_id" class="list-group-item d-flex justify-content-between align-items-center border-0 border-bottom py-3">
              <div class="d-flex align-items-center">
                <!-- User Avatar -->
                <div class="position-relative me-3">
                  <img
                    v-if="member.avatar || member.profile_picture || member.profile_picture_url"
                    :src="member.avatar || member.profile_picture || member.profile_picture_url"
                    alt="User Avatar"
                    class="rounded-circle user-avatar"
                  />
                  <div
                    v-else
                    class="rounded-circle bg-primary d-flex align-items-center justify-content-center user-avatar"
                  >
                    <span class="text-white fw-bold">{{ getUserInitials(member.name || member.full_name || member.email) }}</span>
                  </div>
                </div>
                
                <!-- User Info -->
                <div>
                  <h6 class="mb-0">{{ member.name || member.full_name || 'User' }}</h6>
                  <small class="text-muted">{{ member.email }}</small>
                </div>
              </div>
              
              <!-- Member Role & Actions -->
              <div class="d-flex align-items-center">
                <span class="badge bg-light text-dark me-3 py-2 px-3">{{ formatRole(member.role) }}</span>
                <div v-if="(member.id || member.user_id) !== currentUserId && !member.isRemoving && !member.isUpdating" class="dropdown">
                  <button class="btn btn-link p-0 text-dark" type="button" @click.stop.prevent="toggleMemberOptions(member, $event)">
                    <i class="bi bi-three-dots-vertical"></i>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end member-options-menu" :class="{ 'show': activeMemberId === member.id || activeMemberId === member.user_id || activeMemberId === member.workspace_member_id }">
                    <li v-if="member.role !== 'owner'"><a class="dropdown-item" href="#" @click.prevent="changeRole(member, 'owner')">Make Owner</a></li>
                    <li v-if="member.role !== 'member'"><a class="dropdown-item" href="#" @click.prevent="changeRole(member, 'member')">Make Member</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="#" @click.prevent="removeMember(member)">Remove</a></li>
                  </ul>
                </div>
                <div v-else-if="member.isRemoving || member.isUpdating" class="me-3">
                  <div class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                  </div>
                </div>
                <span v-else class="text-muted small fst-italic ms-2">(You)</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, inject, onUnmounted } from 'vue'
import { useWorkspaceStore } from '../stores/workspaceStore'
import axios from 'axios'
import { createToast } from 'mosha-vue-toastify'
import 'mosha-vue-toastify/dist/style.css'

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: false,
    default: false
  },
  workspace: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['close'])
const workspaceStore = useWorkspaceStore()

// Get modals from the parent component
const modals = inject('modals', {
  showInviteMembers: () => console.warn('modals not provided')
})

// Internal state for drawer open status 
const isDrawerOpen = ref(false)
const currentWorkspace = ref({})
const members = ref([])
const isLoading = ref(false)
const error = ref(null)
const activeMemberId = ref(null)

// Get current user ID from local storage
const currentUserId = computed(() => {
  try {
    const userData = localStorage.getItem('authUser')
    if (userData) {
      const user = JSON.parse(userData)
      return user.id || user.user_id
    }
    return null
  } catch (e) {
    console.error('Error getting current user ID:', e)
    return null
  }
})

// Expose isOpen as a computed property with getter and setter
defineExpose({
  get isOpen() {
    return isDrawerOpen.value
  },
  set isOpen(value) {
    isDrawerOpen.value = value
    if (value) {
      fetchMembers()
    }
  },
  get workspace() {
    return currentWorkspace.value
  },
  set workspace(value) {
    currentWorkspace.value = value
    if (value && isDrawerOpen.value) {
      fetchMembers()
    }
  }
})

// Watch for changes to the isOpen prop
watch(() => props.isOpen, (newValue) => {
  isDrawerOpen.value = newValue
  if (newValue) {
    fetchMembers()
  }
}, { immediate: true })

// Watch for changes to the workspace prop
watch(() => props.workspace, (newWorkspace) => {
  if (newWorkspace) {
    currentWorkspace.value = {...newWorkspace}
    if (isDrawerOpen.value) {
      fetchMembers()
    }
  }
}, { immediate: true, deep: true })

// Methods
const close = () => {
  isDrawerOpen.value = false
  emit('close')
}

const fetchMembers = async () => {
  if (!currentWorkspace.value || !currentWorkspace.value.id) {
    console.error('No workspace selected to fetch members')
    return
  }

  const workspaceId = currentWorkspace.value.id || 
                     currentWorkspace.value._id || 
                     currentWorkspace.value.workspace_id

  if (!workspaceId) {
    error.value = 'Invalid workspace ID'
    return
  }

  isLoading.value = true
  error.value = null

  try {
    // Get auth token from localStorage
    const userData = localStorage.getItem('authUser')
    let token = null
    if (userData) {
      const auth = JSON.parse(userData)
      token = auth.token || localStorage.getItem('authToken')
    }
    
    if (!token) {
      error.value = 'Authentication token not found'
      return
    }
    
    // Ensure token has Bearer prefix
    const bearerToken = token.startsWith('Bearer ') ? token : `Bearer ${token}`
    
    // Build request headers with Bearer token
    const headers = {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'Authorization': bearerToken
    }
    
    // Make API call to get workspace members
    const url = `http://localhost/api/workspaces/${workspaceId}/members`
    const response = await axios.get(url, { headers })
  
    // Update members list with properly mapped data
    if (response.data) {
      // Check if response has data array property (your API structure)
      if (response.data.data && Array.isArray(response.data.data)) {
        // Map the API response structure to the expected component structure
        members.value = response.data.data.map(member => ({
          id: member.user_id,
          user_id: member.user_id,
          workspace_member_id: member.workspace_member_id,
          name: member.full_name,
          email: member.email,
          avatar: member.profile_picture_url,
          profile_picture: member.profile_picture_url,
          role: member.role,
          joined_at: member.joined_at
        }))
      } else if (Array.isArray(response.data)) {
        // Handle the case where response data is directly an array
        members.value = response.data
      } else if (response.data.members && Array.isArray(response.data.members)) {
        // Handle the case with members property
        members.value = response.data.members
      } else {
        members.value = []
        console.warn('Unexpected response format:', response.data)
      }
    } else {
      members.value = []
      console.warn('No data in response')
    }
  } catch (error) {
    console.error('Error fetching workspace members:', error)
    error.value = error.response?.data?.message || error.message || 'Failed to load members'
  } finally {
    isLoading.value = false
  }
}

const showInviteMembersModal = () => {
  // First close this drawer
  close()
  
  // Then show the invite members modal
  setTimeout(() => {
    modals.showInviteMembers()
  }, 300)
}

const formatRole = (role) => {
  if (!role) return 'Member'
  return role.charAt(0).toUpperCase() + role.slice(1)
}

const toggleMemberOptions = (member, event) => {
  if (event) {
    event.stopPropagation()
    event.preventDefault()
  }
  
  const memberId = member.id || member.user_id || member.workspace_member_id
  if (activeMemberId.value === memberId) {
    activeMemberId.value = null
  } else {
    activeMemberId.value = memberId
  }
}

const changeRole = async (member, newRole) => {
  if (!confirm(`Are you sure you want to change ${member.name || member.full_name || member.email}'s role to ${formatRole(newRole)}?`)) {
    return;
  }
  
  activeMemberId.value = null;
  
  try {
    // Set loading state
    const memberIndex = members.value.findIndex(m => 
      (m.workspace_member_id && m.workspace_member_id === member.workspace_member_id)
    );
    
    if (memberIndex !== -1) {
      // Create a new object to ensure reactivity
      members.value[memberIndex] = { ...members.value[memberIndex], isUpdating: true };
    }
    
    // Get the workspace ID
    const workspaceId = currentWorkspace.value.id || 
                       currentWorkspace.value._id || 
                       currentWorkspace.value.workspace_id;
    
    // Get the workspace_member_id
    const workspaceMemberId = member.workspace_member_id;
    
    if (!workspaceId || !workspaceMemberId) {
      throw new Error('Invalid workspace or workspace member ID');
    }
    
    // Get auth token from localStorage
    const userData = localStorage.getItem('authUser');
    let token = null;
    if (userData) {
      const auth = JSON.parse(userData);
      token = auth.token || localStorage.getItem('authToken');
    }
    
    if (!token) {
      throw new Error('Authentication token not found');
    }
    
    // Ensure token has Bearer prefix
    const bearerToken = token.startsWith('Bearer ') ? token : `Bearer ${token}`;
    
    // Build request headers with Bearer token
    const headers = {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'Authorization': bearerToken
    };
    
    console.log(`Updating role for member with workspace_member_id ${workspaceMemberId} in workspace ${workspaceId}`);
    
    // Make API call to update member role
    const url = `http://localhost/api/workspaces/${workspaceId}/members/${workspaceMemberId}/role`;
    const response = await axios.put(url, { role: newRole }, { headers });
    
    console.log('Update role response:', response.data);
    
    // Update the member in the local list
    if (memberIndex !== -1) {
      members.value[memberIndex] = { 
        ...members.value[memberIndex], 
        role: newRole,
        isUpdating: false 
      };
    }
    
    // Show success message
    createToast(`${member.name || member.full_name || member.email}'s role has been updated to ${formatRole(newRole)}.`, {
      position: 'top-right',
      type: 'success',
      timeout: 3000
    });
    
  } catch (error) {
    console.error('Error updating member role:', error);
    
    // Reset the loading state if there was an error
    const memberIndex = members.value.findIndex(m => 
      m.workspace_member_id === member.workspace_member_id
    );
    
    if (memberIndex !== -1) {
      // Create a new object to ensure reactivity
      members.value[memberIndex] = { ...members.value[memberIndex], isUpdating: false };
    }
    
    createToast(`Failed to update role: ${error.response?.data?.message || error.message || 'Unknown error'}`, {
      position: 'top-right',
      type: 'danger',
      timeout: 5000
    });
  }
}

const removeMember = async (member) => {
  if (!confirm(`Are you sure you want to remove ${member.name || member.full_name || member.email} from this workspace?`)) {
    return;
  }
  
  activeMemberId.value = null;

  try {
    // Set loading state for this specific member
    const memberIndex = members.value.findIndex(m => 
      (m.id && m.id === member.id) || 
      (m.user_id && m.user_id === member.user_id)
    );
    
    if (memberIndex !== -1) {
      // Create a new object to ensure reactivity
      members.value[memberIndex] = { ...members.value[memberIndex], isRemoving: true };
    }
    
    // Get the workspace ID
    const workspaceId = currentWorkspace.value.id || 
                       currentWorkspace.value._id || 
                       currentWorkspace.value.workspace_id;
    
    // Get the workspace_member_id - this is the critical change
    const workspaceMemberId = member.workspace_member_id;
    
    if (!workspaceId || !workspaceMemberId) {
      throw new Error('Invalid workspace or workspace member ID');
    }
    
    // Get auth token from localStorage
    const userData = localStorage.getItem('authUser');
    let token = null;
    if (userData) {
      const auth = JSON.parse(userData);
      token = auth.token || localStorage.getItem('authToken');
    }
    
    if (!token) {
      throw new Error('Authentication token not found');
    }
    
    // Ensure token has Bearer prefix
    const bearerToken = token.startsWith('Bearer ') ? token : `Bearer ${token}`;
    
    // Build request headers with Bearer token
    const headers = {
      'Accept': 'application/json',
      'Authorization': bearerToken
    };
    
    console.log(`Removing member with workspace_member_id ${workspaceMemberId} from workspace ${workspaceId}`);
    
    // Use the correct ID (workspace_member_id) for the API endpoint
    const url = `http://localhost/api/workspaces/${workspaceId}/members/${workspaceMemberId}`;
    const response = await axios.delete(url, { headers });
    
    console.log('Remove member response:', response.data);
    
    // Remove the member from the local list
    members.value = members.value.filter(m => 
      m.workspace_member_id !== workspaceMemberId
    );
    
    // Show success message
    createToast(`${member.name || member.full_name || member.email} has been removed from the workspace.`, {
      position: 'top-right',
      type: 'success',
      timeout: 3000
    });
    
  } catch (error) {
    console.error('Error removing workspace member:', error);
    
    // Reset the loading state if there was an error
    const memberIndex = members.value.findIndex(m => 
      (m.id && m.id === member.id) || 
      (m.user_id && m.user_id === member.user_id)
    );
    
    if (memberIndex !== -1) {
      // Create a new object to ensure reactivity
      members.value[memberIndex] = { ...members.value[memberIndex], isRemoving: false };
    }
    
    createToast(`Failed to remove member: ${error.response?.data?.message || error.message || 'Unknown error'}`, {
      position: 'top-right',
      type: 'danger',
      timeout: 5000
    });
  }
};

const getUserInitials = (name) => {
  if (!name) return '?'
  
  const parts = name.split(/\s+/)
  if (parts.length === 1) return name.charAt(0).toUpperCase()
  
  return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase()
}

// Helper function to get workspace logo from different possible fields
const getWorkspaceLogo = (workspace) => {
  if (!workspace) return null
  
  if (workspace.logo_url) {
    return workspace.logo_url
  } else if (workspace.logo) {
    // If logo is a full URL or base64 data
    if (typeof workspace.logo === 'string' && 
        (workspace.logo.startsWith('http') || workspace.logo.startsWith('data:'))) {
      return workspace.logo
    }
    // If it might be a relative path
    return `http://localhost/storage/${workspace.logo}`
  }
  
  return null
}

// Add click event listener to close dropdowns when clicking outside
onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

// Remove event listener on unmount
onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})

// Function to handle clicks outside dropdown
const handleClickOutside = (event) => {
  if (activeMemberId.value !== null) {
    // Check if click was outside the dropdown
    const dropdown = document.querySelector('.member-options-menu.show')
    if (dropdown && !dropdown.contains(event.target)) {
      activeMemberId.value = null
    }
  }
}
</script>

<style scoped>
.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background-color: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(2px);
  z-index: 1040;
}

.members-modal {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%) scale(0.9);
  width: 600px;
  max-width: 90vw;
  max-height: 80vh;
  background-color: white;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
  border-radius: 8px;
  transition: all 0.3s ease;
  opacity: 0;
  visibility: hidden;
  z-index: 1050;
  display: flex;
  flex-direction: column;
}

.modal-open {
  transform: translate(-50%, -50%) scale(1);
  opacity: 1;
  visibility: visible;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  border-bottom: 1px solid #dee2e6;
  border-top-left-radius: 8px;
  border-top-right-radius: 8px;
}

.modal-body {
  padding: 1.5rem;
  overflow-y: auto;
  max-height: calc(80vh - 60px);
}

.workspace-logo-sm {
  width: 48px;
  height: 48px;
  object-fit: cover;
}

.user-avatar {
  width: 40px;
  height: 40px;
  object-fit: cover;
}

.members-list {
  max-height: 400px;
  overflow-y: auto;
}

.member-options-menu {
  position: absolute;
  transform: translate3d(-152px, 32px, 0px);
  top: 0px;
  left: 0px;
  will-change: transform;
}

.dropdown-menu.show {
  display: block;
}

@media (max-width: 576px) {
  .members-modal {
    width: 95vw;
    max-height: 90vh;
  }
  
  .modal-body {
    max-height: calc(90vh - 60px);
  }
}
</style> 