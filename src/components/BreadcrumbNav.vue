<template>
  <nav class="breadcrumb-nav bg-white border-bottom">
    <div class="d-flex align-items-center h-100 px-3">
      <ol class="breadcrumb mb-0">
        <li v-for="(item, index) in navigationStore.breadcrumbItems" 
            :key="item.id"
            class="breadcrumb-item"
            :class="{ 'active': index === navigationStore.breadcrumbItems.length - 1 }">
          <span 
            class="text-secondary small cursor-pointer"
            :class="{ 'text-dark': index === navigationStore.breadcrumbItems.length - 1 }"
            @click="handleItemClick(item)">
            {{ item.name }}
          </span>
        </li>
      </ol>
    </div>
  </nav>
</template>

<script setup>
import { useNavigationStore } from '../stores/navigationStore'

const navigationStore = useNavigationStore()

const handleItemClick = (item) => {
  switch (item.type) {
    case 'teamspace':
      navigationStore.setTeamspace(item)
      break
    case 'project':
      navigationStore.setProject(item)
      break
    case 'list':
      navigationStore.setList(item)
      break
  }
}
</script>

<style scoped>
.breadcrumb-nav {
  height: 49px;
}

.breadcrumb {
  --bs-breadcrumb-divider: '/';
  padding: 0.25rem 0;
}

.breadcrumb-item {
  display: flex;
  align-items: center;
  font-weight: 500;
}

.breadcrumb-item + .breadcrumb-item::before {
  font-size: 0.75rem;
  color: #6c757d;
  padding: 0 0.75rem;
}

.small {
  font-size: 0.875rem;
}

.cursor-pointer {
  cursor: pointer;
}

.cursor-pointer:hover {
  opacity: 0.8;
}
</style> 