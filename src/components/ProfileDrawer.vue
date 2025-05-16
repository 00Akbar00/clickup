<!-- Profile Drawer Component -->
<template>
  <div>
    <!-- Backdrop -->
    <div 
      v-if="isOpen" 
      class="drawer-backdrop"
      @click="close"
    ></div>

    <!-- Drawer -->
    <div 
      class="drawer" 
      :class="{ 'drawer-open': isOpen }"
    >
      <div class="drawer-header border-bottom">
        <h5 class="mb-0">Profile</h5>
        <button class="btn btn-link text-dark p-0" @click="close">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>

      <div class="drawer-body">
        <!-- Profile Info -->
        <div class="text-center mb-4">
          <div class="position-relative d-inline-block">
            <img
              v-if="profilePictureUrl"
              :src="profilePictureUrl"
              alt="Profile"
              class="rounded-circle profile-picture"
            />
            <div
              v-else
              class="rounded-circle bg-secondary d-flex align-items-center justify-content-center profile-picture"
            >
              <i class="bi bi-person text-white" style="font-size: 2rem;"></i>
            </div>
            <label 
              class="btn btn-light btn-sm rounded-circle position-absolute bottom-0 end-0 p-1 edit-button"
              role="button"
            >
              <i class="bi bi-pencil"></i>
              <input 
                type="file" 
                class="d-none" 
                accept="image/*"
                @change="handleImageChange"
              >
            </label>
          </div>
        </div>

        <!-- Profile Fields -->
        <div class="profile-fields mb-4">
          <!-- Name Field -->
          <div class="profile-field mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <label class="form-label mb-0">Full Name</label>
              <button 
                v-if="!isEditingName"
                class="btn btn-link text-primary p-0" 
                @click="startEditingName"
              >
                <i class="bi bi-pencil-square"></i>
              </button>
            </div>
            <div class="d-flex gap-2">
              <input 
                type="text" 
                class="form-control"
                v-model="form.fullName"
                :readonly="!isEditingName"
                :class="{ 'form-control-plaintext': !isEditingName }"
                placeholder="Enter your full name"
              >
              <div v-if="isEditingName" class="edit-actions">
                <button class="btn btn-primary btn-sm" @click="saveNameChanges">
                  <i class="bi bi-check-lg"></i>
                </button>
                <button class="btn btn-outline-secondary btn-sm" @click="cancelNameEdit">
                  <i class="bi bi-x-lg"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- Email Field -->
          <div class="profile-field mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <label class="form-label mb-0">Email</label>
              <button 
                v-if="!isEditingEmail"
                class="btn btn-link text-primary p-0" 
                @click="startEditingEmail"
              >
                <i class="bi bi-pencil-square"></i>
              </button>
            </div>
            <div class="d-flex gap-2">
              <input 
                type="email" 
                class="form-control"
                v-model="form.email"
                :readonly="!isEditingEmail"
                :class="{ 'form-control-plaintext': !isEditingEmail }"
                placeholder="Enter your email"
              >
              <div v-if="isEditingEmail" class="edit-actions">
                <button class="btn btn-primary btn-sm" @click="saveEmailChanges">
                  <i class="bi bi-check-lg"></i>
                </button>
                <button class="btn btn-outline-secondary btn-sm" @click="cancelEmailEdit">
                  <i class="bi bi-x-lg"></i>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Additional Actions -->
        <div class="border-top pt-3">
          <button class="btn btn-outline-danger w-100" @click="handleLogout">
            <i class="bi bi-box-arrow-right me-2"></i>
            Logout
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true
  }
})

const emit = defineEmits(['close'])
const router = useRouter()

// Edit states
const isEditingName = ref(false)
const isEditingEmail = ref(false)
const originalName = ref('')
const originalEmail = ref('')

// Form state
const form = ref({
  fullName: '',
  email: ''
})

// Computed properties
const userName = computed(() => {
  const userData = localStorage.getItem('authUser')
  if (userData) {
    const user = JSON.parse(userData)
    return user.full_name || 'User'
  }
  return 'User'
})

const userEmail = computed(() => {
  const userData = localStorage.getItem('authUser')
  if (userData) {
    const user = JSON.parse(userData)
    return user.email || ''
  }
  return ''
})

const profilePictureUrl = computed(() => {
  const userData = localStorage.getItem('authUser')
  if (userData) {
    const user = JSON.parse(userData)
    return user.profile_picture_url || null
  }
  return null
})

// Initialize form with user data
onMounted(() => {
  form.value.fullName = userName.value
  form.value.email = userEmail.value
})

// Methods
const close = () => {
  // Reset any ongoing edits
  cancelNameEdit()
  cancelEmailEdit()
  emit('close')
}

const startEditingName = () => {
  originalName.value = form.value.fullName
  isEditingName.value = true
}

const startEditingEmail = () => {
  originalEmail.value = form.value.email
  isEditingEmail.value = true
}

const saveNameChanges = () => {
  const userData = JSON.parse(localStorage.getItem('authUser') || '{}')
  userData.full_name = form.value.fullName
  localStorage.setItem('authUser', JSON.stringify(userData))
  isEditingName.value = false
}

const saveEmailChanges = () => {
  const userData = JSON.parse(localStorage.getItem('authUser') || '{}')
  userData.email = form.value.email
  localStorage.setItem('authUser', JSON.stringify(userData))
  isEditingEmail.value = false
}

const cancelNameEdit = () => {
  form.value.fullName = originalName.value
  isEditingName.value = false
}

const cancelEmailEdit = () => {
  form.value.email = originalEmail.value
  isEditingEmail.value = false
}

const handleImageChange = (event) => {
  const file = event.target.files[0]
  if (file) {
    const reader = new FileReader()
    reader.onload = (e) => {
      const userData = JSON.parse(localStorage.getItem('authUser') || '{}')
      userData.profile_picture_url = e.target.result
      localStorage.setItem('authUser', JSON.stringify(userData))
      // Force a reload to update the image everywhere
      window.location.reload()
    }
    reader.readAsDataURL(file)
  }
}

const handleLogout = () => {
  localStorage.removeItem('authUser')
  router.push('/auth')
  close()
}

// Add default export
defineOptions({
  name: 'ProfileDrawer'
})
</script>

<style scoped>
.drawer-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background-color: rgba(0, 0, 0, 0.2);
  backdrop-filter: blur(1px);
  z-index: 1040;
}

.drawer {
  position: fixed;
  top: 0;
  right: -400px;
  width: 400px;
  height: 100vh;
  background-color: white;
  box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
  transition: right 0.3s ease;
  z-index: 1050;
}

.drawer-open {
  right: 0;
}

.drawer-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
}

.drawer-body {
  padding: 1.5rem;
  overflow-y: auto;
  height: calc(100vh - 60px);
}

.profile-picture {
  width: 120px;
  height: 120px;
  object-fit: cover;
}

.edit-button {
  cursor: pointer;
}

.edit-button:hover {
  background-color: #e9ecef !important;
}

.profile-field {
  background-color: #f8f9fa;
  padding: 1rem;
  border-radius: 8px;
}

.form-control-plaintext {
  background-color: transparent;
  border: none;
  padding-left: 0;
  padding-right: 0;
}

.form-control-plaintext:focus {
  outline: none;
}

.edit-actions {
  display: flex;
  gap: 0.5rem;
}

.edit-actions .btn {
  width: 32px;
  height: 32px;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

@media (max-width: 576px) {
  .drawer {
    width: 100%;
    right: -100%;
  }
}
</style> 