import './style.css'

import { createApp } from 'vue'
import { createRouter, createWebHistory } from 'vue-router'
import App from './App.vue'
import Dashboard from './components/Dashboard.vue'
import AddCandidate from './components/AddCandidate.vue'
import CandidateDetail from './components/CandidateDetail.vue'

const routes = [
  { path: '/', component: Dashboard },
  { path: '/add', component: AddCandidate },
  { path: '/candidate/:id', component: CandidateDetail },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

const app = createApp(App)
app.use(router)
app.mount('#app')