export const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

export const getRiskBadge = (level) => {
    const colors = {
        CRITICAL: 'bg-red-100 text-red-800',
        HIGH: 'bg-red-100 text-red-800',
        MEDIUM: 'bg-yellow-100 text-yellow-800',
        LOW: 'bg-green-100 text-green-800',
        SAFE: 'bg-green-100 text-green-800',
    };
    return colors[level] || 'bg-gray-100 text-gray-800';
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