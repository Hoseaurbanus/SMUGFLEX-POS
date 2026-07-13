<?php

namespace App\Controllers;

use Database;
use Response;
use AuthMiddleware;

class PermissionsController
{
    public function index(): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $permissions = $db->fetchAll("SELECT * FROM permissions ORDER BY module ASC, name ASC");
        Response::success($permissions);
    }
}
