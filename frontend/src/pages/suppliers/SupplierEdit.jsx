import { useEffect } from 'react';
import { useNavigate, useParams, Link } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import toast from 'react-hot-toast';
import api from '../../services/api';

export default function SupplierEdit() {
  const { id } = useParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const { register, handleSubmit, reset, formState: { errors } } = useForm();

  const { data, isLoading } = useQuery({
    queryKey: ['supplier', id],
    queryFn: () => api.get(`/suppliers/${id}`).then(res => res.data.data || res.data),
  });

  useEffect(() => {
    if (data) reset(data);
  }, [data, reset]);

  const mutation = useMutation({
    mutationFn: (formData) => api.put(`/suppliers/${id}`, formData),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['suppliers'] });
      toast.success('Supplier updated');
      navigate('/suppliers');
    },
    onError: (err) => toast.error(err.response?.data?.message || 'Failed to update supplier'),
  });

  if (isLoading) return <div className="text-center py-4"><div className="spinner-border text-primary"></div></div>;

  return (
    <div>
      <div className="page-header d-flex justify-content-between align-items-center mb-3">
        <h4 className="mb-0">Edit Supplier</h4>
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
              <div className="col-md-4 mb-3">
                <label className="form-label">City</label>
                <input className="form-control" {...register('city')} />
              </div>
              <div className="col-md-4 mb-3">
                <label className="form-label">State</label>
                <input className="form-control" {...register('state')} />
              </div>
              <div className="col-md-4 mb-3">
                <label className="form-label">Country</label>
                <input className="form-control" {...register('country')} />
              </div>
              <div className="col-md-4 mb-3">
                <label className="form-label">Tax Number</label>
                <input className="form-control" {...register('tax_number')} />
              </div>
              <div className="col-md-4 mb-3">
                <label className="form-label">Bank Name</label>
                <input className="form-control" {...register('bank_name')} />
              </div>
              <div className="col-md-4 mb-3">
                <label className="form-label">Bank Account Number</label>
                <input className="form-control" {...register('bank_account_number')} />
              </div>
              <div className="col-md-6 mb-3">
                <label className="form-label">Bank Account Name</label>
                <input className="form-control" {...register('bank_account_name')} />
              </div>
              <div className="col-md-6 mb-3">
                <label className="form-label">Outstanding Balance</label>
                <input type="number" step="0.01" className="form-control" {...register('outstanding_balance')} />
              </div>
              <div className="col-12 mb-3">
                <label className="form-label">Notes</label>
                <textarea className="form-control" rows="2" {...register('notes')}></textarea>
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
              {mutation.isPending ? 'Updating...' : 'Update Supplier'}
            </button>
          </form>
        </div>
      </div>
    </div>
  );
}
