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
      // $data = $this->model->findAll();
      // if (empty($data)) {
      //   $this->createLog('READ_ALL_SUB_CATEGORIES', 'Tidak ada data kategori.');
      //   return $this->failNotFound('Tidak ada data kategori.');
      // }
      // $this->createLog('READ_ALL_SUB_CATEGORIES', ['SUCCESS']);
      // return $this->respond([
      //   'status' => 'success',
      //   'data'   => $data
      // ]);
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


  // Ambil detail transaksi berdasarkan ID
  // public function show($id = null)
  // {
  //   $data = $this->model->find($id);
  //   if (!$data) {
  //     return $this->failNotFound('Detail transaksi tidak ditemukan');
  //   }
  //   return $this->respond($data);
  // }

  // Ambil detail transaksi berdasarkan transaction_id
  // public function showByTransactionId($transaction_id)
  // {
  //   $data = $this->model->where('transaction_id', $transaction_id)->findAll();
  //   if (empty($data)) {
  //     return $this->failNotFound('Detail transaksi tidak ditemukan untuk ID transaksi ini');
  //   }
  //   return $this->respond($data);
  // }

  // // Tambah detail transaksi
  // public function create()
  // {
  //   $rules = [
  //     'transaction_id' => 'required|integer',
  //     'product_id' => 'required|integer',
  //     'quantity' => 'required|integer',
  //     'price' => 'required|decimal'
  //   ];

  //   if (!$this->validate($rules)) {
  //     return $this->failValidationErrors($this->validator->getErrors());
  //   }

  //   $data = [
  //     'transaction_id' => $this->request->getPost('transaction_id'),
  //     'product_id' => $this->request->getPost('product_id'),
  //     'quantity' => $this->request->getPost('quantity'),
  //     'price' => $this->request->getPost('price'),
  //     'subtotal' => $this->request->getPost('quantity') * $this->request->getPost('price')
  //   ];

  //   $this->model->insert($data);
  //   return $this->respondCreated($data, 'Detail transaksi berhasil ditambahkan');
  // }

  // // Perbarui detail transaksi
  // public function update($id = null)
  // {
  //   $data = $this->model->find($id);
  //   if (!$data) {
  //     return $this->failNotFound('Detail transaksi tidak ditemukan');
  //   }

  //   $updateData = $this->request->getRawInput();
  //   $updateData['subtotal'] = $updateData['quantity'] * $updateData['price'];

  //   $this->model->update($id, $updateData);
  //   return $this->respondUpdated($updateData, 'Detail transaksi berhasil diperbarui');
  // }

  // // Hapus detail transaksi
  // public function delete($id = null)
  // {
  //   $data = $this->model->find($id);
  //   if (!$data) {
  //     return $this->failNotFound('Detail transaksi tidak ditemukan');
  //   }

  //   $this->model->delete($id);
  //   return $this->respondDeleted(['id' => $id], 'Detail transaksi berhasil dihapus');
  // }
}
