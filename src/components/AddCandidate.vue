<template>
  <div class="max-w-3xl mx-auto">
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-gray-900">Add New Candidate</h1>
      <p class="text-gray-600 mt-1">Start tracking digital footprint of a candidate</p>
    </div>

    <div class="bg-white rounded-lg shadow-sm border p-6">
      <form @submit.prevent="handleSubmit" class="space-y-6">
        <div v-if="error" class="flex items-start gap-3 p-4 bg-red-50 border border-red-200 rounded-lg">
          <AlertCircle class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" />
          <div>
            <p class="text-sm font-medium text-red-800">Error</p>
            <p class="text-sm text-red-600">{{ error }}</p>
          </div>
        </div>

        <div v-if="success" class="flex items-start gap-3 p-4 bg-green-50 border border-green-200 rounded-lg">
          <CheckCircle class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" />
          <div>
            <p class="text-sm font-medium text-green-800">Success!</p>
            <p class="text-sm text-green-600">Candidate added successfully. Redirecting...</p>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
            <input 
              v-model="formData.name"
              type="text" 
              required 
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none" 
              placeholder="John Doe"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Username (Internal) *</label>
            <input 
              v-model="formData.username"
              type="text" 
              required 
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none" 
              placeholder="johndoe_123"
            />
          </div>
        </div>

        <div>
          <h3 class="text-sm font-medium text-gray-700 mb-3">Social Media Profiles</h3>
          <p class="text-xs text-gray-500 mb-4">Enter the username (without @) for each platform</p>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-for="platform in platforms" :key="platform.key">
              <label class="block text-sm text-gray-600 mb-1 capitalize">{{ platform.label }}</label>
              <div class="relative">
                <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400">@</span>
                <input 
                  v-model="formData.social_links[platform.key]"
                  type="text" 
                  class="w-full pl-7 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none" 
                  :placeholder="platform.label.toLowerCase()"
                />
              </div>
            </div>
          </div>
        </div>

        <div class="flex gap-3 pt-4 border-t border-gray-200">
          <button type="submit" :disabled="loading" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 font-medium disabled:opacity-50">
            {{ loading ? 'Adding...' : 'Add Candidate & Start Scraping' }}
          </button>
          <button type="button" @click="router.push('/')" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors duration-200 font-medium">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { AlertCircle, CheckCircle } from 'lucide-vue-next'
import { candidateService } from '../services/apiService'

const router = useRouter()
const loading = ref(false)
const success = ref(false)
const error = ref(null)

const formData = reactive({
  name: '',
  username: '',
  social_links: {
    instagram: '',
    twitter: '',
    tiktok: '',
    threads: '',
    facebook: '',
    linkedin: ''
  }
})

const platforms = [
  { key: 'instagram', label: 'Instagram' },
  { key: 'twitter', label: 'Twitter' },
  { key: 'tiktok', label: 'TikTok' },
  { key: 'threads', label: 'Threads' },
  { key: 'facebook', label: 'Facebook' },
  { key: 'linkedin', label: 'LinkedIn' },
]

const handleSubmit = async () => {
  loading.value = true
  error.value = null
  success.value = false

  const socialLinks = Object.fromEntries(
    Object.entries(formData.social_links).filter(([_, value]) => value.trim() !== '')
  )

  if (Object.keys(socialLinks).length === 0) {
    error.value = 'Please provide at least one social media username'
    loading.value = false
    return
  }

  try {
    const response = await candidateService.create({
      name: formData.name.trim(),
      username: formData.username.trim(),
      social_links: socialLinks
    })

    success.value = true
    setTimeout(() => {
      router.push(`/candidate/${response.data.candidate.id}`)
    }, 2000)
  } catch (err) {
    error.value = err.message || 'Error adding candidate'
    console.error('Error:', err)
  } finally {
    loading.value = false
  }
}
</script>