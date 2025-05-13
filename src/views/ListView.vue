<template>
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h1 class="mb-0">{{ listName }}</h1>
          <button class="btn btn-primary btn-sm" @click="showNewTaskModal = true">
            <i class="bi bi-plus"></i> Add Task
          </button>
        </div>

        <!-- Tasks List -->
        <div class="card">
          <div class="card-body">
            <div v-for="task in tasks" :key="task.id" class="task-item d-flex align-items-center py-2 border-bottom">
              <div class="form-check me-3">
                <input class="form-check-input" type="checkbox" :id="'task' + task.id">
                <label class="form-check-label" :for="'task' + task.id">
                  {{ task.name }}
                </label>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { workspaceData } from '../data/workspace'

// Props
const props = defineProps({
  id: {
    type: String,
    required: true
  }
})

// State
const showNewTaskModal = ref(false)

// Computed
const list = computed(() => {
  // Find the list with matching ID
  for (const teamspace of workspaceData.teamspaces) {
    for (const project of teamspace.projects) {
      const list = project.lists.find(l => l.id === parseInt(props.id))
      if (list) return list
    }
  }
  return null
})

const listName = computed(() => list.value ? list.value.name : 'List not found')
const tasks = computed(() => list.value ? list.value.tasks : [])
</script>

<style scoped>
.task-item:last-child {
  border-bottom: none !important;
}

.form-check-input:checked + .form-check-label {
  text-decoration: line-through;
  color: #6c757d;
}
</style> 