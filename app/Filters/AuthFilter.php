<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Config\Services;
use App\Helpers\JwtHelper;
use Exception;

class AuthFilter implements FilterInterface
{
  public function before(RequestInterface $request, $arguments = null)
  {
    $header = $request->getServer('HTTP_AUTHORIZATION');

    if (!$header) {
      $request->getHeaderLine("Authorization");
    }

    if (!$header) {
      return service('response')->setJSON(['error' => 'Token tidak ditemukan'])->setStatusCode(401);
    }

    $token = explode(' ', $header)[1];
    $JwtHelper = new JwtHelper();
    $user = $JwtHelper->validateJWT($token);

    if (!$user) {
      return service('response')->setJSON(['error' => 'Token tidak valid'])->setStatusCode(401);
    }

    // $request->setGlobal('user', $user);
    // // Routes yang dikecualikan dari JWT Auth
    // $excludedRoutes = ['api/auth/login', 'api/auth/register'];

    // // Ambil URI saat ini
    // $currentURI = service('request')->getPath();

    // // Jika route termasuk dalam pengecualian, tidak perlu validasi JWT
    // if (in_array($currentURI, $excludedRoutes)) {
    //   return;
    // }

    // // Validasi token JWT // Ambil header Authorization
    // $authHeader = $request->getServer('HTTP_AUTHORIZATION');

    // if (!$authHeader) {
    //   $request->getHeaderLine("Authorization");
    // }

    // if (!$authHeader) {
    //   return Services::response()
    //     ->setJSON([
    //       'status' => 'error',
    //       'message' => 'Token tidak ditemukan. Silakan login untuk mendapatkan token.'
    //     ])
    //     ->setStatusCode(401);
    // }

    // log_message('debug', 'Token diterima:' . $authHeader);

    // // Ambil nilai token dari header
    // $tokenParts = explode(' ', $authHeader);


    // // Pastikan formatnya "Bearer <token>"
    // if (count($tokenParts) !== 2 || strtolower($tokenParts[0]) !== 'bearer') {
    //   return Services::response()
    //     ->setJSON([
    //       'status' => 'error',
    //       'message' => 'Format token tidak valid. Gunakan format "Bearer <token>".'
    //     ])
    //     ->setStatusCode(401);
    // }

    // $token = $tokenParts[1];

    // // return Services::response()
    // //   ->setJSON([
    // //     'status' => 'error',
    // //     'message' => 'token tidak valid /kadluarsa',
    // //     'token'  => $token,
    // //   ])
    // //   ->setStatusCode(401);

    // try {
    //   // $jwtHelper = new JwtHelper();
    //   // $jwtHelper->validateJWT($token);
    // } catch (Exception) {

    //   return Services::response()
    //     ->setJSON([
    //       'status' => 'error',
    //       'message' => 'token tidak valid /kadluarsa'
    //     ])
    //     ->setStatusCode(401);
    // }
  }

  public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
  {
    // Tidak digunakan
  }
}
