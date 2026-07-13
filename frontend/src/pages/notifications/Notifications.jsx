import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import toast from 'react-hot-toast';
import api from '../../services/api';

export default function Notifications() {
  const queryClient = useQueryClient();

  const { data, isLoading } = useQuery({
    queryKey: ['notifications'],
    queryFn: () => api.get('/notifications').then(res => res.data.data || res.data),
  });

  const markReadMutation = useMutation({
    mutationFn: (id) => api.post(`/notifications/${id}/read`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['notifications'] }),
  });

  const markAllMutation = useMutation({
    mutationFn: () => api.post('/notifications/read-all'),
    onSuccess: () => { queryClient.invalidateQueries({ queryKey: ['notifications'] }); toast.success('All marked as read'); },
  });

  const notifications = Array.isArray(data) ? data : data?.data || [];
  const unreadCount = notifications.filter(n => !n.read_at).length;

  const getIcon = (type) => {
    switch (type) {
      case 'sale': return 'bi-receipt';
      case 'stock': return 'bi-box-seam';
      case 'user': return 'bi-person';
      default: return 'bi-bell';
    }
  };

  return (
    <div>
      <div className="page-header d-flex justify-content-between align-items-center mb-3">
        <h4 className="mb-0">
          Notifications
          {unreadCount > 0 && <span className="badge bg-danger ms-2">{unreadCount}</span>}
        </h4>
        {unreadCount > 0 && (
          <button className="btn btn-primary btn-sm" onClick={() => markAllMutation.mutate()} disabled={markAllMutation.isPending}>
            <i className="bi bi-check-all me-1"></i> Mark All Read
          </button>
        )}
      </div>

      <div className="card">
        <div className="card-body">
          {isLoading ? (
            <div className="text-center py-4"><div className="spinner-border text-primary"></div></div>
          ) : notifications.length === 0 ? (
            <div className="empty-state text-center py-5">
              <i className="bi bi-bell fs-1 d-block mb-2"></i>
              <p>No notifications</p>
            </div>
          ) : (
            <div className="list-group">
              {notifications.map((n) => (
                <div
                  key={n.id}
                  className={`list-group-item list-group-item-action d-flex align-items-start ${!n.read_at ? 'list-group-item-warning' : ''}`}
                  style={{ background: !n.read_at ? '#2a2520' : '#1e1e1e', borderColor: '#333', cursor: 'pointer' }}
                  onClick={() => { if (!n.read_at) markReadMutation.mutate(n.id); }}
                >
                  <i className={`bi ${getIcon(n.type)} me-3 fs-5`}></i>
                  <div className="flex-grow-1">
                    <div className="d-flex justify-content-between">
                      <h6 className="mb-1">{n.title}</h6>
                      <small className="text-muted">{new Date(n.created_at).toLocaleString()}</small>
                    </div>
                    <p className="mb-0 text-muted small">{n.message}</p>
                  </div>
                  {!n.read_at && <span className="badge bg-primary ms-2">New</span>}
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
