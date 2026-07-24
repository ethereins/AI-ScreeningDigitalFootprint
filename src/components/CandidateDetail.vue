<template>
  <div>
    <div class="flex items-center justify-between mb-8">
      <div class="flex items-center gap-4">
        <button @click="router.push('/')" class="p-2 hover:bg-gray-100 rounded-lg">
          <ArrowLeft class="w-5 h-5" />
        </button>
        <div v-if="candidate">
          <h1 class="text-2xl font-bold">{{ candidate.name }}</h1>
          <p class="text-gray-600">@{{ candidate.username }}</p>
        </div>
      </div>
      <div v-if="candidate" class="flex items-center gap-3">
        <span :class="['badge', getRiskBadge(candidate.risk_level)]">
          {{ getRiskLabel(candidate.risk_level) }}
        </span>
        <button @click="handleRescan" class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
          <RefreshCw class="w-4 h-4" />
          Rescan
        </button>
      </div>
    </div>

    <div v-if="loading" class="flex items-center justify-center h-64 text-gray-500">
      Loading...
    </div>

    <div v-else-if="!candidate" class="text-center py-12">
      <AlertTriangle class="w-12 h-12 text-red-500 mx-auto mb-4" />
      <h2 class="text-xl font-semibold">Candidate not found</h2>
    </div>

    <div v-else>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white p-4 rounded-lg shadow-sm border">
          <p class="text-sm text-gray-600">Risk Score</p>
          <p :class="['text-2xl font-bold', candidate.overall_risk_score > 70 ? 'text-red-600' : candidate.overall_risk_score > 40 ? 'text-yellow-600' : 'text-green-600']">
            {{ candidate.overall_risk_score || 0 }}%
          </p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border">
          <p class="text-sm text-gray-600">Total Posts</p>
          <p class="text-2xl font-bold">{{ riskSummary?.statistics?.total_posts || 0 }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border">
          <p class="text-sm text-gray-600">High Risk Posts</p>
          <p class="text-2xl font-bold text-red-600">{{ riskSummary?.statistics?.high_risk_count || 0 }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border">
          <p class="text-sm text-gray-600">Last Scanned</p>
          <p class="text-lg font-medium">{{ formatDate(candidate.last_scanned_at) }}</p>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm border">
        <div class="p-4 border-b">
          <h3 class="text-lg font-semibold">Recent Posts</h3>
        </div>
        <div class="divide-y">
          <div v-for="post in posts" :key="post.id" class="p-4 hover:bg-gray-50">
            <div class="flex items-start gap-3">
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1">
                  <span class="text-sm font-medium capitalize">{{ post.platform }}</span>
                  <span class="text-xs text-gray-500">{{ formatDate(post.posted_at) }}</span>
                  <span v-if="post.analysis_result" :class="[
                    'ml-auto text-xs px-2 py-1 rounded-full',
                    post.analysis_result.risk_label === 'high_risk' ? 'bg-red-100 text-red-800' :
                    post.analysis_result.risk_label === 'review' ? 'bg-yellow-100 text-yellow-800' :
                    'bg-green-100 text-green-800'
                  ]">
                    {{ post.analysis_result.risk_label }}
                  </span>
                </div>
                <p class="text-sm text-gray-700">{{ post.content || '(No text content)' }}</p>
              </div>
            </div>
          </div>
          <div v-if="posts.length === 0" class="p-8 text-center text-gray-500">
            No posts found
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ArrowLeft, RefreshCw, AlertTriangle } from 'lucide-vue-next'
import { candidateService } from '../services/apiService'
import { formatDate, getRiskBadge, getRiskLabel } from '../utils/helpers'

const route = useRoute()
const router = useRouter()
const id = route.params.id

const candidate = ref(null)
const posts = ref([])
const loading = ref(true)
const riskSummary = ref(null)

const fetchData = async () => {
  loading.value = true
  try {
    const [candidateRes, riskRes, postsRes] = await Promise.all([
      candidateService.getById(id),
      candidateService.getRiskSummary(id),
      candidateService.getPosts(id, { limit: 50 })
    ])
    candidate.value = candidateRes.data
    riskSummary.value = riskRes.data
    posts.value = postsRes.data.data || []
  } catch (error) {
    console.error('Error fetching data:', error)
  } finally {
    loading.value = false
  }
}

const handleRescan = async () => {
  try {
    await candidateService.rescan(id)
    alert('Rescan started!')
    setTimeout(fetchData, 5000)
  } catch (error) {
    alert('Error rescanning: ' + error.message)
  }
}

onMounted(fetchData)
</script>