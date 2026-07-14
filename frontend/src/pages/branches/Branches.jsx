import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import toast from 'react-hot-toast';
import Swal from 'sweetalert2';
import api from '../../services/api';

const emptyForm = { name: '', code: '', phone: '', address: '', status: 'active' };

export default function Branches() {
  const queryClient = useQueryClient();
  const [showModal, setShowModal] = useState(false);
  const [editId, setEditId] = useState(null);
  const [form, setForm] = useState(emptyForm);

  const { data, isLoading } = useQuery({
    queryKey: ['branches'],
    queryFn: () => api.get('/branches').then(res => res.data.data || res.data),
  });

  const createMutation = useMutation({
    mutationFn: (data) => api.post('/branches', data),
    onSuccess: () => { queryClient.invalidateQueries({ queryKey: ['branches'] }); toast.success('Branch created'); closeModal(); },
    onError: (err) => toast.error(err.response?.data?.message || 'Error'),
  });

  const updateMutation = useMutation({
    mutationFn: (formData) => api.put(`/branches/${editId}`, formData),
    onSuccess: () => { queryClient.invalidateQueries({ queryKey: ['branches'] }); toast.success('Branch updated'); closeModal(); },
    onError: (err) => toast.error(err.response?.data?.message || 'Error'),
  });

  const deleteMutation = useMutation({
    mutationFn: (id) => api.delete(`/branches/${id}`),
    onSuccess: () => { queryClient.invalidateQueries({ queryKey: ['branches'] }); toast.success('Branch deleted'); },
    onError: (err) => toast.error(err.response?.data?.message || 'Cannot delete'),
  });

  const closeModal = () => { setShowModal(false); setEditId(null); setForm(emptyForm); };
  const openCreate = () => { setEditId(null); setForm(emptyForm); setShowModal(true); };
  const openEdit = (b) => { setEditId(b.id); setForm({ name: b.name, code: b.code || '', phone: b.phone || '', address: b.address || '', status: b.status || 'active' }); setShowModal(true); };

  const handleSubmit = (e) => {
    e.preventDefault();
    if (editId) updateMutation.mutate(form);
    else createMutation.mutate(form);
  };

  const handleDelete = (id, name) => {
    Swal.fire({ title: 'Delete Branch?', text: `Delete "${name}"?`, icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc3545', confirmButtonText: 'Delete' })
      .then((r) => { if (r.isConfirmed) deleteMutation.mutate(id); });
  };

  const branches = Array.isArray(data) ? data : data?.data || [];

  return (
    <div>
      <div className="page-header d-flex justify-content-between align-items-center mb-3">
        <h4 className="mb-0">Branches</h4>
        <button className="btn btn-primary" onClick={openCreate}>
          <i className="bi bi-plus-lg me-1"></i> Add Branch
        </button>
      </div>

      <div className="card">
        <div className="card-body">
          {isLoading ? (
            <div className="text-center py-4"><div className="spinner-border text-primary"></div></div>
          ) : branches.length === 0 ? (
            <div className="empty-state text-center py-5">
              <i className="bi bi-building fs-1 d-block mb-2"></i>
              <p>No branches yet</p>
            </div>
          ) : (
            <div className="table-responsive">
              <table className="table table-dark table-hover">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Users</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {branches.map((b) => (
                    <tr key={b.id}>
                      <td>{b.name}</td>
                      <td>{b.code}</td>
                      <td>{b.phone}</td>
                      <td><span className={`badge bg-${b.is_active ? 'success' : 'secondary'}`}>{b.is_active ? 'Active' : 'Inactive'}</span></td>
                      <td>{b.user_count ?? 0}</td>
                      <td>
                        <button className="btn btn-sm btn-outline-info me-1" onClick={() => openEdit(b)}><i className="bi bi-pencil"></i></button>
                        <button className="btn btn-sm btn-outline-danger" onClick={() => handleDelete(b.id, b.name)}><i className="bi bi-trash"></i></button>
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
          <div className="modal-dialog">
            <div className="modal-content" style={{ background: '#2a2a2a', color: '#fff' }}>
              <div className="modal-header">
                <h5 className="modal-title">{editId ? 'Edit' : 'Add'} Branch</h5>
                <button type="button" className="btn-close btn-close-white" onClick={closeModal}></button>
              </div>
              <form onSubmit={handleSubmit}>
                <div className="modal-body">
                  <div className="mb-3">
                    <label className="form-label">Name *</label>
                    <input className="form-control" required value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} />
                  </div>
                  <div className="mb-3">
                    <label className="form-label">Code *</label>
                    <input className="form-control" required value={form.code} onChange={(e) => setForm({ ...form, code: e.target.value })} />
                  </div>
                  <div className="mb-3">
                    <label className="form-label">Phone</label>
                    <input className="form-control" value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} />
                  </div>
                  <div className="mb-3">
                    <label className="form-label">Address</label>
                    <textarea className="form-control" rows="2" value={form.address} onChange={(e) => setForm({ ...form, address: e.target.value })}></textarea>
                  </div>
                  <div className="mb-3">
                    <label className="form-label">Status</label>
                    <select className="form-select" value={form.status} onChange={(e) => setForm({ ...form, status: e.target.value })}>
                      <option value="active">Active</option>
                      <option value="inactive">Inactive</option>
                    </select>
                  </div>
                </div>
                <div className="modal-footer">
                  <button type="button" className="btn btn-secondary" onClick={closeModal}>Cancel</button>
                  <button type="submit" className="btn btn-primary" disabled={createMutation.isPending || updateMutation.isPending}>Save</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
