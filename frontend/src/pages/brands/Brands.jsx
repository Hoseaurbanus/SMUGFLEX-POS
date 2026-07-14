import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import toast from 'react-hot-toast';
import Swal from 'sweetalert2';
import api from '../../services/api';

const emptyForm = { name: '', description: '' };

export default function Brands() {
  const queryClient = useQueryClient();
  const [showModal, setShowModal] = useState(false);
  const [editId, setEditId] = useState(null);
  const [form, setForm] = useState(emptyForm);

  const { data, isLoading } = useQuery({
    queryKey: ['brands'],
    queryFn: () => api.get('/brands').then(res => res.data.data || res.data),
  });

  const createMutation = useMutation({
    mutationFn: (data) => api.post('/brands', data),
    onSuccess: () => { queryClient.invalidateQueries({ queryKey: ['brands'] }); toast.success('Brand created'); closeModal(); },
    onError: (err) => toast.error(err.response?.data?.message || 'Error'),
  });

  const updateMutation = useMutation({
    mutationFn: (formData) => api.put(`/brands/${editId}`, formData),
    onSuccess: () => { queryClient.invalidateQueries({ queryKey: ['brands'] }); toast.success('Brand updated'); closeModal(); },
    onError: (err) => toast.error(err.response?.data?.message || 'Error'),
  });

  const deleteMutation = useMutation({
    mutationFn: (id) => api.delete(`/brands/${id}`),
    onSuccess: () => { queryClient.invalidateQueries({ queryKey: ['brands'] }); toast.success('Brand deleted'); },
    onError: (err) => toast.error(err.response?.data?.message || 'Cannot delete brand'),
  });

  const closeModal = () => { setShowModal(false); setEditId(null); setForm(emptyForm); };
  const openCreate = () => { setEditId(null); setForm(emptyForm); setShowModal(true); };
  const openEdit = (b) => { setEditId(b.id); setForm({ name: b.name, description: b.description || '' }); setShowModal(true); };

  const handleSubmit = (e) => {
    e.preventDefault();
    if (editId) updateMutation.mutate(form);
    else createMutation.mutate(form);
  };

  const handleDelete = (id, name) => {
    Swal.fire({
      title: 'Delete Brand?',
      text: `Delete "${name}"?`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc3545',
      confirmButtonText: 'Delete',
    }).then((r) => { if (r.isConfirmed) deleteMutation.mutate(id); });
  };

  const brands = Array.isArray(data) ? data : data?.data || [];

  return (
    <div>
      <div className="page-header d-flex justify-content-between align-items-center mb-3">
        <h4 className="mb-0">Brands</h4>
        <button className="btn btn-primary" onClick={openCreate}>
          <i className="bi bi-plus-lg me-1"></i> Add Brand
        </button>
      </div>

      <div className="card">
        <div className="card-body">
          {isLoading ? (
            <div className="text-center py-4"><div className="spinner-border text-primary"></div></div>
          ) : brands.length === 0 ? (
            <div className="empty-state text-center py-5">
              <i className="bi bi-tag fs-1 d-block mb-2"></i>
              <p>No brands yet</p>
            </div>
          ) : (
            <div className="table-responsive">
              <table className="table table-dark table-hover">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Products</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {brands.map((b) => (
                    <tr key={b.id}>
                      <td>{b.name}</td>
                      <td>{b.description}</td>
                      <td>{b.product_count ?? 0}</td>
                      <td>
                        <button className="btn btn-sm btn-outline-info me-1" onClick={() => openEdit(b)}>
                          <i className="bi bi-pencil"></i>
                        </button>
                        <button className="btn btn-sm btn-outline-danger" onClick={() => handleDelete(b.id, b.name)}>
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
          <div className="modal-dialog">
            <div className="modal-content" style={{ background: '#2a2a2a', color: '#fff' }}>
              <div className="modal-header">
                <h5 className="modal-title">{editId ? 'Edit' : 'Add'} Brand</h5>
                <button type="button" className="btn-close btn-close-white" onClick={closeModal}></button>
              </div>
              <form onSubmit={handleSubmit}>
                <div className="modal-body">
                  <div className="mb-3">
                    <label className="form-label">Name *</label>
                    <input className="form-control" required value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} />
                  </div>
                  <div className="mb-3">
                    <label className="form-label">Description</label>
                    <textarea className="form-control" rows="3" value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })}></textarea>
                  </div>
                </div>
                <div className="modal-footer">
                  <button type="button" className="btn btn-secondary" onClick={closeModal}>Cancel</button>
                  <button type="submit" className="btn btn-primary" disabled={createMutation.isPending || updateMutation.isPending}>
                    {createMutation.isPending || updateMutation.isPending ? 'Saving...' : 'Save'}
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
