import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../../hooks/useAuth';
import { useNotifications } from '../../contexts/NotificationContext';
import { useTheme } from '../../contexts/ThemeContext';
import toast from 'react-hot-toast';

export default function TopBar({ onMenuToggle }) {
  const { user, logout } = useAuth();
  const { unreadCount } = useNotifications();
  const { theme, toggleTheme } = useTheme();
  const [showDropdown, setShowDropdown] = useState(false);
  const navigate = useNavigate();

  const handleLogout = async () => {
    await logout();
    toast.success('Logged out successfully');
    navigate('/login');
  };

  return (
    <header className="topbar">
      <div className="topbar-left">
        <button className="btn btn-ghost" onClick={onMenuToggle} style={{ fontSize: '1.25rem' }}>
          <i className="bi bi-list" />
        </button>
        <div className="search-box" style={{ position: 'relative' }}>
          <i className="bi bi-search" style={{ position: 'absolute', left: 12, top: '50%', transform: 'translateY(-50%)', color: '#64748B', fontSize: '0.85rem' }} />
          <input
            type="text"
            placeholder="Search... (Ctrl+K)"
            style={{
              width: 320,
              padding: '0.5rem 1rem 0.5rem 2.5rem',
              background: 'rgba(15, 23, 42, 0.6)',
              border: '1px solid #334155',
              borderRadius: 8,
              color: '#F8FAFC',
              fontSize: '0.8125rem',
              outline: 'none',
            }}
          />
        </div>
      </div>

      <div className="topbar-right">
        <button
          className="btn btn-ghost"
          onClick={toggleTheme}
          title="Toggle theme"
          style={{ fontSize: '1.125rem' }}
        >
          <i className={`bi ${theme === 'dark' ? 'bi-sun' : 'bi-moon'}`} />
        </button>

        <Link to="/notifications" style={{ position: 'relative' }}>
          <button className="btn btn-ghost" style={{ fontSize: '1.125rem' }}>
            <i className="bi bi-bell" />
          </button>
          {unreadCount > 0 && <span className="notification-badge">{unreadCount}</span>}
        </Link>

        <div style={{ position: 'relative' }}>
          <button
            onClick={() => setShowDropdown(!showDropdown)}
            style={{
              display: 'flex',
              alignItems: 'center',
              gap: '0.5rem',
              padding: '0.375rem 0.75rem',
              background: 'rgba(15, 23, 42, 0.6)',
              border: '1px solid #334155',
              borderRadius: 8,
              color: '#F8FAFC',
              cursor: 'pointer',
              fontSize: '0.8125rem',
            }}
          >
            <div
              style={{
                width: 30,
                height: 30,
                borderRadius: 6,
                background: 'linear-gradient(135deg, #2563EB, #7C3AED)',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                color: 'white',
                fontSize: '0.75rem',
                fontWeight: 600,
              }}
            >
              {user?.first_name?.[0]}{user?.last_name?.[0]}
            </div>
            <span style={{ fontWeight: 500 }}>{user?.first_name}</span>
            <i className="bi bi-chevron-down" style={{ fontSize: '0.625rem', color: '#64748B' }} />
          </button>

          {showDropdown && (
            <>
              <div
                style={{ position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, zIndex: 99 }}
                onClick={() => setShowDropdown(false)}
              />
              <div
                style={{
                  position: 'absolute',
                  top: '100%',
                  right: 0,
                  marginTop: 8,
                  width: 200,
                  background: '#16213E',
                  border: '1px solid #334155',
                  borderRadius: 10,
                  padding: '0.5rem',
                  boxShadow: '0 10px 15px -3px rgba(0,0,0,0.4)',
                  zIndex: 100,
                }}
              >
                <Link
                  to="/profile"
                  onClick={() => setShowDropdown(false)}
                  style={{
                    display: 'flex',
                    alignItems: 'center',
                    gap: '0.5rem',
                    padding: '0.5rem 0.75rem',
                    color: '#CBD5E1',
                    textDecoration: 'none',
                    borderRadius: 6,
                    fontSize: '0.8125rem',
                    transition: 'all 0.15s',
                  }}
                >
                  <i className="bi bi-person" /> Profile
                </Link>
                <button
                  onClick={handleLogout}
                  style={{
                    display: 'flex',
                    alignItems: 'center',
                    gap: '0.5rem',
                    padding: '0.5rem 0.75rem',
                    color: '#EF4444',
                    background: 'none',
                    border: 'none',
                    borderRadius: 6,
                    fontSize: '0.8125rem',
                    cursor: 'pointer',
                    width: '100%',
                    textAlign: 'left',
                  }}
                >
                  <i className="bi bi-box-arrow-right" /> Logout
                </button>
              </div>
            </>
          )}
        </div>
      </div>
    </header>
  );
}
