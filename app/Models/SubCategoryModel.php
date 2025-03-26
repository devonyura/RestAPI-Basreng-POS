<?php

namespace App\Models;

use CodeIgniter\Model;

class SubCategoryModel extends Model
{
  protected $table = 'sub_categories';
  protected $primaryKey = 'id';
  protected $allowedFields = [
    'id_categories',
    'name',
  ];
  protected $useTimestamps = true;
  protected $dateFormat    = 'datetime';
  protected $createdField  = 'created_at';
  protected $updatedField = '';
}
