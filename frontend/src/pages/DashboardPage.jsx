import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { candidateApi } from '../api/candidateApi';
import { Search, Eye, RefreshCw, AlertTriangle, CheckCircle, Clock } from 'lucide-react';
import { formatDate, getRiskBadge, getRiskLabel } from '../utils/helpers';

const DashboardPage = () => {
  const [candidates, setCandidates] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [filters, setFilters] = useState({ search: '', risk_level: 'all', status: 'all' });
  const [stats, setStats] = useState({ total: 0, high_risk: 0, medium_risk: 0, low_risk: 0 });
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
      const data = response.data.data || [];
      setCandidates(data);

      const high = data.filter((c) => ['HIGH', 'CRITICAL'].includes(c.risk_level)).length;
      const medium = data.filter((c) => c.risk_level === 'MEDIUM').length;
      const low = data.filter((c) => ['LOW', 'SAFE'].includes(c.risk_level)).length;

      setStats({ total: data.length, high_risk: high, medium_risk: medium, low_risk: low });
    } catch (err) {
      setError(err.message || 'Error fetching candidates');
    } finally {
      setLoading(false);
    }
  };

  const StatCard = ({ title, value, icon: Icon, color }) => (
    <div className="card">
      <div className="flex items-center justify-between">
        <div>
          <p className="text-sm text-gray-500">{title}</p>
          <p className="text-2xl font-bold mt-1">{value}</p>
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

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <StatCard title="Total Candidates" value={stats.total} icon={Search} color="bg-blue-600" />
        <StatCard title="High Risk" value={stats.high_risk} icon={AlertTriangle} color="bg-red-600" />
        <StatCard title="Medium Risk" value={stats.medium_risk} icon={AlertTriangle} color="bg-yellow-600" />
        <StatCard title="Low Risk" value={stats.low_risk} icon={CheckCircle} color="bg-green-600" />
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
                <th className="table-header">Status</th>
                <th className="table-header">Last Scanned</th>
                <th className="table-header">Action</th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {loading ? (
                <tr>
                  <td colSpan="7" className="px-6 py-8 text-center text-gray-500">
                    Loading...
                  </td>
                </tr>
              ) : error ? (
                <tr>
                  <td colSpan="7" className="px-6 py-8 text-center text-red-500">
                    {error}
                  </td>
                </tr>
              ) : candidates.length === 0 ? (
                <tr>
                  <td colSpan="7" className="px-6 py-8 text-center text-gray-500">
                    No candidates found
                  </td>
                </tr>
              ) : (
                candidates.map((candidate) => (
                  <tr key={candidate.id} className="hover:bg-gray-50 transition-colors">
                    <td className="table-cell">
                      <div className="font-medium text-gray-900">{candidate.name}</div>
                      <div className="text-sm text-gray-500">@{candidate.username}</div>
                    </td>
                    <td className="table-cell">
                      <div className="flex flex-wrap gap-1">
                        {candidate.social_links &&
                          Object.entries(candidate.social_links).map(
                            ([platform, username]) =>
                              username && (
                                <span key={platform} className="badge bg-gray-100 text-gray-800 capitalize">
                                  {platform}
                                </span>
                              )
                          )}
                      </div>
                    </td>
                    <td className="table-cell">
                      <div className="flex items-center gap-2">
                        <div className="w-20 bg-gray-200 rounded-full h-2">
                          <div
                            className={`h-2 rounded-full transition-all ${
                              candidate.overall_risk_score > 70
                                ? 'bg-red-500'
                                : candidate.overall_risk_score > 40
                                ? 'bg-yellow-500'
                                : 'bg-green-500'
                            }`}
                            style={{ width: `${candidate.overall_risk_score || 0}%` }}
                          />
                        </div>
                        <span className="text-sm font-medium">{candidate.overall_risk_score || 0}%</span>
                      </div>
                    </td>
                    <td className="table-cell">
                      <span className={`badge ${getRiskBadge(candidate.risk_level)}`}>
                        {getRiskLabel(candidate.risk_level)}
                      </span>
                    </td>
                    <td className="table-cell">
                      <span className="capitalize">{candidate.status}</span>
                    </td>
                    <td className="table-cell text-gray-500">{formatDate(candidate.last_scanned_at)}</td>
                    <td className="table-cell">
                      <button
                        onClick={() => navigate(`/candidate/${candidate.id}`)}
                        className="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                      >
                        <Eye className="w-4 h-4" />
                      </button>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
};

export default DashboardPage;