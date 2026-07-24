import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { candidateApi } from '../api/candidateApi';
import { 
  Search, Eye, RefreshCw, AlertTriangle, CheckCircle, 
  Clock, Zap, BarChart3, Activity, Users, FileText 
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

      // Hitung statistik dengan CRITICAL terpisah
      const critical = data.filter((c) => {
        const level = c.overall_risk_level || c.risk_level || '';
        return level.toUpperCase() === 'CRITICAL';
      }).length;
      
      const high = data.filter((c) => {
        const level = c.overall_risk_level || c.risk_level || '';
        return level.toUpperCase() === 'HIGH';
      }).length;
      
      const medium = data.filter((c) => {
        const level = c.overall_risk_level || c.risk_level || '';
        return level.toUpperCase() === 'MEDIUM';
      }).length;
      
      const low = data.filter((c) => {
        const level = c.overall_risk_level || c.risk_level || '';
        return ['LOW', 'SAFE'].includes(level.toUpperCase());
      }).length;

      // Hitung total posts dari semua kandidat
      let totalPosts = 0;
      let highRiskPosts = 0;
      data.forEach(c => {
        // Dari summary
        if (c.summary) {
          totalPosts += c.summary.total_posts_analyzed || 0;
          highRiskPosts += c.summary.high_risk_posts_count || 0;
        }
        // Atau dari social_media
        if (c.social_media && Array.isArray(c.social_media)) {
          c.social_media.forEach(sm => {
            if (sm.posts && Array.isArray(sm.posts)) {
              totalPosts += sm.posts.length;
              sm.posts.forEach(post => {
                if (post.analysis && post.analysis.risk_level === 'HIGH') {
                  highRiskPosts++;
                }
              });
            }
          });
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

  // Helper untuk mendapatkan risk level
  const getRiskLevel = (candidate) => {
    return candidate.overall_risk_level || candidate.risk_level || 'LOW';
  };

  // Helper untuk mendapatkan risk score
  const getRiskScore = (candidate) => {
    return candidate.overall_risk_score || candidate.risk_score || 0;
  };

  // Helper untuk mendapatkan aggregated scores
  const getAggregatedScores = (candidate) => {
    return candidate.aggregated_scores || {
      toxicity: 0,
      threat: 0,
      insult: 0,
      hate_speech: 0
    };
  };

  // Helper untuk mendapatkan social media
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

  // Helper untuk mendapatkan nama
  const getCandidateName = (candidate) => {
    return candidate.name || candidate.full_name || candidate.username || 'Unknown';
  };

  // Helper untuk mendapatkan username
  const getCandidateUsername = (candidate) => {
    return candidate.username || candidate.handle || '';
  };

  const StatCard = ({ title, value, icon: Icon, color, subtitle }) => (
    <div className="card hover:shadow-md transition-shadow">
      <div className="flex items-center justify-between">
        <div>
          <p className="text-sm text-gray-500">{title}</p>
          <p className="text-2xl font-bold mt-1">{value}</p>
          {subtitle && <p className="text-xs text-gray-400 mt-1">{subtitle}</p>}
        </div>
        <div className={`p-3 rounded-lg ${color}`}>
          <Icon className="w-5 h-5 text-white" />
        </div>
      </div>
    </div>
  );

  return (
    <div>
      <div className="mb-8">
        <h1 className="text-2xl font-bold text-gray-900">Dashboard</h1>
        <p className="text-gray-600 mt-1">Monitor digital footprint of candidates</p>
      </div>

      {/* Stats - 7 Cards */}
      <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-7 gap-3 mb-8">
        <StatCard title="Total" value={stats.total} icon={Users} color="bg-blue-600" />
        <StatCard title="Critical" value={stats.critical} icon={Zap} color="bg-red-700" />
        <StatCard title="High" value={stats.high_risk} icon={AlertTriangle} color="bg-red-600" />
        <StatCard title="Medium" value={stats.medium_risk} icon={AlertTriangle} color="bg-yellow-600" />
        <StatCard title="Low" value={stats.low_risk} icon={CheckCircle} color="bg-green-600" />
        <StatCard title="Total Posts" value={stats.total_posts} icon={FileText} color="bg-purple-600" />
        <StatCard title="High Risk Posts" value={stats.high_risk_posts} icon={Activity} color="bg-orange-600" />
      </div>

      {/* Filters */}
      <div className="card mb-6">
        <div className="flex flex-wrap gap-4">
          <div className="flex-1 min-w-[200px]">
            <div className="relative">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
              <input
                type="text"
                placeholder="Search candidates..."
                className="input-field pl-9"
                value={filters.search}
                onChange={(e) => setFilters({ ...filters, search: e.target.value })}
              />
            </div>
          </div>

          <select
            className="input-field w-auto"
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
            className="input-field w-auto"
            value={filters.status}
            onChange={(e) => setFilters({ ...filters, status: e.target.value })}
          >
            <option value="all">All Status</option>
            <option value="pending">Pending</option>
            <option value="processing">Processing</option>
            <option value="completed">Completed</option>
            <option value="failed">Failed</option>
          </select>

          <button onClick={fetchCandidates} className="btn-primary">
            <RefreshCw className="w-4 h-4 inline mr-2" />
            Refresh
          </button>
        </div>
      </div>

      {/* Table */}
      <div className="card overflow-hidden p-0">
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="table-header">Candidate</th>
                <th className="table-header">Platforms</th>
                <th className="table-header">Risk Score</th>
                <th className="table-header">Level</th>
                <th className="table-header">Scores</th>
                <th className="table-header">Posts</th>
                <th className="table-header">Status</th>
                <th className="table-header">Action</th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {loading ? (
                <tr>
                  <td colSpan="8" className="px-6 py-8 text-center text-gray-500">
                    <div className="flex items-center justify-center gap-2">
                      <RefreshCw className="w-4 h-4 animate-spin" />
                      Loading...
                    </div>
                  </td>
                </tr>
              ) : error ? (
                <tr>
                  <td colSpan="8" className="px-6 py-8 text-center text-red-500">
                    <AlertTriangle className="w-5 h-5 mx-auto mb-2" />
                    {error}
                  </td>
                </tr>
              ) : candidates.length === 0 ? (
                <tr>
                  <td colSpan="8" className="px-6 py-8 text-center text-gray-500">
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
                    <tr key={candidate.id || candidate._id} className="hover:bg-gray-50 transition-colors">
                      <td className="table-cell">
                        <div className="font-medium text-gray-900">{getCandidateName(candidate)}</div>
                        <div className="text-sm text-gray-500">@{getCandidateUsername(candidate)}</div>
                      </td>
                      <td className="table-cell">
                        <div className="flex flex-wrap gap-1">
                          {socialMedia.map((social, idx) => (
                            <span 
                              key={idx} 
                              className="badge bg-gray-100 text-gray-800 capitalize text-xs"
                            >
                              {social.platform || 'unknown'}
                            </span>
                          ))}
                        </div>
                      </td>
                      <td className="table-cell">
                        <div className="flex items-center gap-2">
                          <div className="w-16 bg-gray-200 rounded-full h-2">
                            <div
                              className={`h-2 rounded-full transition-all ${
                                getRiskScore(candidate) > 80 ? 'bg-red-700' :
                                getRiskScore(candidate) > 70 ? 'bg-red-500' :
                                getRiskScore(candidate) > 40 ? 'bg-yellow-500' :
                                'bg-green-500'
                              }`}
                              style={{ width: `${Math.min(getRiskScore(candidate), 100)}%` }}
                            />
                          </div>
                          <span className="text-sm font-medium">{getRiskScore(candidate)}%</span>
                        </div>
                      </td>
                      <td className="table-cell">
                        <span className={`badge ${getRiskBadge(level)}`}>
                          {getRiskLabel(level)}
                        </span>
                      </td>
                      <td className="table-cell">
                        <div className="flex flex-col gap-0.5 text-xs">
                          <span className="text-gray-600">Tox: {Math.round((scores.toxicity || 0) * 100)}%</span>
                          <span className="text-gray-600">Hate: {Math.round((scores.hate_speech || 0) * 100)}%</span>
                        </div>
                      </td>
                      <td className="table-cell text-center">
                        <div className="text-sm font-medium">{totalPosts}</div>
                      </td>
                      <td className="table-cell">
                        <span className="capitalize text-sm">{candidate.status || 'pending'}</span>
                      </td>
                      <td className="table-cell">
                        <button
                          onClick={() => navigate(`/candidate/${candidate.id || candidate._id}`)}
                          className="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
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
