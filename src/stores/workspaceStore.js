import { defineStore } from 'pinia'
import { workspaceData } from '../data/workspace'
import { ref } from 'vue'

export const useWorkspaceStore = defineStore('workspace', () => {
  // State
  const teamspaces = ref(workspaceData.teamspaces)

  // Actions
  function addProject(teamspace, project) {
    const teamspaceToUpdate = teamspaces.value.find(t => t.id === teamspace.id)
    if (teamspaceToUpdate) {
      if (!teamspaceToUpdate.projects) {
        teamspaceToUpdate.projects = []
      }
      project.lists = []
      teamspaceToUpdate.projects = [...teamspaceToUpdate.projects, project]
    }
  }

  function addList(project, list) {
    const activeTeamspaceId = window.activeTeamspace?.id
    if (!activeTeamspaceId) {
      console.error('No active teamspace found')
      return
    }

    const teamspace = teamspaces.value.find(t => t.id === activeTeamspaceId)
    if (!teamspace) {
      console.error('Active teamspace not found in store')
      return
    }

    const projectToUpdate = teamspace.projects.find(p => p.id === project.id)
    if (!projectToUpdate) {
      console.error(`Project ${project.name} not found in teamspace ${teamspace.name}`)
      return
    }

    if (!projectToUpdate.lists) {
      projectToUpdate.lists = []
    }

    projectToUpdate.lists = [...projectToUpdate.lists, list]
    console.log(`Added list "${list.name}" to: ${teamspace.name}/Project ${projectToUpdate.name}`)
  }

  return {
    teamspaces,
    addProject,
    addList
  }
}) 