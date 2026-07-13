<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\RoleModel;
use App\Services\AuthService;

class Auth extends BaseController
{
    protected $authService;
    protected $userModel;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->userModel = new UserModel();
    }

    public function login()
    {
        $rules = [
            'email'    => 'required|valid_email|max_length[100]',
            'password' => 'required|min_length[6]',
        ];

        if (!$this->validate($rules)) {
            return api_error('Validation failed', 422, $this->validator->getErrors());
        }

        $email = $this->request->getVar('email');
        $password = $this->request->getVar('password');

        $result = $this->authService->login($email, $password);

        if (!$result['success']) {
            return api_error($result['message'], 401);
        }

        return api_success($result['data'], 'Login successful');
    }

    public function register()
    {
        $rules = [
            'first_name' => 'required|max_length[50]',
            'last_name'  => 'required|max_length[50]',
            'email'      => 'required|valid_email|max_length[100]|is_unique[users.email]',
            'password'   => 'required|min_length[8]',
            'phone'      => 'permit_empty|max_length[20]',
            'role_id'    => 'required|integer',
            'branch_id'  => 'permit_empty|integer',
        ];

        if (!$this->validate($rules)) {
            return api_error('Validation failed', 422, $this->validator->getErrors());
        }

        $data = [
            'first_name' => $this->request->getVar('first_name'),
            'last_name'  => $this->request->getVar('last_name'),
            'email'      => $this->request->getVar('email'),
            'password'   => password_hash($this->request->getVar('password'), PASSWORD_BCRYPT),
            'phone'      => $this->request->getVar('phone'),
            'role_id'    => $this->request->getVar('role_id'),
            'branch_id'  => $this->request->getVar('branch_id') ?: null,
            'is_active'  => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $userId = $this->userModel->insert($data);

        if (!$userId) {
            return api_error('Failed to create user', 500);
        }

        $user = $this->userModel->find($userId);
        unset($user['password']);

        return api_success($user, 'User registered successfully', 201);
    }

    public function logout()
    {
        return api_success(null, 'Logged out successfully');
    }

    public function refresh()
    {
        $userId = get_user_id_from_token();

        if (!$userId) {
            return api_error('Unauthorized', 401);
        }

        $user = $this->userModel->find($userId);

        if (!$user || !$user['is_active']) {
            return api_error('User not found or inactive', 401);
        }

        $token = generate_jwt([
            'user_id' => $user['id'],
            'email'   => $user['email'],
            'role_id' => $user['role_id'],
        ]);

        return api_success([
            'token'      => $token,
            'expires_in' => (int) getenv('JWT_EXPIRATION'),
        ], 'Token refreshed');
    }

    public function me()
    {
        $userId = get_user_id_from_token();

        if (!$userId) {
            return api_error('Unauthorized', 401);
        }

        $user = $this->userModel->find($userId);

        if (!$user) {
            return api_error('User not found', 404);
        }

        unset($user['password']);
        unset($user['remember_token']);

        $roleModel = new RoleModel();
        $role = $roleModel->find($user['role_id']);

        $user['role'] = $role;
        $user['permissions'] = $this->authService->getUserPermissions($user['id']);

        return api_success($user);
    }

    public function changePassword()
    {
        $userId = get_user_id_from_token();

        if (!$userId) {
            return api_error('Unauthorized', 401);
        }

        $rules = [
            'current_password' => 'required',
            'new_password'     => 'required|min_length[8]',
            'confirm_password' => 'required|matches[new_password]',
        ];

        if (!$this->validate($rules)) {
            return api_error('Validation failed', 422, $this->validator->getErrors());
        }

        $result = $this->authService->changePassword(
            $userId,
            $this->request->getVar('current_password'),
            $this->request->getVar('new_password')
        );

        if (!$result['success']) {
            return api_error($result['message'], 400);
        }

        return api_success(null, 'Password changed successfully');
    }

    public function forgotPassword()
    {
        $rules = [
            'email' => 'required|valid_email',
        ];

        if (!$this->validate($rules)) {
            return api_error('Validation failed', 422, $this->validator->getErrors());
        }

        return api_success(null, 'If the email exists, a password reset link has been sent');
    }
}
