<?php

namespace App\Controllers;

use App\Models\TransactionDetailsModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

class CategoriesController extends ResourceController
{
  protected $modelName = 'App\Models\CategoryModel';
  protected $format = 'json';

  // Ambil semua detail transaksi
  public function index()
  {
    return $this->respond($this->model->findAll());
  }

  // Ambil detail transaksi berdasarkan ID
  public function show($id = null)
  {
    $data = $this->model->find($id);
    if (!$data) {
      return $this->failNotFound('Detail transaksi tidak ditemukan');
    }
    return $this->respond($data);
  }

  // Ambil detail transaksi berdasarkan transaction_id
  public function showByTransactionId($transaction_id)
  {
    $data = $this->model->where('transaction_id', $transaction_id)->findAll();
    if (empty($data)) {
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

    $data = [
      'transaction_id' => $this->request->getPost('transaction_id'),
      'product_id' => $this->request->getPost('product_id'),
      'quantity' => $this->request->getPost('quantity'),
      'price' => $this->request->getPost('price'),
      'subtotal' => $this->request->getPost('quantity') * $this->request->getPost('price')
    ];

    $this->model->insert($data);
    return $this->respondCreated($data, 'Detail transaksi berhasil ditambahkan');
  }

  // Perbarui detail transaksi
  public function update($id = null)
  {
    $data = $this->model->find($id);
    if (!$data) {
      return $this->failNotFound('Detail transaksi tidak ditemukan');
    }

    $updateData = $this->request->getRawInput();
    $updateData['subtotal'] = $updateData['quantity'] * $updateData['price'];

    $this->model->update($id, $updateData);
    return $this->respondUpdated($updateData, 'Detail transaksi berhasil diperbarui');
  }

  // Hapus detail transaksi
  public function delete($id = null)
  {
    $data = $this->model->find($id);
    if (!$data) {
      return $this->failNotFound('Detail transaksi tidak ditemukan');
    }

    $this->model->delete($id);
    return $this->respondDeleted(['id' => $id], 'Detail transaksi berhasil dihapus');
  }
}
