import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../services/api';

export default function Inventory() {
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [warehouseId, setWarehouseId] = useState('');

  const { data: warehousesData } = useQuery({
    queryKey: ['warehouses-list'],
    queryFn: () => api.get('/warehouses').then(res => res.data.data || res.data),
  });

  const { data, isLoading } = useQuery({
    queryKey: ['inventory', { search, page, warehouseId }],
    queryFn: () => {
      const params = new URLSearchParams({ search, page, per_page: 15 });
      if (warehouseId) params.append('warehouse_id', warehouseId);
      return api.get(`/inventory?${params}`).then(res => res.data);
    },
  });

  const inventory = data?.data || [];
  const pagination = data?.pagination;
  const warehouses = Array.isArray(warehousesData) ? warehousesData : warehousesData?.data || [];

  return (
    <div>
      <div className="page-header mb-3">
        <h4 className="mb-0">Inventory</h4>
      </div>

      <div className="card">
        <div className="card-body">
          <div className="row g-2 mb-3">
            <div className="col-md-4">
              <input
                type="text"
                className="form-control"
                placeholder="Search by product or SKU..."
                value={search}
                onChange={(e) => { setSearch(e.target.value); setPage(1); }}
              />
            </div>
            <div className="col-md-3">
              <select className="form-select" value={warehouseId} onChange={(e) => { setWarehouseId(e.target.value); setPage(1); }}>
                <option value="">All Warehouses</option>
                {warehouses.map((w) => <option key={w.id} value={w.id}>{w.name}</option>)}
              </select>
            </div>
          </div>

          {isLoading ? (
            <div className="text-center py-4"><div className="spinner-border text-primary"></div></div>
          ) : inventory.length === 0 ? (
            <div className="empty-state text-center py-5">
              <i className="bi bi-box-seam fs-1 d-block mb-2"></i>
              <p>No inventory records found</p>
            </div>
          ) : (
            <div className="table-responsive">
              <table className="table table-dark table-hover">
                <thead>
                  <tr>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Warehouse</th>
                    <th>Quantity</th>
                    <th>Category</th>
                  </tr>
                </thead>
                <tbody>
                  {inventory.map((item) => (
                    <tr key={item.id} className={item.quantity <= (item.low_stock_threshold || 5) ? 'table-danger' : ''}>
                      <td>{item.product?.name || item.product_name}</td>
                      <td>{item.product?.sku || item.sku}</td>
                      <td>{item.warehouse?.name || '—'}</td>
                      <td>
                        {item.quantity}
                        {item.quantity <= (item.low_stock_threshold || 5) && (
                          <span className="badge bg-danger ms-2">Low</span>
                        )}
                      </td>
                      <td>{item.product?.category?.name || item.category}</td>
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
