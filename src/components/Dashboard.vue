<template>
  <div>
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
      <p class="text-gray-600 mt-1">Monitor digital footprint of candidates</p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
      <StatCard title="Total Candidates" :value="stats.total" icon="Search" color="bg-blue-600" />
      <StatCard title="High Risk" :value="stats.high_risk" icon="AlertTriangle" color="bg-red-600" />
      <StatCard title="Medium Risk" :value="stats.medium_risk" icon="AlertTriangle" color="bg-yellow-600" />
      <StatCard title="Low Risk" :value="stats.low_risk" icon="CheckCircle" color="bg-green-600" />
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border p-4 mb-6">
      <div class="flex flex-wrap gap-4">
        <div class="flex-1 min-w-[200px]">
          <div class="relative">
            <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
            <input
              v-model="filters.search"
              type="text"
              placeholder="Search candidates..."
              class="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>
        </div>
        
        <select 
          v-model="filters.risk_level"
          class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
        >
          <option value="all">All Risk Levels</option>
          <option value="LOW">Low</option>
          <option value="MEDIUM">Medium</option>
          <option value="HIGH">High</option>
          <option value="CRITICAL">Critical</option>
        </select>

        <select 
          v-model="filters.status"
          class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
        >
          <option value="all">All Status</option>
          <option value="pending">Pending</option>
          <option value="processing">Processing</option>
          <option value="completed">Completed</option>
          <option value="failed">Failed</option>
        </select>

        <button 
          @click="fetchCandidates"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
        >
          Refresh
        </button>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Candidate</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Platforms</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Risk Score</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Level</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last Scanned</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-if="loading">
              <td colspan="7" class="px-6 py-8 text-center text-gray-500">Loading...</td>
            </tr>
            <tr v-else-if="error">
              <td colspan="7" class="px-6 py-8 text-center text-red-500">{{ error }}</td>
            </tr>
            <tr v-else-if="candidates.length === 0">
              <td colspan="7" class="px-6 py-8 text-center text-gray-500">No candidates found</td>
            </tr>
            <tr v-else v-for="candidate in candidates" :key="candidate.id" class="hover:bg-gray-50">
              <td class="px-6 py-4">
                <div class="font-medium text-gray-900">{{ candidate.name }}</div>
                <div class="text-sm text-gray-500">@{{ candidate.username }}</div>
              </td>
              <td class="px-6 py-4">
                <div class="flex flex-wrap gap-1">
                  <span 
                    v-for="(username, platform) in candidate.social_links" 
                    v-if="username"
                    :key="platform"
                    class="px-2 py-1 bg-gray-100 rounded text-xs capitalize"
                  >
                    {{ platform }}
                  </span>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <div class="w-20 bg-gray-200 rounded-full h-2">
                    <div 
                      :class="[
                        'h-2 rounded-full',
                        candidate.overall_risk_score > 70 ? 'bg-red-500' :
                        candidate.overall_risk_score > 40 ? 'bg-yellow-500' :
                        'bg-green-500'
                      ]"
                      :style="{ width: `${candidate.overall_risk_score || 0}%` }"
                    />
                  </div>
                  <span class="text-sm">{{ candidate.overall_risk_score || 0 }}%</span>
                </div>
              </td>
              <td class="px-6 py-4">
                <span :class="['badge', getRiskBadge(candidate.risk_level)]">
                  {{ getRiskLabel(candidate.risk_level) }}
                </span>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-1">
                  <component :is="getStatusIcon(candidate.status)" class="w-3 h-3" />
                  <span class="text-sm capitalize">{{ candidate.status }}</span>
                </div>
              </td>
              <td class="px-6 py-4 text-sm text-gray-500">{{ formatDate(candidate.last_scanned_at) }}</td>
              <td class="px-6 py-4">
                <button 
                  @click="navigateToDetail(candidate.id)"
                  class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg"
                >
                  <Eye class="w-4 h-4" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { 
  Search, 
  Eye, 
  RefreshCw,
  AlertTriangle,
  CheckCircle,
  Clock,
} from 'lucide-vue-next'
import { candidateService } from '../services/apiService'
import { formatDate, getRiskBadge, getRiskLabel } from '../utils/helpers'
import StatCard from './StatCard.vue'

const router = useRouter()
const candidates = ref([])
const loading = ref(true)
const error = ref(null)
const filters = ref({
  search: '',
  risk_level: 'all',
  status: 'all'
})
const stats = ref({
  total: 0,
  high_risk: 0,
  medium_risk: 0,
  low_risk: 0,
})

const fetchCandidates = async () => {
  loading.value = true
  error.value = null
  try {
    const params = {}
    if (filters.value.search) params.search = filters.value.search
    if (filters.value.risk_level !== 'all') params.risk_level = filters.value.risk_level
    if (filters.value.status !== 'all') params.status = filters.value.status
    
    const response = await candidateService.getAll(params)
    const data = response.data.data || []
    candidates.value = data
    
    const high = data.filter(c => ['HIGH', 'CRITICAL'].includes(c.risk_level)).length
    const medium = data.filter(c => c.risk_level === 'MEDIUM').length
    const low = data.filter(c => ['LOW', 'SAFE'].includes(c.risk_level)).length
    
    stats.value = {
      total: data.length,
      high_risk: high,
      medium_risk: medium,
      low_risk: low,
    }
  } catch (err) {
    error.value = err.message || 'Error fetching candidates'
    console.error('Error:', err)
  } finally {
    loading.value = false
  }
}

const getStatusIcon = (status) => {
  switch(status) {
    case 'pending': return Clock
    case 'processing': return RefreshCw
    case 'completed': return CheckCircle
    case 'failed': return AlertTriangle
    default: return null
  }
}

const navigateToDetail = (id) => {
  router.push(`/candidate/${id}`)
}

watch(filters, fetchCandidates, { deep: true })

onMounted(fetchCandidates)
</script>