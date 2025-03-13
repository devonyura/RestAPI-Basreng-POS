<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\ActivityLogModel;

class LogController extends ResourceController
{
    protected $model;

    public function __construct()
    {
        $this->model = new ActivityLogModel();
    }

    public function index()
    {
        $logs = $this->model->orderBy('timestamp', 'DESC')->findAll();
        return $this->respond($logs);
    }
}
