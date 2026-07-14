import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../services/api';
import { formatCurrency } from '../../utils/formatters';

const tabs = ['daily', 'weekly', 'monthly', 'yearly'];

export default function Reports() {
  const [activeTab, setActiveTab] = useState('daily');
  const [date, setDate] = useState(new Date().toISOString().split('T')[0]);

  const { data, isLoading } = useQuery({
    queryKey: ['reports', activeTab, date],
    queryFn: () => api.get(`/reports/${activeTab}?date=${date}`).then(res => res.data),
  });

  const summary = data?.data || data || {};

  return (
    <div>
      <div className="page-header mb-3">
        <h4 className="mb-0">Reports</h4>
      </div>

      <div className="card mb-3">
        <div className="card-body">
          <ul className="nav nav-tabs">
            {tabs.map((tab) => (
              <li className="nav-item" key={tab}>
                <button
                  className={`nav-link ${activeTab === tab ? 'active' : ''}`}
                  onClick={() => setActiveTab(tab)}
                  style={activeTab === tab ? { background: '#0d6efd', color: '#fff' } : {}}
                >
                  {tab.charAt(0).toUpperCase() + tab.slice(1)}
                </button>
              </li>
            ))}
          </ul>
        </div>
      </div>

      <div className="card mb-3">
        <div className="card-body">
          <div className="row align-items-end g-2">
            <div className="col-md-3">
              <label className="form-label">Select Date</label>
              <input type="date" className="form-control" value={date} onChange={(e) => setDate(e.target.value)} />
            </div>
          </div>
        </div>
      </div>

      {isLoading ? (
        <div className="text-center py-4"><div className="spinner-border text-primary"></div></div>
      ) : (
        <>
          <div className="row mb-3">
            <div className="col-md-3 mb-2">
              <div className="card">
                <div className="card-body text-center">
                  <h6 className="text-muted">Total Sales</h6>
                  <h3 className="text-primary">{formatCurrency(summary.total_sales || 0)}</h3>
                </div>
              </div>
            </div>
            <div className="col-md-3 mb-2">
              <div className="card">
                <div className="card-body text-center">
                  <h6 className="text-muted">Total Expenses</h6>
                  <h3 className="text-danger">{formatCurrency(summary.total_expenses || 0)}</h3>
                </div>
              </div>
            </div>
            <div className="col-md-3 mb-2">
              <div className="card">
                <div className="card-body text-center">
                  <h6 className="text-muted">Net Profit</h6>
                  <h3 className="text-success">{formatCurrency(summary.net_profit || summary.profit || 0)}</h3>
                </div>
              </div>
            </div>
            <div className="col-md-3 mb-2">
              <div className="card">
                <div className="card-body text-center">
                  <h6 className="text-muted">Transactions</h6>
                  <h3>{summary.total_transactions || summary.sales_count || 0}</h3>
                </div>
              </div>
            </div>
          </div>

          {summary.top_products && summary.top_products.length > 0 && (
            <div className="card">
              <div className="card-body">
                <h6 className="card-title">Top Products</h6>
                <div className="table-responsive">
                  <table className="table table-dark table-hover">
                    <thead>
                      <tr>
                        <th>Product</th>
                        <th>Qty Sold</th>
                        <th>Revenue</th>
                      </tr>
                    </thead>
                    <tbody>
                      {summary.top_products.map((p, idx) => (
                        <tr key={idx}>
                          <td>{p.name}</td>
                          <td>{p.sold || p.quantity_sold || 0}</td>
                          <td>{formatCurrency(p.revenue || 0)}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          )}

          {summary.daily_sales && summary.daily_sales.length > 0 && (
            <div className="card">
              <div className="card-body">
                <h6 className="card-title">Sales Breakdown</h6>
                <div className="table-responsive">
                  <table className="table table-dark table-hover">
                    <thead>
                      <tr>
                        <th>Date</th>
                        <th>Sales</th>
                        <th>Transactions</th>
                      </tr>
                    </thead>
                    <tbody>
                      {summary.daily_sales.map((d, idx) => (
                        <tr key={idx}>
                          <td>{d.date}</td>
                          <td>{formatCurrency(d.total || 0)}</td>
                          <td>{d.count}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          )}

          {summary.daily_data && summary.daily_data.length > 0 && (
            <div className="card">
              <div className="card-body">
                <h6 className="card-title">Daily Breakdown</h6>
                <div className="table-responsive">
                  <table className="table table-dark table-hover">
                    <thead>
                      <tr>
                        <th>Date</th>
                        <th>Sales</th>
                        <th>Transactions</th>
                      </tr>
                    </thead>
                    <tbody>
                      {summary.daily_data.map((d, idx) => (
                        <tr key={idx}>
                          <td>{d.date}</td>
                          <td>{formatCurrency(d.total || 0)}</td>
                          <td>{d.count}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          )}

          {summary.monthly_data && summary.monthly_data.length > 0 && (
            <div className="card">
              <div className="card-body">
                <h6 className="card-title">Monthly Breakdown</h6>
                <div className="table-responsive">
                  <table className="table table-dark table-hover">
                    <thead>
                      <tr>
                        <th>Month</th>
                        <th>Sales</th>
                        <th>Transactions</th>
                      </tr>
                    </thead>
                    <tbody>
                      {summary.monthly_data.map((d, idx) => (
                        <tr key={idx}>
                          <td>{d.month}</td>
                          <td>{formatCurrency(d.total || 0)}</td>
                          <td>{d.count}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          )}
        </>
      )}
    </div>
  );
}
