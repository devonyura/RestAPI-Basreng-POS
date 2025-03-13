<?php

namespace App\Models;

use CodeIgniter\Model;

class SiswaModel extends Model
{
	protected $table = 'siswa';
	protected $primaryKey = 'id';
	protected $allowedFields = ['name', 'address', 'gender'];
	protected $useTimestamps = true;
	protected $dateFormat    = 'datetime';
	protected $createdField  = 'created_at';
	protected $updatedField  = 'updated_at';
}
