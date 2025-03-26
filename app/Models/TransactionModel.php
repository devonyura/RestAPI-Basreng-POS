<?php

namespace App\Models;

use CodeIgniter\Model;

class TransactionModel extends Model
{
  protected $table = 'transactions';
  protected $primaryKey = 'id';
  protected $allowedFields = [
    'transaction_code',
    'user_id',
    'date_time',
    'total_price',
    'payment_method',
    'is_online_order',
    'customer_name',
    'customer_address',
    'customer_phone',
    'notes'
  ];
  protected $useTimestamps = true;
  protected $dateFormat    = 'datetime';
  protected $createdField  = 'created_at';
  protected $updatedField = '';
}
