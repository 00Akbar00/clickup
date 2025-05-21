<template>
  <div class="modal fade" id="inviteMembersModal" ref="modalRef" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content bg-white shadow-sm border">
        <div class="modal-header py-3">
          <div>
            <h5 class="modal-title">Invite Members</h5>
            <div v-if="workspaceStore.currentWorkspace" class="text-muted small">
              to {{ workspaceStore.currentWorkspace.name }}
            </div>
          </div>
          <button type="button" class="btn-close" @click="hide" aria-label="Close"></button>
        </div>
        <div class="modal-body py-4">
          <div v-if="linkError" class="alert alert-danger mb-3">
            {{ linkError }}
          </div>
          
          <div class="mb-3">
            <label for="email" class="form-label">Email Addresses</label>
            <div class="email-input-container">
              <input 
                type="email" 
                class="form-control" 
                id="email" 
                v-model="currentEmail" 
                placeholder="Enter email address and press Enter"
                @keydown.enter.prevent="addEmail"
                @keydown.backspace="handleBackspace"
                :disabled="isLoading"
              >
            </div>
            <div class="email-chips mt-2">
              <div v-for="(email, index) in emailList" :key="index" class="email-chip">
                <span class="email-text">{{ email }}</span>
                <button class="remove-email" @click="removeEmail(index)" :disabled="isLoading">
                  <i class="bi bi-x"></i>
                </button>
              </div>
            </div>
          </div>
          <div class="mb-3">
            <label for="message" class="form-label">Invitation Message (Optional)</label>
            <textarea 
              class="form-control" 
              id="message" 
              v-model="message" 
              rows="3"
              placeholder="Add a personal message to your invitation"
              :disabled="isLoading"
            ></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button 
            type="button" 
            class="btn btn-primary w-100" 
            @click="sendInvites"
            :disabled="emailList.length === 0 || isLoading || !inviteGenerated"
          >
            <span v-if="isGeneratingInvite" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            <span v-else-if="isLoading" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            {{ isGeneratingInvite ? 'Preparing...' : (isLoading ? 'Sending...' : 'Send Invites') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { Modal } from 'bootstrap'
import axios from 'axios'
import { useWorkspaceStore } from '../stores/workspaceStore'
import { useRouter } from 'vue-router'

const workspaceStore = useWorkspaceStore()
const modalRef = ref(null)
const currentEmail = ref('')
const emailList = ref([])
const message = ref('')
let modalInstance = null
const inviteLink = ref('')
const inviteToken = ref('')
const isLoading = ref(false)
const isGeneratingInvite = ref(false)
const linkError = ref(null)
const inviteGenerated = ref(false)

// Add router
const router = useRouter()

// Get current workspace ID
const currentWorkspaceId = computed(() => {
  const workspace = workspaceStore.currentWorkspace
  if (!workspace) return null
  return workspace.id || workspace._id || workspace.workspace_id
})

onMounted(() => {
  modalInstance = new Modal(modalRef.value)
})

const isValidEmail = (email) => {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return emailRegex.test(email)
}

const addEmail = () => {
  const email = currentEmail.value.trim()
  if (email && isValidEmail(email) && !emailList.value.includes(email)) {
    emailList.value.push(email)
    currentEmail.value = ''
  }
}

const removeEmail = (index) => {
  emailList.value.splice(index, 1)
}

const handleBackspace = (event) => {
  if (currentEmail.value === '' && emailList.value.length > 0) {
    event.preventDefault()
    emailList.value.pop()
  }
}

const hide = () => {
  modalInstance?.hide()
  emailList.value = []
  currentEmail.value = ''
  message.value = ''
  inviteLink.value = ''
  inviteToken.value = ''
  linkError.value = null
  inviteGenerated.value = false
}

const getInviteLink = async () => {
  if (!currentWorkspaceId.value) {
    linkError.value = 'No workspace selected'
    console.error('No workspace ID available')
    return false
  }
  
  console.log('Generating invite link for workspace:', currentWorkspaceId.value)
  isGeneratingInvite.value = true
  linkError.value = null
  
  try {
    // Get auth token from localStorage
    const token = localStorage.getItem('authToken') || 
                  (localStorage.getItem('authUser') ? 
                   JSON.parse(localStorage.getItem('authUser')).token : null)
    
    if (!token) {
      console.error('No authentication token found')
      throw new Error('Authentication token not found. Please log in again.')
    }
    
    // API call with empty body as per the Postman collection
    const response = await axios.post(
      `http://localhost/api/workspaces/${currentWorkspaceId.value}/invites/link`,
      {}, // Empty body
      {
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        }
      }
    )
    
    console.log('Invite link response status:', response.status)
    console.log('Invite link response data:', response.data)
    
    if (response.data && response.data.data && response.data.data.invite_link) {
      inviteLink.value = response.data.data.invite_link
      inviteGenerated.value = true
      return true
    } else if (response.data && response.data.invite_link) {
      inviteLink.value = response.data.invite_link
      inviteGenerated.value = true
      return true
    } else if (response.data && response.data.invite_url) {
      // Handle alternative response format
      inviteLink.value = response.data.invite_url
      inviteGenerated.value = true
      return true
    } else {
      console.error('Invalid response format:', response.data)
      linkError.value = 'Could not generate invitation link. Please try again.'
      return false
    }
  } catch (error) {
    console.error('Error fetching invite link:', error)
    
    // Detailed error logging
    if (error.response) {
      console.error('Response status:', error.response.status)
      console.error('Response data:', error.response.data)
      console.error('Response headers:', error.response.headers)
    }
    
    // Check for authentication error
    if (error.response && error.response.status === 401) {
      linkError.value = 'Unauthorized: Please log in again to generate an invite link'
    } else {
      linkError.value = error.response?.data?.message || error.message || 'Failed to generate invite link'
    }
    
    return false
  } finally {
    isGeneratingInvite.value = false
  }
}

const sendInvites = async () => {
  if (emailList.value.length === 0) return;
  
  isLoading.value = true;
  
  try {
    // Get auth token from localStorage
    const token = localStorage.getItem('authToken') || 
                  (localStorage.getItem('authUser') ? 
                   JSON.parse(localStorage.getItem('authUser')).token : null)
    
    if (!token) {
      console.error('No authentication token found')
      throw new Error('Authentication token not found. Please log in again.')
    }
    
    // Prepare request payload
    const payload = {
      emails: emailList.value,
      role: "member"
    };
    
    // Add message if provided
    if (message.value.trim()) {
      payload.message = message.value.trim();
    }
    
    // Make API call to send invites
    const response = await axios.post(
      `http://localhost/api/workspaces/${currentWorkspaceId.value}/invites/send`,
      payload,
      {
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        }
      }
    );
    
    console.log('Invite send response status:', response.status);
    console.log('Invite send response data:', response.data);
    
    // Display success message
    alert('Invitations sent successfully!');
    
    // Close the modal after sending
    hide();
    
  } catch (error) {
    console.error('Error sending invites:', error);
    
    // Detailed error logging
    if (error.response) {
      console.error('Response status:', error.response.status);
      console.error('Response data:', error.response.data);
      console.error('Response headers:', error.response.headers);
    }
    
    // Display error message
    linkError.value = error.response?.data?.message || error.message || 'Failed to send invites';
  } finally {
    isLoading.value = false;
  }
};

// Add function to check authentication status
const checkAuthStatus = () => {
  const token = localStorage.getItem('authToken') || 
                (localStorage.getItem('authUser') ? 
                 JSON.parse(localStorage.getItem('authUser')).token : null)
  
  return !!token
}

// Expose methods to parent components
defineExpose({
  show: async () => {
    // Check if user is authenticated
    if (!checkAuthStatus()) {
      console.error('Authentication required')
      console.error('Please log in to invite members')
      
      // Redirect to login page
      router.push('/auth')
      return
    }
    
    // Check if a workspace is selected
    if (!currentWorkspaceId.value) {
      console.error('No workspace selected')
      console.error('Please select a workspace first')
      return
    }
    
    // Reset form when showing the modal
    emailList.value = []
    currentEmail.value = ''
    message.value = ''
    linkError.value = null
    inviteGenerated.value = false
    
    // Show the modal
    modalInstance?.show()
    
    // Generate invite link when the modal is opened
    await getInviteLink()
  },
  hide
})
</script>

<style scoped>
.modal {
  z-index: 2000 !important;
}

.modal-dialog {
  max-width: 500px;
}

.modal-content {
  border-radius: 8px;
  box-shadow: 0 0.5rem 2rem rgba(0, 0, 0, 0.15) !important;
}

.modal-title {
  font-size: var(--app-font-size-lg);
}

.form-control {
  padding: 0.5rem 1rem;
  font-size: var(--app-font-size-base);
  border-radius: 6px;
  border: 1.5px solid rgba(0, 0, 0, 0.1);
  box-shadow: none;
}

.form-control:focus {
  border-color: var(--app-primary-color);
  box-shadow: 0 0 8px rgba(84, 62, 208, 0.25);
}

.form-label {
  font-weight: 500;
  color: #444;
  font-size: var(--app-font-size-base);
}

.btn {
  font-size: var(--app-font-size-base);
  padding: 0.5rem 1rem;
}

.modal-header {
  border-bottom: none;
}

.modal-footer {
  border-color: rgba(0,0,0,.1);
}

.email-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.email-chip {
  display: flex;
  align-items: center;
  background-color: var(--app-active-color);
  color: #212529;
  padding: 0.25rem 0.75rem;
  border-radius: 16px;
  font-size: 0.875rem;
}

.email-text {
  margin-right: 0.5rem;
}

.remove-email {
  background: none;
  border: none;
  color: #212529;
  padding: 0;
  display: flex;
  align-items: center;
  cursor: pointer;
  opacity: 0.8;
  transition: opacity 0.2s;
}

.remove-email:hover {
  opacity: 1;
}

.email-input-container {
  position: relative;
}

.alert-danger {
  font-size: 0.9rem;
  padding: 0.75rem;
}
</style> 