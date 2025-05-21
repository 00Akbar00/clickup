<template>
  <div class="invitation-test card p-4 shadow my-4">
    <h3>Workspace Invitation Test</h3>
    <p class="text-muted">This component is for testing the workspace invitation functionality.</p>
    
    <div class="mb-3">
      <label class="form-label">Sample invitation URL:</label>
      <input 
        type="text" 
        class="form-control" 
        v-model="sampleUrl" 
        placeholder="http://localhost/api/workspaces/id/join/token"
      />
      <div class="form-text">This would be the URL received in the email invitation.</div>
    </div>
    
    <div class="mb-3">
      <label class="form-label">Manual token for testing:</label>
      <input 
        type="text" 
        class="form-control" 
        v-model="inviteToken" 
        placeholder="Invitation token"
      />
    </div>
    
    <div class="row mb-3">
      <div class="col">
        <button 
          class="btn btn-primary w-100" 
          @click="simulateInviteUrl"
          :disabled="!sampleUrl"
        >
          Simulate URL Navigation
        </button>
      </div>
      <div class="col">
        <button 
          class="btn btn-secondary w-100" 
          @click="acceptInviteManually"
          :disabled="!inviteToken"
        >
          Accept Manually
        </button>
      </div>
    </div>
    
    <div class="mb-3">
      <button 
        class="btn btn-outline-danger w-100" 
        @click="clearInviteStorage"
      >
        Clear Invitation Storage
      </button>
    </div>
    
    <div v-if="result" class="alert" :class="resultSuccess ? 'alert-success' : 'alert-danger'">
      {{ result }}
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useWorkspaceStore } from '../stores/workspaceStore'
import { extractInvitationParams } from '../utils/inviteHandler'

const workspaceStore = useWorkspaceStore()
const sampleUrl = ref('http://localhost/api/workspaces/06373c55-dadc-481a-8001-e1ed19e401a9/join/33e7c6ab-b672-4423-995d-da5c8c09baf7')
const inviteToken = ref('')
const result = ref('')
const resultSuccess = ref(false)

const simulateInviteUrl = () => {
  try {
    const inviteParams = extractInvitationParams(sampleUrl.value)
    
    if (!inviteParams) {
      result.value = 'Invalid invitation URL format'
      resultSuccess.value = false
      return
    }
    
    // Store the invitation params in localStorage
    localStorage.setItem('pendingInvitation', JSON.stringify(inviteParams))
    
    // Check if user is authenticated
    const isAuthenticated = !!localStorage.getItem('authUser') || 
                           !!localStorage.getItem('authToken')
    
    if (!isAuthenticated) {
      result.value = 'User not authenticated. In a real scenario, you would be redirected to login.'
      resultSuccess.value = false
      return
    }
    
    // Process the stored invitation
    processStoredInvitation()
  } catch (error) {
    result.value = `Error: ${error.message}`
    resultSuccess.value = false
  }
}

const acceptInviteManually = async () => {
  try {
    // Check if user is authenticated
    const isAuthenticated = !!localStorage.getItem('authUser') || 
                           !!localStorage.getItem('authToken')
    
    if (!isAuthenticated) {
      result.value = 'User not authenticated. Please log in first.'
      resultSuccess.value = false
      return
    }
    
    result.value = 'Processing invitation...'
    resultSuccess.value = true
    
    await workspaceStore.acceptWorkspaceInvitation(inviteToken.value)
    
    result.value = 'Invitation accepted successfully! Workspace has been added to your account.'
    resultSuccess.value = true
  } catch (error) {
    result.value = `Error accepting invitation: ${error.message}`
    resultSuccess.value = false
  }
}

const processStoredInvitation = async () => {
  try {
    const pendingInvitation = localStorage.getItem('pendingInvitation')
    
    if (!pendingInvitation) {
      result.value = 'No pending invitation found'
      resultSuccess.value = false
      return
    }
    
    const inviteParams = JSON.parse(pendingInvitation)
    
    result.value = 'Processing stored invitation...'
    resultSuccess.value = true
    
    await workspaceStore.acceptWorkspaceInvitation(inviteParams.token)
    
    // Clear the stored invitation
    localStorage.removeItem('pendingInvitation')
    
    result.value = 'Invitation accepted successfully! Workspace has been added to your account.'
    resultSuccess.value = true
  } catch (error) {
    result.value = `Error processing stored invitation: ${error.message}`
    resultSuccess.value = false
  }
}

const clearInviteStorage = () => {
  localStorage.removeItem('pendingInvitation')
  result.value = 'Invitation storage cleared'
  resultSuccess.value = true
}
</script>

<style scoped>
.invitation-test {
  max-width: 600px;
  margin: 0 auto;
}

.btn {
  padding: 0.5rem 1rem;
}

.alert {
  margin-top: 1rem;
}
</style> 