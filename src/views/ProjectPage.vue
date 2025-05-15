<script setup>
import { ref, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useWorkspaceStore } from '../stores/workspaceStore';
import * as bootstrap from 'bootstrap';

const route = useRoute();
const router = useRouter();
const workspaceStore = useWorkspaceStore();

const project = computed(() => {
  let foundProject = null;
  workspaceStore.teamspaces.some(teamspace => {
    foundProject = teamspace.projects.find(p => p.id === parseInt(route.params.id));
    return foundProject;
  });
  return foundProject;
});

const lists = computed(() => {
  return project.value ? project.value.lists : [];
});

const getListProgress = (list) => {
  if (!list.tasks || list.tasks.length === 0) return 0;
  const completedTasks = list.tasks.filter(task => task.status === 'completed').length;
  return Math.round((completedTasks / list.tasks.length) * 100);
};

const getTaskCount = (list) => {
  return list.tasks ? list.tasks.length : 0;
};

const handleListClick = (listId) => {
  router.push(`/list/${listId}`);
};

const showNewListModal = () => {
  const modal = document.querySelector('#newListModal');
  if (modal) {
    const modalInstance = new bootstrap.Modal(modal);
    modalInstance.show();
  }
};
</script>

<template>
  <div class="list-view">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="app-text-lg mb-0">{{ project?.name }}</h2>
      <button class="btn btn-primary" @click="showNewListModal">
        <i class="bi bi-plus"></i> Add List
      </button>
    </div>

    <div class="lists-container">
      <!-- Header Row -->
      <div class="list-row header app-text">
        <div class="list-name">List Name</div>
        <div class="list-due-date">Due Date</div>
        <div class="list-priority">Priority</div>
        <div class="list-progress">Progress</div>
      </div>

      <!-- List Rows -->
      <div v-if="lists.length" class="list-list">
        <div v-for="list in lists" 
             :key="list.id" 
             class="list-row app-text"
             @click="handleListClick(list.id)">
          <div class="list-name">
            <i class="bi bi-list-ul me-2"></i>
            {{ list.name }}
          </div>
          <div class="list-due-date">
            <i class="bi bi-calendar me-1"></i>
            {{ list.dueDate || 'No date' }}
          </div>
          <div class="list-priority">
            <i class="bi bi-flag me-1"></i>
            {{ list.priority || 'Normal' }}
          </div>
          <div class="list-progress">
            <div class="d-flex align-items-center">
              <div class="progress" style="height: 6px; width: 120px;">
                <div class="progress-bar bg-success" 
                     role="progressbar" 
                     :style="{ width: getListProgress(list) + '%' }" 
                     :aria-valuenow="getListProgress(list)" 
                     aria-valuemin="0" 
                     aria-valuemax="100">
                </div>
              </div>
              <span class="task-count">
                ({{ list.tasks?.filter(t => t.status === 'completed').length || 0 }}/{{ getTaskCount(list) }})
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="empty-state app-text text-center py-5">
        <i class="bi bi-list-ul fs-1 mb-3 d-block"></i>
        <p class="mb-0">No lists yet. Click the "Add List" button to create one.</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.list-view {
  padding: 1rem;
}

.lists-container {
  border-radius: 8px;
}

.list-row {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 2fr;
  gap: 1rem;
  padding: 0.875rem 1rem;
  align-items: center;
  border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.list-list div.list-row:last-child {
  border-bottom: none;
}

.list-row.header {
  background-color: white;
  font-size: 0.75rem;
  color: #666;
  font-weight: 500;
}

.list-name {
  font-weight: 500;
}

.list-due-date,
.list-priority,
.list-progress {
  color: #666;
}

.empty-state {
  color: #666;
}

.empty-state i {
  color: #ccc;
}

.progress {
  background-color: #e9ecef;
  border-radius: 3px;
}

.progress-bar {
  border-radius: 3px;
}

.list-progress {
  color: #666;
}

.task-count {
  font-size: 0.875rem;
  color: #666;
  margin-left: 0.5rem;
}
</style> 