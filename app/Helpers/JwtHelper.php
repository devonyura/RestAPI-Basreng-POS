<?php

namespace App\Helpers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Config\JWT as JWTConfig;
use Exception;
use Config\Services;

class JwtHelper
{
  function generateJWT($data)
  {
    $issuedAt = time();
    $expireAt = $issuedAt + JWTConfig::$tokenExpiry;

    $payload = [
      'iat' => $issuedAt,
      'exp' => $expireAt,
      'data' => $data
    ];

    return JWT::encode($payload, JWTConfig::$secretKey, JWTConfig::$algorithm);
  }

  function validateJWT($token)
  {

    try {
      $decoded = JWT::decode($token, new Key(JWTConfig::$secretKey, JWTConfig::$algorithm));
      return (array) $decoded->data;
    } catch (Exception $e) {
      return false;
    }
    // $isToken = explode('.', $token);

    // return Services::response()
    //   ->setJSON([
    //     'status' => 'error',
    //     'message' => 'token tidak valid /kadluarsa',
    //     'token'  => $isToken,
    //   ])
    //   ->setStatusCode(401);

    // try {
    //   $decoded = JWT::decode($token, new Key(JWTConfig::$secretKey, JWTConfig::$algorithm));

    //   if (!isset($decoded->user_id)) {
    //     return Services::response()
    //       ->setJSON([
    //         'status' => 'error',
    //         'message' => 'Token tidak valid'
    //       ])
    //       ->setStatusCode(401);
    //   }

    //   return (array) $decoded->data;
    // } catch (Exception $e) {
    //   return false;
    // }

  }
}
