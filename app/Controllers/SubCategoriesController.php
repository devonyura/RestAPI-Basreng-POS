<?php

namespace App\Controllers;

use App\Models\TransactionDetailsModel;
use App\Models\ActivityLogModel;
use App\Helpers\JwtHelper;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use Exception;

class SubCategoriesController extends ResourceController
{
  protected $modelName = 'App\Models\SubCategoryModel';
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

  // GET /sub-categories/{id_categories}
  public function show($id_subcategories = null)
  {
    try {



      $db = \Config\Database::connect();
      $builder = $db->table('sub_categories');
      $builder->select('*');

      // Ambil param username
      $id_categories = $this->request->getGet('id_categories');
      if (!empty($id_categories)) {
        $builder->where('id_categories', $id_categories);
      } else {
        $builder->where('id', $id_subcategories);
      }

      $subCategories = $builder->get()->getResultArray();

      if (empty($subCategories)) {
        $this->createLog("SHOW_SUB_CATEGORIES", ['ERROR: Tidak ditemukan.']);
        return $this->failNotFound('Sub kategori tidak ditemukan.');
      }

      $this->createLog("SHOW_SUB_CATEGORIES", ['SUCCESS']);
      return $this->respond([
        'status' => 'success',
        'data'   => $subCategories
      ]);
    } catch (Exception $e) {
      $this->createLog('SHOW_SUB_CATEGORIES', ['ERROR']);
      return Services::response()
        ->setJSON([
          'status'  => 'error',
          'message' => 'Terjadi kesalahan pada server.',
          'error'   => $e->getMessage()
        ])
        ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
    }
  }


  // Ambil semua detail transaksi
  public function index($id_subcategories = null)
  {
    try {
      $db = \Config\Database::connect();
      $builder = $db->table('sub_categories');
      $builder->select('*');

      // Ambil param id categories
      $id_categories = $this->request->getGet('id_categories');
      if (!empty($id_categories)) {
        $builder->where('id_categories', $id_categories);
        $subCategories = $builder->get()->getResultArray();
      } else if (!empty($id_subcategories)) {
        $builder->where('id', $id_subcategories);
        $subCategories = $builder->get()->getResultArray();
      } else {
        $subCategories = $this->model->findAll();
      }


      if (empty($subCategories)) {
        $this->createLog("SHOW_SUB_CATEGORIES", ['ERROR: Tidak ditemukan.']);
        return $this->failNotFound('Sub kategori tidak ditemukan.');
      }

      $this->createLog("SHOW_SUB_CATEGORIES", ['SUCCESS']);
      return $this->respond([
        'status' => 'success',
        'data'   => $subCategories
      ]);
    } catch (Exception $e) {
      $this->createLog('READ_ALL_SUB_CATEGORIES', ['ERROR']);
      return Services::response()
        ->setJSON([
          'status'  => 'error',
          'message' => 'Terjadi kesalahan pada server.',
          'error'   => $e->getMessage()
        ])
        ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
    }
    // return $this->respond($this->model->findAll());
  }

  // // Tambah sub category
  public function create()
  {
    $rules = [
      'id_categories' => 'required|integer',
      'name'        => 'required|min_length[3]|is_unique[products.name]',
    ];

    if (!$this->validate($rules)) {
      return $this->failValidationErrors($this->validator->getErrors());
    }

    $data = $this->request->getJSON();
    $data = [
      'id_categories' => $data->id_categories,
      'name'        => $data->name,
    ];

    try {
      if (!$this->model->insert($data)) {
        $this->createLog('CREATE_SubCategory', ['ERROR']);
        return Services::response()
          ->setJSON([
            'status'  => 'error',
            'message' => 'Gagal menambahkan Sub category.',
            'errors'  => $this->model->errors()
          ])
          ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
      }
      $this->createLog('CREATE_SubCategory', ['SUCCESS']);
      return Services::response()
        ->setJSON([
          'status'  => 'success',
          'message' => 'Sub category berhasil ditambahkan',
          'data'    => $data
        ])
        ->setStatusCode(ResponseInterface::HTTP_CREATED);
    } catch (Exception $e) {
      $this->createLog('CREATE_SubCategory', ['ERROR']);
      return Services::response()
        ->setJSON([
          'status'  => 'error',
          'message' => 'Terjadi kesalahan pada server.',
          'error'   => $e->getMessage()
        ])
        ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  // Perbarui subcategories
  public function update($id = null)
  {
    $rules = [
      'name'        => 'required|min_length[3]|is_unique[sub_categories.name,id,{$id}]'
    ];

    $data = $this->request->getJSON();

    if (!$this->model->find($id)) {
      return Services::response()
        ->setJSON(['status' => 'error', 'message' => 'Sub Kategori tidak ditemukan'])
        ->setStatusCode(404);
    }

    if (!$this->validate($rules)) {
      $this->createLog('UPDATE_SubCategory', ['ERROR: Validasi gagal']);
      return $this->failValidationErrors($this->validator->getErrors());
    }

    $data = [
      'name'        => $data->name,
    ];

    try {
      $this->model->update($id, $data);
      $this->createLog('UPDATE_SubCategory', ['SUCCESS']);
      return Services::response()
        ->setJSON([
          'status'  => 'success',
          'message' => 'Sub Kategori berhasil diperbarui',
          'data'    => $data
        ])
        ->setStatusCode(200);
    } catch (Exception $e) {
      $this->createLog('UPDATE_SubCategory', ['ERROR']);
      return Services::response()
        ->setJSON([
          'status'  => 'error',
          'message' => 'Gagal memperbarui Sub Kategori',
          'error'   => $e->getMessage()
        ])
        ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
    }
  }


  // DELETE /subcategories/{id}
  public function delete($id = null)
  {
    try {
      $db = \Config\Database::connect();

      // Cek apakah sub kategori ditemukan
      if (!$this->model->find($id)) {
        $this->createLog('DELETE_SUB_CATEGORIES', ['ERROR: Sub kategori tidak ditemukan.']);
        return $this->failNotFound('Sub kategori tidak ditemukan.');
      }

      // Cek apakah sub kategori masih digunakan oleh produk
      $hasProducts = $db->table('products')
        ->where('subcategory_id', $id)
        ->countAllResults();

      if ($hasProducts > 0) {
        $this->createLog('DELETE_SUB_CATEGORIES', ['ERROR: Sub kategori masih digunakan oleh produk.']);
        return Services::response()
          ->setJSON([
            'status'  => 'error',
            'message' => 'Sub kategori tidak dapat dihapus karena masih digunakan oleh produk.'
          ])
          ->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST);
      }

      // Hapus sub kategori
      if (!$this->model->delete($id)) {
        $this->createLog('DELETE_SUB_CATEGORIES', ['ERROR: Gagal menghapus dari DB.']);
        return Services::response()
          ->setJSON([
            'status'  => 'error',
            'message' => 'Gagal menghapus sub kategori.'
          ])
          ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
      }

      $this->createLog('DELETE_SUB_CATEGORIES', ['SUCCESS']);
      return Services::response()
        ->setJSON([
          'status'  => 'success',
          'message' => 'Sub kategori berhasil dihapus.'
        ])
        ->setStatusCode(200);
    } catch (Exception $e) {
      $this->createLog('DELETE_SUB_CATEGORIES', ['ERROR']);
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
