import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../services/api';

export default function ActivityLog() {
  const [page, setPage] = useState(1);
  const [userId, setUserId] = useState('');
  const [action, setAction] = useState('');

  const { data: usersData } = useQuery({
    queryKey: ['users-list'],
    queryFn: () => api.get('/users?per_page=100').then(res => res.data.data || res.data),
  });

  const { data, isLoading } = useQuery({
    queryKey: ['activity-logs', { page, userId, action }],
    queryFn: () => {
      const params = new URLSearchParams({ page, per_page: 20 });
      if (userId) params.append('user_id', userId);
      if (action) params.append('action', action);
      return api.get(`/activity-logs?${params}`).then(res => res.data);
    },
  });

  const logs = data?.data || [];
  const pagination = data?.pagination;
  const users = Array.isArray(usersData) ? usersData : usersData?.data || [];

  const getActionBadge = (act) => {
    switch (act) {
      case 'create': return 'success';
      case 'update': return 'warning';
      case 'delete': return 'danger';
      case 'login': return 'info';
      default: return 'secondary';
    }
  };

  return (
    <div>
      <div className="page-header mb-3">
        <h4 className="mb-0">Activity Log</h4>
      </div>

      <div className="card">
        <div className="card-body">
          <div className="row g-2 mb-3">
            <div className="col-md-3">
              <select className="form-select" value={userId} onChange={(e) => { setUserId(e.target.value); setPage(1); }}>
                <option value="">All Users</option>
                {users.map((u) => <option key={u.id} value={u.id}>{u.first_name} {u.last_name}</option>)}
              </select>
            </div>
            <div className="col-md-3">
              <select className="form-select" value={action} onChange={(e) => { setAction(e.target.value); setPage(1); }}>
                <option value="">All Actions</option>
                <option value="create">Create</option>
                <option value="update">Update</option>
                <option value="delete">Delete</option>
                <option value="login">Login</option>
                <option value="logout">Logout</option>
              </select>
            </div>
          </div>

          {isLoading ? (
            <div className="text-center py-4"><div className="spinner-border text-primary"></div></div>
          ) : logs.length === 0 ? (
            <div className="empty-state text-center py-5">
              <i className="bi bi-clock-history fs-1 d-block mb-2"></i>
              <p>No activity logs found</p>
            </div>
          ) : (
            <div className="table-responsive">
              <table className="table table-dark table-hover">
                <thead>
                  <tr>
                    <th>User</th>
                    <th>Action</th>
                    <th>Module</th>
                    <th>Description</th>
                    <th>Date</th>
                  </tr>
                </thead>
                <tbody>
                  {logs.map((log) => (
                    <tr key={log.id}>
                      <td>{log.user?.first_name} {log.user?.last_name}</td>
                      <td>
                        <span className={`badge bg-${getActionBadge(log.action)}`}>{log.action}</span>
                      </td>
                      <td>{log.module || log.subject_type?.split('\\').pop()}</td>
                      <td>{log.description}</td>
                      <td>{new Date(log.created_at).toLocaleString()}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}

          {pagination && pagination.last_page > 1 && (
            <nav>
              <ul className="pagination justify-content-center mb-0">
                <li className={`page-item ${pagination.current_page === 1 ? 'disabled' : ''}`}>
                  <button className="page-link" onClick={() => setPage(page - 1)}>Prev</button>
                </li>
                {Array.from({ length: Math.min(pagination.last_page, 10) }, (_, i) => i + 1).map((p) => (
                  <li key={p} className={`page-item ${p === pagination.current_page ? 'active' : ''}`}>
                    <button className="page-link" onClick={() => setPage(p)}>{p}</button>
                  </li>
                ))}
                <li className={`page-item ${pagination.current_page === pagination.last_page ? 'disabled' : ''}`}>
                  <button className="page-link" onClick={() => setPage(page + 1)}>Next</button>
                </li>
              </ul>
            </nav>
          )}
        </div>
      </div>
    </div>
  );
}
