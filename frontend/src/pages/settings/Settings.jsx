import { useEffect, useState } from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import toast from 'react-hot-toast';
import api from '../../services/api';

export default function Settings() {
  const [form, setForm] = useState({
    company_name: '', email: '', phone: '', address: '',
    currency: 'USD', tax_rate: 0, receipt_footer: '',
  });

  const { data, isLoading } = useQuery({
    queryKey: ['settings-company'],
    queryFn: () => api.get('/settings/company').then(res => res.data.data || res.data),
  });

  useEffect(() => {
    if (data) {
      setForm({
        company_name: data.company_name || '',
        email: data.email || '',
        phone: data.phone || '',
        address: data.address || '',
        currency: data.currency || 'USD',
        tax_rate: data.tax_rate || 0,
        receipt_footer: data.receipt_footer || '',
      });
    }
  }, [data]);

  const mutation = useMutation({
    mutationFn: (formData) => api.put('/settings/company', formData),
    onSuccess: () => toast.success('Settings saved'),
    onError: (err) => toast.error(err.response?.data?.message || 'Failed'),
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    mutation.mutate({ ...form, tax_rate: parseFloat(form.tax_rate) });
  };

  const update = (field, value) => setForm(prev => ({ ...prev, [field]: value }));

  if (isLoading) return <div className="text-center py-4"><div className="spinner-border text-primary"></div></div>;

  return (
    <div>
      <div className="page-header mb-3">
        <h4 className="mb-0">Settings</h4>
      </div>

      <div className="card">
        <div className="card-body">
          <form onSubmit={handleSubmit}>
            <div className="row">
              <div className="col-md-6 mb-3">
                <label className="form-label">Company Name *</label>
                <input className="form-control" required value={form.company_name} onChange={(e) => update('company_name', e.target.value)} />
              </div>
              <div className="col-md-6 mb-3">
                <label className="form-label">Email</label>
                <input type="email" className="form-control" value={form.email} onChange={(e) => update('email', e.target.value)} />
              </div>
              <div className="col-md-6 mb-3">
                <label className="form-label">Phone</label>
                <input className="form-control" value={form.phone} onChange={(e) => update('phone', e.target.value)} />
              </div>
              <div className="col-md-6 mb-3">
                <label className="form-label">Currency</label>
                <input className="form-control" value={form.currency} onChange={(e) => update('currency', e.target.value)} />
              </div>
              <div className="col-md-6 mb-3">
                <label className="form-label">Tax Rate (%)</label>
                <input type="number" step="0.01" className="form-control" value={form.tax_rate} onChange={(e) => update('tax_rate', e.target.value)} />
              </div>
              <div className="col-12 mb-3">
                <label className="form-label">Address</label>
                <textarea className="form-control" rows="2" value={form.address} onChange={(e) => update('address', e.target.value)}></textarea>
              </div>
              <div className="col-12 mb-3">
                <label className="form-label">Receipt Footer</label>
                <textarea className="form-control" rows="3" value={form.receipt_footer} onChange={(e) => update('receipt_footer', e.target.value)}></textarea>
              </div>
            </div>
            <button type="submit" className="btn btn-primary" disabled={mutation.isPending}>
              {mutation.isPending ? 'Saving...' : 'Save Settings'}
            </button>
          </form>
        </div>
      </div>
    </div>
  );
}
