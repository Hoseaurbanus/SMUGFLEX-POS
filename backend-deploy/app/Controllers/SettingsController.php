<?php

namespace App\Controllers;

use Database;
use Request;
use Response;
use AuthMiddleware;

class SettingsController
{
    public function company(): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $settings = $db->fetch("SELECT * FROM company_settings LIMIT 1");
        Response::success($settings);
    }

    public function updateCompany(): void
    {
        AuthMiddleware::authenticate();
        $body = Request::getBody();
        $db = Database::getInstance();

        $existing = $db->fetch("SELECT id FROM company_settings LIMIT 1");

        $data = [
            'name' => $body['name'] ?? null,
            'address' => $body['address'] ?? null,
            'phone' => $body['phone'] ?? null,
            'email' => $body['email'] ?? null,
            'website' => $body['website'] ?? null,
            'tax_number' => $body['tax_number'] ?? null,
            'logo' => $body['logo'] ?? null,
            'currency' => $body['currency'] ?? 'USD',
            'currency_symbol' => $body['currency_symbol'] ?? '$',
            'tax_rate' => $body['tax_rate'] ?? 0,
            'receipt_footer' => $body['receipt_footer'] ?? null,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $data = array_filter($data, fn($v) => $v !== null);

        if ($existing) {
            $db->update('company_settings', $data, 'id = ?', [$existing['id']]);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $db->insert('company_settings', $data);
        }

        $settings = $db->fetch("SELECT * FROM company_settings LIMIT 1");
        Response::success($settings, 'Settings updated');
    }
}
