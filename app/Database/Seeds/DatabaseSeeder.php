<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
  public function run()
  {
    $db = \Config\Database::connect();
    $db->query('SET FOREIGN_KEY_CHECKS = 0');

    // $this->db->table('users')->truncate();
    // $this->seedUsers();
    // $this->db->table('categories')->truncate();
    // $this->seedCategories();

    // $this->db->table('products')->truncate();
    // $this->seedProducts();

    $this->db->table('transactions')->truncate();
    $this->db->table('transaction_details')->truncate();
    $this->seedTransactions();

    $db->query('SET FOREIGN_KEY_CHECKS = 1'); // Aktifkan kembali FK constraints
  }

  private function seedCategories()
  {
    $data = [
      ['id' => 1, 'name' => 'cemilan'],
      ['id' => 2, 'name' => 'mochi'],
      ['id' => 3, 'name' => 'sushi'],
    ];
    $this->db->table('categories')->insertBatch($data);
  }

  private function seedProducts()
  {
    $data = [
      ['category_id' => 1, 'name' => 'basreng 75g', 'price' => 10000.00],
      ['category_id' => 1, 'name' => 'basreng 175g', 'price' => 20000.00],
      ['category_id' => 1, 'name' => 'basreng 250g', 'price' => 35000.00],
      ['category_id' => 2, 'name' => 'mochi mangga', 'price' => 8000.00],
      ['category_id' => 2, 'name' => 'mochi coklat', 'price' => 8000.00],
      ['category_id' => 2, 'name' => 'mochi strwawberry', 'price' => 8000.00],
      ['category_id' => 3, 'name' => 'sushi all variant', 'price' => 2500.00],
    ];
    $this->db->table('products')->insertBatch($data);
  }

  private function seedUsers()
  {
    $data = [
      ['username' => 'admin', 'password' => '$2y$10$5WCNZTNabp30mC5z8sM/N.u0WXzB2d/L3bECiG/23TKDssdVSacMe'],
      ['username' => 'kasir', 'password' => '$2y$10$q73OWarntzNSX8cxc25dF.uU4gS2IfpodLf4IsrocHR9R0HFu1XuG'],
    ];
    $this->db->table('users')->insertBatch($data);
  }

  private function seedTransactions()
  {
    // / Generate transaction data

    $now = new \DateTime();
    $transaction_code1 = sprintf("CAB01%s%02d", $now->format('dmyHi'), 1);
    $transaction_code2 = sprintf("CAB01%s%02d", $now->format('dmyHi'), 2);
    $transaction_code3 = sprintf("CAB01%s%02d", $now->format('dmyHi'), 3);
    $transaction_code4 = sprintf("CAB01%s%02d", $now->format('dmyHi'), 4);
    $transaction_code5 = sprintf("CAB01%s%02d", $now->format('dmyHi'), 5);

    $transactions = [
      [
        'transaction_code' => $transaction_code1,
        'user_id' => 1,
        'date_time' => $now->format('Y-m-d H:i:s'),
        'total_price' => 78500, // Random total harga
        'payment_method' => 'cash',
        'is_online_order' => false,
        'customer_name' => '',
        'customer_address' => '',
        'customer_phone' => '',
        'notes' => '',
        'created_at' => $now->format('Y-m-d H:i:s'),
      ],
      [
        'transaction_code' => $transaction_code2,
        'user_id' => 1,
        'date_time' => $now->format('Y-m-d H:i:s'),
        'total_price' => 120500, // Random total harga
        'payment_method' => 'cash',
        'is_online_order' => false,
        'customer_name' => '',
        'customer_address' => '',
        'customer_phone' => '',
        'notes' => '',
        'created_at' => $now->format('Y-m-d H:i:s'),
      ],
      [
        'transaction_code' => $transaction_code3,
        'user_id' => 1,
        'date_time' => $now->format('Y-m-d H:i:s'),
        'total_price' => 86000, // Random total harga
        'payment_method' => 'cash',
        'is_online_order' => false,
        'customer_name' => '',
        'customer_address' => '',
        'customer_phone' => '',
        'notes' => '',
        'created_at' => $now->format('Y-m-d H:i:s'),
      ],
      [
        'transaction_code' => $transaction_code4,
        'user_id' => 1,
        'date_time' => $now->format('Y-m-d H:i:s'),
        'total_price' => 25000, // Random total harga
        'payment_method' => 'cash',
        'is_online_order' => false,
        'customer_name' => '',
        'customer_address' => '',
        'customer_phone' => '',
        'notes' => '',
        'created_at' => $now->format('Y-m-d H:i:s'),
      ],
      [
        'transaction_code' => $transaction_code5,
        'user_id' => 1,
        'date_time' => $now->format('Y-m-d H:i:s'),
        'total_price' => 26500, // Random total harga
        'payment_method' => 'cash',
        'is_online_order' => false,
        'customer_name' => '',
        'customer_address' => '',
        'customer_phone' => '',
        'notes' => '',
        'created_at' => $now->format('Y-m-d H:i:s'),
      ]
    ];

    $this->db->table('transactions')->insertBatch($transactions);

    $details = [
      ['transaction_id' => 1, 'product_id' => 2, 'quantity' => 1, 'price' => 20000, 'subtotal' => 20000],
      ['transaction_id' => 1, 'product_id' => 4, 'quantity' => 2, 'price' => 8000, 'subtotal' => 16000],
      ['transaction_id' => 1, 'product_id' => 7, 'quantity' => 6, 'price' => 2500, 'subtotal' => 15000],

      ['transaction_id' => 2, 'product_id' => 3, 'quantity' => 3, 'price' => 35000, 'subtotal' => 105000],
      ['transaction_id' => 2, 'product_id' => 5, 'quantity' => 1, 'price' => 8000, 'subtotal' => 8000],
      ['transaction_id' => 2, 'product_id' => 7, 'quantity' => 4, 'price' => 2500, 'subtotal' => 10000],

      ['transaction_id' => 3, 'product_id' => 1, 'quantity' => 6, 'price' => 10000, 'subtotal' => 60000],
      ['transaction_id' => 3, 'product_id' => 6, 'quantity' => 3, 'price' => 8000, 'subtotal' => 24000],

      ['transaction_id' => 4, 'product_id' => 1, 'quantity' => 1, 'price' => 10000, 'subtotal' => 10000],
      ['transaction_id' => 4, 'product_id' => 7, 'quantity' => 6, 'price' => 2500, 'subtotal' => 15000],

      ['transaction_id' => 5, 'product_id' => 5, 'quantity' => 3, 'price' => 8000, 'subtotal' => 24000],
      ['transaction_id' => 5, 'product_id' => 7, 'quantity' => 3, 'price' => 2500, 'subtotal' => 7500],
    ];
    $this->db->table('transaction_details')->insertBatch($details);
  }
}
