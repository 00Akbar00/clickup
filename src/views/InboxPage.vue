<template>
  <div class="inbox-page">
    <div class="inbox-content p-4">
      <!-- Important Tab Content -->
      <div v-if="inboxStore.activeTab === 'Important'" class="messages-list">
        <div class="message-card" v-for="message in inboxStore.importantMessages" :key="message.id">
          <div class="message-header">
            <i class="bi bi-exclamation-circle-fill text-danger me-2"></i>
            <span class="message-title">{{ message.title }}</span>
            <span class="message-time ms-auto">{{ message.time }}</span>
          </div>
          <div class="message-body">{{ message.content }}</div>
        </div>
      </div>

      <!-- Updates Tab Content -->
      <div v-if="inboxStore.activeTab === 'Updates'" class="messages-list">
        <div class="message-card" v-for="update in inboxStore.updates" :key="update.id">
          <div class="message-header">
            <i class="bi bi-arrow-clockwise text-primary me-2"></i>
            <span class="message-title">{{ update.title }}</span>
            <span class="message-time ms-auto">{{ update.time }}</span>
          </div>
          <div class="message-body">{{ update.content }}</div>
        </div>
      </div>

      <!-- Messages Tab Content -->
      <div v-if="inboxStore.activeTab === 'Messages'" class="messages-list">
        <div class="message-card" v-for="message in inboxStore.directMessages" :key="message.id">
          <div class="message-header">
            <i class="bi bi-chat-dots text-success me-2"></i>
            <span class="message-title">{{ message.title }}</span>
            <span class="message-time ms-auto">{{ message.time }}</span>
          </div>
          <div class="message-body">{{ message.content }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useInboxStore } from '../stores/inboxStore'

const inboxStore = useInboxStore()
</script>

<style scoped>
.inbox-page {
  height: calc(100vh - 48px);
  display: flex;
  flex-direction: column;
}

.inbox-content {
  flex: 1;
  overflow-y: auto;
}

.messages-list {
  max-width: 800px;
  margin: 0 auto;
}

.message-card {
  background: white;
  border-radius: 8px;
  padding: 1rem;
  margin-bottom: 1rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.message-header {
  display: flex;
  align-items: center;
  margin-bottom: 0.5rem;
}

.message-title {
  font-weight: 500;
  font-size: 1rem;
}

.message-time {
  color: #6c757d;
  font-size: 0.875rem;
}

.message-body {
  color: #4a5568;
  font-size: 0.95rem;
  line-height: 1.5;
}
</style> 