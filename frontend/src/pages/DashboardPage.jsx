import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { candidateApi } from '../api/candidateApi';
import { 
  Search, Eye, RefreshCw, AlertTriangle, CheckCircle, 
  Zap, FileText, Activity, Users 
} from 'lucide-react';
import { formatDate, getRiskBadge, getRiskLabel } from '../utils/helpers';

const DashboardPage = () => {
  const [candidates, setCandidates] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [filters, setFilters] = useState({ search: '', risk_level: 'all', status: 'all' });
  const [stats, setStats] = useState({ 
    total: 0, 
    critical: 0,
    high_risk: 0, 
    medium_risk: 0, 
    low_risk: 0,
    total_posts: 0,
    high_risk_posts: 0
  });
  const navigate = useNavigate();

  useEffect(() => {
    fetchCandidates();
  }, [filters]);

  const fetchCandidates = async () => {
    setLoading(true);
    setError(null);
    try {
      const params = {};
      if (filters.search) params.search = filters.search;
      if (filters.risk_level !== 'all') params.risk_level = filters.risk_level;
      if (filters.status !== 'all') params.status = filters.status;

      const response = await candidateApi.getAll(params);
      
      let data = [];
      if (response.data && Array.isArray(response.data)) {
        data = response.data;
      } else if (response.data && response.data.data && Array.isArray(response.data.data)) {
        data = response.data.data;
      } else if (response.data && response.data.candidates && Array.isArray(response.data.candidates)) {
        data = response.data.candidates;
      } else {
        data = Object.values(response.data).find(val => Array.isArray(val)) || [];
      }
      
      setCandidates(data);

      // Hitung statistik risk level
      const critical = data.filter((c) => {
        const level = c.overall_risk_level || c.risk_summary?.risk_level || c.risk_level || '';
        return level.toUpperCase() === 'CRITICAL';
      }).length;
      
      const high = data.filter((c) => {
        const level = c.overall_risk_level || c.risk_summary?.risk_level || c.risk_level || '';
        return level.toUpperCase() === 'HIGH';
      }).length;
      
      const medium = data.filter((c) => {
        const level = c.overall_risk_level || c.risk_summary?.risk_level || c.risk_level || '';
        return level.toUpperCase() === 'MEDIUM';
      }).length;
      
      const low = data.filter((c) => {
        const level = c.overall_risk_level || c.risk_summary?.risk_level || c.risk_level || '';
        return ['LOW', 'SAFE'].includes(level.toUpperCase());
      }).length;

      // Hitung total posts
      let totalPosts = 0;
      let highRiskPosts = 0;
      data.forEach(c => {
        // Dari summary
        if (c.summary) {
          totalPosts += c.summary.total_posts_analyzed || 0;
          highRiskPosts += c.summary.high_risk_posts_count || 0;
        }
        // Dari social_media
        if (c.social_media && Array.isArray(c.social_media)) {
          c.social_media.forEach(sm => {
            if (sm.posts && Array.isArray(sm.posts)) {
              totalPosts += sm.posts.length;
              sm.posts.forEach(post => {
                if (post.analysis && ['HIGH', 'CRITICAL'].includes(post.analysis.risk_level)) {
                  highRiskPosts++;
                }
              });
            }
          });
        }
        // Dari analysisResults (jika ada)
        if (c.analysis_results && Array.isArray(c.analysis_results)) {
          totalPosts += c.analysis_results.length;
          highRiskPosts += c.analysis_results.filter(r => 
            ['HIGH', 'CRITICAL'].includes(r.risk_level)
          ).length;
        }
      });

      setStats({ 
        total: data.length, 
        critical: critical,
        high_risk: high, 
        medium_risk: medium, 
        low_risk: low,
        total_posts: totalPosts,
        high_risk_posts: highRiskPosts
      });
    } catch (err) {
      setError(err.message || 'Error fetching candidates');
      console.error('Fetch error:', err);
    } finally {
      setLoading(false);
    }
  };

  // ============ HELPERS ============
  
  const getRiskLevel = (candidate) => {
    return candidate.overall_risk_level || 
           candidate.risk_summary?.risk_level || 
           candidate.risk_level || 
           'LOW';
  };

  const getRiskScore = (candidate) => {
    return candidate.overall_risk_score || 
           candidate.risk_summary?.risk_score || 
           candidate.risk_score || 
           0;
  };

  const getAggregatedScores = (candidate) => {
    return candidate.aggregated_scores || {
      toxicity: 0,
      threat: 0,
      insult: 0,
      hate_speech: 0
    };
  };

  const getSocialMedia = (candidate) => {
    if (candidate.social_media && Array.isArray(candidate.social_media)) {
      return candidate.social_media;
    }
    if (candidate.social_links && typeof candidate.social_links === 'object') {
      return Object.entries(candidate.social_links)
        .filter(([_, value]) => value)
        .map(([platform, username]) => ({ platform, username }));
    }
    return [];
  };

  const getCandidateName = (candidate) => {
    return candidate.full_name || candidate.name || candidate.username || 'Unknown';
  };

  const getCandidateUsername = (candidate) => {
    return candidate.username || candidate.handle || '';
  };

  const getLastScanned = (candidate) => {
    return candidate.last_crawled_at || candidate.last_scanned_at || candidate.updated_at;
  };

  const StatCard = ({ title, value, icon: Icon, color, bgColor }) => (
    <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-4 hover:shadow-md transition-shadow">
      <div className="flex items-center justify-between">
        <div>
          <p className="text-xs font-medium text-gray-500 uppercase tracking-wider">{title}</p>
          <p className="text-2xl font-bold mt-1">{value}</p>
        </div>
        <div className={`p-2.5 rounded-lg ${bgColor}`}>
          <Icon className={`w-4 h-4 ${color}`} />
        </div>
      </div>
    </div>
  );

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Dashboard</h1>
        <p className="text-gray-500 text-sm mt-0.5">Monitor digital footprint of candidates</p>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <StatCard title="Total" value={stats.total} icon={Users} color="text-blue-600" bgColor="bg-blue-50" />
        <StatCard title="Critical" value={stats.critical} icon={Zap} color="text-red-700" bgColor="bg-red-50" />
        <StatCard title="High" value={stats.high_risk} icon={AlertTriangle} color="text-red-600" bgColor="bg-red-50" />
        <StatCard title="Medium" value={stats.medium_risk} icon={AlertTriangle} color="text-yellow-600" bgColor="bg-yellow-50" />
        <StatCard title="Low" value={stats.low_risk} icon={CheckCircle} color="text-green-600" bgColor="bg-green-50" />
        <StatCard title="Total Posts" value={stats.total_posts} icon={FileText} color="text-purple-600" bgColor="bg-purple-50" />
        <StatCard title="High Risk Posts" value={stats.high_risk_posts} icon={Activity} color="text-orange-600" bgColor="bg-orange-50" />
      </div>

      {/* Filters */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <div className="flex flex-wrap items-center gap-3">
          <div className="flex-1 min-w-[180px]">
            <div className="relative">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-4 h-4" />
              <input
                type="text"
                placeholder="Search candidates..."
                className="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-shadow"
                value={filters.search}
                onChange={(e) => setFilters({ ...filters, search: e.target.value })}
              />
            </div>
          </div>

          <select
            className="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none bg-white"
            value={filters.risk_level}
            onChange={(e) => setFilters({ ...filters, risk_level: e.target.value })}
          >
            <option value="all">All Risk Levels</option>
            <option value="LOW">Low</option>
            <option value="MEDIUM">Medium</option>
            <option value="HIGH">High</option>
            <option value="CRITICAL">Critical</option>
          </select>

          <select
            className="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none bg-white"
            value={filters.status}
            onChange={(e) => setFilters({ ...filters, status: e.target.value })}
          >
            <option value="all">All Status</option>
            <option value="pending">Pending</option>
            <option value="processing">Processing</option>
            <option value="completed">Completed</option>
            <option value="failed">Failed</option>
          </select>

          <button 
            onClick={fetchCandidates} 
            className="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-1.5"
          >
            <RefreshCw className="w-3.5 h-3.5" />
            Refresh
          </button>
        </div>
      </div>

      {/* Table */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50/80">
              <tr>
                <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Candidate</th>
                <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Platforms</th>
                <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Risk Score</th>
                <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Level</th>
                <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Scores</th>
                <th className="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Posts</th>
                <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                <th className="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Action</th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-100">
              {loading ? (
                <tr>
                  <td colSpan="8" className="px-4 py-8 text-center text-gray-500">
                    <div className="flex items-center justify-center gap-2">
                      <RefreshCw className="w-4 h-4 animate-spin" />
                      <span className="text-sm">Loading...</span>
                    </div>
                  </td>
                </tr>
              ) : error ? (
                <tr>
                  <td colSpan="8" className="px-4 py-8 text-center text-red-500">
                    <AlertTriangle className="w-5 h-5 mx-auto mb-1" />
                    <span className="text-sm">{error}</span>
                  </td>
                </tr>
              ) : candidates.length === 0 ? (
                <tr>
                  <td colSpan="8" className="px-4 py-8 text-center text-gray-500 text-sm">
                    No candidates found
                  </td>
                </tr>
              ) : (
                candidates.map((candidate) => {
                  const level = getRiskLevel(candidate);
                  const scores = getAggregatedScores(candidate);
                  const socialMedia = getSocialMedia(candidate);
                  const totalPosts = candidate.summary?.total_posts_analyzed || 
                    socialMedia.reduce((acc, sm) => acc + (sm.posts?.length || 0), 0);
                  
                  return (
                    <tr key={candidate.id || candidate._id} className="hover:bg-gray-50/60 transition-colors">
                      <td className="px-4 py-3">
                        <div className="font-medium text-gray-900 text-sm">{getCandidateName(candidate)}</div>
                        <div className="text-xs text-gray-500">@{getCandidateUsername(candidate)}</div>
                      </td>
                      <td className="px-4 py-3">
                        <div className="flex flex-wrap gap-1">
                          {socialMedia.slice(0, 3).map((social, idx) => (
                            <span key={idx} className="px-1.5 py-0.5 bg-gray-100 text-gray-600 rounded text-[10px] capitalize">
                              {social.platform || 'unknown'}
                            </span>
                          ))}
                          {socialMedia.length > 3 && (
                            <span className="px-1.5 py-0.5 bg-gray-100 text-gray-600 rounded text-[10px]">
                              +{socialMedia.length - 3}
                            </span>
                          )}
                        </div>
                      </td>
                      <td className="px-4 py-3">
                        <div className="flex items-center gap-2">
                          <div className="w-14 bg-gray-200 rounded-full h-1.5">
                            <div
                              className={`h-1.5 rounded-full transition-all ${
                                getRiskScore(candidate) > 80 ? 'bg-red-700' :
                                getRiskScore(candidate) > 70 ? 'bg-red-500' :
                                getRiskScore(candidate) > 40 ? 'bg-yellow-500' :
                                'bg-green-500'
                              }`}
                              style={{ width: `${Math.min(getRiskScore(candidate), 100)}%` }}
                            />
                          </div>
                          <span className="text-xs font-medium">{getRiskScore(candidate)}%</span>
                        </div>
                      </td>
                      <td className="px-4 py-3">
                        <span className={`px-2 py-0.5 rounded-full text-[10px] font-medium ${getRiskBadge(level)}`}>
                          {getRiskLabel(level)}
                        </span>
                      </td>
                      <td className="px-4 py-3">
                        <div className="flex gap-2 text-[10px] text-gray-500">
                          <span>T: {Math.round((scores.toxicity || 0) * 100)}%</span>
                          <span>H: {Math.round((scores.hate_speech || 0) * 100)}%</span>
                        </div>
                      </td>
                      <td className="px-4 py-3 text-center text-sm font-medium text-gray-700">
                        {totalPosts}
                      </td>
                      <td className="px-4 py-3">
                        <span className={`text-xs capitalize ${
                          candidate.status === 'completed' ? 'text-green-600' :
                          candidate.status === 'processing' ? 'text-yellow-600' :
                          candidate.status === 'failed' ? 'text-red-600' :
                          'text-gray-500'
                        }`}>
                          {candidate.status || 'pending'}
                        </span>
                      </td>
                      <td className="px-4 py-3 text-center">
                        <button
                          onClick={() => navigate(`/candidate/${candidate.id || candidate._id}`)}
                          className="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                          title="View Details"
                        >
                          <Eye className="w-4 h-4" />
                        </button>
                      </td>
                    </tr>
                  );
                })
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
};

export default DashboardPage;
