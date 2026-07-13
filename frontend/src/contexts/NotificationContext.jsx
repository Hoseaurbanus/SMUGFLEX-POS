import { createContext, useContext, useState, useEffect, useCallback } from 'react';
import api from '../services/api';

const NotificationContext = createContext(null);

export function NotificationProvider({ children }) {
  const [notifications, setNotifications] = useState([]);
  const [unreadCount, setUnreadCount] = useState(0);
  const [loading, setLoading] = useState(false);

  const fetchNotifications = useCallback(async () => {
    try {
      setLoading(true);
      const response = await api.get('/notifications');
      if (response.data.success) {
        setNotifications(response.data.data || []);
        setUnreadCount((response.data.data || []).filter((n) => !n.is_read).length);
      }
    } catch (error) {
      // Silent fail
    } finally {
      setLoading(false);
    }
  }, []);

  const markRead = useCallback(async (id) => {
    try {
      await api.put(`/notifications/${id}/read`);
      setNotifications((prev) =>
        prev.map((n) => (n.id === id ? { ...n, is_read: 1 } : n))
      );
      setUnreadCount((prev) => Math.max(0, prev - 1));
    } catch (error) {
      // Silent fail
    }
  }, []);

  const markAllRead = useCallback(async () => {
    try {
      await api.put('/notifications/read-all');
      setNotifications((prev) => prev.map((n) => ({ ...n, is_read: 1 })));
      setUnreadCount(0);
    } catch (error) {
      // Silent fail
    }
  }, []);

  useEffect(() => {
    fetchNotifications();
  }, [fetchNotifications]);

  return (
    <NotificationContext.Provider
      value={{ notifications, unreadCount, loading, fetchNotifications, markRead, markAllRead }}
    >
      {children}
    </NotificationContext.Provider>
  );
}

export function useNotifications() {
  const context = useContext(NotificationContext);
  if (!context) {
    throw new Error('useNotifications must be used within a NotificationProvider');
  }
  return context;
}

export default NotificationContext;
