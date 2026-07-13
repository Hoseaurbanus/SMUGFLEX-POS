export function formatCurrency(amount, currency = '₦') {
  return currency + Number(amount || 0).toLocaleString('en-NG', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
}

export function formatDate(dateString, options = {}) {
  if (!dateString) return '-';
  const defaultOptions = { year: 'numeric', month: 'short', day: 'numeric' };
  return new Date(dateString).toLocaleDateString('en-US', { ...defaultOptions, ...options });
}

export function formatDateTime(dateString) {
  if (!dateString) return '-';
  return new Date(dateString).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

export function formatNumber(num) {
  return Number(num || 0).toLocaleString('en-US');
}

export function getStatusColor(status) {
  const colors = {
    active: 'success',
    completed: 'success',
    paid: 'success',
    received: 'success',
    approved: 'success',
    inactive: 'danger',
    cancelled: 'danger',
    voided: 'danger',
    rejected: 'danger',
    pending: 'warning',
    partial: 'warning',
    held: 'info',
    unpaid: 'danger',
    refunded: 'info',
  };
  return colors[status] || 'secondary';
}
