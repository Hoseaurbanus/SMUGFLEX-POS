import { useState, useCallback } from 'react';
import api from '../services/api';

export function useApi() {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const request = useCallback(async (method, url, data = null, options = {}) => {
    setLoading(true);
    setError(null);

    try {
      const response = await api[method](url, data, options);
      return response.data;
    } catch (err) {
      const errorMessage = err.message || 'An error occurred';
      setError(errorMessage);
      throw err;
    } finally {
      setLoading(false);
    }
  }, []);

  const get = useCallback((url, options) => request('get', url, null, options), [request]);
  const post = useCallback((url, data, options) => request('post', url, data, options), [request]);
  const put = useCallback((url, data, options) => request('put', url, data, options), [request]);
  const del = useCallback((url, options) => request('delete', url, null, options), [request]);

  return { loading, error, request, get, post, put, del };
}
