import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import toast from 'react-hot-toast';
import Swal from 'sweetalert2';
import api from '../../services/api';

export default function Roles() {
  const queryClient = useQueryClient();
  const [showModal, setShowModal] = useState(false);
  const [editId, setEditId] = useState(null);
  const [name, setName] = useState('');
  const [description, setDescription] = useState('');
  const [selectedPerms, setSelectedPerms] = useState([]);

  const { data: rolesData, isLoading } = useQuery({
    queryKey: ['roles'],
    queryFn: () => api.get('/roles').then(res => res.data.data || res.data),
  });

  const { data: permsData } = useQuery({
    queryKey: ['permissions'],
    queryFn: () => api.get('/permissions').then(res => res.data.data || res.data),
  });

  const createMutation = useMutation({
    mutationFn: (data) => api.post('/roles', data),
    onSuccess: () => { queryClient.invalidateQueries({ queryKey: ['roles'] }); toast.success('Role created'); closeModal(); },
    onError: (err) => toast.error(err.response?.data?.message || 'Error'),
  });

  const updateMutation = useMutation({
    mutationFn: (data) => api.put(`/roles/${editId}`, data),
    onSuccess: () => { queryClient.invalidateQueries({ queryKey: ['roles'] }); toast.success('Role updated'); closeModal(); },
    onError: (err) => toast.error(err.response?.data?.message || 'Error'),
  });

  const deleteMutation = useMutation({
    mutationFn: (id) => api.delete(`/roles/${id}`),
    onSuccess: () => { queryClient.invalidateQueries({ queryKey: ['roles'] }); toast.success('Role deleted'); },
    onError: (err) => toast.error(err.response?.data?.message || 'Cannot delete'),
  });

  const roles = Array.isArray(rolesData) ? rolesData : rolesData?.data || [];
  const permissions = Array.isArray(permsData) ? permsData : permsData?.data || [];

  const closeModal = () => { setShowModal(false); setEditId(null); setName(''); setDescription(''); setSelectedPerms([]); };

  const openCreate = () => { setEditId(null); setName(''); setDescription(''); setSelectedPerms([]); setShowModal(true); };

  const openEdit = (role) => {
    setEditId(role.id);
    setName(role.name);
    setDescription(role.description || '');
    setSelectedPerms(role.permissions?.map(p => p.id) || []);
    setShowModal(true);
  };

  const togglePerm = (permId) => {
    setSelectedPerms(prev => prev.includes(permId) ? prev.filter(id => id !== permId) : [...prev, permId]);
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    const payload = { name, description, permissions: selectedPerms };
    if (editId) updateMutation.mutate(payload);
    else createMutation.mutate(payload);
  };

  const handleDelete = (id, roleName) => {
    Swal.fire({
      title: 'Delete Role?',
      text: `Delete "${roleName}"?`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc3545',
      confirmButtonText: 'Delete',
    }).then((r) => { if (r.isConfirmed) deleteMutation.mutate(id); });
  };

  return (
    <div>
      <div className="page-header d-flex justify-content-between align-items-center mb-3">
        <h4 className="mb-0">Roles</h4>
        <button className="btn btn-primary" onClick={openCreate}>
          <i className="bi bi-plus-lg me-1"></i> Add Role
        </button>
      </div>

      <div className="card">
        <div className="card-body">
          {isLoading ? (
            <div className="text-center py-4"><div className="spinner-border text-primary"></div></div>
          ) : roles.length === 0 ? (
            <div className="empty-state text-center py-5">
              <i className="bi bi-shield fs-1 d-block mb-2"></i>
              <p>No roles yet</p>
            </div>
          ) : (
            <div className="table-responsive">
              <table className="table table-dark table-hover">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Users</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {roles.map((r) => (
                    <tr key={r.id}>
                      <td>{r.name}</td>
                      <td>{r.description}</td>
                      <td>{r.user_count ?? 0}</td>
                      <td>
                        <button className="btn btn-sm btn-outline-info me-1" onClick={() => openEdit(r)}>
                          <i className="bi bi-pencil"></i>
                        </button>
                        <button className="btn btn-sm btn-outline-danger" onClick={() => handleDelete(r.id, r.name)}>
                          <i className="bi bi-trash"></i>
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </div>

      {showModal && (
        <div className="modal d-block" tabIndex="-1" style={{ backgroundColor: 'rgba(0,0,0,0.5)' }}>
          <div className="modal-dialog modal-lg">
            <div className="modal-content" style={{ background: '#2a2a2a', color: '#fff' }}>
              <div className="modal-header">
                <h5 className="modal-title">{editId ? 'Edit' : 'Add'} Role</h5>
                <button type="button" className="btn-close btn-close-white" onClick={closeModal}></button>
              </div>
              <form onSubmit={handleSubmit}>
                <div className="modal-body">
                  <div className="mb-3">
                    <label className="form-label">Name *</label>
                    <input className="form-control" required value={name} onChange={(e) => setName(e.target.value)} />
                  </div>
                  <div className="mb-3">
                    <label className="form-label">Description</label>
                    <textarea className="form-control" rows="2" value={description} onChange={(e) => setDescription(e.target.value)}></textarea>
                  </div>
                  <div className="mb-3">
                    <label className="form-label">Permissions</label>
                    <div className="row" style={{ maxHeight: '200px', overflowY: 'auto' }}>
                      {permissions.map((p) => (
                        <div key={p.id} className="col-md-4">
                          <div className="form-check">
                            <input
                              className="form-check-input"
                              type="checkbox"
                              id={`perm-${p.id}`}
                              checked={selectedPerms.includes(p.id)}
                              onChange={() => togglePerm(p.id)}
                            />
                            <label className="form-check-label" htmlFor={`perm-${p.id}`}>{p.name}</label>
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                </div>
                <div className="modal-footer">
                  <button type="button" className="btn btn-secondary" onClick={closeModal}>Cancel</button>
                  <button type="submit" className="btn btn-primary" disabled={createMutation.isPending || updateMutation.isPending}>
                    Save
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
