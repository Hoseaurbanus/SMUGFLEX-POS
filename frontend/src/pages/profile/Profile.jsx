import { useState } from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import toast from 'react-hot-toast';
import api from '../../services/api';

export default function Profile() {
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');

  const { data: user, isLoading } = useQuery({
    queryKey: ['profile'],
    queryFn: () => api.get('/auth/me').then(res => res.data.data || res.data),
  });

  const passwordMutation = useMutation({
    mutationFn: (data) => api.post('/auth/change-password', data),
    onSuccess: () => {
      toast.success('Password changed');
      setCurrentPassword('');
      setNewPassword('');
      setConfirmPassword('');
    },
    onError: (err) => toast.error(err.response?.data?.message || 'Failed to change password'),
  });

  const handlePasswordSubmit = (e) => {
    e.preventDefault();
    if (newPassword !== confirmPassword) { toast.error('Passwords do not match'); return; }
    if (newPassword.length < 6) { toast.error('Password must be at least 6 characters'); return; }
    passwordMutation.mutate({ current_password: currentPassword, new_password: newPassword });
  };

  if (isLoading) return <div className="text-center py-4"><div className="spinner-border text-primary"></div></div>;
  if (!user) return <div className="alert alert-danger">Could not load profile</div>;

  return (
    <div>
      <div className="page-header mb-3">
        <h4 className="mb-0">My Profile</h4>
      </div>

      <div className="row">
        <div className="col-md-6 mb-3">
          <div className="card">
            <div className="card-body">
              <h6 className="card-title">Profile Information</h6>
              <table className="table table-dark table-sm mb-0">
                <tbody>
                  <tr><td>Name</td><td>{user.first_name} {user.last_name}</td></tr>
                  <tr><td>Email</td><td>{user.email}</td></tr>
                  <tr><td>Phone</td><td>{user.phone || '—'}</td></tr>
                  <tr><td>Role</td><td>{user.role?.name || user.role}</td></tr>
                  <tr><td>Branch</td><td>{user.branch?.name || '—'}</td></tr>
                  <tr><td>Status</td><td><span className={`badge bg-${user.status === 'active' ? 'success' : 'secondary'}`}>{user.status}</span></td></tr>
                  <tr><td>Member Since</td><td>{new Date(user.created_at).toLocaleDateString()}</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div className="col-md-6 mb-3">
          <div className="card">
            <div className="card-body">
              <h6 className="card-title">Change Password</h6>
              <form onSubmit={handlePasswordSubmit}>
                <div className="mb-3">
                  <label className="form-label">Current Password</label>
                  <input
                    type="password"
                    className="form-control"
                    required
                    value={currentPassword}
                    onChange={(e) => setCurrentPassword(e.target.value)}
                  />
                </div>
                <div className="mb-3">
                  <label className="form-label">New Password</label>
                  <input
                    type="password"
                    className="form-control"
                    required
                    minLength="6"
                    value={newPassword}
                    onChange={(e) => setNewPassword(e.target.value)}
                  />
                </div>
                <div className="mb-3">
                  <label className="form-label">Confirm New Password</label>
                  <input
                    type="password"
                    className="form-control"
                    required
                    value={confirmPassword}
                    onChange={(e) => setConfirmPassword(e.target.value)}
                  />
                </div>
                <button type="submit" className="btn btn-primary" disabled={passwordMutation.isPending}>
                  {passwordMutation.isPending ? 'Updating...' : 'Change Password'}
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
