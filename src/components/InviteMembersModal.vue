<template>
  <div class="modal fade" id="inviteMembersModal" ref="modalRef" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content bg-white shadow-sm border">
        <div class="modal-header py-3">
          <h5 class="modal-title">Invite Members</h5>
          <button type="button" class="btn-close" @click="hide"></button>
        </div>
        <div class="modal-body py-4">
          <div class="mb-3">
            <label for="emails" class="form-label">Email Addresses</label>
            <textarea 
              class="form-control" 
              id="emails" 
              v-model="emails" 
              rows="3"
              placeholder="Enter email addresses (separated by commas)"
            ></textarea>
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
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button 
            type="button" 
            class="btn btn-primary" 
            @click="sendInvites"
            :disabled="!emails.trim()"
          >Send Invites</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Modal } from 'bootstrap'

const modal = ref(null)
const modalRef = ref(null)
const emails = ref('')
const message = ref('')

onMounted(() => {
  modal.value = new Modal(modalRef.value, {
    backdrop: true
  })
})

const hide = () => {
  modal.value?.hide()
  emails.value = ''
  message.value = ''
}

const sendInvites = () => {
  if (emails.value.trim()) {
    console.log('Sending invites to:', emails.value)
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

textarea.form-control {
  min-height: 100px;
  resize: vertical;
}
</style> 