import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import api from '../../services/api';
import { formatCurrency, getStatusColor } from '../../utils/formatters';
import toast from 'react-hot-toast';

export default function Products() {
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);

  const { data, isLoading, refetch } = useQuery({
    queryKey: ['products', search, page],
    queryFn: async () => {
      const res = await api.get('/products', { params: { search, page, limit: 15 } });
      return res.data.data;
    },
  });

  const handleDelete = async (id) => {
    if (!confirm('Are you sure you want to delete this product?')) return;
    try {
      await api.delete(`/products/${id}`);
      toast.success('Product deleted');
      refetch();
    } catch (error) {
      toast.error(error.message);
    }
  };

  return (
    <div className="animate-fade-in">
      <div className="page-header">
        <h2>Products</h2>
        <Link to="/products/create" className="btn btn-primary">
          <i className="bi bi-plus-lg" /> Add Product
        </Link>
      </div>

      <div className="card">
        <div className="card-header" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <div className="search-box" style={{ position: 'relative', width: 300 }}>
            <i className="bi bi-search" style={{ position: 'absolute', left: 12, top: '50%', transform: 'translateY(-50%)', color: '#64748B', fontSize: '0.85rem' }} />
            <input
              type="text"
              className="form-control"
              placeholder="Search products..."
              value={search}
              onChange={(e) => { setSearch(e.target.value); setPage(1); }}
              style={{ paddingLeft: '2.5rem' }}
            />
          </div>
          <button className="btn btn-outline-secondary btn-sm">
            <i className="bi bi-download" /> Export
          </button>
        </div>
        <div className="card-body" style={{ padding: 0 }}>
          <table className="table table-hover">
            <thead>
              <tr>
                <th>Product</th>
                <th>SKU</th>
                <th>Category</th>
                <th>Buying Price</th>
                <th>Selling Price</th>
                <th>Stock</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {isLoading ? (
                <tr><td colSpan={8} style={{ textAlign: 'center', padding: '2rem' }}>Loading...</td></tr>
              ) : (data?.items || []).length > 0 ? (
                data.items.map((product) => (
                  <tr key={product.id}>
                    <td style={{ fontWeight: 500, color: '#F8FAFC' }}>{product.name}</td>
                    <td><code style={{ color: '#38BDF8' }}>{product.sku}</code></td>
                    <td>{product.category_name || '-'}</td>
                    <td>{formatCurrency(product.buying_price)}</td>
                    <td style={{ fontWeight: 600, color: '#22C55E' }}>{formatCurrency(product.selling_price)}</td>
                    <td>
                      <span style={{ color: product.total_stock <= product.minimum_stock ? '#EF4444' : '#F8FAFC' }}>
                        {product.total_stock || 0}
                      </span>
                    </td>
                    <td><span className={`badge badge-${getStatusColor(product.status)}`}>{product.status}</span></td>
                    <td>
                      <div style={{ display: 'flex', gap: '0.25rem' }}>
                        <Link to={`/products/${product.id}/edit`} className="btn btn-ghost btn-sm">
                          <i className="bi bi-pencil" />
                        </Link>
                        <button onClick={() => handleDelete(product.id)} className="btn btn-ghost btn-sm" style={{ color: '#EF4444' }}>
                          <i className="bi bi-trash" />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))
              ) : (
                <tr><td colSpan={8} style={{ textAlign: 'center', padding: '2rem', color: '#64748B' }}>No products found</td></tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
