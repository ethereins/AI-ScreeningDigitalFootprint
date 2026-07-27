import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { candidateApi } from '../api/candidateApi';
import { AlertCircle, CheckCircle, Instagram, Twitter, Music } from 'lucide-react';

const AddCandidatePage = () => {
  const navigate = useNavigate();
  const [loading, setLoading] = useState(false);
  const [success, setSuccess] = useState(false);
  const [error, setError] = useState(null);
  const [formData, setFormData] = useState({
    name: '',
    social_links: {
      instagram: '',
      twitter: '',
      tiktok: '',
    },
  });

  const platforms = [
    { key: 'instagram', label: 'Instagram', icon: Instagram },
    { key: 'twitter', label: 'Twitter', icon: Twitter },
    { key: 'tiktok', label: 'TikTok', icon: Music },
  ];

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    if (name.startsWith('social_')) {
      const platform = name.replace('social_', '');
      setFormData({
        ...formData,
        social_links: {
          ...formData.social_links,
          [platform]: value,
        },
      });
    } else {
      setFormData({ ...formData, [name]: value });
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError(null);
    setSuccess(false);

    const socialLinks = Object.fromEntries(
      Object.entries(formData.social_links).filter(([_, value]) => value.trim() !== '')
    );

    if (Object.keys(socialLinks).length === 0) {
      setError('Please provide at least one social media username');
      setLoading(false);
      return;
    }

    try {
      const response = await candidateApi.create({
        name: formData.name.trim(),
        social_links: socialLinks,
      });

      setSuccess(true);
      setTimeout(() => {
        navigate(`/candidate/${response.data.candidate.id}`);
      }, 2000);
    } catch (err) {
      setError(err.message || 'Error adding candidate');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="max-w-3xl mx-auto">
      <div className="mb-8">
        <h1 className="text-2xl font-bold text-gray-900">Add New Candidate</h1>
        <p className="text-gray-600 mt-1">Start tracking digital footprint of a candidate</p>
      </div>

      <div className="card">
        <form onSubmit={handleSubmit} className="space-y-6">
          {error && (
            <div className="alert-error">
              <AlertCircle className="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" />
              <div>
                <p className="text-sm font-medium text-red-800">Error</p>
                <p className="text-sm text-red-600">{error}</p>
              </div>
            </div>
          )}

          {success && (
            <div className="alert-success">
              <CheckCircle className="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" />
              <div>
                <p className="text-sm font-medium text-green-800">Success!</p>
                <p className="text-sm text-green-600">Candidate added successfully. Redirecting...</p>
              </div>
            </div>
          )}

          {/* Hanya 1 kolom: Full Name */}
          <div>
            <label className="input-label">Full Name *</label>
            <input
              type="text"
              name="name"
              required
              className="input-field"
              value={formData.name}
              onChange={handleInputChange}
              placeholder="John Doe"
            />
            <p className="input-help">Enter the candidate's full name</p>
          </div>

          <div>
            <h3 className="text-sm font-medium text-gray-700 mb-3">Social Media Profiles</h3>
            <p className="text-xs text-gray-500 mb-4">Enter the username (without @) for each platform</p>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              {platforms.map(({ key, label, icon: Icon }) => (
                <div key={key}>
                  <label className="input-label capitalize">{label}</label>
                  <div className="relative">
                    <Icon className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
                    <input
                      type="text"
                      name={`social_${key}`}
                      className="input-field pl-9"
                      value={formData.social_links[key]}
                      onChange={handleInputChange}
                      placeholder={`@${label.toLowerCase()}`}
                    />
                  </div>
                </div>
              ))}
            </div>
          </div>

          <div className="flex gap-3 pt-4 border-t border-gray-200">
            <button type="submit" disabled={loading} className="btn-primary flex-1 disabled:opacity-50">
              {loading ? 'Adding...' : 'Add Candidate & Start Scraping'}
            </button>
            <button type="button" onClick={() => navigate('/')} className="btn-secondary">
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default AddCandidatePage;
