import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/authStore';
import { authGuard } from './guards/authGuard';

import AppLayout from '../components/layouts/App.vue';
import HomeView from '../views/HomeView.vue';
import UsersView from '../views/UsersView.vue';
import TasksView from '../views/TasksView.vue';
import LoginView from '../views/LoginView.vue';
import RegisterView from '../views/RegisterView.vue';
import ForgotPasswordView from '../views/ForgotPasswordView.vue';
import ResetPasswordView from '../views/ResetPasswordView.vue';

const routes = [
  { path: '/login', name: 'login', component: LoginView },
  { path: '/register', name: 'register', component: RegisterView },
  { path: '/forgot-password', name: 'forgot-password', component: ForgotPasswordView },
  { path: '/reset-password/:token?', name: 'reset-password', component: ResetPasswordView, props: true },
  { path: '/', redirect: '/login' },
  {
    path: '/',
    component: AppLayout,
    meta: { requiresAuth: true },
    children: [
      { path: 'home', name: 'home', component: HomeView },
      { path: 'users', name: 'users', component: UsersView },
      { path: 'tasks', name: 'tasks', component: TasksView }
      //{ path: 'tasks', name: 'tasks', component: () => import('@/views/TasksView.vue') } //to lazy load
    ]
  },
  { path: '/:pathMatch(.*)*', name: 'catchAll', meta: { catchAll: true } }
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach(authGuard);

export default router;
