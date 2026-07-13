import { NavLink, useLocation } from 'react-router-dom';
import { useAuth } from '../../hooks/useAuth';

const menuItems = [
  {
    section: 'Main',
    items: [
      { path: '/', icon: 'bi-grid-1x2', label: 'Dashboard' },
      { path: '/pos', icon: 'bi-cart4', label: 'POS Terminal' },
    ],
  },
  {
    section: 'Catalog',
    items: [
      { path: '/products', icon: 'bi-box-seam', label: 'Products' },
      { path: '/categories', icon: 'bi-tags', label: 'Categories' },
      { path: '/brands', icon: 'bi-bookmark', label: 'Brands' },
      { path: '/units', icon: 'bi-rulers', label: 'Units' },
    ],
  },
  {
    section: 'People',
    items: [
      { path: '/customers', icon: 'bi-people', label: 'Customers' },
      { path: '/suppliers', icon: 'bi-truck', label: 'Suppliers' },
    ],
  },
  {
    section: 'Transactions',
    items: [
      { path: '/sales', icon: 'bi-receipt', label: 'Sales' },
      { path: '/purchases', icon: 'bi-bag-check', label: 'Purchases' },
      { path: '/returns', icon: 'bi-arrow-return-left', label: 'Returns' },
      { path: '/expenses', icon: 'bi-wallet2', label: 'Expenses' },
    ],
  },
  {
    section: 'Stock',
    items: [
      { path: '/inventory', icon: 'bi-clipboard-data', label: 'Inventory' },
      { path: '/warehouses', icon: 'bi-building', label: 'Warehouses' },
      { path: '/branches', icon: 'bi-diagram-3', label: 'Branches' },
    ],
  },
  {
    section: 'Management',
    items: [
      { path: '/users', icon: 'bi-person-gear', label: 'Users' },
      { path: '/roles', icon: 'bi-shield-lock', label: 'Roles' },
      { path: '/reports', icon: 'bi-graph-up', label: 'Reports' },
      { path: '/settings', icon: 'bi-gear', label: 'Settings' },
    ],
  },
];

export default function Sidebar({ collapsed, onToggle, open, onClose }) {
  const { user } = useAuth();
  const location = useLocation();

  return (
    <>
      <div
        className={`sidebar-overlay ${open ? 'visible' : ''}`}
        onClick={onClose}
      />
      <aside className={`sidebar ${collapsed ? 'collapsed' : ''} ${open ? 'open' : ''}`}>
        <div className="sidebar-logo">
          <div
            style={{
              width: 34,
              height: 34,
              borderRadius: 10,
              background: 'rgba(37, 99, 235, 0.15)',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              border: '1px solid rgba(37, 99, 235, 0.3)',
              flexShrink: 0,
            }}
          >
            <i className="bi bi-grid-3x3-gap-fill" style={{ fontSize: '1.125rem', color: '#2563EB' }} />
          </div>
          {!collapsed && (
            <div>
              <h1 style={{ fontSize: '0.9375rem', fontWeight: 700, color: '#F8FAFC', margin: 0, lineHeight: 1.2 }}>SmugFlex</h1>
              <p style={{ fontSize: '0.5625rem', color: '#64748B', margin: 0 }}>POS System</p>
            </div>
          )}
        </div>

        <nav className="sidebar-nav">
          {menuItems.map((section) => (
            <div key={section.section} className="sidebar-section">
              {!collapsed && <div className="sidebar-section-title">{section.section}</div>}
              {section.items.map((item) => (
                <NavLink
                  key={item.path}
                  to={item.path}
                  end={item.path === '/'}
                  className={({ isActive }) =>
                    `sidebar-link ${isActive || (item.path !== '/' && location.pathname.startsWith(item.path)) ? 'active' : ''}`
                  }
                  title={collapsed ? item.label : undefined}
                  onClick={onClose}
                >
                  <i className={`bi ${item.icon}`} />
                  {!collapsed && <span>{item.label}</span>}
                </NavLink>
              ))}
            </div>
          ))}
        </nav>

        <div className="sidebar-footer">
          {!collapsed && user && (
            <div style={{ display: 'flex', alignItems: 'center', gap: '0.625rem' }}>
              <div
                style={{
                  width: 32,
                  height: 32,
                  borderRadius: 8,
                  background: 'linear-gradient(135deg, #2563EB, #7C3AED)',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  color: 'white',
                  fontSize: '0.6875rem',
                  fontWeight: 600,
                  flexShrink: 0,
                }}
              >
                {user.first_name?.[0]}{user.last_name?.[0]}
              </div>
              <div style={{ minWidth: 0 }}>
                <p
                  style={{
                    fontSize: '0.75rem',
                    fontWeight: 600,
                    color: '#F8FAFC',
                    margin: 0,
                    overflow: 'hidden',
                    textOverflow: 'ellipsis',
                    whiteSpace: 'nowrap',
                  }}
                >
                  {user.first_name} {user.last_name}
                </p>
                <p
                  style={{
                    fontSize: '0.625rem',
                    color: '#64748B',
                    margin: 0,
                    overflow: 'hidden',
                    textOverflow: 'ellipsis',
                    whiteSpace: 'nowrap',
                  }}
                >
                  {user.role?.name || 'User'}
                </p>
              </div>
            </div>
          )}
          {collapsed && user && (
            <div
              style={{
                width: 32,
                height: 32,
                borderRadius: 8,
                background: 'linear-gradient(135deg, #2563EB, #7C3AED)',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                color: 'white',
                fontSize: '0.6875rem',
                fontWeight: 600,
                margin: '0 auto',
              }}
              title={`${user.first_name} ${user.last_name}`}
            >
              {user.first_name?.[0]}{user.last_name?.[0]}
            </div>
          )}
        </div>
      </aside>
    </>
  );
}
