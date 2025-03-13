<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\RESTful\ResourceController;
use App\Helpers\JwtHelper;
use Config\Services;
use App\Models\ActivityLogModel;


class AuthController extends ResourceController
{
  protected $logModel;

  public function __construct()
  {
    $this->logModel = new ActivityLogModel();
  }

  public function register()
  {
    $rules = [
      'username' => 'required|is_unique[users.username]',
      'password' => 'required|min_length[6]',
      'role'     => 'required',
    ];

    if (!$this->validate($rules)) {
      return $this->failValidationErrors($this->validator->getErrors());
    }

    $data = $this->request->getJSON();

    $userData = [
      'username' => $data->username,
      'password' => password_hash($data->password, PASSWORD_DEFAULT),
      'role' => $data->role,
    ];

    $model = new UserModel();
    $model->insert($userData);

    $this->logModel->logActivity(null, $data->username, 'REGISTER', ['Registrasi Berhasil!']);
    return $this->response->setJSON([
      'status' => 'success',
      'message' => 'Registrasi Berhasil!',
      'data' => $userData
    ]);
  }

  public function login()
  {
    $model = new UserModel();
    $jwtHelper = new JwtHelper();
    $data = $this->request->getJSON();

    $user = $model->where('username', $data->username)->first();

    if (!$user || !password_verify($data->password, $user['password'])) {
      $this->logModel->logActivity($user['id'], $user['username'], 'LOGIN', ['Username atau password salah']);
      return Services::response()
        ->setJSON([
          'status' => 'failUnauthorized',
          'message' => 'Username atau password salah',
        ])
        ->setStatusCode(401);
    }

    // Generate JWT
    $token = $jwtHelper->generateJWT(['id' => $user['id'], 'username' => $user['username'], 'role' => $user['role']]);

    $this->logModel->logActivity($user['id'], $user['username'], 'LOGIN', ['Login Berhasil!']);

    return $this->response->setJSON([
      'status' => 'success',
      'message' => 'Login Berhasil!',
      'token' => $token
    ]);
  }
}
