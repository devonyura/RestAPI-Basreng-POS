<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSiswaTable extends Migration
{
	public function up()
	{
		$this->forge->addField([
			'id'       => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true, 'unsigned' => true],
			'name'     => ['type' => 'VARCHAR', 'constraint' => 100],
			'address'   => ['type' => 'TEXT'],
			'gender'   => ['type' => 'ENUM', 'constraint' => ['L', 'P']],
			'created_at' => [
				'type' => 'DATETIME',
				'NULL' => true,
			],
			'updated_at' => [
				'type' => 'DATETIME',
				'NULL' => true,
			],
		]);
		$this->forge->addPrimaryKey('id');
		$this->forge->createTable('siswa');
	}

	public function down()
	{
		$this->forge->dropTable('siswa');
	}
}
