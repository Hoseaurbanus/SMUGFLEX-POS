import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import api from '../../services/api';

export default function Sales() {
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const [status, setStatus] = useState('');

  const { data, isLoading } = useQuery({
    queryKey: ['sales', { search, page, dateFrom, dateTo, status }],
    queryFn: () => {
      const params = new URLSearchParams({ search, page, per_page: 15 });
      if (dateFrom) params.append('date_from', dateFrom);
      if (dateTo) params.append('date_to', dateTo);
      if (status) params.append('status', status);
      return api.get(`/sales?${params}`).then(res => res.data);
    },
  });

  const sales = data?.data || [];
  const pagination = data?.pagination;

  return (
    <div>
      <div className="page-header d-flex justify-content-between align-items-center mb-3">
        <h4 className="mb-0">Sales</h4>
        <Link to="/pos" className="btn btn-primary">
          <i className="bi bi-plus-lg me-1"></i> New Sale
        </Link>
      </div>

      <div className="card">
        <div className="card-body">
          <div className="row g-2 mb-3">
            <div className="col-md-3">
              <input
                type="text"
                className="form-control"
                placeholder="Search invoices..."
                value={search}
                onChange={(e) => { setSearch(e.target.value); setPage(1); }}
              />
            </div>
            <div className="col-md-2">
              <input
                type="date"
                className="form-control"
                value={dateFrom}
                onChange={(e) => { setDateFrom(e.target.value); setPage(1); }}
              />
            </div>
            <div className="col-md-2">
              <input
                type="date"
                className="form-control"
                value={dateTo}
                onChange={(e) => { setDateTo(e.target.value); setPage(1); }}
              />
            </div>
            <div className="col-md-2">
              <select className="form-select" value={status} onChange={(e) => { setStatus(e.target.value); setPage(1); }}>
                <option value="">All Status</option>
                <option value="completed">Completed</option>
                <option value="pending">Pending</option>
                <option value="voided">Voided</option>
              </select>
            </div>
          </div>

          {isLoading ? (
            <div className="text-center py-4"><div className="spinner-border text-primary"></div></div>
          ) : sales.length === 0 ? (
            <div className="empty-state text-center py-5">
              <i className="bi bi-receipt fs-1 d-block mb-2"></i>
              <p>No sales found</p>
            </div>
          ) : (
            <div className="table-responsive">
              <table className="table table-dark table-hover">
                <thead>
                  <tr>
                    <th>Invoice #</th>
                    <th>Customer</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {sales.map((s) => (
                    <tr key={s.id}>
                      <td><strong>{s.invoice_number}</strong></td>
                      <td>{s.customer?.first_name} {s.customer?.last_name}</td>
                      <td>${parseFloat(s.total || 0).toFixed(2)}</td>
                      <td>{s.payment_method}</td>
                      <td>
                        <span className={`badge bg-${s.status === 'completed' ? 'success' : s.status === 'voided' ? 'danger' : 'warning'}`}>
                          {s.status}
                        </span>
                      </td>
                      <td>{new Date(s.created_at).toLocaleDateString()}</td>
                      <td>
                        <Link to={`/sales/${s.id}`} className="btn btn-sm btn-outline-info">
                          <i className="bi bi-eye"></i>
                        </Link>
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
