<?php

namespace App\Controllers;

use App\Models\TransactionDetailsModel;
use App\Models\ActivityLogModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use App\Helpers\JwtHelper;
use Exception;


class TransactionsDetailsController extends ResourceController
{
  protected $modelName = 'App\Models\TransactionDetailsModel';
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

  // Ambil semua detail transaksi
  public function index()
  {
    try {
      $data = $this->model->findAll();
      if (empty($data)) {
        $this->createLog('READ_ALL_DETAIL_TRANSACTIONS', 'Tidak ada data transaksi.');
        return $this->failNotFound('Tidak ada data transaksi.');
      }
      $this->createLog('READ_ALL_DETAIL_TRANSACTIONS', ['SUCCESS']);
      return $this->respond([
        'status' => 'success',
        'data'   => $data
      ]);
    } catch (Exception $e) {
      $this->createLog('READ_ALL_DETAIL_TRANSACTIONS', ['ERROR']);
      return Services::response()
        ->setJSON([
          'status'  => 'error',
          'message' => 'Terjadi kesalahan pada server.',
          'error'   => $e->getMessage()
        ])
        ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  // Ambil detail transaksi berdasarkan ID
  public function show($id = null)
  {
    $data = $this->model->find($id);
    if (!$data) {
      $this->createLog('READ_DETAIL_TRANSACTIONS', ['ERROR']);
      return $this->failNotFound('Detail transaksi tidak ditemukan');
    }
    return $this->respond($data);
  }

  // Ambil detail transaksi berdasarkan transaction_id
  public function showByTransactionId($transaction_id)
  {
    $data = $this->model->select('product_id,quantity,price,subtotal')->where('transaction_id', $transaction_id)->findAll();
    $this->createLog('READ_DETAIL_TRANSACTIONS_BY_ID-TRANSACTION', ['SUCCESS']);
    if (empty($data)) {
      $this->createLog('READ_DETAIL_TRANSACTIONS_BY_ID-TRANSACTION', ['ERROR']);
      return $this->failNotFound('Detail transaksi tidak ditemukan untuk ID transaksi ini');
    }
    return $this->respond($data);
  }

  // Tambah detail transaksi
  public function create()
  {
    $rules = [
      'transaction_id' => 'required|integer',
      'product_id' => 'required|integer',
      'quantity' => 'required|integer',
      'price' => 'required|decimal'
    ];

    if (!$this->validate($rules)) {
      return $this->failValidationErrors($this->validator->getErrors());
    }

    $data = $this->request->getJSON();
    $detailtransactionData = [
      'transaction_id' => $data->transaction_id,
      'product_id' => $data->product_id,
      'quantity' => $data->quantity,
      'price' => $data->price,
      'subtotal' => $data->quantity * $data->price
    ];

    $this->model->insert($detailtransactionData);
    return $this->respondCreated($detailtransactionData, 'Detail transaksi berhasil ditambahkan');
  }

  // Perbarui detail transaksi
  public function update($id = null)
  {
    $data = $this->model->find($id);
    if (!$data) {
      $this->createLog('UPDATE_DETAIL_TRANSACTIONS', ['ERROR']);
      return $this->failNotFound('Detail transaksi tidak ditemukan');
    }

    // $updateData = $this->request->getRawInput();
    $updateData = $this->request->getJSON();
    $updateData->subtotal = $updateData->quantity * $updateData->price;

    $this->model->update($id, $updateData);
    return $this->respondUpdated($updateData, 'Detail transaksi berhasil diperbarui');
  }
}
