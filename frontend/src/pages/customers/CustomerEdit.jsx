import { useEffect } from 'react';
import { useNavigate, useParams, Link } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import toast from 'react-hot-toast';
import api from '../../services/api';

export default function CustomerEdit() {
  const { id } = useParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const { register, handleSubmit, reset, formState: { errors } } = useForm();

  const { data, isLoading } = useQuery({
    queryKey: ['customer', id],
    queryFn: () => api.get(`/customers/${id}`).then(res => res.data.data || res.data),
  });

  useEffect(() => {
    if (data) reset(data);
  }, [data, reset]);

  const mutation = useMutation({
    mutationFn: (formData) => api.put(`/customers/${id}`, formData),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['customers'] });
      toast.success('Customer updated');
      navigate('/customers');
    },
    onError: (err) => toast.error(err.response?.data?.message || 'Failed to update customer'),
  });

  if (isLoading) return <div className="text-center py-4"><div className="spinner-border text-primary"></div></div>;

  return (
    <div>
      <div className="page-header d-flex justify-content-between align-items-center mb-3">
        <h4 className="mb-0">Edit Customer</h4>
        <Link to="/customers" className="btn btn-secondary">
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
              {mutation.isPending ? 'Updating...' : 'Update Customer'}
            </button>
          </form>
        </div>
      </div>
    </div>
  );
}
