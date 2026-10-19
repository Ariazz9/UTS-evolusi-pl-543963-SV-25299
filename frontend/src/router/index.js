import { createRouter, createWebHistory } from 'vue-router'
import authService from '../services/auth'
import LoginPage from '../views/LoginPage.vue'
import MaterialsPage from '../views/MaterialsPage.vue'

const routes = [
  {
    path: '/',
    redirect: '/materials'
  },
  {
    path: '/login',
    name: 'login',
    component: LoginPage,
    meta: { guestOnly: true }
  },
  {
    path: '/materials',
    name: 'materials',
    component: MaterialsPage,
    meta: { requiresAuth: true }
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/materials'
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Navigation Guard: Proteksi rute berbasis token Sanctum
router.beforeEach((to, from, next) => {
  const isAuth = authService.isAuthenticated()

  if (to.meta.requiresAuth && !isAuth) {
    next({ name: 'login' })
  } else if (to.meta.guestOnly && isAuth) {
    next({ name: 'materials' })
  } else {
    next()
  }
})

export default router

