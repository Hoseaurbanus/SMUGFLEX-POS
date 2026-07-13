import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../../hooks/useAuth';
import toast from 'react-hot-toast';

export default function Login() {
  const [email, setEmail] = useState('admin@smugflex.com');
  const [password, setPassword] = useState('password');
  const [loading, setLoading] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const { login } = useAuth();
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (!email || !password) {
      toast.error('Please fill in all fields');
      return;
    }

    setLoading(true);

    try {
      const result = await login(email, password);
      if (result.success) {
        toast.success('Welcome back!');
        navigate('/');
      } else {
        toast.error(result.message || 'Login failed');
      }
    } catch (error) {
      toast.error(error.message || 'Login failed');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div style={styles.container}>
      <div style={styles.bgPattern} />
      <div style={styles.loginWrapper}>
        <div style={styles.loginCard}>
          <div style={styles.logoSection}>
            <div style={styles.logoIcon}>
              <i className="bi bi-grid-3x3-gap-fill" style={{ fontSize: '2rem', color: '#2563EB' }} />
            </div>
            <h1 style={styles.brandName}>SmugFlex</h1>
            <p style={styles.brandTag}>Enterprise POS System</p>
          </div>

          <form onSubmit={handleSubmit}>
            <div style={styles.formGroup}>
              <label style={styles.label}>Email Address</label>
              <div style={styles.inputWrapper}>
                <i className="bi bi-envelope" style={styles.inputIcon} />
                <input
                  type="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  placeholder="Enter your email"
                  style={styles.input}
                  autoComplete="email"
                />
              </div>
            </div>

            <div style={styles.formGroup}>
              <label style={styles.label}>Password</label>
              <div style={styles.inputWrapper}>
                <i className="bi bi-lock" style={styles.inputIcon} />
                <input
                  type={showPassword ? 'text' : 'password'}
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  placeholder="Enter your password"
                  style={styles.input}
                  autoComplete="current-password"
                />
                <button
                  type="button"
                  onClick={() => setShowPassword(!showPassword)}
                  style={styles.eyeBtn}
                  tabIndex={-1}
                >
                  <i className={`bi ${showPassword ? 'bi-eye-slash' : 'bi-eye'}`} />
                </button>
              </div>
            </div>

            <div style={styles.formRow}>
              <label style={styles.checkboxLabel}>
                <input type="checkbox" defaultChecked style={styles.checkbox} />
                <span>Remember me</span>
              </label>
              <a href="#" style={styles.forgotLink}>Forgot password?</a>
            </div>

            <button
              type="submit"
              disabled={loading}
              style={{
                ...styles.submitBtn,
                opacity: loading ? 0.7 : 1,
              }}
            >
              {loading ? (
                <>
                  <span style={styles.spinner} />
                  Signing in...
                </>
              ) : (
                <>
                  <i className="bi bi-box-arrow-in-right" />
                  Sign In
                </>
              )}
            </button>
          </form>

          <div style={styles.footer}>
            <p style={styles.footerText}>
              © 2026 SmugFlex Ventures. All rights reserved.
            </p>
          </div>
        </div>

        <div style={styles.sideInfo}>
          <div style={styles.sideContent}>
            <h2 style={styles.sideTitle}>Welcome to SmugFlex POS</h2>
            <p style={styles.sideDesc}>
              The complete enterprise point of sale solution designed for modern businesses.
              Manage sales, inventory, customers, and more — all in one place.
            </p>
            <div style={styles.features}>
              {[
                { icon: 'bi-speedometer2', text: 'Real-time Dashboard' },
                { icon: 'bi-cart-check', text: 'Smart POS Terminal' },
                { icon: 'bi-box-seam', text: 'Inventory Management' },
                { icon: 'bi-graph-up-arrow', text: 'Advanced Reports' },
                { icon: 'bi-people', text: 'Customer Management' },
                { icon: 'bi-shield-check', text: 'Enterprise Security' },
              ].map((feature, index) => (
                <div key={index} style={styles.featureItem}>
                  <i className={`bi ${feature.icon}`} style={styles.featureIcon} />
                  <span>{feature.text}</span>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

const styles = {
  container: {
    minHeight: '100vh',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    background: 'linear-gradient(135deg, #0F172A 0%, #1E293B 50%, #0B1120 100%)',
    position: 'relative',
    overflow: 'hidden',
  },
  bgPattern: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    backgroundImage: `radial-gradient(circle at 25% 25%, rgba(37, 99, 235, 0.08) 0%, transparent 50%),
                       radial-gradient(circle at 75% 75%, rgba(56, 189, 248, 0.06) 0%, transparent 50%)`,
    pointerEvents: 'none',
  },
  loginWrapper: {
    display: 'flex',
    width: '900px',
    maxWidth: '95vw',
    minHeight: '560px',
    borderRadius: '20px',
    overflow: 'hidden',
    boxShadow: '0 25px 50px -12px rgba(0, 0, 0, 0.5)',
    border: '1px solid rgba(255,255,255,0.1)',
    position: 'relative',
    zIndex: 1,
  },
  loginCard: {
    flex: '0 0 420px',
    padding: '3rem 2.5rem',
    background: 'rgba(22, 33, 62, 0.9)',
    backdropFilter: 'blur(20px)',
  },
  logoSection: {
    textAlign: 'center',
    marginBottom: '2rem',
  },
  logoIcon: {
    width: '64px',
    height: '64px',
    borderRadius: '16px',
    background: 'rgba(37, 99, 235, 0.15)',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    margin: '0 auto 1rem',
    border: '1px solid rgba(37, 99, 235, 0.3)',
  },
  brandName: {
    fontSize: '1.75rem',
    fontWeight: '800',
    color: '#F8FAFC',
    margin: 0,
    letterSpacing: '-0.025em',
  },
  brandTag: {
    fontSize: '0.875rem',
    color: '#94A3B8',
    marginTop: '0.25rem',
  },
  formGroup: {
    marginBottom: '1.25rem',
  },
  label: {
    display: 'block',
    fontSize: '0.8125rem',
    fontWeight: '600',
    color: '#CBD5E1',
    marginBottom: '0.5rem',
  },
  inputWrapper: {
    position: 'relative',
    display: 'flex',
    alignItems: 'center',
  },
  inputIcon: {
    position: 'absolute',
    left: '14px',
    color: '#64748B',
    fontSize: '0.9rem',
    zIndex: 1,
  },
  input: {
    width: '100%',
    padding: '0.75rem 1rem 0.75rem 2.75rem',
    background: 'rgba(15, 23, 42, 0.6)',
    border: '1px solid #334155',
    borderRadius: '10px',
    color: '#F8FAFC',
    fontSize: '0.875rem',
    outline: 'none',
    transition: 'all 0.2s ease',
  },
  eyeBtn: {
    position: 'absolute',
    right: '12px',
    background: 'none',
    border: 'none',
    color: '#64748B',
    cursor: 'pointer',
    padding: '4px',
    fontSize: '0.9rem',
  },
  formRow: {
    display: 'flex',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: '1.5rem',
  },
  checkboxLabel: {
    display: 'flex',
    alignItems: 'center',
    gap: '0.5rem',
    color: '#94A3B8',
    fontSize: '0.8125rem',
    cursor: 'pointer',
  },
  checkbox: {
    accentColor: '#2563EB',
  },
  forgotLink: {
    color: '#3B82F6',
    fontSize: '0.8125rem',
    textDecoration: 'none',
    fontWeight: '500',
  },
  submitBtn: {
    width: '100%',
    padding: '0.8rem',
    background: 'linear-gradient(135deg, #2563EB, #1D4ED8)',
    border: 'none',
    borderRadius: '10px',
    color: 'white',
    fontSize: '0.9375rem',
    fontWeight: '600',
    cursor: 'pointer',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    gap: '0.5rem',
    transition: 'all 0.2s ease',
  },
  spinner: {
    width: '18px',
    height: '18px',
    border: '2px solid rgba(255,255,255,0.3)',
    borderTopColor: 'white',
    borderRadius: '50%',
    animation: 'spin 0.8s linear infinite',
  },
  footer: {
    marginTop: '2rem',
    textAlign: 'center',
  },
  footerText: {
    fontSize: '0.75rem',
    color: '#475569',
  },
  sideInfo: {
    flex: 1,
    padding: '3rem',
    background: 'linear-gradient(135deg, rgba(37, 99, 235, 0.15) 0%, rgba(15, 23, 42, 0.9) 100%)',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    borderLeft: '1px solid rgba(255,255,255,0.08)',
  },
  sideContent: {
    maxWidth: '380px',
  },
  sideTitle: {
    fontSize: '1.75rem',
    fontWeight: '700',
    color: '#F8FAFC',
    marginBottom: '1rem',
    lineHeight: 1.3,
  },
  sideDesc: {
    fontSize: '0.9375rem',
    color: '#94A3B8',
    lineHeight: 1.7,
    marginBottom: '2rem',
  },
  features: {
    display: 'flex',
    flexDirection: 'column',
    gap: '0.875rem',
  },
  featureItem: {
    display: 'flex',
    alignItems: 'center',
    gap: '0.75rem',
    color: '#CBD5E1',
    fontSize: '0.875rem',
  },
  featureIcon: {
    width: '36px',
    height: '36px',
    borderRadius: '8px',
    background: 'rgba(37, 99, 235, 0.15)',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    color: '#3B82F6',
    fontSize: '1rem',
    flexShrink: 0,
  },
};
