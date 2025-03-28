<?php

namespace App\Controllers;

use App\Models\TransactionModel;
use App\Models\ActivityLogModel;
use App\Helpers\JwtHelper;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use Exception;
use PhpParser\Builder\Function_;

class TransactionsController extends ResourceController
{
  protected $modelName = 'App\Models\TransactionModel';
  protected $format    = 'json';
  protected $db;

  public function __construct()
  {
    $this->db = \Config\Database::connect();
  }

  public function createTransaction()
  {
    $input = $this->request->getJSON(true);
    if (!$input) {
      return $this->fail("Invalid JSON Format", 400);
    }

    // Generate transaction code
    $now = new \DateTime();
    $randomNumber = str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT);
    $transaction_code = sprintf("CAB01%s%02d", $now->format('dmyHi'), $randomNumber);

    // Prepare Transaction data
    $transactionData = [
      'user_id'          => $input['user_id'],
      'transaction_code' =>  $transaction_code,
      'total_price' => $input['total_price'],
      'payment_method' => $input['payment_method'],
      'is_online_order' => $input['is_online_order'],
      'cash_amount' => $input['cash_amount'] ?? '0',
      'change_amount' => $input['change_amount'] ?? '0',
      'created_at' => date('Y-m-d H:i:s')
    ];

    // Add Customer data if is_online_order === 1
    if ($input['is_online_order'] === 1) {
      $transactionData['customer_name'] = $input['customer_name'] ?? null;
      $transactionData['customer_address'] = $input['customer_address'] ?? null;
      $transactionData['customer_phone'] = $input['customer_phone'] ?? null;
      $transactionData['notes'] = $input['notes'] ?? null;
    }

    $this->db->transStart();

    // Insert Transaction data actions
    $this->db->table('transactions')->insert($transactionData);
    $transaction_id = $this->db->insertID();

    if (!$transaction_id) {
      $this->db->transRollback();
      return $this->fail('Failed to create transaction', 500);
    }

    // Insert transaction_details
    $transaction_details = [];
    foreach ($input['transaction_details'] as $detail) {
      $transaction_details[] = [
        'transaction_id' => $transaction_id,
        'product_id' => $detail['product_id'],
        'quantity' => $detail['quantity'],
        'price' => $detail['price'],
        'subtotal' => ($detail['price'] * $detail['quantity']),
        'created_at' => date('Y-m-d H:i:s')
      ];
    }

    $this->db->table('transaction_details')->insertBatch($transaction_details);

    $this->db->transComplete();

    if ($this->db->transStatus() === false) {
      return $this->fail('Transaction Failed, please try again', 500);
    }

    return $this->respond([
      'message' => 'Transaction created Successfully',
      'transaction_code' => $transaction_code
    ]);
  }

  public function get_receipt()
  {
    $input = $this->request->getJSON(true);
    $transaction_code = $input['transaction_code'];

    if (!$input) {
      return $this->fail("Invalid JSON Format", 400);
    }

    if (!$transaction_code) {
      return $this->fail('Transaction code is required', 400);
    }

    // Ambil data transaksi utama
    $transaction = $this->db->table('transactions')
      ->select('transactions.*, users.username as cashier')
      ->join('users', 'users.id = transactions.user_id')
      ->where('transactions.transaction_code', $transaction_code)
      ->get()
      ->getRowArray();

    if (!$transaction) {
      return $this->failNotFound('Transaction not found');
    }

    // Ambil detail transaksi
    $transaction_details = $this->db->table('transaction_details')
      ->select('transaction_details.*, products.name as product_name')
      ->join('products', 'products.id = transaction_details.product_id')
      ->where('transaction_details.transaction_id', $transaction['id'])
      ->get()
      ->getResultArray();

    // Format data produk
    $products = array_map(function ($detail) {
      return [
        'product_name' => $detail['product_name'],
        'quantity' => (int) $detail['quantity'],
        'price' => (int) $detail['price'],
        'subtotal' => (int) $detail['subtotal'],
      ];
    }, $transaction_details);

    // Format response
    $data = [
      'transaction_code' => $transaction['transaction_code'],
      'cashier' => $transaction['cashier'],
      'products' => $products,
      'total_price' => (int) $transaction['total_price'],
      'cash_amount' => (int) $transaction['cash_amount'],
      'change_amount' => (int) $transaction['change_amount'],
      'is_online_order' => (int) $transaction['is_online_order'],
      'customer_name' => $transaction['customer_name'] ?? '',
      'customer_address' => $transaction['customer_address'] ?? '',
      'customer_phone' => $transaction['customer_phone'] ?? '',
      'notes' => $transaction['notes'] ?? '',
      'tanggal' => date('d-m-Y', strtotime($transaction['created_at']))
    ];

    return $this->respond([
      'status' => 'success',
      'data'   => $data
    ]);
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

  // GET /transactions
  public function index()
  {
    try {
      $data = $this->model->findAll();
      if (empty($data)) {
        $this->createLog('READ_ALL_TRANSACTIONS', 'Tidak ada data transaksi.');
        return $this->failNotFound('Tidak ada data transaksi.');
      }
      $this->createLog('READ_ALL_TRANSACTIONS', ['SUCCESS']);
      return $this->respond([
        'status' => 'success',
        'data'   => $data
      ]);
    } catch (Exception $e) {
      $this->createLog('READ_ALL_TRANSACTIONS', ['ERROR']);
      return Services::response()
        ->setJSON([
          'status'  => 'error',
          'message' => 'Terjadi kesalahan pada server.',
          'error'   => $e->getMessage()
        ])
        ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  // GET /transactions/{id}
  public function show($id = null)
  {
    try {
      $data = $this->model->find($id);
      if (!$data) {
        $this->createLog("SHOW_TRANSACTION", ['ERROR: Transaksi tidak ditemukan.']);
        return $this->failNotFound('Transaksi tidak ditemukan.');
      }
      $this->createLog("SHOW_TRANSACTION", ['SUCCESS']);
      return $this->respond([
        'status' => 'success',
        'data'   => $data
      ]);
    } catch (Exception $e) {
      $this->createLog('SHOW_TRANSACTION', ['ERROR']);
      return Services::response()
        ->setJSON([
          'status'  => 'error',
          'message' => 'Terjadi kesalahan pada server.',
          'error'   => $e->getMessage()
        ])
        ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  // POST /transactions
  public function create()
  {
    $rules = [
      'transaction_code' => 'required|is_unique[transactions.transaction_code]',
      'user_id'          => 'required|integer',
      'date_time'        => 'required',
      'total_price'      => 'required|decimal',
      'payment_method'   => 'required',
      'is_online_order'  => 'required'
      // Field tambahan bisa disesuaikan
    ];

    if (!$this->validate($rules)) {
      return $this->failValidationErrors($this->validator->getErrors());
    }

    $data = $this->request->getJSON();
    $transactionData = [
      'transaction_code' => $data->transaction_code,
      'user_id'          => $data->user_id,
      'date_time'        => $data->date_time,
      'total_price'      => $data->total_price,
      'payment_method'   => $data->payment_method,
      'is_online_order'  => $data->is_online_order,
      'customer_name'    => $data->customer_name ?? null,
      'customer_address' => $data->customer_address ?? null,
      'customer_phone'   => $data->customer_phone ?? null,
      'notes'            => $data->notes ?? null,
    ];

    try {
      if (!$this->model->insert($transactionData)) {
        $this->createLog("CREATE_TRANSACTION", ['ERROR']);
        return Services::response()
          ->setJSON([
            'status'  => 'error',
            'message' => 'Gagal menambahkan transaksi.',
            'errors'  => $this->model->errors()
          ])
          ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
      }
      $this->createLog("CREATE_TRANSACTION", ['SUCCESS']);
      return Services::response()
        ->setJSON([
          'status'  => 'success',
          'message' => 'Transaksi berhasil ditambahkan',
          'data'    => $transactionData
        ])
        ->setStatusCode(ResponseInterface::HTTP_CREATED);
    } catch (Exception $e) {
      $this->createLog('CREATE_TRANSACTION', ['ERROR']);
      return Services::response()
        ->setJSON([
          'status'  => 'error',
          'message' => 'Terjadi kesalahan pada server.',
          'error'   => $e->getMessage()
        ])
        ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  // DELETE /transactions/{id}
  public function delete($id = null)
  {
    try {
      if (!$this->model->find($id)) {
        $this->createLog("DELETE_TRANSACTION", ['ERROR: Transaksi tidak ditemukan.']);
        return $this->failNotFound('Transaksi tidak ditemukan.');
      }

      if (!$this->model->delete($id)) {
        $this->createLog('DELETE_TRANSACTION', ['ERROR']);
        return Services::response()
          ->setJSON([
            'status'  => 'error',
            'message' => 'Gagal menghapus transaksi.'
          ])
          ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
      }
      $this->createLog('DELETE_TRANSACTION', ['SUCCESS']);
      return Services::response()
        ->setJSON([
          'status'  => 'success',
          'message' => 'Transaksi berhasil dihapus.'
        ])
        ->setStatusCode(ResponseInterface::HTTP_OK);
    } catch (Exception $e) {
      $this->createLog('DELETE_TRANSACTION', ['ERROR']);
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
