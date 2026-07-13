import { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { useQuery, useMutation } from '@tanstack/react-query';
import toast from 'react-hot-toast';
import api from '../../services/api';

export default function PurchaseCreate() {
  const navigate = useNavigate();
  const [supplierId, setSupplierId] = useState('');
  const [notes, setNotes] = useState('');
  const [items, setItems] = useState([{ product_id: '', quantity: 1, unit_cost: 0 }]);

  const { data: suppliersData } = useQuery({
    queryKey: ['suppliers-list'],
    queryFn: () => api.get('/suppliers?per_page=100').then(res => res.data.data || res.data),
  });

  const { data: productsData } = useQuery({
    queryKey: ['products-list'],
    queryFn: () => api.get('/products?per_page=100').then(res => res.data.data || res.data),
  });

  const mutation = useMutation({
    mutationFn: (data) => api.post('/purchases', data),
    onSuccess: () => { toast.success('Purchase created'); navigate('/purchases'); },
    onError: (err) => toast.error(err.response?.data?.message || 'Failed'),
  });

  const suppliers = Array.isArray(suppliersData) ? suppliersData : suppliersData?.data || [];
  const products = Array.isArray(productsData) ? productsData : productsData?.data || [];

  const addItem = () => setItems([...items, { product_id: '', quantity: 1, unit_cost: 0 }]);

  const removeItem = (idx) => setItems(items.filter((_, i) => i !== idx));

  const updateItem = (idx, field, value) => {
    const updated = [...items];
    updated[idx] = { ...updated[idx], [field]: value };
    setItems(updated);
  };

  const total = items.reduce((sum, item) => sum + item.quantity * item.unit_cost, 0);

  const handleSubmit = (e) => {
    e.preventDefault();
    if (!supplierId) { toast.error('Select a supplier'); return; }
    if (items.length === 0 || !items[0].product_id) { toast.error('Add at least one item'); return; }
    mutation.mutate({ supplier_id: supplierId, notes, items, total });
  };

  return (
    <div>
      <div className="page-header d-flex justify-content-between align-items-center mb-3">
        <h4 className="mb-0">New Purchase</h4>
        <Link to="/purchases" className="btn btn-secondary">
          <i className="bi bi-arrow-left me-1"></i> Back
        </Link>
      </div>

      <div className="card">
        <div className="card-body">
          <form onSubmit={handleSubmit}>
            <div className="row mb-3">
              <div className="col-md-6">
                <label className="form-label">Supplier *</label>
                <select className="form-select" value={supplierId} onChange={(e) => setSupplierId(e.target.value)} required>
                  <option value="">Select Supplier</option>
                  {suppliers.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                </select>
              </div>
            </div>

            <h6>Items</h6>
            {items.map((item, idx) => (
              <div key={idx} className="row g-2 mb-2 align-items-end">
                <div className="col-md-5">
                  {idx === 0 && <label className="form-label">Product</label>}
                  <select className="form-select" value={item.product_id} onChange={(e) => updateItem(idx, 'product_id', e.target.value)} required>
                    <option value="">Select Product</option>
                    {products.map((p) => <option key={p.id} value={p.id}>{p.name}</option>)}
                  </select>
                </div>
                <div className="col-md-2">
                  {idx === 0 && <label className="form-label">Qty</label>}
                  <input type="number" className="form-control" min="1" value={item.quantity} onChange={(e) => updateItem(idx, 'quantity', parseInt(e.target.value) || 0)} />
                </div>
                <div className="col-md-3">
                  {idx === 0 && <label className="form-label">Unit Cost</label>}
                  <input type="number" step="0.01" className="form-control" value={item.unit_cost} onChange={(e) => updateItem(idx, 'unit_cost', parseFloat(e.target.value) || 0)} />
                </div>
                <div className="col-md-2">
                  {idx === 0 && <label className="form-label">&nbsp;</label>}
                  <button type="button" className="btn btn-outline-danger w-100" onClick={() => removeItem(idx)} disabled={items.length === 1}>
                    <i className="bi bi-trash"></i>
                  </button>
                </div>
              </div>
            ))}
            <button type="button" className="btn btn-outline-primary btn-sm mb-3" onClick={addItem}>
              <i className="bi bi-plus me-1"></i> Add Item
            </button>

            <div className="mb-3">
              <label className="form-label">Notes</label>
              <textarea className="form-control" rows="2" value={notes} onChange={(e) => setNotes(e.target.value)}></textarea>
            </div>

            <div className="text-end mb-3">
              <strong>Total: ${total.toFixed(2)}</strong>
            </div>

            <button type="submit" className="btn btn-primary" disabled={mutation.isPending}>
              {mutation.isPending ? 'Saving...' : 'Create Purchase'}
            </button>
          </form>
        </div>
      </div>
    </div>
  );
}
