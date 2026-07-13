import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import toast from 'react-hot-toast';
import Swal from 'sweetalert2';
import api from '../../services/api';

const emptyForm = { category: '', amount: '', payment_method: 'cash', description: '', date: '' };

export default function Expenses() {
  const queryClient = useQueryClient();
  const [page, setPage] = useState(1);
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const [showModal, setShowModal] = useState(false);
  const [form, setForm] = useState(emptyForm);

  const { data, isLoading } = useQuery({
    queryKey: ['expenses', { page, dateFrom, dateTo }],
    queryFn: () => {
      const params = new URLSearchParams({ page, per_page: 15 });
      if (dateFrom) params.append('date_from', dateFrom);
      if (dateTo) params.append('date_to', dateTo);
      return api.get(`/expenses?${params}`).then(res => res.data);
    },
  });

  const createMutation = useMutation({
    mutationFn: (data) => api.post('/expenses', data),
    onSuccess: () => { queryClient.invalidateQueries({ queryKey: ['expenses'] }); toast.success('Expense added'); closeModal(); },
    onError: (err) => toast.error(err.response?.data?.message || 'Error'),
  });

  const deleteMutation = useMutation({
    mutationFn: (id) => api.delete(`/expenses/${id}`),
    onSuccess: () => { queryClient.invalidateQueries({ queryKey: ['expenses'] }); toast.success('Expense deleted'); },
  });

  const closeModal = () => { setShowModal(false); setForm(emptyForm); };

  const handleSubmit = (e) => {
    e.preventDefault();
    createMutation.mutate({ ...form, amount: parseFloat(form.amount) });
  };

  const handleDelete = (id) => {
    Swal.fire({ title: 'Delete Expense?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc3545', confirmButtonText: 'Delete' })
      .then((r) => { if (r.isConfirmed) deleteMutation.mutate(id); });
  };

  const expenses = data?.data || [];
  const pagination = data?.pagination;

  return (
    <div>
      <div className="page-header d-flex justify-content-between align-items-center mb-3">
        <h4 className="mb-0">Expenses</h4>
        <button className="btn btn-primary" onClick={() => { setForm(emptyForm); setShowModal(true); }}>
          <i className="bi bi-plus-lg me-1"></i> Add Expense
        </button>
      </div>

      <div className="card">
        <div className="card-body">
          <div className="row g-2 mb-3">
            <div className="col-md-3">
              <input type="date" className="form-control" value={dateFrom} onChange={(e) => { setDateFrom(e.target.value); setPage(1); }} />
            </div>
            <div className="col-md-3">
              <input type="date" className="form-control" value={dateTo} onChange={(e) => { setDateTo(e.target.value); setPage(1); }} />
            </div>
          </div>

          {isLoading ? (
            <div className="text-center py-4"><div className="spinner-border text-primary"></div></div>
          ) : expenses.length === 0 ? (
            <div className="empty-state text-center py-5">
              <i className="bi bi-wallet2 fs-1 d-block mb-2"></i>
              <p>No expenses found</p>
            </div>
          ) : (
            <div className="table-responsive">
              <table className="table table-dark table-hover">
                <thead>
                  <tr>
                    <th>Ref #</th>
                    <th>Category</th>
                    <th>Amount</th>
                    <th>Payment</th>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {expenses.map((e) => (
                    <tr key={e.id}>
                      <td>{e.reference_number || `EXP-${e.id}`}</td>
                      <td>{e.category}</td>
                      <td>${parseFloat(e.amount || 0).toFixed(2)}</td>
                      <td>{e.payment_method}</td>
                      <td>{new Date(e.date || e.created_at).toLocaleDateString()}</td>
                      <td>{e.description}</td>
                      <td>
                        <button className="btn btn-sm btn-outline-danger" onClick={() => handleDelete(e.id)}>
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

      {showModal && (
        <div className="modal d-block" tabIndex="-1" style={{ backgroundColor: 'rgba(0,0,0,0.5)' }}>
          <div className="modal-dialog">
            <div className="modal-content" style={{ background: '#2a2a2a', color: '#fff' }}>
              <div className="modal-header">
                <h5 className="modal-title">Add Expense</h5>
                <button type="button" className="btn-close btn-close-white" onClick={closeModal}></button>
              </div>
              <form onSubmit={handleSubmit}>
                <div className="modal-body">
                  <div className="mb-3">
                    <label className="form-label">Category *</label>
                    <input className="form-control" required value={form.category} onChange={(e) => setForm({ ...form, category: e.target.value })} />
                  </div>
                  <div className="mb-3">
                    <label className="form-label">Amount *</label>
                    <input type="number" step="0.01" className="form-control" required value={form.amount} onChange={(e) => setForm({ ...form, amount: e.target.value })} />
                  </div>
                  <div className="mb-3">
                    <label className="form-label">Payment Method</label>
                    <select className="form-select" value={form.payment_method} onChange={(e) => setForm({ ...form, payment_method: e.target.value })}>
                      <option value="cash">Cash</option>
                      <option value="card">Card</option>
                      <option value="bank_transfer">Bank Transfer</option>
                    </select>
                  </div>
                  <div className="mb-3">
                    <label className="form-label">Date *</label>
                    <input type="date" className="form-control" required value={form.date} onChange={(e) => setForm({ ...form, date: e.target.value })} />
                  </div>
                  <div className="mb-3">
                    <label className="form-label">Description</label>
                    <textarea className="form-control" rows="2" value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })}></textarea>
                  </div>
                </div>
                <div className="modal-footer">
                  <button type="button" className="btn btn-secondary" onClick={closeModal}>Cancel</button>
                  <button type="submit" className="btn btn-primary" disabled={createMutation.isPending}>Save</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
