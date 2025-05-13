import { createRouter, createWebHistory } from 'vue-router'
import HomePage from '../views/HomePage.vue'
import Page2 from '../views/Page2.vue'
import Page3 from '../views/Page3.vue'
import ListView from '../views/ListView.vue'
import Auth from '../views/Auth.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomePage,
  
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