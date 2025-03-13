<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use Config\Services;

class PingController extends ResourceController
{
  public function index()
  {
    return Services::response()->setStatusCode(200);
  }
}
