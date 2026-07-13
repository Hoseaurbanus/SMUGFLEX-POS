import { useState, useRef, useEffect } from 'react';
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
  const dropdownRef = useRef(null);
  const navigate = useNavigate();

  useEffect(() => {
    const handleClickOutside = (e) => {
      if (dropdownRef.current && !dropdownRef.current.contains(e.target)) {
        setShowDropdown(false);
      }
    };
    if (showDropdown) document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, [showDropdown]);

  const handleLogout = async () => {
    await logout();
    toast.success('Logged out successfully');
    navigate('/login');
  };

  return (
    <header className="topbar">
      <div className="topbar-left">
        <button className="btn btn-ghost" onClick={onMenuToggle} style={{ fontSize: '1.25rem', padding: '0.375rem' }}>
          <i className="bi bi-list" />
        </button>
        <div className="search-box">
          <i className="bi bi-search" />
          <input type="text" placeholder="Search... (Ctrl+K)" />
        </div>
      </div>

      <div className="topbar-right">
        <button
          className="btn btn-ghost"
          onClick={toggleTheme}
          title="Toggle theme"
          style={{ fontSize: '1.125rem', padding: '0.375rem' }}
        >
          <i className={`bi ${theme === 'dark' ? 'bi-sun' : 'bi-moon'}`} />
        </button>

        <Link to="/notifications" style={{ position: 'relative' }}>
          <button className="btn btn-ghost" style={{ fontSize: '1.125rem', padding: '0.375rem' }}>
            <i className="bi bi-bell" />
          </button>
          {unreadCount > 0 && <span className="notification-badge">{unreadCount}</span>}
        </Link>

        <div ref={dropdownRef} style={{ position: 'relative' }}>
          <button
            onClick={() => setShowDropdown(!showDropdown)}
            style={{
              display: 'flex',
              alignItems: 'center',
              gap: '0.5rem',
              padding: '0.25rem 0.5rem',
              background: 'rgba(15, 23, 42, 0.6)',
              border: '1px solid var(--border-color)',
              borderRadius: 'var(--radius-sm)',
              color: 'var(--text-primary)',
              cursor: 'pointer',
              fontSize: '0.8125rem',
            }}
          >
            <div
              style={{
                width: 28,
                height: 28,
                borderRadius: 6,
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
              {user?.first_name?.[0]}{user?.last_name?.[0]}
            </div>
            <span className="user-name" style={{ fontWeight: 500 }}>{user?.first_name}</span>
            <i className="bi bi-chevron-down hide-mobile" style={{ fontSize: '0.5625rem', color: 'var(--text-muted)' }} />
          </button>

          {showDropdown && (
            <div
              style={{
                position: 'absolute',
                top: '100%',
                right: 0,
                marginTop: 6,
                width: 180,
                background: 'var(--bg-card)',
                border: '1px solid var(--border-color)',
                borderRadius: 'var(--radius-sm)',
                padding: '0.25rem',
                boxShadow: 'var(--shadow-lg)',
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
                  padding: '0.5rem 0.625rem',
                  color: 'var(--text-secondary)',
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
                  padding: '0.5rem 0.625rem',
                  color: 'var(--danger)',
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
          )}
        </div>
      </div>
    </header>
  );
}
