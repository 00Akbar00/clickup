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
      path: '/teamspace/:id',
      name: 'teamspace',
      component: TeamSpacePage,
      props: true
    },
    {
      path: '/project/:id',
      name: 'project',
      component: ProjectPage,
      props: true
    },
    {
      path: '/page2',
      name: 'page2',
      component: Page2,
    },
    {
      path: '/page3',
      name: 'page3',
      component: Page3,
    },
    {
      path: '/list/:id',
      name: 'list',
      component: ListView,
      props: true,
    },
    {
      path: '/auth',
      name: 'auth',
      // path: '/reset-password/:token',
      // name: 'reset-password',
      
      component: Auth
    },
    {
      path: '/everything',
      name: 'everything',
      component: EverythingPage
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
    }
  ]
})

// Navigation guards
router.beforeEach((to, from, next) => {
  const isAuthenticated = !!localStorage.getItem('authUser')
  
  // Skip auth check for auth page
  if (to.name === 'auth') {
    next()
    return
  }
  
  if (to.meta.requiresAuth && !isAuthenticated) {
    // If route requires auth and user is not authenticated, redirect to auth page
    next({ name: 'auth' })
  } else {
    // Otherwise proceed as normal
    next()
  }
})

export default router 