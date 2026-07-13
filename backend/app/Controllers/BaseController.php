<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;

class BaseController extends ResourceController
{
    protected $format = 'json';

    public function __construct()
    {
        $this->format = 'json';
        parent::__construct();
    }

    protected function getRequestData(): array
    {
        $contentType = $this->request->getHeaderLine('Content-Type');

        if (strpos($contentType, 'application/json') !== false) {
            return $this->request->getJSON(true) ?? [];
        }

        return $this->request->getPost();
    }
}
