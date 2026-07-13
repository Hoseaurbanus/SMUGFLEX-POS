import { useQuery } from '@tanstack/react-query';
import api from '../../services/api';
import { formatCurrency } from '../../utils/formatters';

export default function Dashboard() {
  const { data: stats, isLoading } = useQuery({
    queryKey: ['dashboard'],
    queryFn: async () => {
      const res = await api.get('/dashboard');
      return res.data.data;
    },
  });

  if (isLoading) {
    return (
      <div>
        <div className="page-header">
          <h2>Dashboard</h2>
          <span style={{ color: '#94A3B8', fontSize: '0.8125rem' }}>
            {new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}
          </span>
        </div>
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: '1rem', marginBottom: '1.5rem' }}>
          {[1, 2, 3, 4].map((i) => (
            <div key={i} className="stat-card" style={{ animation: 'pulse 1.5s infinite' }}>
              <div style={{ height: 48, background: '#1E293B', borderRadius: 8, marginBottom: 12 }} />
              <div style={{ height: 24, background: '#1E293B', borderRadius: 6, width: '60%', marginBottom: 8 }} />
              <div style={{ height: 16, background: '#1E293B', borderRadius: 6, width: '40%' }} />
            </div>
          ))}
        </div>
      </div>
    );
  }

  const statCards = [
    {
      icon: 'bi-currency-dollar',
      iconBg: 'rgba(37, 99, 235, 0.15)',
      iconColor: '#3B82F6',
      label: "Today's Sales",
      value: formatCurrency(stats?.today_sales || 0),
      change: '+12.5%',
      positive: true,
    },
    {
      icon: 'bi-graph-up-arrow',
      iconBg: 'rgba(34, 197, 94, 0.15)',
      iconColor: '#22C55E',
      label: "Today's Profit",
      value: formatCurrency(stats?.today_profit || 0),
      change: '+8.2%',
      positive: true,
    },
    {
      icon: 'bi-wallet2',
      iconBg: 'rgba(239, 68, 68, 0.15)',
      iconColor: '#EF4444',
      label: 'Expenses',
      value: formatCurrency(stats?.today_expenses || 0),
      change: '-3.1%',
      positive: false,
    },
    {
      icon: 'bi-people',
      iconBg: 'rgba(139, 92, 246, 0.15)',
      iconColor: '#8B5CF6',
      label: 'Total Customers',
      value: stats?.total_customers || 0,
      change: '+5',
      positive: true,
    },
  ];

  const secondaryCards = [
    {
      icon: 'bi-box-seam',
      iconBg: 'rgba(56, 189, 248, 0.15)',
      iconColor: '#38BDF8',
      label: 'Total Products',
      value: stats?.total_products || 0,
    },
    {
      icon: 'bi-exclamation-triangle',
      iconBg: 'rgba(245, 158, 11, 0.15)',
      iconColor: '#F59E0B',
      label: 'Low Stock Items',
      value: stats?.low_stock_count || 0,
    },
    {
      icon: 'bi-truck',
      iconBg: 'rgba(34, 197, 94, 0.15)',
      iconColor: '#22C55E',
      label: 'Suppliers',
      value: stats?.total_suppliers || 0,
    },
    {
      icon: 'bi-calendar-month',
      iconBg: 'rgba(139, 92, 246, 0.15)',
      iconColor: '#8B5CF6',
      label: 'Monthly Sales',
      value: formatCurrency(stats?.monthly_sales || 0),
    },
  ];

  return (
    <div className="animate-fade-in">
      <div className="page-header">
        <h2>Dashboard</h2>
        <span style={{ color: '#94A3B8', fontSize: '0.8125rem' }}>
          {new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}
        </span>
      </div>

      {/* Primary Stats */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: '1rem', marginBottom: '1.5rem' }}>
        {statCards.map((card, index) => (
          <div key={index} className="stat-card">
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '1rem' }}>
              <div className="stat-icon" style={{ background: card.iconBg }}>
                <i className={`bi ${card.icon}`} style={{ color: card.iconColor }} />
              </div>
              <span className={`stat-change ${card.positive ? 'positive' : 'negative'}`}>
                {card.change}
              </span>
            </div>
            <div className="stat-value">{card.value}</div>
            <div className="stat-label">{card.label}</div>
          </div>
        ))}
      </div>

      {/* Secondary Stats */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: '1rem', marginBottom: '1.5rem' }}>
        {secondaryCards.map((card, index) => (
          <div key={index} className="stat-card">
            <div style={{ display: 'flex', alignItems: 'center', gap: '0.875rem' }}>
              <div className="stat-icon" style={{ background: card.iconBg }}>
                <i className={`bi ${card.icon}`} style={{ color: card.iconColor }} />
              </div>
              <div>
                <div className="stat-value" style={{ fontSize: '1.25rem' }}>{card.value}</div>
                <div className="stat-label">{card.label}</div>
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* Recent Sales & Top Products */}
      <div style={{ display: 'grid', gridTemplateColumns: '2fr 1fr', gap: '1.5rem' }}>
        <div className="card">
          <div className="card-header" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <h6 style={{ margin: 0, fontWeight: 600 }}>Recent Sales</h6>
            <a href="/sales" style={{ color: '#3B82F6', fontSize: '0.8125rem', textDecoration: 'none' }}>View All</a>
          </div>
          <div className="card-body" style={{ padding: 0 }}>
            <table className="table">
              <thead>
                <tr>
                  <th>Invoice</th>
                  <th>Customer</th>
                  <th>Amount</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                {(stats?.recent_sales || []).length > 0 ? (
                  stats.recent_sales.map((sale) => (
                    <tr key={sale.id}>
                      <td style={{ fontWeight: 500, color: '#F8FAFC' }}>{sale.invoice_number}</td>
                      <td>{sale.customer_first_name ? `${sale.customer_first_name} ${sale.customer_last_name}` : 'Walk-in'}</td>
                      <td style={{ fontWeight: 600, color: '#22C55E' }}>{formatCurrency(sale.total)}</td>
                      <td>
                        <span className={`badge badge-${sale.payment_status === 'paid' ? 'success' : sale.payment_status === 'partial' ? 'warning' : 'danger'}`}>
                          {sale.payment_status}
                        </span>
                      </td>
                    </tr>
                  ))
                ) : (
                  <tr>
                    <td colSpan={4} style={{ textAlign: 'center', padding: '2rem', color: '#64748B' }}>
                      No recent sales
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </div>

        <div className="card">
          <div className="card-header">
            <h6 style={{ margin: 0, fontWeight: 600 }}>Top Products</h6>
          </div>
          <div className="card-body">
            {(stats?.top_products || []).length > 0 ? (
              stats.top_products.map((product, index) => (
                <div
                  key={index}
                  style={{
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'space-between',
                    padding: '0.625rem 0',
                    borderBottom: index < stats.top_products.length - 1 ? '1px solid #334155' : 'none',
                  }}
                >
                  <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem' }}>
                    <div
                      style={{
                        width: 32,
                        height: 32,
                        borderRadius: 8,
                        background: 'rgba(37, 99, 235, 0.1)',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        fontSize: '0.75rem',
                        fontWeight: 700,
                        color: '#3B82F6',
                      }}
                    >
                      #{index + 1}
                    </div>
                    <div>
                      <p style={{ fontSize: '0.8125rem', fontWeight: 500, color: '#F8FAFC', margin: 0 }}>{product.name}</p>
                      <p style={{ fontSize: '0.6875rem', color: '#64748B', margin: 0 }}>{product.quantity_sold} sold</p>
                    </div>
                  </div>
                  <span style={{ fontSize: '0.8125rem', fontWeight: 600, color: '#22C55E' }}>
                    {formatCurrency(product.revenue)}
                  </span>
                </div>
              ))
            ) : (
              <div className="empty-state">
                <i className="bi bi-box-seam" />
                <p>No product data</p>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
