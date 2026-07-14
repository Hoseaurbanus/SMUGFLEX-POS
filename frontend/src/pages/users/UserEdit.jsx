import { useEffect } from 'react';
import { useNavigate, useParams, Link } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import toast from 'react-hot-toast';
import api from '../../services/api';

export default function UserEdit() {
  const { id } = useParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const { register, handleSubmit, reset, formState: { errors } } = useForm();

  const { data: userData, isLoading } = useQuery({
    queryKey: ['user', id],
    queryFn: () => api.get(`/users/${id}`).then(res => res.data.data || res.data),
  });

  const { data: rolesData } = useQuery({
    queryKey: ['roles-list'],
    queryFn: () => api.get('/roles').then(res => res.data.data || res.data),
  });

  const { data: branchesData } = useQuery({
    queryKey: ['branches-list'],
    queryFn: () => api.get('/branches').then(res => res.data.data || res.data),
  });

  const { data: warehousesData } = useQuery({
    queryKey: ['warehouses-list'],
    queryFn: () => api.get('/warehouses?per_page=100').then(res => res.data.data || res.data || []),
  });

  const roles = Array.isArray(rolesData) ? rolesData : rolesData?.data || [];
  const branches = Array.isArray(branchesData) ? branchesData : branchesData?.data || [];
  const warehouses = Array.isArray(warehousesData) ? warehousesData : [];

  useEffect(() => {
    if (userData) {
      reset({
        first_name: userData.first_name || '',
        last_name: userData.last_name || '',
        email: userData.email || '',
        phone: userData.phone || '',
        role_id: userData.role_id || '',
        branch_id: userData.branch_id || '',
        warehouse_id: userData.warehouse_id || '',
        is_active: userData.is_active ?? 1,
      });
    }
  }, [userData, reset]);

  const mutation = useMutation({
    mutationFn: (formData) => {
      const payload = { ...formData };
      if (!payload.password) delete payload.password;
      return api.put(`/users/${id}`, payload);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['users'] });
      toast.success('User updated');
      navigate('/users');
    },
    onError: (err) => toast.error(err.response?.data?.message || 'Failed to update user'),
  });

  if (isLoading) return <div className="text-center py-4"><div className="spinner-border text-primary"></div></div>;

  return (
    <div>
      <div className="page-header d-flex justify-content-between align-items-center mb-3">
        <h4 className="mb-0">Edit User</h4>
        <Link to="/users" className="btn btn-secondary">
          <i className="bi bi-arrow-left me-1"></i> Back
        </Link>
      </div>

      <div className="card">
        <div className="card-body">
          <form onSubmit={handleSubmit((data) => mutation.mutate(data))}>
            <div className="row">
              <div className="col-md-6 mb-3">
                <label className="form-label">First Name *</label>
                <input className={`form-control ${errors.first_name ? 'is-invalid' : ''}`} {...register('first_name', { required: true })} />
                {errors.first_name && <div className="invalid-feedback">Required</div>}
              </div>
              <div className="col-md-6 mb-3">
                <label className="form-label">Last Name *</label>
                <input className={`form-control ${errors.last_name ? 'is-invalid' : ''}`} {...register('last_name', { required: true })} />
                {errors.last_name && <div className="invalid-feedback">Required</div>}
              </div>
              <div className="col-md-6 mb-3">
                <label className="form-label">Email *</label>
                <input type="email" className={`form-control ${errors.email ? 'is-invalid' : ''}`} {...register('email', { required: true })} />
                {errors.email && <div className="invalid-feedback">Required</div>}
              </div>
              <div className="col-md-6 mb-3">
                <label className="form-label">Password</label>
                <input type="password" className="form-control" {...register('password')} placeholder="Leave blank to keep current" />
                <small className="text-muted">Leave blank to keep current password</small>
              </div>
              <div className="col-md-6 mb-3">
                <label className="form-label">Phone</label>
                <input className="form-control" {...register('phone')} />
              </div>
              <div className="col-md-6 mb-3">
                <label className="form-label">Role *</label>
                <select className={`form-select ${errors.role_id ? 'is-invalid' : ''}`} {...register('role_id', { required: true })}>
                  <option value="">Select Role</option>
                  {roles.map((r) => <option key={r.id} value={r.id}>{r.name}</option>)}
                </select>
                {errors.role_id && <div className="invalid-feedback">Required</div>}
              </div>
              <div className="col-md-6 mb-3">
                <label className="form-label">Branch</label>
                <select className="form-select" {...register('branch_id')}>
                  <option value="">Select Branch</option>
                  {branches.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}
                </select>
              </div>
              <div className="col-md-6 mb-3">
                <label className="form-label">Warehouse</label>
                <select className="form-select" {...register('warehouse_id')}>
                  <option value="">Select Warehouse</option>
                  {warehouses.map((w) => <option key={w.id} value={w.id}>{w.name}</option>)}
                </select>
              </div>
              <div className="col-md-6 mb-3">
                <label className="form-label">Status</label>
                <select className="form-select" {...register('is_active')}>
                  <option value="1">Active</option>
                  <option value="0">Inactive</option>
                </select>
              </div>
            </div>
            <button type="submit" className="btn btn-primary" disabled={mutation.isPending}>
              {mutation.isPending ? 'Updating...' : 'Update User'}
            </button>
          </form>
        </div>
      </div>
    </div>
  );
}
