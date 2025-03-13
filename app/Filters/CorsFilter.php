<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class CorsFilter implements FilterInterface
{
  public function before(RequestInterface $request, $arguments = null)
  {
    // Allowed origins (sesuaikan dengan domain React kamu)
		$allowedOrigins = ['http://localhost:8100', 'https://app.rindapermai.com'];

		$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

		// Cek apakah origin diizinkan
		if (in_array($origin, $allowedOrigins)) {
			header("Access-Control-Allow-Origin: $origin");
			header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
			header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
			header("Access-Control-Allow-Credentials: true");
		}

		// Handle request preflight (OPTIONS)
		if ($request->getMethod() === 'options') {
			header("HTTP/1.1 200 OK");
			exit();
		}
  }

  public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
  {
    return $response;
  }
}
