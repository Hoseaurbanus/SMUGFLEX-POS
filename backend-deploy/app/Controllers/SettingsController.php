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

        $settings = $db->fetch("SELECT * FROM company LIMIT 1");
        Response::success($settings);
    }

    public function updateCompany(): void
    {
        AuthMiddleware::authenticate();
        $body = Request::getBody();
        $db = Database::getInstance();

        $existing = $db->fetch("SELECT id FROM company LIMIT 1");

        $data = [
            'name' => $body['name'] ?? null,
            'slug' => $body['slug'] ?? null,
            'email' => $body['email'] ?? null,
            'phone' => $body['phone'] ?? null,
            'address' => $body['address'] ?? null,
            'city' => $body['city'] ?? null,
            'state' => $body['state'] ?? null,
            'country' => $body['country'] ?? null,
            'logo' => $body['logo'] ?? null,
            'tax_number' => $body['tax_number'] ?? null,
            'registration_number' => $body['registration_number'] ?? null,
            'website' => $body['website'] ?? null,
            'currency' => $body['currency'] ?? 'USD',
            'currency_symbol' => $body['currency_symbol'] ?? '$',
            'tax_rate' => $body['tax_rate'] ?? 0,
            'receipt_header' => $body['receipt_header'] ?? null,
            'receipt_footer' => $body['receipt_footer'] ?? null,
            'timezone' => $body['timezone'] ?? 'UTC',
            'date_format' => $body['date_format'] ?? 'Y-m-d',
            'time_format' => $body['time_format'] ?? 'H:i:s',
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $data = array_filter($data, fn($v) => $v !== null);

        if ($existing) {
            $db->update('company', $data, 'id = ?', [$existing['id']]);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $db->insert('company', $data);
        }

        $settings = $db->fetch("SELECT * FROM company LIMIT 1");
        Response::success($settings, 'Settings updated');
    }

    public function getGroup(string $group): void
    {
        AuthMiddleware::authenticate();
        $db = Database::getInstance();

        $settings = $db->fetchAll(
            "SELECT * FROM settings WHERE group_name = ?",
            [$group]
        );

        $result = [];
        foreach ($settings as $s) {
            $value = $s['setting_value'];
            if ($s['type'] === 'boolean') {
                $value = (bool)$value;
            } elseif ($s['type'] === 'integer') {
                $value = (int)$value;
            } elseif ($s['type'] === 'float') {
                $value = (float)$value;
            } elseif ($s['type'] === 'json') {
                $value = json_decode($value, true);
            }
            $result[$s['setting_key']] = $value;
        }

        Response::success($result);
    }

    public function updateGroup(string $group): void
    {
        AuthMiddleware::authenticate();
        $body = Request::getBody();
        $db = Database::getInstance();

        if (!$body || !is_array($body)) {
            Response::error('Settings data is required', 422);
        }

        foreach ($body as $key => $value) {
            $existing = $db->fetch(
                "SELECT id, type FROM settings WHERE group_name = ? AND setting_key = ?",
                [$group, $key]
            );

            if ($existing) {
                $settingValue = $value;
                if ($existing['type'] === 'json' && is_array($value)) {
                    $settingValue = json_encode($value);
                } elseif ($existing['type'] === 'boolean') {
                    $settingValue = $value ? '1' : '0';
                }

                $db->update('settings', [
                    'setting_value' => $settingValue,
                    'updated_at' => date('Y-m-d H:i:s'),
                ], 'id = ?', [$existing['id']]);
            } else {
                $settingValue = $value;
                if (is_array($value)) {
                    $settingValue = json_encode($value);
                    $type = 'json';
                } elseif (is_bool($value)) {
                    $settingValue = $value ? '1' : '0';
                    $type = 'boolean';
                } elseif (is_int($value)) {
                    $type = 'integer';
                } elseif (is_float($value)) {
                    $type = 'float';
                } else {
                    $type = 'string';
                }

                $db->insert('settings', [
                    'group_name' => $group,
                    'setting_key' => $key,
                    'setting_value' => $settingValue,
                    'type' => $type,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        Response::success(null, 'Settings updated');
    }
}
