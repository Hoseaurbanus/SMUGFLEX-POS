import { useParams, Link, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import toast from 'react-hot-toast';
import Swal from 'sweetalert2';
import api from '../../services/api';

export default function SaleView() {
  const { id } = useParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();

  const { data: sale, isLoading } = useQuery({
    queryKey: ['sale', id],
    queryFn: () => api.get(`/sales/${id}`).then(res => res.data.data || res.data),
  });

  const voidMutation = useMutation({
    mutationFn: () => api.post(`/sales/${id}/void`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['sale', id] });
      toast.success('Sale voided');
    },
    onError: (err) => toast.error(err.response?.data?.message || 'Failed to void sale'),
  });

  const handleVoid = () => {
    Swal.fire({
      title: 'Void Sale?',
      text: 'This action cannot be undone.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc3545',
      confirmButtonText: 'Yes, void it!',
    }).then((result) => {
      if (result.isConfirmed) voidMutation.mutate();
    });
  };

  if (isLoading) return <div className="text-center py-4"><div className="spinner-border text-primary"></div></div>;
  if (!sale) return <div className="alert alert-danger">Sale not found</div>;

  return (
    <div>
      <div className="page-header d-flex justify-content-between align-items-center mb-3">
        <h4 className="mb-0">Sale #{sale.invoice_number}</h4>
        <div>
          {sale.status !== 'voided' && (
            <button className="btn btn-danger me-2" onClick={handleVoid} disabled={voidMutation.isPending}>
              <i className="bi bi-x-lg me-1"></i> Void Sale
            </button>
          )}
          <Link to="/sales" className="btn btn-secondary">
            <i className="bi bi-arrow-left me-1"></i> Back
          </Link>
        </div>
      </div>

      <div className="row">
        <div className="col-md-6 mb-3">
          <div className="card">
            <div className="card-body">
              <h6 className="card-title">Invoice Details</h6>
              <table className="table table-dark table-sm mb-0">
                <tbody>
                  <tr><td>Invoice #</td><td>{sale.invoice_number}</td></tr>
                  <tr><td>Date</td><td>{new Date(sale.created_at).toLocaleString()}</td></tr>
                  <tr><td>Customer</td><td>{sale.customer?.first_name} {sale.customer?.last_name}</td></tr>
                  <tr><td>Payment Method</td><td>{sale.payment_method}</td></tr>
                  <tr>
                    <td>Status</td>
                    <td>
                      <span className={`badge bg-${sale.status === 'completed' ? 'success' : sale.status === 'voided' ? 'danger' : 'warning'}`}>
                        {sale.status}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <div className="col-md-6 mb-3">
          <div className="card">
            <div className="card-body">
              <h6 className="card-title">Summary</h6>
              <table className="table table-dark table-sm mb-0">
                <tbody>
                  <tr><td>Subtotal</td><td>${parseFloat(sale.subtotal || 0).toFixed(2)}</td></tr>
                  <tr><td>Tax</td><td>${parseFloat(sale.tax || 0).toFixed(2)}</td></tr>
                  <tr><td>Discount</td><td>-${parseFloat(sale.discount || 0).toFixed(2)}</td></tr>
                  <tr><td><strong>Total</strong></td><td><strong>${parseFloat(sale.total || 0).toFixed(2)}</strong></td></tr>
                  <tr><td>Paid</td><td>${parseFloat(sale.amount_paid || 0).toFixed(2)}</td></tr>
                  <tr><td>Change</td><td>${parseFloat(sale.change || 0).toFixed(2)}</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <div className="card">
        <div className="card-body">
          <h6 className="card-title">Items</h6>
          <div className="table-responsive">
            <table className="table table-dark table-hover">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Qty</th>
                  <th>Unit Price</th>
                  <th>Discount</th>
                  <th>Total</th>
                </tr>
              </thead>
              <tbody>
                {(sale.items || []).map((item, idx) => (
                  <tr key={idx}>
                    <td>{item.product?.name || item.product_name}</td>
                    <td>{item.quantity}</td>
                    <td>${parseFloat(item.unit_price || 0).toFixed(2)}</td>
                    <td>${parseFloat(item.discount || 0).toFixed(2)}</td>
                    <td>${parseFloat(item.total || 0).toFixed(2)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  );
}
