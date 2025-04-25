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
    'branch_id',
    'date_time',
    'total_price',
    'cash_amount',
    'change_amount',
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
