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

  public function getDetailReport($date)
  {
    // Validasi tanggal
    if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
      return $this->failValidationErrors('Tanggal tidak valid. Gunakan format YYYY-MM-DD.');
    }

    $db = \Config\Database::connect();

    /**
     * Details Report
     */
    $detailsQuery = $db->query("
	SELECT 
		b.branch_id,
		b.branch_name,
		t.transaction_code,
		DATE(t.date_time) AS date,
		SUM(td.quantity) AS total_item,
		t.payment_method,
		t.is_online_order,
		t.total_price
	FROM transactions t
	JOIN transaction_details td ON t.id = td.transaction_id
	JOIN branch b ON t.branch_id = b.branch_id
	WHERE DATE(t.date_time) = ?
	GROUP BY 
		b.branch_id, 
		b.branch_name, 
		t.id, 
		t.transaction_code, 
		date, 
		t.payment_method, 
		t.is_online_order, 
		t.total_price
	ORDER BY 
		b.branch_id, 
		t.transaction_code
", [$date]);

    $results = $detailsQuery->getResult();

    $detailsFormatted = [];
    foreach ($results as $row) {
      $branchName = $row->branch_name;
      $detailsFormatted[$branchName][] = [
        'date'             => $row->date,
        'transaction_code' => $row->transaction_code,
        'total_item'       => $row->total_item,
        'payment_method'   => $row->payment_method,
        'is_online_order'  => $row->is_online_order,
        'total_price'      => $row->total_price
      ];
    }


    /**
     * Product Sells Report
     */
    $productSellsQuery = $db->query("
      SELECT 
        p.id AS product_id, 
        p.name AS product_name, 
        SUM(td.quantity) AS total_sold,
        SUM(td.subtotal) AS total_sales
      FROM transactions t
      JOIN transaction_details td ON t.id = td.transaction_id
      JOIN products p ON td.product_id = p.id
      WHERE DATE(t.date_time) = ?
      GROUP BY p.id, p.name
      ORDER BY total_sold DESC
    ", [$date]);

    /**
     * Branch Report
     */
    $branchQuery = $db->query("
      SELECT 
        b.branch_id, 
        b.branch_name, 
        COUNT(t.id) AS total_transactions, 
        SUM(t.total_price) AS total_sales
      FROM transactions t
      JOIN branch b ON t.branch_id = b.branch_id
      WHERE DATE(t.date_time) = ?
      GROUP BY b.branch_id, b.branch_name
      ORDER BY total_sales DESC
    ", [$date]);

    return $this->respond([
      'transactions_report'      => $detailsFormatted,
      'product_sells_report' => $productSellsQuery->getResult(),
      'branch_report'        => $branchQuery->getResult()
    ]);
  }



  public function getAllReports()
  {
    $day = $this->request->getGet('day');
    $month = $this->request->getGet('month');
    $year = $this->request->getGet('year');

    // Default day = 7 jika tidak ada day dan month
    if (!is_numeric($day) || $day <= 0) {
      $day = 7;
    }

    // Default year ke tahun sekarang jika kosong atau invalid
    if (!is_numeric($year) || $year < 1970) {
      $year = date('Y');
    }

    $monthCondition = '';
    if (is_numeric($month) && $month >= 1 && $month <= 12) {
      $monthNumber = (int)$month;
      $monthCondition = "MONTH(t.date_time) = {$monthNumber} AND YEAR(t.date_time) = {$year}";
    } else {
      $monthCondition = "t.date_time >= DATE_SUB(CURDATE(), INTERVAL {$day} DAY)";
    }

    $db = \Config\Database::connect();

    /**
     * Transactions Report
     */
    $transactionsQuery = $db->query("
		SELECT 
			b.branch_id,
			b.branch_name,
			DATE(t.date_time) AS date,
			COUNT(t.id) AS total_transactions,
			SUM(t.total_price) AS total_sales
		FROM transactions t
		JOIN branch b ON t.branch_id = b.branch_id
		WHERE {$monthCondition}
		GROUP BY b.branch_id, b.branch_name, DATE(t.date_time)
		ORDER BY DATE(t.date_time)
	");
    $transactions = $transactionsQuery->getResult();

    $transactionsFormatted = [];
    foreach ($transactions as $row) {
      $branchName = $row->branch_name;
      $transactionsFormatted[$branchName][] = [
        'date' => format_tanggal_lokal($row->date),
        'total_transactions' => $row->total_transactions,
        'total_sales' => $row->total_sales
      ];
    }

    /**
     * Product Sells Report (limit 6)
     */
    $productSellsQuery = $db->query("
		SELECT 
			p.id AS product_id, 
			p.name AS product_name, 
			SUM(td.quantity) AS total_sold,
			SUM(td.subtotal) AS total_sales
		FROM transactions t
		JOIN transaction_details td ON t.id = td.transaction_id
		JOIN products p ON td.product_id = p.id
		WHERE {$monthCondition}
		GROUP BY p.id, p.name
		ORDER BY total_sold DESC
	");

    /**
     * Branch Report
     */
    $branchQuery = $db->query("
		SELECT 
			b.branch_id, 
			b.branch_name, 
			COUNT(t.id) AS total_transactions, 
			SUM(t.total_price) AS total_sales
		FROM transactions t
		JOIN branch b ON t.branch_id = b.branch_id
		WHERE {$monthCondition}
		GROUP BY b.branch_id, b.branch_name
		ORDER BY total_sales DESC
	");

    return $this->respond([
      'transactions_report'  => $transactionsFormatted,
      'product_sells_report' => $productSellsQuery->getResult(),
      'branch_report'        => $branchQuery->getResult()
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
      ->get();

    return $this->respond([
      'status' => 'success',
      'data' => $query->getResult()
    ]);
  }
}
