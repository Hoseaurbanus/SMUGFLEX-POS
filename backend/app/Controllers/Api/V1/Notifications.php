<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;

class Notifications extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $userId = get_user_id_from_token();
        $page = (int) ($this->request->getVar('page') ?? 1);
        $limit = (int) ($this->request->getVar('limit') ?? 20);
        $unreadOnly = $this->request->getVar('unread') ?? '';

        $builder = $this->db->table('notifications');
        $builder->where('user_id', $userId);

        if ($unreadOnly === '1' || $unreadOnly === 'true') {
            $builder->where('is_read', 0);
        }

        $total = $builder->countAllResults(false);
        $builder->orderBy('created_at', 'DESC');
        $builder->limit($limit, ($page - 1) * $limit);
        $notifications = $builder->get()->getResultArray();

        $unreadCount = $this->db->table('notifications')
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->countAllResults();

        return api_success([
            'items'        => $notifications,
            'unread_count' => $unreadCount,
            'pagination'   => [
                'total'          => $total,
                'per_page'       => $limit,
                'current_page'   => $page,
                'last_page'      => (int) ceil($total / $limit),
                'has_more'       => $page < (int) ceil($total / $limit),
            ],
        ]);
    }

    public function markRead($id = null)
    {
        $userId = get_user_id_from_token();

        $notification = $this->db->table('notifications')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->get()
            ->getRowArray();

        if (!$notification) {
            return api_error('Notification not found', 404);
        }

        $this->db->table('notifications')->where('id', $id)->update([
            'is_read'    => 1,
            'read_at'    => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return api_success(null, 'Notification marked as read');
    }

    public function markAllRead()
    {
        $userId = get_user_id_from_token();

        $this->db->table('notifications')
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->update([
                'is_read'    => 1,
                'read_at'    => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        return api_success(null, 'All notifications marked as read');
    }
}
