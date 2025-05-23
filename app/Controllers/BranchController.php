<?php

namespace App\Controllers;

use App\Models\BranchModel;
use App\Models\ActivityLogModel;
use App\Helpers\JwtHelper;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use Exception;

class BranchController extends ResourceController
{
  protected $modelName = 'App\Models\BranchModel';
  protected $format    = 'json';

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

  // GET /branches
  public function index()
  {
    try {
      $data = $this->model->findAll();
      if (empty($data)) {
        $this->createLog('READ_ALL_BRANCHES', 'Tidak ada data cabang.');
        return $this->failNotFound('Tidak ada data cabang.');
      }
      $this->createLog('READ_ALL_BRANCHES', ['SUCCESS']);
      return $this->respond([
        'status' => 'success',
        'data'   => $data
      ]);
    } catch (Exception $e) {
      $this->createLog('READ_ALL_BRANCHES', ['ERROR']);
      return Services::response()
        ->setJSON([
          'status'  => 'error',
          'message' => 'Terjadi kesalahan pada server.',
          'error'   => $e->getMessage()
        ])
        ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  // GET /branches/{id}
  public function show($id = null)
  {
    $data = $this->model->find($id);
    if (!$data) {
      return $this->failNotFound('Data cabang tidak ditemukan');
    }
    return $this->respond([
      'status' => 'success',
      'data'   => $data
    ]);
  }

  // POST /branches
  public function create()
  {
    $rules = [
      'branch_name'    => 'required|min_length[3]',
      'branch_address' => 'required|min_length[5]'
    ];

    if (!$this->validate($rules)) {
      return $this->failValidationErrors($this->validator->getErrors());
    }

    $data = $this->request->getJSON();
    $insertData = [
      'branch_name'    => $data->branch_name,
      'branch_address' => $data->branch_address
    ];

    try {
      if (!$this->model->insert($insertData)) {
        $this->createLog('CREATE_BRANCH', ['ERROR']);
        return Services::response()
          ->setJSON([
            'status'  => 'error',
            'message' => 'Gagal menambahkan cabang.',
            'errors'  => $this->model->errors()
          ])
          ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
      }
      $this->createLog('CREATE_BRANCH', ['SUCCESS']);
      return Services::response()
        ->setJSON([
          'status'  => 'success',
          'message' => 'Cabang berhasil ditambahkan',
          'data'    => $insertData
        ])
        ->setStatusCode(ResponseInterface::HTTP_CREATED);
    } catch (Exception $e) {
      $this->createLog('CREATE_BRANCH', ['ERROR']);
      return Services::response()
        ->setJSON([
          'status'  => 'error',
          'message' => 'Terjadi kesalahan pada server.',
          'error'   => $e->getMessage()
        ])
        ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  // PUT /branches/{id}
  public function update($id = null)
  {
    $data = $this->request->getJSON();
    $rules = [
      'branch_name'    => 'required|min_length[3]',
      'branch_address' => 'required|min_length[5]'
    ];

    if (!$this->model->find($id)) {
      return $this->failNotFound('Cabang tidak ditemukan');
    }

    if (!$this->validate($rules)) {
      $this->createLog('UPDATE_BRANCH', ['ERROR: Validasi gagal']);
      return $this->failValidationErrors($this->validator->getErrors());
    }

    $updateData = [
      'branch_name'    => $data->branch_name,
      'branch_address' => $data->branch_address
    ];

    try {
      $this->model->update($id, $updateData);
      $this->createLog('UPDATE_BRANCH', ['SUCCESS']);
      return $this->respond([
        'status'  => 'success',
        'message' => 'Cabang berhasil diperbarui',
        'data'    => $updateData
      ]);
    } catch (Exception $e) {
      $this->createLog('UPDATE_BRANCH', ['ERROR']);
      return Services::response()
        ->setJSON([
          'status'  => 'error',
          'message' => 'Gagal memperbarui cabang.',
          'error'   => $e->getMessage()
        ])
        ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  // DELETE /branches/{id}
  public function delete($id = null)
  {
    try {
      if (!$this->model->find($id)) {
        $this->createLog('DELETE_BRANCH', ['ERROR: Tidak ditemukan']);
        return $this->failNotFound('Cabang tidak ditemukan.');
      }

      $db = \Config\Database::connect();

      // Cek relasi dengan tabel users
      $usedByUsers = $db->table('users')
        ->where('branch_id', $id)
        ->countAllResults();

      if ($usedByUsers > 0) {
        $this->createLog('DELETE_BRANCH', ['ERROR: Digunakan oleh tabel users.']);
        return Services::response()
          ->setJSON([
            'status'  => 'error',
            'message' => 'Branch tidak dapat dihapus karena masih digunakan oleh user.'
          ])
          ->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST);
      }

      // Cek relasi dengan tabel transactions
      $usedByTransactions = $db->table('transactions')
        ->where('branch_id', $id)
        ->countAllResults();

      if ($usedByTransactions > 0) {
        $this->createLog('DELETE_BRANCH', ['ERROR: Digunakan oleh tabel transactions.']);
        return Services::response()
          ->setJSON([
            'status'  => 'error',
            'message' => 'Branch tidak dapat dihapus karena masih digunakan oleh transaksi.'
          ])
          ->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST);
      }

      // Lanjut hapus
      if (!$this->model->delete($id)) {
        $this->createLog('DELETE_BRANCH', ['ERROR']);
        return Services::response()
          ->setJSON([
            'status'  => 'error',
            'message' => 'Gagal menghapus cabang.'
          ])
          ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
      }

      $this->createLog('DELETE_BRANCH', ['SUCCESS']);
      return $this->respond([
        'status'  => 'success',
        'message' => 'Cabang berhasil dihapus.'
      ]);
    } catch (Exception $e) {
      $this->createLog('DELETE_BRANCH', ['ERROR']);
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
