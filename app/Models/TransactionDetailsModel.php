<?php

namespace App\Models;

use CodeIgniter\Model;

class TransactionDetailsModel extends Model
{
  protected $table = 'transaction_details';
  protected $primaryKey = 'id';
  protected $allowedFields = [
    'transaction_id',
    'product_id',
    'quantity',
    'price',
    'subtotal',
  ];
  protected $useTimestamps = true;
  protected $dateFormat    = 'datetime';
  protected $createdField  = 'created_at';
  protected $updatedField = '';
}
