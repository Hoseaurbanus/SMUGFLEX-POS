import { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import api from '../../services/api';
import toast from 'react-hot-toast';

export default function ProductEdit() {
  const { id } = useParams();
  const navigate = useNavigate();
  const [loading, setLoading] = useState(false);
  const [fetching, setFetching] = useState(true);
  const { register, handleSubmit, reset, formState: { errors } } = useForm();

  useEffect(() => {
    const fetchProduct = async () => {
      try {
        const res = await api.get(`/products/${id}`);
        reset(res.data.data);
      } catch (error) {
        toast.error('Failed to load product');
        navigate('/products');
      } finally {
        setFetching(false);
      }
    };
    fetchProduct();
  }, [id, reset, navigate]);

  const onSubmit = async (data) => {
    setLoading(true);
    try {
      await api.put(`/products/${id}`, data);
      toast.success('Product updated successfully');
      navigate('/products');
    } catch (error) {
      toast.error(error.message);
    } finally {
      setLoading(false);
    }
  };

  if (fetching) return <div style={{ padding: '2rem', textAlign: 'center', color: '#94A3B8' }}>Loading...</div>;

  return (
    <div className="animate-fade-in">
      <div className="page-header">
        <h2>Edit Product</h2>
      </div>
      <div className="card">
        <div className="card-body">
          <form onSubmit={handleSubmit(onSubmit)}>
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '1rem' }}>
              <div className="mb-3">
                <label className="form-label">Product Name *</label>
                <input {...register('name', { required: 'Name is required' })} className="form-control" />
                {errors.name && <small className="text-danger">{errors.name.message}</small>}
              </div>
              <div className="mb-3">
                <label className="form-label">Barcode</label>
                <input {...register('barcode')} className="form-control" />
              </div>
              <div className="mb-3">
                <label className="form-label">SKU</label>
                <input {...register('sku')} className="form-control" />
              </div>
              <div className="mb-3">
                <label className="form-label">Buying Price *</label>
                <input type="number" step="0.01" {...register('buying_price', { required: true })} className="form-control" />
              </div>
              <div className="mb-3">
                <label className="form-label">Selling Price *</label>
                <input type="number" step="0.01" {...register('selling_price', { required: true })} className="form-control" />
              </div>
              <div className="mb-3">
                <label className="form-label">Wholesale Price</label>
                <input type="number" step="0.01" {...register('wholesale_price')} className="form-control" />
              </div>
              <div className="mb-3">
                <label className="form-label">Tax Rate (%)</label>
                <input type="number" step="0.01" {...register('tax_rate')} className="form-control" />
              </div>
              <div className="mb-3">
                <label className="form-label">Minimum Stock</label>
                <input type="number" {...register('minimum_stock')} className="form-control" />
              </div>
              <div className="mb-3">
                <label className="form-label">Status</label>
                <select {...register('status')} className="form-select">
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                  <option value="discontinued">Discontinued</option>
                </select>
              </div>
            </div>
            <div className="mb-3">
              <label className="form-label">Description</label>
              <textarea {...register('description')} className="form-control" rows={3} />
            </div>
            <div style={{ display: 'flex', gap: '0.75rem', justifyContent: 'flex-end' }}>
              <button type="button" onClick={() => navigate('/products')} className="btn btn-secondary">Cancel</button>
              <button type="submit" disabled={loading} className="btn btn-primary">
                {loading ? 'Updating...' : 'Update Product'}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
}
