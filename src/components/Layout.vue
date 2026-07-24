<template>
  <div class="flex h-screen bg-gray-50">
    <!-- Sidebar -->
    <div 
      :class="sidebarOpen ? 'w-64' : 'w-20'"
      class="bg-white border-r border-gray-200 transition-all duration-300 flex flex-col"
    >
      <div class="p-4 border-b border-gray-200 flex items-center justify-between">
        <div v-if="sidebarOpen" class="flex items-center gap-2">
          <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
            <span class="text-white font-bold text-sm">DF</span>
          </div>
          <span class="font-semibold text-gray-800">Footprint Tracker</span>
        </div>
        <div v-else class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center mx-auto">
          <span class="text-white font-bold text-sm">DF</span>
        </div>
        <button 
          @click="sidebarOpen = !sidebarOpen"
          class="p-1 hover:bg-gray-100 rounded-lg"
        >
          <Menu v-if="sidebarOpen" class="w-5 h-5" />
          <X v-else class="w-5 h-5" />
        </button>
      </div>

      <nav class="flex-1 p-4 space-y-1">
        <router-link 
          v-for="item in navigation" 
          :key="item.name"
          :to="item.href"
          class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors"
          :class="$route.path === item.href ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50'"
        >
          <component :is="item.icon" class="w-5 h-5 flex-shrink-0" />
          <span v-if="sidebarOpen">{{ item.name }}</span>
        </router-link>
      </nav>

      <div class="p-4 border-t border-gray-200">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
            <User class="w-4 h-4 text-gray-500" />
          </div>
          <div v-if="sidebarOpen" class="flex-1">
            <p class="text-sm font-medium">Admin</p>
            <p class="text-xs text-gray-500">admin@email.com</p>
          </div>
          <button class="p-1 hover:bg-gray-100 rounded-lg">
            <LogOut class="w-4 h-4 text-gray-500" />
          </button>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
      <header class="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
        <div class="flex-1">
          <div class="max-w-md relative">
            <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
            <input
              type="text"
              placeholder="Search candidates..."
              class="w-full pl-9 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>
        </div>
        <div class="flex items-center gap-3">
          <button class="p-2 hover:bg-gray-100 rounded-lg relative">
            <Bell class="w-5 h-5 text-gray-500" />
            <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
          </button>
        </div>
      </header>

      <main class="flex-1 overflow-auto p-6">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { 
  LayoutDashboard, 
  UserPlus, 
  Menu, 
  X, 
  Search, 
  Bell, 
  User, 
  LogOut 
} from 'lucide-vue-next'

const sidebarOpen = ref(true)

const navigation = [
  { name: 'Dashboard', href: '/', icon: LayoutDashboard },
  { name: 'Add Candidate', href: '/add', icon: UserPlus },
]
</script>