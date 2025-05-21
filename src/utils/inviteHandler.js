/**
 * Utility for handling workspace invitation links
 */
import { useRouter } from 'vue-router'
import { useWorkspaceStore } from '../stores/workspaceStore'

/**
 * Extracts invitation parameters from a workspace invitation URL
 * Example URL format: http://localhost/api/workspaces/:workspaceId/join/:token
 * @param {string} url - The full invite URL
 * @returns {Object|null} Object with workspaceId and token or null if not found
 */
export function extractInvitationParams(url) {
  if (!url) return null
  
  // Try to match the invitation URL pattern
  const regex = /\/api\/workspaces\/([^\/]+)\/join\/([^\/]+)/
  const match = url.match(regex)
  
  if (match && match.length === 3) {
    return {
      workspaceId: match[1],
      token: match[2]
    }
  }
  
  return null
}

/**
 * Handles an incoming workspace invitation URL 
 * Redirects to auth if not logged in, or processes the invitation
 * @param {string} url - The invitation URL to process
 */
export async function handleInvitationUrl(url) {
  const router = useRouter()
  const workspaceStore = useWorkspaceStore()
  
  const inviteParams = extractInvitationParams(url)
  
  if (!inviteParams) {
    console.error('Invalid invitation URL format')
    return false
  }
  
  // Store the invitation params for later use after auth
  localStorage.setItem('pendingInvitation', JSON.stringify(inviteParams))
  
  // Check if user is already authenticated
  const isAuthenticated = !!localStorage.getItem('authUser') || !!localStorage.getItem('authToken')
  
  if (!isAuthenticated) {
    // Redirect to auth page
    router.push({ name: 'auth' })
    return true
  }
  
  // User is authenticated, process the invitation
  try {
    await workspaceStore.acceptWorkspaceInvitation(inviteParams.token)
    
    // Clear the stored invitation after processing
    localStorage.removeItem('pendingInvitation')
    
    // Redirect to home page to show the newly joined workspace
    router.push({ name: 'home' })
    return true
  } catch (error) {
    console.error('Failed to process workspace invitation:', error)
    return false
  }
}

/**
 * Checks for and processes any pending invitations stored in localStorage
 * Typically called during app initialization
 * @returns {Promise<boolean>} True if an invitation was processed
 */
export async function processPendingInvitation() {
  const workspaceStore = useWorkspaceStore()
  const pendingInvitation = localStorage.getItem('pendingInvitation')
  
  if (pendingInvitation) {
    try {
      const inviteParams = JSON.parse(pendingInvitation)
      
      // Check if user is authenticated
      const isAuthenticated = !!localStorage.getItem('authUser') || !!localStorage.getItem('authToken')
      
      if (isAuthenticated) {
        await workspaceStore.acceptWorkspaceInvitation(inviteParams.token)
        localStorage.removeItem('pendingInvitation')
        return true
      }
    } catch (error) {
      console.error('Error processing pending invitation:', error)
    }
  }
  
  return false
} 