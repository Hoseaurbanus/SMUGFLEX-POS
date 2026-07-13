<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Database extends BaseConfig
{
    public $defaultGroup = 'default';

    public $queries = [];

    public $active = 1;

    public $default = [
        'hostname' => 'localhost',
        'username' => 'root',
        'password' => '',
        'database' => 'smugflex_pos',
        'DBDriver' => 'MySQLi',
        'DBPrefix' => '',
        'pConnect' => false,
        'DBDebug' => (ENVIRONMENT === 'development'),
        'cacheOn' => false,
        'cacheDir' => '',
        'charset' => 'utf8mb4',
        'DBCollat' => 'utf8mb4_unicode_ci',
        'swapPre' => '',
        'encrypt' => false,
        'compress' => false,
        'strictOn' => false,
        'failover' => [],
        'port' => 3306,
    ];
}
