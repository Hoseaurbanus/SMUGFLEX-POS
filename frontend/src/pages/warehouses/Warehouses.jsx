import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import toast from 'react-hot-toast';
import Swal from 'sweetalert2';
import api from '../../services/api';

const emptyForm = { name: '', code: '', branch_id: '', status: 'active' };

export default function Warehouses() {
  const queryClient = useQueryClient();
  const [showModal, setShowModal] = useState(false);
  const [editId, setEditId] = useState(null);
  const [form, setForm] = useState(emptyForm);

  const { data: branchesData } = useQuery({
    queryKey: ['branches-list'],
    queryFn: () => api.get('/branches').then(res => res.data.data || res.data),
  });

  const { data, isLoading } = useQuery({
    queryKey: ['warehouses'],
    queryFn: () => api.get('/warehouses').then(res => res.data.data || res.data),
  });

  const createMutation = useMutation({
    mutationFn: (data) => api.post('/warehouses', data),
    onSuccess: () => { queryClient.invalidateQueries({ queryKey: ['warehouses'] }); toast.success('Warehouse created'); closeModal(); },
    onError: (err) => toast.error(err.response?.data?.message || 'Error'),
  });

  const updateMutation = useMutation({
    mutationFn: (formData) => api.put(`/warehouses/${editId}`, formData),
    onSuccess: () => { queryClient.invalidateQueries({ queryKey: ['warehouses'] }); toast.success('Warehouse updated'); closeModal(); },
    onError: (err) => toast.error(err.response?.data?.message || 'Error'),
  });

  const deleteMutation = useMutation({
    mutationFn: (id) => api.delete(`/warehouses/${id}`),
    onSuccess: () => { queryClient.invalidateQueries({ queryKey: ['warehouses'] }); toast.success('Warehouse deleted'); },
    onError: (err) => toast.error(err.response?.data?.message || 'Cannot delete'),
  });

  const closeModal = () => { setShowModal(false); setEditId(null); setForm(emptyForm); };
  const openCreate = () => { setEditId(null); setForm(emptyForm); setShowModal(true); };
  const openEdit = (w) => { setEditId(w.id); setForm({ name: w.name, code: w.code || '', branch_id: w.branch_id || '', status: w.status || 'active' }); setShowModal(true); };

  const handleSubmit = (e) => {
    e.preventDefault();
    if (editId) updateMutation.mutate(form);
    else createMutation.mutate(form);
  };

  const handleDelete = (id, name) => {
    Swal.fire({ title: 'Delete Warehouse?', text: `Delete "${name}"?`, icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc3545', confirmButtonText: 'Delete' })
      .then((r) => { if (r.isConfirmed) deleteMutation.mutate(id); });
  };

  const warehouses = Array.isArray(data) ? data : data?.data || [];
  const branches = Array.isArray(branchesData) ? branchesData : branchesData?.data || [];

  return (
    <div>
      <div className="page-header d-flex justify-content-between align-items-center mb-3">
        <h4 className="mb-0">Warehouses</h4>
        <button className="btn btn-primary" onClick={openCreate}>
          <i className="bi bi-plus-lg me-1"></i> Add Warehouse
        </button>
      </div>

      <div className="card">
        <div className="card-body">
          {isLoading ? (
            <div className="text-center py-4"><div className="spinner-border text-primary"></div></div>
          ) : warehouses.length === 0 ? (
            <div className="empty-state text-center py-5">
              <i className="bi bi-building fs-1 d-block mb-2"></i>
              <p>No warehouses yet</p>
            </div>
          ) : (
            <div className="table-responsive">
              <table className="table table-dark table-hover">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Branch</th>
                    <th>Status</th>
                    <th>Products</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {warehouses.map((w) => (
                    <tr key={w.id}>
                      <td>{w.name}</td>
                      <td>{w.code}</td>
                      <td>{w.branch_name || '—'}</td>
                      <td><span className={`badge bg-${w.is_active ? 'success' : 'secondary'}`}>{w.is_active ? 'Active' : 'Inactive'}</span></td>
                      <td>{w.product_count ?? 0}</td>
                      <td>
                        <button className="btn btn-sm btn-outline-info me-1" onClick={() => openEdit(w)}><i className="bi bi-pencil"></i></button>
                        <button className="btn btn-sm btn-outline-danger" onClick={() => handleDelete(w.id, w.name)}><i className="bi bi-trash"></i></button>
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
                <h5 className="modal-title">{editId ? 'Edit' : 'Add'} Warehouse</h5>
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
                    <label className="form-label">Branch</label>
                    <select className="form-select" value={form.branch_id} onChange={(e) => setForm({ ...form, branch_id: e.target.value })}>
                      <option value="">Select Branch</option>
                      {branches.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}
                    </select>
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
