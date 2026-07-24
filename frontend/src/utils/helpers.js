export const formatDate = (dateString) => {
  if (!dateString) return '-';
  const date = new Date(dateString);
  return date.toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

export const formatDateShort = (dateString) => {
  if (!dateString) return '-';
  const date = new Date(dateString);
  return date.toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  });
};

export const getRiskBadge = (level) => {
  const badges = {
    CRITICAL: 'badge-critical',
    HIGH: 'badge-high',
    MEDIUM: 'badge-medium',
    LOW: 'badge-low',
    SAFE: 'badge-low',
  };
  return badges[level] || 'badge-medium';
};

export const getRiskLabel = (level) => {
  const labels = {
    CRITICAL: 'Kritis',
    HIGH: 'Tinggi',
    MEDIUM: 'Sedang',
    LOW: 'Rendah',
    SAFE: 'Aman',
  };
  return labels[level] || level;
};

export const getRiskColor = (score) => {
  if (score >= 70) return 'text-red-600';
  if (score >= 40) return 'text-yellow-600';
  return 'text-green-600';
};

export const truncateText = (text, length = 100) => {
  if (!text) return '';
  if (text.length <= length) return text;
  return text.substring(0, length) + '...';
};

export const getPlatformIcon = (platform) => {
  const icons = {
    instagram: '📸',
    twitter: '🐦',
    tiktok: '🎵',
    threads: '🧵',
    facebook: '📘',
    linkedin: '💼',
    youtube: '📺',
  };
  return icons[platform?.toLowerCase()] || '🌐';
};

export const getPlatformColor = (platform) => {
  const colors = {
    instagram: 'bg-pink-500',
    twitter: 'bg-blue-400',
    tiktok: 'bg-black',
    threads: 'bg-black',
    facebook: 'bg-blue-600',
    linkedin: 'bg-blue-700',
  };
  return colors[platform?.toLowerCase()] || 'bg-gray-500';
};