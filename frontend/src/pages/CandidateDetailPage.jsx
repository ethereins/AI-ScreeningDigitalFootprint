import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { candidateApi } from '../api/candidateApi';
import { ArrowLeft, RefreshCw, AlertTriangle } from 'lucide-react';
import { formatDate, getRiskBadge, getRiskLabel } from '../utils/helpers';

const CandidateDetailPage = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [candidate, setCandidate] = useState(null);
  const [posts, setPosts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [riskSummary, setRiskSummary] = useState(null);

  useEffect(() => {
    fetchData();
  }, [id]);

  const fetchData = async () => {
    setLoading(true);
    try {
      const [candidateRes, riskRes, postsRes] = await Promise.all([
        candidateApi.getById(id),
        candidateApi.getRiskSummary(id),
        candidateApi.getPosts(id, { limit: 50 }),
      ]);
      setCandidate(candidateRes.data);
      setRiskSummary(riskRes.data);
      setPosts(postsRes.data.data || []);
    } catch (error) {
      console.error('Error fetching data:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleRescan = async () => {
    try {
      await candidateApi.rescan(id);
      alert('Rescan started!');
      setTimeout(fetchData, 5000);
    } catch (error) {
      alert('Error rescanning: ' + error.message);
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64 text-gray-500">Loading...</div>
    );
  }

  if (!candidate) {
    return (
      <div className="text-center py-12">
        <AlertTriangle className="w-12 h-12 text-red-500 mx-auto mb-4" />
        <h2 className="text-xl font-semibold">Candidate not found</h2>
      </div>
    );
  }

  return (
    <div>
      <div className="flex items-center justify-between mb-8">
        <div className="flex items-center gap-4">
          <button onClick={() => navigate('/')} className="p-2 hover:bg-gray-100 rounded-lg">
            <ArrowLeft className="w-5 h-5" />
          </button>
          <div>
            <h1 className="text-2xl font-bold">{candidate.name}</h1>
            <p className="text-gray-600">@{candidate.username}</p>
          </div>
        </div>
        <div className="flex items-center gap-3">
          <span className={`badge ${getRiskBadge(candidate.risk_level)}`}>
            {getRiskLabel(candidate.risk_level)}
          </span>
          <button onClick={handleRescan} className="btn-primary flex items-center gap-2">
            <RefreshCw className="w-4 h-4" />
            Rescan
          </button>
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div className="card">
          <p className="text-sm text-gray-600">Risk Score</p>
          <p className={`text-2xl font-bold ${candidate.overall_risk_score > 70 ? 'text-red-600' : candidate.overall_risk_score > 40 ? 'text-yellow-600' : 'text-green-600'}`}>
            {candidate.overall_risk_score || 0}%
          </p>
        </div>
        <div className="card">
          <p className="text-sm text-gray-600">Total Posts</p>
          <p className="text-2xl font-bold">{riskSummary?.statistics?.total_posts || 0}</p>
        </div>
        <div className="card">
          <p className="text-sm text-gray-600">High Risk Posts</p>
          <p className="text-2xl font-bold text-red-600">{riskSummary?.statistics?.high_risk_count || 0}</p>
        </div>
        <div className="card">
          <p className="text-sm text-gray-600">Last Scanned</p>
          <p className="text-lg font-medium">{formatDate(candidate.last_scanned_at)}</p>
        </div>
      </div>

      <div className="card">
        <div className="p-4 border-b">
          <h3 className="text-lg font-semibold">Recent Posts</h3>
        </div>
        <div className="divide-y">
          {posts.slice(0, 20).map((post) => (
            <div key={post.id} className="p-4 hover:bg-gray-50">
              <div className="flex items-start gap-3">
                <div className="flex-1 min-w-0">
                  <div className="flex items-center gap-2 mb-1">
                    <span className="text-sm font-medium capitalize">{post.platform}</span>
                    <span className="text-xs text-gray-500">{formatDate(post.posted_at)}</span>
                    {post.analysis_result && (
                      <span className={`ml-auto text-xs px-2 py-1 rounded-full ${
                        post.analysis_result.risk_label === 'high_risk' ? 'badge-high' :
                        post.analysis_result.risk_label === 'review' ? 'badge-medium' :
                        'badge-low'
                      }`}>
                        {post.analysis_result.risk_label}
                      </span>
                    )}
                  </div>
                  <p className="text-sm text-gray-700">{post.content || '(No text content)'}</p>
                </div>
              </div>
            </div>
          ))}
          {posts.length === 0 && (
            <div className="p-8 text-center text-gray-500">No posts found</div>
          )}
        </div>
      </div>
    </div>
  );
};

export default CandidateDetailPage;