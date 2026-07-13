import { useNavigate, Link } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { useMutation } from '@tanstack/react-query';
import toast from 'react-hot-toast';
import api from '../../services/api';

export default function SupplierCreate() {
  const navigate = useNavigate();
  const { register, handleSubmit, formState: { errors } } = useForm();

  const mutation = useMutation({
    mutationFn: (data) => api.post('/suppliers', data),
    onSuccess: () => { toast.success('Supplier created'); navigate('/suppliers'); },
    onError: (err) => toast.error(err.response?.data?.message || 'Failed'),
  });

  return (
    <div>
      <div className="page-header d-flex justify-content-between align-items-center mb-3">
        <h4 className="mb-0">Add Supplier</h4>
        <Link to="/suppliers" className="btn btn-secondary">
          <i className="bi bi-arrow-left me-1"></i> Back
        </Link>
      </div>

      <div className="card">
        <div className="card-body">
          <form onSubmit={handleSubmit((data) => mutation.mutate(data))}>
            <div className="row">
              <div className="col-md-6 mb-3">
                <label className="form-label">Name *</label>
                <input className={`form-control ${errors.name ? 'is-invalid' : ''}`} {...register('name', { required: true })} />
                {errors.name && <div className="invalid-feedback">Required</div>}
              </div>
              <div className="col-md-6 mb-3">
                <label className="form-label">Contact Person</label>
                <input className="form-control" {...register('contact_person')} />
              </div>
              <div className="col-md-6 mb-3">
                <label className="form-label">Email</label>
                <input type="email" className="form-control" {...register('email')} />
              </div>
              <div className="col-md-6 mb-3">
                <label className="form-label">Phone *</label>
                <input className={`form-control ${errors.phone ? 'is-invalid' : ''}`} {...register('phone', { required: true })} />
                {errors.phone && <div className="invalid-feedback">Required</div>}
              </div>
              <div className="col-12 mb-3">
                <label className="form-label">Address</label>
                <textarea className="form-control" rows="2" {...register('address')}></textarea>
              </div>
              <div className="col-md-6 mb-3">
                <label className="form-label">City</label>
                <input className="form-control" {...register('city')} />
              </div>
              <div className="col-md-6 mb-3">
                <label className="form-label">State</label>
                <input className="form-control" {...register('state')} />
              </div>
            </div>
            <button type="submit" className="btn btn-primary" disabled={mutation.isPending}>
              {mutation.isPending ? 'Saving...' : 'Save Supplier'}
            </button>
          </form>
        </div>
      </div>
    </div>
  );
}
