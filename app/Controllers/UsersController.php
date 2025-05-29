<?php

namespace App\Controllers;

use App\Models\ActivityLogModel;
use App\Helpers\JwtHelper;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use Exception;

class UsersController extends ResourceController
{
  protected $modelName = 'App\Models\UserModel';
  protected $format = 'json';

  protected $logModel;

  public function __construct()
  {
    $this->logModel = new ActivityLogModel();
  }

  private function createLog($action, $details = null)
  {
    $jwtHelper = new JwtHelper();
    $logModel  = new ActivityLogModel();
    $request   = service('request');
    $authHeader = $request->getHeaderLine('Authorization');

    if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
      $token   = $matches[1];
      $decoded = $jwtHelper->validateJWT($token);
      if ($decoded) {
        $logModel->logActivity($decoded['id'], $decoded['username'], $action, $details);
      }
    }
  }

  // GET /users
  public function index()
  {
    try {

      $data = array_map(function ($user) {
        unset($user['password']);
        return $user;
      }, $this->model->findAll());

      if (empty($data)) {
        $this->createLog('READ_ALL_USERS', 'Tidak ada data user.');
        return $this->failNotFound('Tidak ada data user.');
      }
      $this->createLog('READ_ALL_USERS', ['SUCCESS']);
      return $this->respond([
        'status' => 'success',
        'data'   => $data
      ]);
    } catch (Exception $e) {
      $this->createLog('READ_ALL_USERS', ['ERROR']);
      return Services::response()
        ->setJSON([
          'status'  => 'error',
          'message' => 'Terjadi kesalahan pada server.',
          'error'   => $e->getMessage()
        ])
        ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  // GET /USERS/{id}
  public function show($id = null)
  {
    $data = $this->model->find($id);
    if (!$data) {
      return $this->failNotFound('Detail transaksi tidak ditemukan');
    }
    return $this->respond([
      'status' => 'success',
      'data'   => [
        'id' => $data['id'],
        'username' => $data['username'],
        'branch_id' => $data['branch_id'],
        'role' => $data['role'],
      ]
    ]);
  }

  // POST /USERS
  public function create()
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

    // $model = new UserModel();
    $this->model->insert($userData);

    $this->logModel->logActivity(null, $data->username, 'REGISTER', ['Registrasi Berhasil!']);
    return $this->response->setJSON([
      'status' => 'success',
      'message' => 'Registrasi Berhasil!'
    ]);
  }

  // POST /users/reset-password
  public function resetPassword()
  {
    try {
      $data = $this->request->getJSON();

      // Validasi input
      if (!isset($data->username) || !isset($data->old_password) || !isset($data->new_password)) {
        return $this->failValidationErrors('username, old_password, dan new_password wajib diisi.');
      }

      $user = $this->model->where('username', $data->username)->first();

      if (!$user) {
        $this->createLog('RESET_PASSWORD', ['ERROR: Username tidak ditemukan']);
        return $this->failNotFound('Username tidak ditemukan.');
      }

      // Verifikasi password lama
      if (!password_verify($data->old_password, $user['password'])) {
        $this->createLog('RESET_PASSWORD', ['ERROR: Password lama tidak cocok']);
        return $this->fail('Password lama salah.', 401);
      }

      // Simpan password baru
      $this->model->update($user['id'], [
        'password' => password_hash($data->new_password, PASSWORD_DEFAULT)
      ]);

      $this->createLog('RESET_PASSWORD', ['SUCCESS: Password diubah', 'username' => $data->username]);
      return $this->respond([
        'status'  => 'success',
        'message' => 'Password berhasil diubah.'
      ]);
    } catch (Exception $e) {
      $this->createLog('RESET_PASSWORD', ['ERROR']);
      return Services::response()
        ->setJSON([
          'status'  => 'error',
          'message' => 'Terjadi kesalahan pada server.',
          'error'   => $e->getMessage()
        ])
        ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
    }
  }


  // DELETE /USERS/{id}
  public function delete($id = null)
  {
    try {
      $request = service('request');
      $authHeader = $request->getHeaderLine('Authorization');

      // Validasi token JWT
      if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        return $this->failUnauthorized('Token tidak ditemukan.');
      }

      $token = $matches[1];
      $jwtHelper = new JwtHelper();
      $decoded = $jwtHelper->validateJWT($token);

      if (!$decoded) {
        return $this->failUnauthorized('Token tidak valid atau kadaluarsa.');
      }

      // Cek role
      if ($decoded['role'] !== 'admin') {
        $this->createLog('DELETE_USERS_ATTEMPT', ['ERROR: Akses ditolak.']);
        return $this->failForbidden('Anda tidak memiliki izin untuk menghapus user.');
      }

      $db = \Config\Database::connect();

      // Cek apakah user ditemukan
      if (!$this->model->find($id)) {
        $this->createLog('DELETE_USERS', ['ERROR: user tidak ditemukan.']);
        return $this->failNotFound('user tidak ditemukan.');
      }

      // Lanjut hapus user
      if (!$this->model->delete($id)) {
        $this->createLog('DELETE_USERS', ['ERROR saat menghapus.']);
        return Services::response()
          ->setJSON([
            'status'  => 'error',
            'message' => 'Gagal menghapus user.'
          ])
          ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
      }

      $this->createLog('DELETE_USERS', ['SUCCESS']);
      return Services::response()
        ->setJSON([
          'status'  => 'success',
          'message' => 'user berhasil dihapus.'
        ])
        ->setStatusCode(200);
    } catch (Exception $e) {
      $this->createLog('DELETE_USERS', ['ERROR']);
      return Services::response()
        ->setJSON([
          'status'  => 'error',
          'message' => 'Terjadi kesalahan pada server.',
          'error'   => $e->getMessage()
        ])
        ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
    }
  }
}
