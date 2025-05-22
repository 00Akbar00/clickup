import { createRouter, createWebHistory } from 'vue-router'
import HomePage from '../views/HomePage.vue'
import ListView from '../views/ListView.vue'
import Auth from '../views/Auth.vue'
import InboxPage from '../views/InboxPage.vue'
import TeamSpacePage from '../views/TeamSpacePage.vue'
import ProjectPage from '../views/ProjectPage.vue'
import EverythingPage from '../views/EverythingPage.vue'
import CreateWorkspacePage from '../views/CreateWorkspacePage.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomePage
    },
    {
      path: '/inbox',
      name: 'inbox',
      component: InboxPage
    },
    {
      path: '/everything',
      name: 'everything',
      component: EverythingPage
    },
    {
      path: '/auth',
      name: 'auth',
      component: Auth
    },
    {
      path: '/join',
      name: 'join-workspace',
      component: Auth,
      props: route => ({ inviteToken: route.query.token })
    },
    {
      path: '/create-workspace',
      name: 'create-workspace',
      component: CreateWorkspacePage
    },
    {
      path: '/forgot-password',
      name: 'forgot-password',
      component: Auth
    },
    {
      path: '/reset-password/:token',
      name: 'reset-password',
      component: Auth
    },
    {
      path: '/api/workspaces/:workspaceId/join/:token',
      name: 'workspace-invitation',
      component: Auth,
      props: true
    },
    {
      path: '/:teamspaceName',
      name: 'teamspace',
      component: TeamSpacePage
    },
    {
      path: '/:teamspaceName/:projectName',
      name: 'project',
      component: ProjectPage
    },
    {
      path: '/:teamspaceName/:projectName/:listName',
      name: 'list',
      component: ListView
    },
    {
      path: '/list/:teamId/:projectId/:listId',
      name: 'list-view',
      component: ListView
    }
  ]
})

// Navigation guards
router.beforeEach((to, from, next) => {
  try {
    const isAuthenticated = !!localStorage.getItem('authUser')
    const hasWorkspace = !!localStorage.getItem('has_workspace')
    
    console.log('Route navigation:', { 
      to: to.name, 
      isAuthenticated, 
      hasWorkspace 
    })
    
    // Store invitation params in localStorage if present in the route
    if (to.name === 'workspace-invitation') {
      localStorage.setItem('pendingInvitation', JSON.stringify({
        workspaceId: to.params.workspaceId,
        token: to.params.token
      }))
      
      // If not authenticated, continue to auth component
      if (!isAuthenticated) {
        next()
        return
      }
      
      // If already authenticated, redirect to home to process invitation
      next({ name: 'home' })
      return
    }
    
    // Skip auth check for auth page and related pages
    if (to.name === 'auth' || to.name === 'forgot-password' || to.name === 'reset-password') {
      next()
      return
    }

    // If not authenticated, redirect to auth
    if (!isAuthenticated) {
      next({ name: 'auth' })
      return
    }

    // If authenticated but no workspace, force create-workspace
    // unless they're already on the create-workspace page
    if (!hasWorkspace && to.name !== 'create-workspace') {
      next({ name: 'create-workspace' })
      return
    }

    // Otherwise proceed as normal
    next()
  } catch (error) {
    console.error('Navigation guard error:', error)
    // In case of error, redirect to auth page as fallback
    next({ name: 'auth' })
  }
})

export default router 