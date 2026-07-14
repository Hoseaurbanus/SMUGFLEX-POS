import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import api from '../../services/api';
import { formatCurrency } from '../../utils/formatters';

export default function Purchases() {
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [status, setStatus] = useState('');

  const { data, isLoading } = useQuery({
    queryKey: ['purchases', { search, page, status }],
    queryFn: () => {
      const params = new URLSearchParams({ search, page, per_page: 15 });
      if (status) params.append('status', status);
      return api.get(`/purchases?${params}`).then(res => res.data);
    },
  });

  const purchases = data?.data || [];
  const pagination = data?.pagination;

  return (
    <div>
      <div className="page-header d-flex justify-content-between align-items-center mb-3">
        <h4 className="mb-0">Purchases</h4>
        <Link to="/purchases/create" className="btn btn-primary">
          <i className="bi bi-plus-lg me-1"></i> New Purchase
        </Link>
      </div>

      <div className="card">
        <div className="card-body">
          <div className="row g-2 mb-3">
            <div className="col-md-4">
              <input
                type="text"
                className="form-control"
                placeholder="Search purchases..."
                value={search}
                onChange={(e) => { setSearch(e.target.value); setPage(1); }}
              />
            </div>
            <div className="col-md-3">
              <select className="form-select" value={status} onChange={(e) => { setStatus(e.target.value); setPage(1); }}>
                <option value="">All Status</option>
                <option value="received">Received</option>
                <option value="pending">Pending</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
          </div>

          {isLoading ? (
            <div className="text-center py-4"><div className="spinner-border text-primary"></div></div>
          ) : purchases.length === 0 ? (
            <div className="empty-state text-center py-5">
              <i className="bi bi-bag fs-1 d-block mb-2"></i>
              <p>No purchases found</p>
            </div>
          ) : (
            <div className="table-responsive">
              <table className="table table-dark table-hover">
                <thead>
                  <tr>
                    <th>Ref #</th>
                    <th>Supplier</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Date</th>
                  </tr>
                </thead>
                <tbody>
                  {purchases.map((p) => (
                    <tr key={p.id}>
                      <td><strong>{p.reference_number || `PUR-${p.id}`}</strong></td>
                      <td>{p.supplier_name || '-'}</td>
                      <td>{formatCurrency(p.total || 0)}</td>
                      <td>
                        <span className={`badge bg-${p.payment_status === 'paid' ? 'success' : p.payment_status === 'partial' ? 'warning' : 'secondary'}`}>
                          {p.payment_status}
                        </span>
                      </td>
                      <td>
                        <span className={`badge bg-${p.status === 'received' ? 'success' : p.status === 'cancelled' ? 'danger' : 'warning'}`}>
                          {p.status}
                        </span>
                      </td>
                      <td>{new Date(p.created_at).toLocaleDateString()}</td>
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
