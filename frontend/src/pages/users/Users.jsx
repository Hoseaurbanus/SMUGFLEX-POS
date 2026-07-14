import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import toast from 'react-hot-toast';
import Swal from 'sweetalert2';
import api from '../../services/api';

export default function Users() {
  const queryClient = useQueryClient();
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);

  const { data, isLoading } = useQuery({
    queryKey: ['users', { search, page }],
    queryFn: () => api.get(`/users?search=${search}&page=${page}`).then(res => res.data),
  });

  const deleteMutation = useMutation({
    mutationFn: (id) => api.delete(`/users/${id}`),
    onSuccess: () => { queryClient.invalidateQueries({ queryKey: ['users'] }); toast.success('User deleted'); },
  });

  const toggleStatus = useMutation({
    mutationFn: (id) => api.post(`/users/${id}/toggle-status`),
    onSuccess: () => { queryClient.invalidateQueries({ queryKey: ['users'] }); toast.success('Status updated'); },
  });

  const handleDelete = (id, name) => {
    Swal.fire({
      title: 'Delete User?',
      text: `Delete "${name}"?`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc3545',
      confirmButtonText: 'Delete',
    }).then((r) => { if (r.isConfirmed) deleteMutation.mutate(id); });
  };

  const users = data?.data || [];
  const pagination = data?.pagination;

  return (
    <div>
      <div className="page-header d-flex justify-content-between align-items-center mb-3">
        <h4 className="mb-0">Users</h4>
        <Link to="/users/create" className="btn btn-primary">
          <i className="bi bi-plus-lg me-1"></i> Add User
        </Link>
      </div>

      <div className="card">
        <div className="card-body">
          <div className="row mb-3">
            <div className="col-md-4">
              <input
                type="text"
                className="form-control"
                placeholder="Search users..."
                value={search}
                onChange={(e) => { setSearch(e.target.value); setPage(1); }}
              />
            </div>
          </div>

          {isLoading ? (
            <div className="text-center py-4"><div className="spinner-border text-primary"></div></div>
          ) : users.length === 0 ? (
            <div className="empty-state text-center py-5">
              <i className="bi bi-people fs-1 d-block mb-2"></i>
              <p>No users found</p>
            </div>
          ) : (
            <div className="table-responsive">
              <table className="table table-dark table-hover">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Branch</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {users.map((u) => (
                    <tr key={u.id}>
                      <td>{u.first_name} {u.last_name}</td>
                      <td>{u.email}</td>
                      <td>{u.role_name || u.role?.name || '-'}</td>
                      <td>{u.branch_name || '—'}</td>
                      <td>
                        <button
                          className={`btn btn-sm ${u.is_active ? 'btn-outline-success' : 'btn-outline-secondary'}`}
                          onClick={() => toggleStatus.mutate(u.id)}
                        >
                          {u.is_active ? 'Active' : 'Inactive'}
                        </button>
                      </td>
                      <td>
                        <Link to={`/users/${u.id}/edit`} className="btn btn-sm btn-outline-info me-1">
                          <i className="bi bi-pencil"></i>
                        </Link>
                        <button className="btn btn-sm btn-outline-danger" onClick={() => handleDelete(u.id, `${u.first_name} ${u.last_name}`)}>
                          <i className="bi bi-trash"></i>
                        </button>
                      </td>
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
                {Array.from({ length: pagination.last_page }, (_, i) => i + 1).map((p) => (
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
