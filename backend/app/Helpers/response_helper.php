<?php

if (!function_exists('api_success')) {
    function api_success($data = null, string $message = 'Success', int $code = 200)
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return service('response')->setJSON($response)->setStatusCode($code);
    }
}

if (!function_exists('api_error')) {
    function api_error(string $message = 'Error', int $code = 400, $errors = null)
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return service('response')->setJSON($response)->setStatusCode($code);
    }
}

if (!function_exists('paginated_response')) {
    function paginated_response($data, int $total, int $page, int $perPage): object
    {
        $lastPage = (int) ceil($total / $perPage);

        return api_success([
            'items' => $data,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => $lastPage,
                'has_more' => $page < $lastPage,
            ],
        ]);
    }
}
