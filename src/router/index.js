import { createRouter, createWebHistory } from 'vue-router'
import HomePage from '../views/HomePage.vue'
import Page2 from '../views/Page2.vue'
import Page3 from '../views/Page3.vue'
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
      // path: '/reset-password/:token',
      // name: 'reset-password',
      
      component: Auth
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
      path: '/api/reset-password/:token',
      name: 'reset-password',
      component: Auth
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
    }
  ]
})

// Navigation guards
router.beforeEach((to, from, next) => {
  const isAuthenticated = !!localStorage.getItem('authUser')
  const hasWorkspace = !!localStorage.getItem('hasWorkspace')
  
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
})

export default router 