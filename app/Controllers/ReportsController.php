<?php

namespace App\Controllers;

use App\Models\SalesReportModel;
use App\Models\ActivityLogModel;
use App\Helpers\JwtHelper;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use Exception;

class ReportsController extends ResourceController
{
  protected $format = 'json';

  private function createLog($action, $details = null)
  {
    $jwtHelper = new JwtHelper();
    $logModel  = new ActivityLogModel();
    $request   = service('request');
    $authHeader = $request->getHeaderLine('Authorization');

    if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
      $token   = $matches[1];
      $decoded = $jwtHelper->validateJWT($token);
      if ($decoded) {
        $logModel->logActivity($decoded['id'], $decoded['username'], $action, $details);
      }
    }
  }

  // GET /reports/sales
  public function sales()
  {
    try {
      $salesReportModel = new SalesReportModel();
      $data = $salesReportModel->findAll();
      if (empty($data)) {
        $this->createLog('SALES_REPORT', 'Tidak ada data laporan penjualan.');
        return $this->failNotFound('Tidak ada data laporan penjualan.');
      }
      $this->createLog('SALES_REPORT', ['SUCCESS']);
      return $this->respond([
        'status' => 'success',
        'data'   => $data
      ]);
    } catch (Exception $e) {
      $this->createLog('SALES_REPORT', ['ERROR']);
      return Services::response()
        ->setJSON([
          'status'  => 'error',
          'message' => 'Terjadi kesalahan pada server.',
          'error'   => $e->getMessage()
        ])
        ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  // GET /reports/charts
  public function charts()
  {
    try {
      // Logika untuk mengambil data grafik penjualan dapat disesuaikan.
      // Contoh: data dummy grafik
      $chartData = [
        'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'Mei'],
        'data'   => [1000, 1500, 1200, 1800, 2000]
      ];
      $this->createLog('SALES_CHARTS', ['SUCCESS']);
      return $this->respond([
        'status' => 'success',
        'data'   => $chartData
      ]);
    } catch (Exception $e) {
      $this->createLog('SALES_CHARTS', ['ERROR']);
      return Services::response()
        ->setJSON([
          'status'  => 'error',
          'message' => 'Terjadi kesalahan pada server.',
          'error'   => $e->getMessage()
        ])
        ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  // GET /reports/export
  // public function export()
  // {
  //   try {
  //     // Implementasi export PDF bisa menggunakan library seperti TCPDF atau Dompdf.
  //     // Berikut contoh respon dummy.
  //     $this->createLog('EXPORT_PDF', ['SUCCESS']);
  //     return $this->respond([
  //       'status'  => 'success',
  //       'message' => 'Fungsi export PDF belum diimplementasikan.'
  //     ]);
  //   } catch (Exception $e) {
  //     $this->createLog('EXPORT_PDF', ['ERROR']);
  //     return Services::response()
  //       ->setJSON([
  //         'status'  => 'error',
  //         'message' => 'Terjadi kesalahan pada server.',
  //         'error'   => $e->getMessage()
  //       ])
  //       ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
  //   }
  // }
}
