import { useParams, Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import api from '../../services/api';
import { formatCurrency } from '../../utils/formatters';

export default function PurchaseDetail() {
  const { id } = useParams();

  const { data, isLoading } = useQuery({
    queryKey: ['purchase', id],
    queryFn: () => api.get(`/purchases/${id}`).then(res => res.data.data || res.data),
  });

  if (isLoading) return <div className="text-center py-4"><div className="spinner-border text-primary"></div></div>;

  const p = data;
  if (!p) return <div className="text-center py-4">Purchase not found</div>;

  const items = p.items || [];
  const payments = p.payments || [];

  return (
    <div>
      <div className="page-header d-flex justify-content-between align-items-center mb-3">
        <h4 className="mb-0">Purchase {p.reference_number || `PUR-${p.id}`}</h4>
        <Link to="/purchases" className="btn btn-secondary">
          <i className="bi bi-arrow-left me-1"></i> Back
        </Link>
      </div>

      <div className="row">
        <div className="col-lg-8">
          <div className="card mb-3">
            <div className="card-header">
              <h6 className="mb-0">Purchase Details</h6>
            </div>
            <div className="card-body">
              <div className="row mb-3">
                <div className="col-md-6">
                  <p className="mb-1 text-muted">Supplier</p>
                  <p className="fw-bold">{p.supplier_name || '-'}</p>
                </div>
                <div className="col-md-6">
                  <p className="mb-1 text-muted">Warehouse</p>
                  <p className="fw-bold">{p.warehouse_name || '-'}</p>
                </div>
                <div className="col-md-6">
                  <p className="mb-1 text-muted">Status</p>
                  <span className={`badge bg-${p.status === 'received' ? 'success' : p.status === 'cancelled' ? 'danger' : 'warning'}`}>
                    {p.status}
                  </span>
                </div>
                <div className="col-md-6">
                  <p className="mb-1 text-muted">Payment Status</p>
                  <span className={`badge bg-${p.payment_status === 'paid' ? 'success' : p.payment_status === 'partial' ? 'warning' : 'secondary'}`}>
                    {p.payment_status}
                  </span>
                </div>
                <div className="col-md-6">
                  <p className="mb-1 text-muted">Order Date</p>
                  <p>{p.order_date || new Date(p.created_at).toLocaleDateString()}</p>
                </div>
                <div className="col-md-6">
                  <p className="mb-1 text-muted">Created By</p>
                  <p>{p.created_by_name || '-'}</p>
                </div>
              </div>
              {p.notes && (
                <div>
                  <p className="mb-1 text-muted">Notes</p>
                  <p>{p.notes}</p>
                </div>
              )}
            </div>
          </div>

          <div className="card mb-3">
            <div className="card-header">
              <h6 className="mb-0">Items ({items.length})</h6>
            </div>
            <div className="card-body">
              <div className="table-responsive">
                <table className="table table-dark table-hover">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Product</th>
                      <th>SKU</th>
                      <th>Qty</th>
                      <th>Unit Cost</th>
                      <th>Total</th>
                    </tr>
                  </thead>
                  <tbody>
                    {items.map((item, idx) => (
                      <tr key={item.id || idx}>
                        <td>{idx + 1}</td>
                        <td>{item.product_name}</td>
                        <td>{item.sku || '-'}</td>
                        <td>{item.quantity}</td>
                        <td>{formatCurrency(item.unit_cost)}</td>
                        <td>{formatCurrency(item.total || item.quantity * item.unit_cost)}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          {payments.length > 0 && (
            <div className="card mb-3">
              <div className="card-header">
                <h6 className="mb-0">Payment History ({payments.length})</h6>
              </div>
              <div className="card-body">
                <div className="table-responsive">
                  <table className="table table-dark table-hover">
                    <thead>
                      <tr>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th>By</th>
                      </tr>
                    </thead>
                    <tbody>
                      {payments.map((pay, idx) => (
                        <tr key={pay.id || idx}>
                          <td>{new Date(pay.payment_date || pay.created_at).toLocaleDateString()}</td>
                          <td>{formatCurrency(pay.amount)}</td>
                          <td>{pay.payment_method}</td>
                          <td>{pay.reference || '-'}</td>
                          <td>{pay.user_name || '-'}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          )}
        </div>

        <div className="col-lg-4">
          <div className="card mb-3">
            <div className="card-header">
              <h6 className="mb-0">Summary</h6>
            </div>
            <div className="card-body">
              <div className="d-flex justify-content-between mb-2">
                <span>Subtotal</span>
                <span>{formatCurrency(p.subtotal || 0)}</span>
              </div>
              <div className="d-flex justify-content-between mb-2">
                <span>Discount</span>
                <span>-{formatCurrency(p.discount_amount || 0)}</span>
              </div>
              <div className="d-flex justify-content-between mb-2">
                <span>Tax</span>
                <span>{formatCurrency(p.tax_amount || 0)}</span>
              </div>
              <div className="d-flex justify-content-between mb-2">
                <span>Shipping</span>
                <span>{formatCurrency(p.shipping_cost || 0)}</span>
              </div>
              <hr />
              <div className="d-flex justify-content-between mb-2 fw-bold">
                <span>Total</span>
                <span>{formatCurrency(p.total || 0)}</span>
              </div>
              <div className="d-flex justify-content-between mb-2">
                <span>Paid</span>
                <span className="text-success">{formatCurrency(p.paid_amount || 0)}</span>
              </div>
              <div className="d-flex justify-content-between fw-bold">
                <span>Due</span>
                <span className="text-danger">{formatCurrency(p.due_amount || 0)}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
