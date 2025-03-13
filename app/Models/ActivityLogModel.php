<?php

namespace App\Models;

use CodeIgniter\Model;

class ActivityLogModel extends Model
{
    protected $table = 'activity_logs';
    protected $primaryKey = 'id';
    protected $allowedFields = ['user_id', 'username', 'action', 'ip_address', 'user_agent', 'details'];
    protected $useTimestamps = false; // Karena sudah ada timestamp di database

    public function logActivity($userId, $username, $action, $details = null)
    {
        $this->insert([
            'user_id'    => $userId,
            'username'   => $username,
            'action'     => $action,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN',
            'details'    => is_array($details) ? json_encode($details) : $details
        ]);
    }
}
