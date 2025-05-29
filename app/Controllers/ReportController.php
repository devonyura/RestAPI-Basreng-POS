<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use Exception;

class ReportController extends ResourceController
{

  public function getTransactionsReport($day = 7)
  {
    $db = \Config\Database::connect();

    // $day -= 1;

    // Ambil parameter 'day' dari query string, default 7 jika tidak ada
    // $days = $this->request->getGet('day');
    // if (!is_numeric($days) || $days <= 0) {
    //   $days = 7;
    // }

    // Query laporan penjualan berdasarkan jumlah hari
    $query = $db->query("
        SELECT DATE(date_time) AS date, SUM(total_price) AS total_sales
        FROM transactions
        WHERE date_time >= DATE_SUB(CURDATE(), INTERVAL {$day} DAY)
        GROUP BY DATE(date_time)
        ORDER BY DATE(date_time)
      ");

    return $this->respond($query->getResult());
  }

  public function getProductSellsReport($day = 2)
  {
    $db = \Config\Database::connect();
    // $day -= 1;

    $query = $db->query("
		SELECT p.id AS product_id, p.name AS product_name, SUM(td.quantity) AS total_sold
		FROM transactions t
		JOIN transaction_details td ON t.id = td.transaction_id
		JOIN products p ON td.product_id = p.id
		WHERE t.date_time >= DATE_SUB(CURDATE(), INTERVAL {$day} DAY)
		GROUP BY p.id, p.name
		ORDER BY total_sold DESC
	");

    return $this->respond($query->getResult());
  }

  public function getBranchReport($day = 0)
  {
    $db = \Config\Database::connect();
    // $day -= 1;

    $query = $db->query("
		SELECT b.branch_id, b.branch_name, COUNT(t.id) AS total_transactions, SUM(t.total_price) AS total_income
		FROM transactions t
		JOIN branch b ON t.branch_id = b.branch_id
		WHERE t.date_time >= DATE_SUB(CURDATE(), INTERVAL {$day} DAY)
		GROUP BY b.branch_id, b.branch_name
		ORDER BY total_income DESC
	");

    return $this->respond($query->getResult());
  }


  public function getAllReports()
  {



    $day = $this->request->getGet('day');
    if (!is_numeric($day) || $day <= 0) {
      $day = 7;
    }
    // $day -= 1;

    $db = \Config\Database::connect();

    // Transactions Report
    $transactionsQuery = $db->query("
      SELECT DATE(t.date_time) AS date, SUM(t.total_price) AS total_sales
      FROM transactions t
      WHERE t.date_time >= DATE_SUB(CURDATE(), INTERVAL {$day} DAY)
      GROUP BY DATE(t.date_time)
      ORDER BY DATE(t.date_time)
    ");
    $transactions = $transactionsQuery->getResult();

    // Format tanggal ke lokal
    $transactionsFormatted = array_map(function ($row) {
      return [
        'date' => format_tanggal_lokal($row->date),
        'total_sales' => $row->total_sales
      ];
    }, $transactions);

    // Product Sells Report
    $productSellsQuery = $db->query("
      SELECT p.id AS product_id, p.name AS product_name, SUM(td.quantity) AS total_sold
      FROM transactions t
      JOIN transaction_details td ON t.id = td.transaction_id
      JOIN products p ON td.product_id = p.id
      WHERE t.date_time >= DATE_SUB(CURDATE(), INTERVAL {$day} DAY)
      GROUP BY p.id, p.name
      ORDER BY total_sold DESC
    ");

    // Branch Report
    $branchQuery = $db->query("
      SELECT b.branch_id, b.branch_name, COUNT(t.id) AS total_transactions, SUM(t.total_price) AS total_sales
      FROM transactions t
      JOIN branch b ON t.branch_id = b.branch_id
      WHERE t.date_time >= DATE_SUB(CURDATE(), INTERVAL {$day} DAY)
      GROUP BY b.branch_id, b.branch_name
      ORDER BY total_sales DESC
    ");

    return $this->respond([
      'transactions_report'  => $transactionsFormatted,
      'product_sells_report'  => $productSellsQuery->getResult(),
      'branch_report'      => $branchQuery->getResult()
    ]);
  }


  // GET /api/report/summary
  public function summary()
  {
    try {
      $db = \Config\Database::connect();

      // Hari ini
      $today = date('Y-m-d');
      $builder = $db->table('transactions');
      $todaySales = $builder->selectSum('total_price')
        ->where('DATE(date_time)', $today)
        ->get()->getRow()->total_price ?? 0;
      $todayCount = $builder->selectCount('id')
        ->where('DATE(date_time)', $today)
        ->get()->getRow()->id ?? 0;

      // Minggu ini (Senin s/d hari ini)
      $monday = date('Y-m-d', strtotime('monday this week'));
      $weekSales = $builder->selectSum('total_price')
        ->where("DATE(date_time) BETWEEN '$monday' AND '$today'")
        ->get()->getRow()->total_price ?? 0;

      // Bulan ini
      $monthStart = date('Y-m-01');
      $monthSales = $builder->selectSum('total_price')
        ->where("DATE(date_time) BETWEEN '$monthStart' AND '$today'")
        ->get()->getRow()->total_price ?? 0;

      return $this->respond([
        'status' => 'success',
        'data' => [
          'hari_ini' => (int)$todaySales,
          'minggu_ini' => (int)$weekSales,
          'bulan_ini' => (int)$monthSales,
          'jumlah_transaksi_hari_ini' => (int)$todayCount,
        ]
      ]);
    } catch (Exception $e) {
      return $this->failServerError($e->getMessage());
    }
  }

  // GET /api/report/top-selling
  public function topSelling($day = 0)
  {
    $db = \Config\Database::connect();

    // Hitung tanggal batas bawah berdasarkan $day
    $dateThreshold = date('Y-m-d', strtotime("-$day days"));

    $query = $db->table('transaction_details td')
      ->select('p.name, SUM(td.quantity) as total_sold')
      ->join('products p', 'p.id = td.product_id')
      ->join('transactions t', 't.id = td.transaction_id')
      ->where('t.date_time >=', $dateThreshold) // Filter berdasarkan tanggal
      ->groupBy('td.product_id')
      ->orderBy('total_sold', 'DESC')
      ->limit(5)
      ->get();

    return $this->respond([
      'status' => 'success',
      'data' => $query->getResult()
    ]);
  }
}
