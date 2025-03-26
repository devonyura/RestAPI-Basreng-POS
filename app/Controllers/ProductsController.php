<?php

namespace App\Controllers;

use App\Models\ProductModel;
use App\Models\ActivityLogModel;
use App\Helpers\JwtHelper;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use Exception;

class ProductsController extends ResourceController
{
  protected $modelName = 'App\Models\ProductModel';
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

  // GET /products
  public function index()
  {
    try {
      $data = $this->model->findAll();
      if (empty($data)) {
        $this->createLog('READ_ALL_PRODUCTS', 'Tidak ada data produk.');
        return $this->failNotFound('Tidak ada data produk.');
      }
      $this->createLog('READ_ALL_PRODUCTS', ['SUCCESS']);
      return $this->respond([
        'status' => 'success',
        'data'   => $data
      ]);
    } catch (Exception $e) {
      $this->createLog('READ_ALL_PRODUCTS', ['ERROR']);
      return Services::response()
        ->setJSON([
          'status'  => 'error',
          'message' => 'Terjadi kesalahan pada server.',
          'error'   => $e->getMessage()
        ])
        ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  // POST /products
  public function create()
  {
    $rules = [
      'category_id' => 'required|integer',
      'name'        => 'required|min_length[3]|is_unique[products.name]',
      'price'       => 'required|decimal'
    ];

    if (!$this->validate($rules)) {
      return $this->failValidationErrors($this->validator->getErrors());
    }

    $data = $this->request->getJSON();
    $productData = [
      'category_id' => $data->category_id,
      'name'        => $data->name,
      'price'       => $data->price
    ];

    try {
      if (!$this->model->insert($productData)) {
        $this->createLog('CREATE_PRODUCT', ['ERROR']);
        return Services::response()
          ->setJSON([
            'status'  => 'error',
            'message' => 'Gagal menambahkan produk.',
            'errors'  => $this->model->errors()
          ])
          ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
      }
      $this->createLog('CREATE_PRODUCT', ['SUCCESS']);
      return Services::response()
        ->setJSON([
          'status'  => 'success',
          'message' => 'Produk berhasil ditambahkan',
          'data'    => $productData
        ])
        ->setStatusCode(ResponseInterface::HTTP_CREATED);
    } catch (Exception $e) {
      $this->createLog('CREATE_PRODUCT', ['ERROR']);
      return Services::response()
        ->setJSON([
          'status'  => 'error',
          'message' => 'Terjadi kesalahan pada server.',
          'error'   => $e->getMessage()
        ])
        ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  // PUT /products/{id}
  public function update($id = null)
  {
    $rules = [
      'category_id' => 'required|integer',
      'name'        => 'required|min_length[3]',
      'price'       => 'required|decimal'
    ];

    $data = $this->request->getJSON();

    if (!$this->model->find($id)) {
      return Services::response()
        ->setJSON(['status' => 'error', 'message' => 'Produk tidak ditemukan'])
        ->setStatusCode(404);
    }

    if (!$this->validate($rules)) {
      $this->createLog('UPDATE_PRODUCT', ['ERROR: Validasi gagal']);
      return $this->failValidationErrors($this->validator->getErrors());
    }

    $productData = [
      'category_id' => $data->category_id,
      'name'        => $data->name,
      'price'       => $data->price
    ];

    try {
      $this->model->update($id, $productData);
      $this->createLog('UPDATE_PRODUCT', ['SUCCESS']);
      return Services::response()
        ->setJSON([
          'status'  => 'success',
          'message' => 'Produk berhasil diperbarui',
          'data'    => $productData
        ])
        ->setStatusCode(200);
    } catch (Exception $e) {
      $this->createLog('UPDATE_PRODUCT', ['ERROR']);
      return Services::response()
        ->setJSON([
          'status'  => 'error',
          'message' => 'Gagal memperbarui produk',
          'error'   => $e->getMessage()
        ])
        ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  // DELETE /products/{id}
  public function delete($id = null)
  {
    try {
      if (!$this->model->find($id)) {
        $this->createLog('DELETE_PRODUCT', ['ERROR: Produk tidak ditemukan.']);
        return $this->failNotFound('Produk tidak ditemukan.');
      }

      if (!$this->model->delete($id)) {
        $this->createLog('DELETE_PRODUCT', ['ERROR']);
        return Services::response()
          ->setJSON([
            'status'  => 'error',
            'message' => 'Gagal menghapus produk.'
          ])
          ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
      }

      $this->createLog('DELETE_PRODUCT', ['SUCCESS']);
      return Services::response()
        ->setJSON([
          'status'  => 'success',
          'message' => 'Produk berhasil dihapus.'
        ])
        ->setStatusCode(200);
    } catch (Exception $e) {
      $this->createLog('DELETE_PRODUCT', ['ERROR']);
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
