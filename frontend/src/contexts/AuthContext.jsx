import { createContext, useContext, useState, useEffect, useCallback } from 'react';
import api from '../services/api';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  const isAuthenticated = !!user && !!localStorage.getItem('sf_token');

  const login = useCallback(async (email, password) => {
    const response = await api.post('/auth/login', { email, password });

    if (response.data.success) {
      const { user: userData, token, refresh_token } = response.data.data;
      localStorage.setItem('sf_token', token);
      localStorage.setItem('sf_refresh_token', refresh_token);
      localStorage.setItem('sf_user', JSON.stringify(userData));
      setUser(userData);
      return { success: true };
    }

    return { success: false, message: response.data.message };
  }, []);

  const logout = useCallback(async () => {
    try {
      await api.post('/auth/logout');
    } catch (error) {
      // Ignore logout errors
    } finally {
      localStorage.removeItem('sf_token');
      localStorage.removeItem('sf_refresh_token');
      localStorage.removeItem('sf_user');
      setUser(null);
    }
  }, []);

  const hasPermission = useCallback(
    (permission) => {
      if (!user) return false;
      if (user.role_id === 1) return true;
      return user.permissions?.includes(permission) || false;
    },
    [user]
  );

  const refreshUser = useCallback(async () => {
    try {
      const response = await api.get('/auth/me');
      if (response.data.success) {
        setUser(response.data.data);
        localStorage.setItem('sf_user', JSON.stringify(response.data.data));
      }
    } catch (error) {
      // Token might be expired
    }
  }, []);

  useEffect(() => {
    const initAuth = async () => {
      const token = localStorage.getItem('sf_token');
      const savedUser = localStorage.getItem('sf_user');

      if (token && savedUser) {
        try {
          setUser(JSON.parse(savedUser));
          await refreshUser();
        } catch (error) {
          localStorage.removeItem('sf_token');
          localStorage.removeItem('sf_user');
          setUser(null);
        }
      }

      setLoading(false);
    };

    initAuth();
  }, [refreshUser]);

  return (
    <AuthContext.Provider value={{ user, isAuthenticated, loading, login, logout, hasPermission, refreshUser }}>
      {children}
    </AuthContext.Provider>
  );
}

export default AuthContext;
