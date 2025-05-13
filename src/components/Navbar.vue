<template>
  <nav class="navbar navbar-expand-lg fixed-top py-1" style="background-color: #2A216A;">
    <div class="container-fluid px-3">
      <!-- Left side - Brand -->
      <div class="d-flex align-items-center">
        <span class="navbar-brand text-white mb-0 h5">Click Flow</span>
      </div>

      <!-- Center - Search Bar -->
      <div class="d-flex align-items-center flex-grow-1 justify-content-center mx-4">
        <div class="input-group" style="max-width: 400px;">
          <input 
            type="text" 
            class="form-control search-input border-0" 
            placeholder="Search..."
            v-model="searchQuery"
            @input="handleSearch"
          >
          <button 
            class="btn search-button border-0 d-flex align-items-center"
            @click="handleSearch"
          >
            <i class="bi bi-search text-white"></i>
          </button>
        </div>
      </div>

      <!-- Right side - User Dropdown -->
      <div class="d-flex align-items-center">
        <div class="dropdown">
          <button 
            class="btn btn-link text-white p-1 dropdown-toggle d-flex align-items-center gap-2" 
            type="button" 
            data-bs-toggle="dropdown" 
            aria-expanded="false"
            style="text-decoration: none;"
          >
            <i class="bi bi-person-circle"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="#">hehe</a></li>
            <li><a class="dropdown-item" href="#">haha</a></li>
          </ul>
        </div>
      </div>
    </div>
  </nav>
</template>

<script setup lang="ts">
import { ref } from 'vue'

// State
const searchQuery = ref('')

// Methods
const handleSearch = () => {
  // TODO: Implement search functionality
  console.log('Searching for:', searchQuery.value)
}
</script>

<style scoped>
.navbar {
  height: 42px;
  z-index: 1030;
}

.navbar-brand {
  font-size: 1rem;
  font-weight: 500;
}

.input-group {
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
  border-radius: 4px;
  overflow: hidden;
  height: 30px;
}

.search-input {
  height: 30px;
  padding: 0.25rem 0.75rem;
  font-size: 0.875rem;
  background-color: #605990;
  color: white;
}

.search-input::placeholder {
  color: rgba(255, 255, 255, 0.7);
}

.search-input:focus {
  background-color: #605990;
  color: white;
  box-shadow: none;
}

.search-button {
  height: 30px;
  padding: 0 0.75rem;
  background-color: #605990;
}

.search-button:hover {
  background-color: #6c64a1;
}

.dropdown-toggle::after {
  margin-left: 0.5rem;
}

.bi-person-circle {
  font-size: 1.4rem;
}

.bi-search {
  font-size: 0.875rem;
}
</style> 