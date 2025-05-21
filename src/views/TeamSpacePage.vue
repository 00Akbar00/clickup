<script setup>
import { ref, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useWorkspaceStore } from '../stores/workspaceStore';
import { useTeamspaceStore } from '../stores/teamspaceStore';
import * as bootstrap from 'bootstrap';
import { Pie } from 'vue-chartjs';
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js';
import { useNavigationStore } from '../stores/navigationStore';

ChartJS.register(ArcElement, Tooltip, Legend);

const route = useRoute();
const router = useRouter();
const workspaceStore = useWorkspaceStore();
const teamspaceStore = useTeamspaceStore();
const navigationStore = useNavigationStore();

const teamspace = computed(() => {
  return teamspaceStore.teamspaces.find(t => t.name === decodeURIComponent(route.params.teamspaceName));
});

const projects = computed(() => {
  return teamspace.value ? teamspace.value.projects : [];
});

// Add state for expanded slabs
const expandedSlab = ref(null); // 'recent' or 'projects' or 'lists'

const expandSlab = (type) => {
  expandedSlab.value = type;
  document.body.style.overflow = 'hidden';
};

const closeSlab = () => {
  expandedSlab.value = null;
  document.body.style.overflow = 'auto';
};

const getTaskCount = (list) => {
  return list.tasks ? list.tasks.length : 0;
};

const getCompletedTaskCount = (list) => {
  return list.tasks ? list.tasks.filter(task => task.status === 'completed').length : 0;
};

// Mock recent items - replace with actual data later
const recentItems = computed(() => {
  const items = [];
  teamspace.value?.projects.forEach(project => {
    project.lists?.forEach(list => {
      list.tasks?.forEach(task => {
        items.push({
          id: task.id,
          name: task.name,
          type: 'Task',
          date: task.updatedAt || task.createdAt,
          projectId: project.id,
          listId: list.id
        });
      });
      items.push({
        id: list.id,
        name: list.name,
        type: 'List',
        date: list.updatedAt || list.createdAt,
        projectId: project.id
      });
    });
    items.push({
      id: project.id,
      name: project.name,
      type: 'Project',
      date: project.updatedAt || project.createdAt
    });
  });
  return items.sort((a, b) => new Date(b.date) - new Date(a.date)).slice(0, 5);
});

const getTotalTasks = (project) => {
  return project.lists?.reduce((total, list) => total + (list.tasks?.length || 0), 0) || 0;
};

const getItemIcon = (type) => {
  switch (type) {
    case 'Project':
      return 'bi bi-folder';
    case 'List':
      return 'bi bi-list-ul';
    case 'Task':
      return 'bi bi-check2-square';
    default:
      return 'bi bi-circle';
  }
};

const navigateToProject = (project) => {
  router.push(`/${encodeURIComponent(teamspace.value.name)}/${encodeURIComponent(project.name)}`);
};

const navigateToItem = (item) => {
  if (item.type === 'Task' && item.listId) {
    router.push(`/list/${item.listId}`);
  } else if (item.type === 'List') {
    router.push(`/list/${item.id}`);
  } else if (item.type === 'Project') {
    router.push(`/project/${item.id}`);
  }
};

const showNewProjectModal = () => {
  window.activeTeamspace = teamspace.value;
  const modal = document.querySelector('#newProjectModal');
  if (modal) {
    const modalInstance = bootstrap.Modal.getOrCreateInstance(modal);
    modalInstance.show();
  }
};

// Compute all lists in the teamspace
const allLists = computed(() => {
  const lists = [];
  teamspace.value?.projects.forEach(project => {
    if (project.lists) {
      project.lists.forEach(list => {
        lists.push({
          ...list,
          projectName: project.name
        });
      });
    }
  });
  return lists;
});

// Compute task statistics for pie chart
const taskStats = computed(() => {
  let todo = 0;
  let inProgress = 0;
  let completed = 0;

  allLists.value.forEach(list => {
    if (list.tasks) {
      list.tasks.forEach(task => {
        switch (task.status) {
          case 'todo':
            todo++;
            break;
          case 'in progress':
            inProgress++;
            break;
          case 'completed':
            completed++;
            break;
        }
      });
    }
  });

  return {
    labels: ['To Do', 'In Progress', 'Completed'],
    datasets: [{
      backgroundColor: ['#FFE0E6', '#E5E6FF', '#D4EDDA'],
      data: [todo, inProgress, completed]
    }]
  };
});

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom'
    }
  }
};

const getListProgress = (list) => {
  if (!list.tasks || list.tasks.length === 0) return 0;
  const completedTasks = list.tasks.filter(task => task.status === 'completed').length;
  return Math.round((completedTasks / list.tasks.length) * 100);
};

const navigateToList = (list, project) => {
  router.push(`/${encodeURIComponent(teamspace.value.name)}/${encodeURIComponent(project.name)}/${encodeURIComponent(list.name)}`);
};

// Add a watcher to update navigation store when teamspace is found
watch(teamspace, (newTeamspace) => {
  if (newTeamspace) {
    navigationStore.setTeamspace(newTeamspace);
  }
}, { immediate: true });
</script>

<template>
  <div class="teamspace-page">
    <!-- Content Grid -->
    <div class="slabs-container">
      <!-- Recent Activity -->
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
          <div v-if="recentItems.length" class="recent-list">
            <div v-for="item in recentItems" 
                 :key="item.id" 
                 class="nav-item mb-1"
                 @click="navigateToItem(item)">
              <div class="nav-link d-flex align-items-center text-dark text-decoration-none py-1 px-2 rounded app-text">
                <i :class="[getItemIcon(item.type), 'me-2 text-secondary']"></i>
                <span>{{ item.name }}</span>
              </div>
            </div>
          </div>
          <div v-else class="empty-state  d-flex align-items-center justify-content-center h-100">
            <p class="">No recent activity</p>
          </div>
        </div>
      </div>

      <!-- Projects -->
      <div class="slab">
        <div class="slab-header">
          <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Projects</h6>
            <div class="d-flex align-items-center">
              <button class="btn btn-link text-dark p-0 me-2" @click="showNewProjectModal">
                <i class="bi bi-plus"></i>
              </button>
              <button class="btn btn-link text-dark p-0" @click="expandSlab('projects')">
                <i class="bi bi-arrows-angle-expand"></i>
              </button>
            </div>
          </div>
        </div>
        <div class="items-container">
          <div v-if="teamspace?.projects?.length" class="projects-list">
            <div v-for="project in teamspace.projects" 
                 :key="project.id" 
                 class="nav-item mb-1"
                 @click="navigateToProject(project)">
              <div class="nav-link d-flex align-items-center text-dark text-decoration-none py-1 px-2 rounded app-text">
                <i class="bi bi-folder me-2 text-secondary"></i>
                <div class="d-flex flex-column">
                  <span>{{ project.name }}</span>
                  <small class="text-muted">
                    {{ project.lists?.length || 0 }} lists · {{ getTotalTasks(project) }} tasks
                  </small>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="empty-state d-flex align-items-center justify-content-center h-100">
            <p class="">No projects yet</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Lists and Stats Row -->
    <div class="lists-stats-container">
      <!-- Lists Section -->
      <div class="lists-section">
        <div class="section-header">
          <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0">All Lists</h6>
            <button class="btn btn-link text-dark p-0" @click="expandSlab('lists')">
              <i class="bi bi-arrows-angle-expand"></i>
            </button>
          </div>
        </div>
        <div class="lists-container">
          <div v-if="allLists.length" class="lists-table">
            <!-- Header -->
            <div class="list-row header">
              <div class="list-name">List Name</div>
              <div class="list-project">Project</div>
              <div class="list-tasks">Tasks</div>
              <div class="list-progress">Progress</div>
            </div>
            <!-- List Items -->
            <div v-for="list in allLists" 
                 :key="list.id" 
                 class="list-row"
                 @click="navigateToList(list, teamspace.projects.find(p => p.name === list.projectName))">
              <div class="list-name">
                <i class="bi bi-list-ul me-2 text-secondary"></i>
                {{ list.name }}
              </div>
              <div class="list-project">
                <i class="bi bi-folder me-2 text-secondary"></i>
                {{ list.projectName }}
              </div>
              <div class="list-tasks">
                {{ list.tasks?.length || 0 }} tasks
              </div>
              <div class="list-progress">
                <div class="progress" style="height: 6px; width: 100px;">
                  <div class="progress-bar bg-success" 
                       role="progressbar" 
                       :style="{ width: getListProgress(list) + '%' }" 
                       :aria-valuenow="getListProgress(list)" 
                       aria-valuemin="0" 
                       aria-valuemax="100">
                  </div>
                </div>
                <span class="progress-text ms-2">
                  {{ getListProgress(list) }}%
                </span>
              </div>
            </div>
          </div>
          <div v-else class="empty-state d-flex align-items-center justify-content-center h-100">
            <p class="">No lists available</p>
          </div>
        </div>
      </div>

      <!-- Task Stats Section -->
      <div class="stats-section">
        <div class="section-header">
          <h6 class="mb-0">Task Status</h6>
        </div>
        <div class="chart-container">
          <Pie
            :data="taskStats"
            :options="chartOptions"
          />
        </div>
      </div>
    </div>

    <!-- Modal -->
    <div v-if="expandedSlab" class="modal-backdrop" @click="closeSlab">
      <div class="modal-content" @click.stop>
        <div class="modal-header border-bottom">
          <h6 class="mb-0">{{ 
            expandedSlab === 'recent' ? 'Recent' : 
            expandedSlab === 'projects' ? 'Projects' : 
            'All Lists' 
          }}</h6>
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
                <span>{{ item.name }}</span>
              </div>
            </div>
          </template>
          <template v-else-if="expandedSlab === 'projects'">
            <div v-for="project in teamspace?.projects" 
                 :key="project.id" 
                 class="nav-item mb-1">
              <div class="nav-link d-flex align-items-center text-dark text-decoration-none py-1 px-2 rounded app-text">
                <i class="bi bi-folder me-2 text-secondary"></i>
                <div class="d-flex flex-column">
                  <span>{{ project.name }}</span>
                  <small class="text-muted">
                    {{ project.lists?.length || 0 }} lists · {{ getTotalTasks(project) }} tasks
                  </small>
                </div>
              </div>
            </div>
          </template>
          <template v-else>
            <!-- Lists Table in Modal -->
            <div class="lists-table">
              <div class="list-row header">
                <div class="list-name">List Name</div>
                <div class="list-project">Project</div>
                <div class="list-tasks">Tasks</div>
                <div class="list-progress">Progress</div>
              </div>
              <div v-for="list in allLists" 
                   :key="list.id" 
                   class="list-row"
                   @click="navigateToList(list, teamspace.projects.find(p => p.name === list.projectName))">
                <div class="list-name">
                  <i class="bi bi-list-ul me-2 text-secondary"></i>
                  {{ list.name }}
                </div>
                <div class="list-project">
                  <i class="bi bi-folder me-2 text-secondary"></i>
                  {{ list.projectName }}
                </div>
                <div class="list-tasks">
                  {{ list.tasks?.length || 0 }} tasks
                </div>
                <div class="list-progress">
                  <div class="progress" style="height: 6px; width: 100px;">
                    <div class="progress-bar bg-success" 
                         role="progressbar" 
                         :style="{ width: getListProgress(list) + '%' }" 
                         :aria-valuenow="getListProgress(list)" 
                         aria-valuemin="0" 
                         aria-valuemax="100">
                    </div>
                  </div>
                  <span class="progress-text ms-2">
                    {{ getListProgress(list) }}%
                  </span>
                </div>
              </div>
            </div>
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.teamspace-page {
  height: calc(100vh - 96px);
  overflow-y: auto;
}

.slabs-container {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
  margin-bottom: 1rem;
}

.slab {
  background: white;
  height: 370px;
  display: flex;
  flex-direction: column;
  border-radius: 8px;
  border: 1px solid #dee2e6;
}

.slab-header {
  padding: 0.5rem 1rem;
  flex-shrink: 0;
  background: white;
  border-bottom: 1px solid #dee2e6;
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
  cursor: pointer;
}

/* Modal styles */
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
  width: 95%;
  height: 85vh;
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
    height: 70vh;
  }
}

.lists-stats-container {
  display: grid;
  grid-template-columns: 65fr 35fr;
  gap: 1rem;
  margin-bottom: 1rem;
}

.lists-section,
.stats-section {
  background: white;
  height: 300px;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
}

.section-header {
  padding: 0.5rem 1rem;
  background: white;
  border-bottom: 1px solid #dee2e6;
  flex-shrink: 0;
}

.lists-container {
  padding: 0.5rem;
  overflow-y: auto;
  flex-grow: 1;
  scrollbar-width: none;
  -ms-overflow-style: none;
}

.lists-container::-webkit-scrollbar {
  display: none;
}

.lists-table {
  height: 100%;
}

.list-row {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1.5fr;
  gap: 1rem;
  padding: 0.75rem 1rem;
  align-items: center;
  border-bottom: 1px solid rgba(0, 0, 0, 0.1);
  cursor: pointer;
}

.list-row.header {
  font-size: 0.75rem;
  color: #666;
  font-weight: 500;
  cursor: default;
  background: white;
  border-bottom: 1px solid #dee2e6;
  margin-bottom: 0.5rem;
}

.list-name {
  font-weight: 500;
}

.list-project,
.list-tasks {
  color: #666;
}

.progress {
  background-color: #e9ecef;
  border-radius: 3px;
}

.progress-bar {
  border-radius: 3px;
}

.progress-text {
  font-size: 0.75rem;
  color: #666;
}

.chart-container {
  padding: 1rem;
  height: 250px;
  display: flex;
  align-items: center;
  justify-content: center;
}

@media (max-width: 768px) {
  .lists-stats-container {
    grid-template-columns: 1fr;
  }
}
</style> 