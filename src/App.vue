<script setup>
import Sidebar from './components/Sidebar.vue'
import Navbar from './components/Navbar.vue'
import BreadcrumbNav from './components/BreadcrumbNav.vue'
import NewProjectModal from './components/NewProjectModal.vue'
import NewListModal from './components/NewListModal.vue'
import { useRoute } from 'vue-router'

const route = useRoute()
</script>

<template>
  <div class="app-container" :class="{ 'auth-page': route.name === 'auth' }">
    <template v-if="route.name !== 'auth'">
      <Navbar />
      <Sidebar />
      <main class="main-content">
        <BreadcrumbNav />
        <div class="content-area">
          <router-view></router-view>
        </div>
      </main>
      <!-- Modals at root level -->
      <NewProjectModal ref="newProjectModal" />
      <NewListModal ref="newListModal" />
    </template>
    <template v-else>
      <router-view></router-view>
    </template>
  </div>
</template>

<style>
@import 'bootstrap/dist/css/bootstrap.min.css';
@import 'bootstrap-icons/font/bootstrap-icons.css';

.app-container {
  display: flex;
  min-height: 100vh;
  padding-top: 48px; /* Match navbar height */
}

.app-container.auth-page {
  padding-top: 0;
  display: block;
}

.main-content {
  flex: 1;
  margin-left: 250px; /* Match sidebar width */
  min-height: calc(100vh - 48px); /* Subtract navbar height */
  background-color: #f8f9fa;
}

.auth-page .main-content {
  margin-left: 0;
}

.content-area {
  padding: 1.5rem;
}

/* Global modal styles */
.modal-backdrop {
  --bs-backdrop-bg: #000000;
  --bs-backdrop-opacity: 0.2;
  backdrop-filter: blur(1px);
}

.modal-backdrop.show {
  opacity: var(--bs-backdrop-opacity);
}

.modal {
  z-index: 2000;
}

.modal-dialog {
  z-index: 2010;
}

.modal-content {
  box-shadow: 0 0.5rem 2rem rgba(0, 0, 0, 0.15);
}
</style>
