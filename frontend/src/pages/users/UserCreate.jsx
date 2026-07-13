import { useNavigate, Link } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { useQuery, useMutation } from '@tanstack/react-query';
import toast from 'react-hot-toast';
import api from '../../services/api';

export default function UserCreate() {
  const navigate = useNavigate();
  const { register, handleSubmit, formState: { errors } } = useForm();

  const { data: rolesData } = useQuery({
    queryKey: ['roles-list'],
    queryFn: () => api.get('/roles').then(res => res.data.data || res.data),
  });

  const { data: branchesData } = useQuery({
    queryKey: ['branches-list'],
    queryFn: () => api.get('/branches').then(res => res.data.data || res.data),
  });

  const mutation = useMutation({
    mutationFn: (data) => api.post('/users', data),
    onSuccess: () => { toast.success('User created'); navigate('/users'); },
    onError: (err) => toast.error(err.response?.data?.message || 'Failed'),
  });

  const roles = Array.isArray(rolesData) ? rolesData : rolesData?.data || [];
  const branches = Array.isArray(branchesData) ? branchesData : branchesData?.data || [];

  return (
    <div>
      <div className="page-header d-flex justify-content-between align-items-center mb-3">
        <h4 className="mb-0">Add User</h4>
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
                <label className="form-label">Password *</label>
                <input type="password" className={`form-control ${errors.password ? 'is-invalid' : ''}`} {...register('password', { required: true })} />
                {errors.password && <div className="invalid-feedback">Required</div>}
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
            </div>
            <button type="submit" className="btn btn-primary" disabled={mutation.isPending}>
              {mutation.isPending ? 'Saving...' : 'Save User'}
            </button>
          </form>
        </div>
      </div>
    </div>
  );
}
