import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useNavigationStore = defineStore('navigation', () => {
  // State
  const activeTeamspace = ref(null)
  const activeProject = ref(null)
  const activeList = ref(null)

  // Actions
  function setTeamspace(teamspace) {
    activeTeamspace.value = teamspace
    activeProject.value = null
    activeList.value = null
  }

  function setProject(project) {
    activeProject.value = project
    activeList.value = null
  }

  function setList(list) {
    activeList.value = list
  }

  function clearActiveItems() {
    activeTeamspace.value = null
    activeProject.value = null
    activeList.value = null
  }

  // Getters
  const breadcrumbItems = computed(() => {
    const items = []
    
    if (activeTeamspace.value) {
      items.push({
        name: activeTeamspace.value.name,
        type: 'teamspace',
        id: activeTeamspace.value.id
      })
    }
    
    if (activeProject.value) {
      items.push({
        name: activeProject.value.name,
        type: 'project',
        id: activeProject.value.id
      })
    }
    
    if (activeList.value) {
      items.push({
        name: activeList.value.name,
        type: 'list',
        id: activeList.value.id
      })
    }
    
    return items
  })

  return {
    activeTeamspace,
    activeProject,
    activeList,
    setTeamspace,
    setProject,
    setList,
    clearActiveItems,
    breadcrumbItems
  }
}) 