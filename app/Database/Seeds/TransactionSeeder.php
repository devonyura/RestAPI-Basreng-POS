<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TransactionSeeder extends Seeder
{
  public function run()
  {
    $db = \Config\Database::connect();
    $faker = \Faker\Factory::create('id_ID');

    $usernames = [
      1 => 'admin',
      2 => 'kasir',
      3 => 'ratna',
      4 => 'delta',
      5 => 'devon',
    ];

    // Daftar produk dengan harga tetap
    $products = [
      ['id' => 1, 'price' => 10000.00],
      ['id' => 2, 'price' => 20000.00],
      ['id' => 3, 'price' => 35000.00],
      ['id' => 4, 'price' => 8000.00],
      ['id' => 5, 'price' => 8000.00],
      ['id' => 6, 'price' => 8000.00],
      ['id' => 7, 'price' => 2500.00],
      ['id' => 8, 'price' => 10000.00],
      ['id' => 9, 'price' => 20000.00],
      ['id' => 10, 'price' => 35000.00],
      ['id' => 11, 'price' => 10000.00],
      ['id' => 12, 'price' => 20000.00],
      ['id' => 13, 'price' => 30000.00],
      ['id' => 14, 'price' => 120000.00],
      ['id' => 15, 'price' => 10000.00],
      ['id' => 16, 'price' => 2500.00],
      ['id' => 17, 'price' => 100000.00],
      ['id' => 18, 'price' => 200000.00],
      ['id' => 19, 'price' => 300000.00],
    ];

    $now = new \DateTime();

    for ($i = 0; $i < 93; $i++) {
      $day = (clone $now)->modify("-$i days");

      $transactionsPerDay = rand(3, 10);
      for ($j = 0; $j < $transactionsPerDay; $j++) {
        $user_id = rand(1, 4);
        $branch_id = rand(1, 2);
        $username = $usernames[$user_id];

        $time = clone $day;
        $time->setTime(rand(8, 20), rand(0, 59), rand(0, 59));
        $timestamp = $time->format('Y-m-d H:i:s');

        $code = $this->generateReceiptNumber($branch_id, $username, $time);

        $isOnline = rand(0, 1);

        $customerName = '';
        $customerAddress = '';
        $customerPhone = '';
        $notes = '';

        if ($isOnline) {
          $customerName = $faker->firstName();
          $customerAddress = $faker->address();
          $customerPhone = $faker->phoneNumber();
          $notes = "{$customerName}, {$customerAddress}, {$customerPhone}";
        }

        // Hitung detail
        $total_price = 0;
        $details = [];
        $detailsCount = rand(1, 5);

        for ($k = 0; $k < $detailsCount; $k++) {
          $product = $faker->randomElement($products);
          $quantity = rand(1, 3);
          $price = $product['price'];
          $subtotal = $price * $quantity;
          $total_price += $subtotal;

          $details[] = [
            'product_id' => $product['id'],
            'quantity' => $quantity,
            'price' => $price,
            'subtotal' => $subtotal,
          ];
        }

        $cash = $total_price;
        $change = 0;

        $transactionData = [
          'transaction_code' => $code,
          'user_id' => $user_id,
          'branch_id' => $branch_id,
          'date_time' => $timestamp,
          'total_price' => $total_price,
          'cash_amount' => $cash,
          'change_amount' => $change,
          'payment_method' => $faker->randomElement(['cash', 'qris', 'transfer_bank']),
          'is_online_order' => $isOnline,
          'customer_name' => $customerName,
          'customer_address' => $customerAddress,
          'customer_phone' => $customerPhone,
          'notes' => $notes,
          'created_at' => $timestamp,
        ];

        $db->table('transactions')->insert($transactionData);
        $transaction_id = $db->insertID();

        foreach ($details as $detail) {
          $db->table('transaction_details')->insert([
            'transaction_id' => $transaction_id,
            'product_id' => $detail['product_id'],
            'quantity' => $detail['quantity'],
            'price' => $detail['price'],
            'subtotal' => $detail['subtotal'],
            'created_at' => $timestamp,
          ]);
        }
      }
    }
  }

  private function generateReceiptNumber($branchID, $username, $date)
  {
    $ddmmyy = $date->format('dmy');
    $hhiiss = $date->format('His');
    return "C{$branchID}-{$ddmmyy}-{$hhiiss}-" . strtoupper($username);
  }
}
