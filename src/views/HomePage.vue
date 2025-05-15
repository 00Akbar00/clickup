<script setup>
import { ref } from 'vue';

// Sample data - replace with actual data from your store/API
const recentItems = ref([
  {
    id: 1,
    type: 'team',
    title: 'Teamspace 1',
    lastVisited: '2 hours ago'
  },
  {
    id: 2,
    type: 'project',
    title: 'Project 2',
    lastVisited: '3 hours ago'
  },
  {
    id: 3,
    type: 'list',
    title: 'List UI Components',
    lastVisited: '5 hours ago'
  },
  {
    id: 4,
    type: 'task',
    title: 'Homepage Layout',
    lastVisited: '1 day ago'
  },
  {
    id: 1,
    type: 'team',
    title: 'Teamspace 1',
    lastVisited: '2 hours ago'
  },
  {
    id: 2,
    type: 'project',
    title: 'Project 2',
    lastVisited: '3 hours ago'
  },
  {
    id: 3,
    type: 'list',
    title: 'List UI Components',
    lastVisited: '5 hours ago'
  },
  {
    id: 4,
    type: 'task',
    title: 'Homepage Layout',
    lastVisited: '1 day ago'
  },
  {
    id: 1,
    type: 'team',
    title: 'Teamspace 1',
    lastVisited: '2 hours ago'
  },
  {
    id: 2,
    type: 'project',
    title: 'Project 2',
    lastVisited: '3 hours ago'
  },
  {
    id: 3,
    type: 'list',
    title: 'List UI Components',
    lastVisited: '5 hours ago'
  },
  {
    id: 4,
    type: 'task',
    title: 'Homepage Layout',
    lastVisited: '1 day ago'
  },
  {
    id: 1,
    type: 'team',
    title: 'Teamspace 1',
    lastVisited: '2 hours ago'
  },
  {
    id: 2,
    type: 'project',
    title: 'Project 2',
    lastVisited: '3 hours ago'
  },
  {
    id: 3,
    type: 'list',
    title: 'List UI Components',
    lastVisited: '5 hours ago'
  },
  {
    id: 4,
    type: 'task',
    title: 'Homepage Layout',
    lastVisited: '1 day ago'
  },
  {
    id: 1,
    type: 'team',
    title: 'Teamspace 1',
    lastVisited: '2 hours ago'
  },
  {
    id: 2,
    type: 'project',
    title: 'Project 2',
    lastVisited: '3 hours ago'
  },
  {
    id: 3,
    type: 'list',
    title: 'List UI Components',
    lastVisited: '5 hours ago'
  }
]);

const assignedTasks = ref([
  {
    id: 1,
    title: 'Frontend Development',
    description: 'Develop the user interface components',
    dueDate: 'Tomorrow',
    status: 'Pending'
  },
  {
    id: 2,
    title: 'Bug Fixes',
    description: 'Address reported issues in the application',
    dueDate: 'Next Week',
    status: 'In Progress'
  },
  // Add more tasks as needed
]);

// Add state for expanded slabs
const expandedSlab = ref(null); // 'recent' or 'assigned'

const expandSlab = (type) => {
  expandedSlab.value = type;
  document.body.style.overflow = 'hidden';
};

const closeSlab = () => {
  expandedSlab.value = null;
  document.body.style.overflow = 'auto';
};

const getItemIcon = (type) => {
  switch(type) {
    case 'project': return 'bi bi-folder';
    case 'team': return 'bi bi-building';
    case 'list': return 'bi bi-list-ul';
    case 'task': return 'bi bi-check2-square';
    default: return 'bi bi-file-text';
  }
};

const getItemLabel = (type) => {
  switch(type) {
    case 'project': return 'Project';
    case 'team': return 'Team Space';
    case 'list': return 'List';
    case 'task': return 'Task';
    default: return '';
  }
};
</script>

<template>
  <div class="homepage-container">
    <div class="slabs-container">
      <!-- Recent Items Slab -->
      <div class="slab">
        <div class="slab-header">
          <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Recent</h6>
            <button class="btn btn-link text-dark p-0" @click="expandSlab('recent')">
              <i class="bi bi-arrows-angle-expand"></i>
            </button>
          </div>
        </div>
        <div class="items-container">
          <div v-for="item in recentItems" 
               :key="item.id" 
               class="nav-item mb-1">
            <div class="nav-link d-flex align-items-center text-dark text-decoration-none py-1 px-2 rounded app-text">
              <i :class="[getItemIcon(item.type), 'me-2 text-secondary']"></i>
              <span>{{ item.title }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Assigned Tasks Slab -->
      <div class="slab">
        <div class="slab-header">
          <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Assigned to Me</h6>
            <button class="btn btn-link text-dark p-0" @click="expandSlab('assigned')">
              <i class="bi bi-arrows-angle-expand"></i>
            </button>
          </div>
        </div>
        <div class="items-container">
          <div v-for="task in assignedTasks" 
               :key="task.id" 
               class="nav-item mb-1">
            <div class="nav-link d-flex align-items-center text-dark text-decoration-none py-1 px-2 rounded app-text">
              <i class="bi bi-check2-square me-2 text-secondary"></i>
              <span>{{ task.title }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <div v-if="expandedSlab" class="modal-backdrop" @click="closeSlab">
      <div class="modal-content" @click.stop>
        <div class="modal-header border-bottom">
          <h6 class="mb-0">{{ expandedSlab === 'recent' ? 'Recent' : 'Assigned to Me' }}</h6>
          <button class="btn btn-link text-dark p-0" @click="closeSlab">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
        <div class="modal-body">
          <template v-if="expandedSlab === 'recent'">
            <div v-for="item in recentItems" 
                 :key="item.id" 
                 class="nav-item mb-1">
              <div class="nav-link d-flex align-items-center text-dark text-decoration-none py-1 px-2 rounded app-text">
                <i :class="[getItemIcon(item.type), 'me-2 text-secondary']"></i>
                <span>{{ item.title }}</span>
              </div>
            </div>
          </template>
          <template v-else>
            <div v-for="task in assignedTasks" 
                 :key="task.id" 
                 class="nav-item mb-1">
              <div class="nav-link d-flex align-items-center text-dark text-decoration-none py-1 px-2 rounded app-text">
                <i class="bi bi-check2-square me-2 text-secondary"></i>
                <span>{{ task.title }}</span>
              </div>
            </div>
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.homepage-container {
  width: 100%;
}

.slabs-container {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
}

.slab {
  background: white;
  height: 380px;
  display: flex;
  flex-direction: column;
  border-radius: 8px;
  border: 1px solid #dee2e6;
}

.slab-header {
  padding: 0.5rem 1rem;
  flex-shrink: 0;
  border-top-left-radius: 8px;
  border-top-right-radius: 8px;
}

.items-container {
  padding: 0.5rem;
  overflow-y: auto;
  flex-grow: 1;
  scrollbar-width: none;
  -ms-overflow-style: none;
}

.items-container::-webkit-scrollbar {
  display: none;
}

.nav-item:hover {
  background-color: #f8f9fa;
  border-radius: 4px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .slabs-container {
    grid-template-columns: 1fr;
  }
}

.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1050;
}

.modal-content {
  background: white;
  width:95%;
  height: 90vh;
  border-radius: 12px;
  display: flex;
  flex-direction: column;
  animation: modalFadeIn 0.2s ease;
  border: 1px solid #dee2e6;
}

.modal-header {
  padding: 1rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #dee2e6;
  border-top-left-radius: 12px;
  border-top-right-radius: 12px;
}

.modal-body {
  padding: 1rem;
  overflow-y: auto;
  flex-grow: 1;
  scrollbar-width: none;
  -ms-overflow-style: none;
}

.modal-body::-webkit-scrollbar {
  display: none;
}

@keyframes modalFadeIn {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@media (max-width: 768px) {
  .slabs-container {
    grid-template-columns: 1fr;
  }
  
  .modal-content {
    width: 90%;
    height: 70vh; /* Maintain 70% height even on mobile */
  }
}

.recent-item {
  display: flex;
  align-items: center;
  padding: 0.75rem;
  margin-bottom: 0.75rem;
  background: white;
  border-radius: 8px;
  transition: all 0.2s ease;
  border: 1px solid #eee;
}

.recent-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  border-color: var(--item-color);
}

.item-icon {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 1rem;
  flex-shrink: 0;
}

.item-icon i {
  font-size: 1.1rem;
}

.item-content {
  flex-grow: 1;
  min-width: 0;
}

.item-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.25rem;
}

.item-header h3 {
  font-size: 0.95rem;
  margin: 0;
  color: #2c3e50;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.item-type {
  font-size: 0.7rem;
  padding: 0.2rem 0.5rem;
  border-radius: 12px;
  background: #f8f9fa;
  color: #6c757d;
  font-weight: 500;
}

.item-date {
  font-size: 0.75rem;
  color: #6c757d;
}

.task-card {
  background: #f8f9fa;
  border-radius: 6px;
  padding: 0.75rem;
  margin-bottom: 0.75rem;
  transition: transform 0.2s ease;
}

.task-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.task-card h3 {
  font-size: 0.95rem;
  margin-bottom: 0.35rem;
  color: #2c3e50;
}

.task-description {
  font-size: 0.8rem;
  color: #6c757d;
  margin-bottom: 0.5rem;
  line-height: 1.3;
}

.task-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.75rem;
}

.task-date {
  color: #6c757d;
}

.task-status {
  padding: 0.2rem 0.5rem;
  border-radius: 8px;
  font-weight: 500;
}

.task-status.completed {
  background: #d4edda;
  color: #155724;
}

.task-status.pending {
  background: #fff3cd;
  color: #856404;
}

.task-status.in.progress {
  background: #cce5ff;
  color: #004085;
}
</style> 