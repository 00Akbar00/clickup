import { defineStore } from 'pinia'
import { workspaceData } from '../data/workspace'
import { ref } from 'vue'

export const useWorkspaceStore = defineStore('workspace', () => {
  // State
  const teamspaces = ref(workspaceData.teamspaces)

  // Helper function to find teamspace and its components
  function findTeamspaceComponents(teamspaceId) {
    const teamspace = teamspaces.value.find(t => t.id === teamspaceId)
    if (!teamspace) return null
    return { teamspace }
  }

  function findProjectComponents(teamspaceId, projectId) {
    const result = findTeamspaceComponents(teamspaceId)
    if (!result) return null
    
    const project = result.teamspace.projects?.find(p => p.id === projectId)
    if (!project) return null
    
    return { ...result, project }
  }

  function findListComponents(teamspaceId, projectId, listId) {
    const result = findProjectComponents(teamspaceId, projectId)
    if (!result) return null
    
    const list = result.project.lists?.find(l => l.id === listId)
    if (!list) return null
    
    return { ...result, list }
  }

  // Actions
  function addTeamspace(teamspace) {
    if (!teamspace.projects) {
      teamspace.projects = []
    }
    teamspaces.value.push(teamspace)
    console.log(`Created new teamspace: ${teamspace.name}`)
  }

  function addProject(teamspaceId, project) {
    const result = findTeamspaceComponents(teamspaceId)
    if (!result) {
      console.error('Teamspace not found')
      return
    }

    if (!result.teamspace.projects) {
      result.teamspace.projects = []
    }

    project.lists = []
    result.teamspace.projects.push(project)
    console.log(`Added project "${project.name}" to teamspace: ${result.teamspace.name}`)
  }

  function addList(teamspaceId, projectId, list) {
    const result = findProjectComponents(teamspaceId, projectId)
    if (!result) {
      console.error('Project or teamspace not found')
      return
    }

    if (!result.project.lists) {
      result.project.lists = []
    }

    list.tasks = []
    result.project.lists.push(list)
    console.log(`Added list "${list.name}" to: ${result.teamspace.name}/Project ${result.project.name}`)
  }

  function addTask(teamspaceId, projectId, listId, task) {
    const result = findListComponents(teamspaceId, projectId, listId)
    if (!result) {
      console.error('List, project, or teamspace not found')
      return
    }

    if (!result.list.tasks) {
      result.list.tasks = []
    }

    result.list.tasks.push(task)
    console.log(`Added task "${task.name}" to: ${result.teamspace.name}/Project ${result.project.name}/List ${result.list.name}`)
  }

  function updateTaskStatus(teamspaceId, projectId, listId, taskId, newStatus) {
    const list = this.getList(teamspaceId, projectId, listId)
    if (list && list.tasks) {
      const task = list.tasks.find(t => t.id === taskId)
      if (task) {
        task.status = newStatus
      }
    }
  }

  // Getters
  function getTeamspace(teamspaceId) {
    return teamspaces.value.find(t => t.id === teamspaceId)
  }

  function getProject(teamspaceId, projectId) {
    const result = findProjectComponents(teamspaceId, projectId)
    return result?.project
  }

  function getList(teamspaceId, projectId, listId) {
    const result = findListComponents(teamspaceId, projectId, listId)
    return result?.list
  }

  return {
    teamspaces,
    addTeamspace,
    addProject,
    addList,
    addTask,
    updateTaskStatus,
    getTeamspace,
    getProject,
    getList
  }
}) 