import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../services/api';

export default function Returns() {
  const [page, setPage] = useState(1);

  const { data, isLoading } = useQuery({
    queryKey: ['returns', { page }],
    queryFn: () => api.get(`/returns?page=${page}&per_page=15`).then(res => res.data),
  });

  const returns = data?.data || [];
  const pagination = data?.pagination;

  return (
    <div>
      <div className="page-header mb-3">
        <h4 className="mb-0">Returns</h4>
      </div>

      <div className="card">
        <div className="card-body">
          {isLoading ? (
            <div className="text-center py-4"><div className="spinner-border text-primary"></div></div>
          ) : returns.length === 0 ? (
            <div className="empty-state text-center py-5">
              <i className="bi bi-arrow-return-left fs-1 d-block mb-2"></i>
              <p>No returns found</p>
            </div>
          ) : (
            <div className="table-responsive">
              <table className="table table-dark table-hover">
                <thead>
                  <tr>
                    <th>Return #</th>
                    <th>Sale Ref</th>
                    <th>Refund Amount</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Date</th>
                  </tr>
                </thead>
                <tbody>
                  {returns.map((r) => (
                    <tr key={r.id}>
                      <td><strong>{r.return_number || `RET-${r.id}`}</strong></td>
                      <td>{r.sale?.invoice_number || r.sale_id}</td>
                      <td>${parseFloat(r.refund_amount || 0).toFixed(2)}</td>
                      <td>{r.reason}</td>
                      <td>
                        <span className={`badge bg-${r.status === 'approved' ? 'success' : r.status === 'rejected' ? 'danger' : 'warning'}`}>
                          {r.status}
                        </span>
                      </td>
                      <td>{new Date(r.created_at).toLocaleDateString()}</td>
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
