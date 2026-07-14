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
    <div className="login-container">
      <div className="login-bg-pattern" />
      <div className="login-wrapper">
        <div className="login-card">
          <div className="login-logo">
            <div className="login-logo-icon">
              <i className="bi bi-grid-3x3-gap-fill" />
            </div>
            <h1 className="login-brand">
              <span style={{ color: '#FF6B6B' }}>S</span>
              <span style={{ color: '#FFA94D' }}>m</span>
              <span style={{ color: '#FFD43B' }}>u</span>
              <span style={{ color: '#69DB7C' }}>g</span>
              <span style={{ color: '#4DABF7' }}>F</span>
              <span style={{ color: '#9775FA' }}>l</span>
              <span style={{ color: '#F783AC' }}>e</span>
              <span style={{ color: '#20C997' }}>x</span>
            </h1>
            <p className="login-tag">Enterprise POS System</p>
          </div>

          <form onSubmit={handleSubmit} style={{ flex: 1, display: 'flex', flexDirection: 'column' }}>
            <div className="login-form-group">
              <label>Email Address</label>
              <div className="login-input-wrap">
                <i className="bi bi-envelope login-field-icon" />
                <input
                  type="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  placeholder="Enter your email"
                  className="login-input"
                  autoComplete="email"
                />
              </div>
            </div>

            <div className="login-form-group">
              <label>Password</label>
              <div className="login-input-wrap">
                <i className="bi bi-lock login-field-icon" />
                <input
                  type={showPassword ? 'text' : 'password'}
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  placeholder="Enter your password"
                  className="login-input"
                  autoComplete="current-password"
                />
                <button
                  type="button"
                  onClick={() => setShowPassword(!showPassword)}
                  className="login-eye-btn"
                  tabIndex={-1}
                >
                  <i className={`bi ${showPassword ? 'bi-eye-slash' : 'bi-eye'}`} />
                </button>
              </div>
            </div>

            <div className="login-form-row">
              <label className="login-checkbox-label">
                <input type="checkbox" defaultChecked />
                <span>Remember me</span>
              </label>
              <a href="#" className="login-forgot">Forgot password?</a>
            </div>

            <button
              type="submit"
              disabled={loading}
              className="login-submit"
            >
              {loading ? (
                <>
                  <span className="login-spinner" />
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

          <div className="login-footer">
            <p>© 2026 SmugFlex Ventures. All rights reserved.</p>
          </div>
        </div>

        <div className="login-side">
          <div className="login-side-content">
            <h2 className="login-side-title">Welcome to SmugFlex POS</h2>
            <p className="login-side-desc">
              The complete enterprise point of sale solution designed for modern businesses.
              Manage sales, inventory, customers, and more — all in one place.
            </p>
            <div className="login-features">
              {[
                { icon: 'bi-speedometer2', text: 'Real-time Dashboard' },
                { icon: 'bi-cart-check', text: 'Smart POS Terminal' },
                { icon: 'bi-box-seam', text: 'Inventory Management' },
                { icon: 'bi-graph-up-arrow', text: 'Advanced Reports' },
                { icon: 'bi-people', text: 'Customer Management' },
                { icon: 'bi-shield-check', text: 'Enterprise Security' },
              ].map((feature, index) => (
                <div key={index} className="login-feature">
                  <div className="login-feature-icon">
                    <i className={`bi ${feature.icon}`} />
                  </div>
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
