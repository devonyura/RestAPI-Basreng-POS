<?php

namespace App\Models;

use CodeIgniter\Model;

class SalesReportModel extends Model
{
  protected $table = 'sales_reports';
  protected $primaryKey = 'id';
  protected $allowedFields = [
    'name',
  ];
  protected $useTimestamps = true;
  protected $dateFormat    = 'datetime';
  protected $createdField  = 'created_at';
  protected $updatedField = '';
}
