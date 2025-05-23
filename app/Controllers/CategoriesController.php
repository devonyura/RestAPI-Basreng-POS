<?php

namespace App\Controllers;

use App\Models\ActivityLogModel;
use App\Helpers\JwtHelper;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use Exception;

class CategoriesController extends ResourceController
{
  protected $modelName = 'App\Models\CategoryModel';
  protected $format = 'json';

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

  // GET /categories
  public function index()
  {
    try {
      $data = $this->model->findAll();
      if (empty($data)) {
        $this->createLog('READ_ALL_CATEGORIES', 'Tidak ada data kategori.');
        return $this->failNotFound('Tidak ada data kategori.');
      }
      $this->createLog('READ_ALL_CATEGORIES', ['SUCCESS']);
      return $this->respond([
        'status' => 'success',
        'data'   => $data
      ]);
    } catch (Exception $e) {
      $this->createLog('READ_ALL_CATEGORIES', ['ERROR']);
      return Services::response()
        ->setJSON([
          'status'  => 'error',
          'message' => 'Terjadi kesalahan pada server.',
          'error'   => $e->getMessage()
        ])
        ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  // GET /categories/{id}
  public function show($id = null)
  {
    $data = $this->model->find($id);
    if (!$data) {
      return $this->failNotFound('Detail transaksi tidak ditemukan');
    }
    return $this->respond($data);
  }

  // POST /categories
  public function create()
  {
    $rules = [
      'name'        => 'required|min_length[3]|is_unique[categories.name]',
    ];

    if (!$this->validate($rules)) {
      return $this->failValidationErrors($this->validator->getErrors());
    }

    $data = $this->request->getJSON();
    $data = [
      'name'        => $data->name,
    ];

    try {
      if (!$this->model->insert($data)) {
        $this->createLog('CREATE_Category', ['ERROR']);
        return Services::response()
          ->setJSON([
            'status'  => 'error',
            'message' => 'Gagal menambahkan  category.',
            'errors'  => $this->model->errors()
          ])
          ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
      }
      $this->createLog('CREATE_Category', ['SUCCESS']);
      return Services::response()
        ->setJSON([
          'status'  => 'success',
          'message' => ' category berhasil ditambahkan',
          'data'    => $data
        ])
        ->setStatusCode(ResponseInterface::HTTP_CREATED);
    } catch (Exception $e) {
      $this->createLog('CREATE_Category', ['ERROR']);
      return Services::response()
        ->setJSON([
          'status'  => 'error',
          'message' => 'Terjadi kesalahan pada server.',
          'error'   => $e->getMessage()
        ])
        ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  // PUT /categories/{id}
  public function update($id = null)
  {
    $rules = [
      'name'        => 'required|min_length[3]|is_unique[categories.name,id,{$id}]'
    ];

    $data = $this->request->getJSON();

    if (!$this->model->find($id)) {
      return Services::response()
        ->setJSON(['status' => 'error', 'message' => 'Kategori tidak ditemukan'])
        ->setStatusCode(404);
    }

    if (!$this->validate($rules)) {
      $this->createLog('UPDATE_PRODUCT', ['ERROR: Validasi gagal']);
      return $this->failValidationErrors($this->validator->getErrors());
    }

    $data = [
      'name'        => $data->name,
    ];

    try {
      $this->model->update($id, $data);
      $this->createLog('UPDATE_Category', ['SUCCESS']);
      return Services::response()
        ->setJSON([
          'status'  => 'success',
          'message' => 'Kategori berhasil diperbarui',
          'data'    => $data
        ])
        ->setStatusCode(200);
    } catch (Exception $e) {
      $this->createLog('UPDATE_Category', ['ERROR']);
      return Services::response()
        ->setJSON([
          'status'  => 'error',
          'message' => 'Gagal memperbarui Kategori',
          'error'   => $e->getMessage()
        ])
        ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
    }
  }


  // DELETE /categories/{id}
  public function delete($id = null)
  {
    try {
      $db = \Config\Database::connect();

      // Cek apakah kategori ditemukan
      if (!$this->model->find($id)) {
        $this->createLog('DELETE_CATEGORIES', ['ERROR: Kategori tidak ditemukan.']);
        return $this->failNotFound('Kategori tidak ditemukan.');
      }

      // Cek apakah kategori masih memiliki sub-kategori
      $subCatCount = $db->table('sub_categories')
        ->where('id_categories', $id)
        ->countAllResults();

      if ($subCatCount > 0) {
        $this->createLog('DELETE_CATEGORIES', ['ERROR: Masih memiliki sub kategori.']);
        return Services::response()
          ->setJSON([
            'status'  => 'error',
            'message' => 'Kategori tidak dapat dihapus karena masih memiliki sub kategori.'
          ])
          ->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST);
      }

      // Cek apakah kategori masih digunakan oleh produk
      $hasProducts = $db->table('products')
        ->where('category_id', $id)
        ->countAllResults();

      if ($hasProducts > 0) {
        $this->createLog('DELETE_CATEGORIES', ['ERROR: Digunakan oleh produk.']);
        return Services::response()
          ->setJSON([
            'status'  => 'error',
            'message' => 'Kategori tidak dapat dihapus karena masih digunakan oleh produk.'
          ])
          ->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST);
      }

      // Lanjut hapus kategori
      if (!$this->model->delete($id)) {
        $this->createLog('DELETE_CATEGORIES', ['ERROR saat menghapus.']);
        return Services::response()
          ->setJSON([
            'status'  => 'error',
            'message' => 'Gagal menghapus Kategori.'
          ])
          ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
      }

      $this->createLog('DELETE_CATEGORIES', ['SUCCESS']);
      return Services::response()
        ->setJSON([
          'status'  => 'success',
          'message' => 'Kategori berhasil dihapus.'
        ])
        ->setStatusCode(200);
    } catch (Exception $e) {
      $this->createLog('DELETE_CATEGORIES', ['ERROR']);
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
