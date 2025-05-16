<template>
  <div class="everything-page">
    <!-- Fixed Header -->
    

    <!-- Scrollable Content -->
    <div class="content-container">
      <!-- Teamspaces with nested content -->
      <div class="teamspaces-section">
        <div v-for="teamspace in teamspaces" :key="teamspace.id" class="teamspace-card mb-4">
          <!-- Teamspace Header -->
          <div class="card border mb-3">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <i class="bi bi-building me-2"></i>
                <h3 class="app-text-lg mb-0">{{ teamspace.name }}</h3>
              </div>
            </div>
          </div>

          <!-- Projects within Teamspace -->
          <div class="projects-section ms-4">
            <div v-for="project in teamspace.projects" :key="project.id" class="project-card mb-4">
              <!-- Project Header -->
              <div class="card border mb-3">
                <div class="card-body">
                  <div class="d-flex align-items-center">
                    <i class="bi bi-folder me-2"></i>
                    <h4 class="app-text mb-0">{{ project.name }}</h4>
                  </div>
                </div>
              </div>

              <!-- Lists within Project -->
              <div class="lists-section ms-4">
                <div v-for="list in project.lists" :key="list.id" class="list-card mb-4">
                  <!-- List Header -->
                  <div class="card border mb-3">
                    <div class="card-body">
                      <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                          <i class="bi bi-list-ul me-2"></i>
                          <h5 class="app-text mb-0">{{ list.name }}</h5>
                        </div>
                        <span class="app-text text-muted">{{ list.tasks?.length || 0 }} tasks</span>
                      </div>
                    </div>
                  </div>

                  <!-- Tasks within List -->
                  <div class="tasks-section ms-4">
                    <div v-if="list.tasks?.length" class="task-list">
                      <div v-for="task in list.tasks" :key="task.id" class="task-card mb-2">
                        <div class="card border">
                          <div class="card-body py-2">
                            <div class="d-flex align-items-center justify-content-between">
                              <div class="d-flex align-items-center">
                                <i class="bi bi-check2-circle me-2"></i>
                                <span class="app-text">{{ task.name }}</span>
                              </div>
                              <div class="task-meta d-flex align-items-center">
                                <span v-if="task.assignee" class="app-text text-muted me-3">
                                  <i class="bi bi-person me-1"></i>
                                  {{ task.assignee }}
                                </span>
                                <span v-if="task.dueDate" class="app-text text-muted">
                                  <i class="bi bi-calendar me-1"></i>
                                  {{ task.dueDate }}
                                </span>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div v-else class="text-muted app-text fst-italic">
                      No tasks yet
                    </div>
                  </div>
                </div>
                <div v-if="!project.lists?.length" class="text-muted app-text fst-italic">
                  No lists yet
                </div>
              </div>
            </div>
            <div v-if="!teamspace.projects?.length" class="text-muted app-text fst-italic">
              No projects yet
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useWorkspaceStore } from '../stores/workspaceStore'

const workspaceStore = useWorkspaceStore()
const teamspaces = computed(() => workspaceStore.teamspaces)
</script>

<style scoped>
.everything-page {
  height: calc(100vh - 96px); /* Subtract navbar + breadcrumb height */
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.page-header {
  padding: 1.5rem;
  background: white;
  flex-shrink: 0;
}

.content-container {
  flex: 1;
  overflow-y: auto;
  padding: 0 1.5rem 1.5rem;
}

/* Hide scrollbar for Chrome, Safari and Opera */
.content-container::-webkit-scrollbar {
  display: none;
}

/* Hide scrollbar for IE, Edge and Firefox */
.content-container {
  -ms-overflow-style: none;  /* IE and Edge */
  scrollbar-width: none;  /* Firefox */
}

.card {
  background: white;
  border-radius: 8px;
  transition: all 0.2s ease;
}

.card:hover {
  transform: translateY(-2px);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.projects-section,
.lists-section,
.tasks-section {
  position: relative;
}

.projects-section::before,
.lists-section::before,
.tasks-section::before {
  content: '';
  position: absolute;
  left: -12px;
  top: 0;
  bottom: 12px;
  width: 1px;
  background-color: rgba(0, 0, 0, 0.1);
}

.bi {
  font-size: 1rem;
}

.task-meta {
  font-size: var(--app-font-size);
}
</style> 