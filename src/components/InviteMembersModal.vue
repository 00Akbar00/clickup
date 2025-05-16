<template>
  <div class="modal fade" id="inviteMembersModal" ref="modalRef" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content bg-white shadow-sm border">
        <div class="modal-header py-3">
          <h5 class="modal-title">Invite Members</h5>
          <button type="button" class="btn-close" @click="hide" aria-label="Close"></button>
        </div>
        <div class="modal-body py-4">
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
              >
            </div>
            <div class="email-chips mt-2">
              <div v-for="(email, index) in emailList" :key="index" class="email-chip">
                <span class="email-text">{{ email }}</span>
                <button class="remove-email" @click="removeEmail(index)">
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
            ></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button 
            type="button" 
            class="btn btn-primary w-100" 
            @click="sendInvites"
            :disabled="emailList.length === 0"
          >Send Invites</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Modal } from 'bootstrap'

const modalRef = ref(null)
const currentEmail = ref('')
const emailList = ref([])
const message = ref('')
let modalInstance = null

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
}

const sendInvites = () => {
  if (emailList.value.length > 0) {
    console.log('Sending invites to:', emailList.value)
    console.log('Message:', message.value)
    hide()
  }
}

// Expose methods to parent components
defineExpose({
  hide,
  sendInvites
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
</style> 