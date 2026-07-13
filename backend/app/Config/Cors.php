<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Cors extends BaseConfig
{
    public $enabled = true;

    public $allowedOrigins = [
        'https://smugflex-pos-mrfb.vercel.app',
        'http://localhost:5173',
        'http://localhost:3000',
    ];

    public $allowedMethods = [
        'GET',
        'POST',
        'PUT',
        'DELETE',
        'OPTIONS',
    ];

    public $allowedHeaders = [
        'Content-Type',
        'Authorization',
        'X-Requested-With',
        'Accept',
        'Origin',
    ];

    public $exposedHeaders = [];

    public $maxAge = 86400;

    public $supportsCredentials = false;
}
