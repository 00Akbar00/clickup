<template>
  <div class="create-workspace-page">
    <div class="top-bar d-flex justify-content-between align-items-center px-4 pt-3">
      <img src="@/assets/mylogo.svg" alt="Logo" class="logo" />
    </div>

    <div class="container">
      <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
          <div class="text-center mb-4">
            <h2>Create Your First Workspace</h2>
            <p class="text-muted">Set up your workspace to start collaborating with your team</p>
          </div>

          <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4">
              <form @submit.prevent="handleSubmit">
                <div class="mb-4">
                  <label for="workspaceName" class="form-label">Workspace Name</label>
                  <input
                    type="text"
                    class="form-control form-control-lg"
                    id="workspaceName"
                    v-model="workspaceName"
                    placeholder="Enter workspace name"
                    required
                    autofocus
                  >
                  <div class="form-text">
                    This is the name of your company, team, or organization
                  </div>
                </div>

                <div class="mb-4">
                  <label for="workspaceDescription" class="form-label">Description (Optional)</label>
                  <textarea
                    class="form-control"
                    id="workspaceDescription"
                    v-model="workspaceDescription"
                    rows="3"
                    placeholder="Enter workspace description"
                  ></textarea>
                </div>

                <div class="d-grid">
                  <button 
                    type="submit" 
                    class="btn btn-primary btn-lg"
                    :disabled="!workspaceName.trim() || isLoading"
                  >
                    <span v-if="isLoading" class="spinner-border spinner-border-sm me-2" role="status"></span>
                    Create Workspace
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useWorkspaceStore } from '../stores/workspaceStore'

const router = useRouter()
const workspaceStore = useWorkspaceStore()
const workspaceName = ref('')
const workspaceDescription = ref('')
const isLoading = ref(false)

const handleSubmit = async () => {
  if (!workspaceName.value.trim()) return

  isLoading.value = true
  try {
    await workspaceStore.createWorkspace({
      name: workspaceName.value.trim(),
      description: workspaceDescription.value.trim()
    })
    localStorage.setItem('hasWorkspace', 'true')
    router.push('/')
  } catch (error) {
    console.error('Error creating workspace:', error)
    alert('Failed to create workspace. Please try again.')
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
.create-workspace-page {
  min-height: 100vh;
  background-color: #f8f9fa;
  padding-bottom: 2rem;
}

.top-bar {
  height: 60px;
  background-color: white;
  border-bottom: 1px solid #dee2e6;
  margin-bottom: 3rem;
}

.logo {
  height: 40px;
}

.form-control:focus {
  border-color: var(--app-primary-color);
  box-shadow: 0 0 0 0.25rem rgba(84, 62, 208, 0.25);
}

.btn-primary {
  background-color: var(--app-primary-color);
  border-color: var(--app-primary-color);
}

.btn-primary:hover {
  background-color: #4935c8;
  border-color: #4935c8;
}

.form-label {
  font-weight: 500;
  color: #444;
}
</style> 