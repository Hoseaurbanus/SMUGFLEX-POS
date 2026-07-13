<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Models\ProductModel;

class Products extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new ProductModel();
    }

    public function index()
    {
        $page = (int) ($this->request->getVar('page') ?? 1);
        $limit = (int) ($this->request->getVar('limit') ?? 15);
        $offset = ($page - 1) * $limit;

        $filters = [
            'search'     => $this->request->getVar('search') ?? '',
            'category_id' => $this->request->getVar('category_id') ?? '',
            'brand_id'   => $this->request->getVar('brand_id') ?? '',
            'status'     => $this->request->getVar('status') ?? '',
        ];

        $products = $this->model->getProductsList($filters);
        $total = count($products);
        $products = array_slice($products, $offset, $limit);

        return paginated_response($products, $total, $page, $limit);
    }

    public function create()
    {
        $data = $this->getRequestData();

        $rules = [
            'name'           => 'required|max_length[200]',
            'selling_price'  => 'required|decimal',
            'buying_price'   => 'permit_empty|decimal',
        ];

        if (!$this->validate($rules)) {
            return api_error('Validation failed', 422, $this->validator->getErrors());
        }

        $id = $this->model->insert($data);

        if (!$id) {
            return api_error('Failed to create product', 500);
        }

        return api_success($this->model->find($id), 'Product created', 201);
    }

    public function show($id = null)
    {
        $product = $this->model->getProductWithDetails($id);

        if (!$product) {
            return api_error('Product not found', 404);
        }

        return api_success($product);
    }

    public function update($id = null)
    {
        $product = $this->model->find($id);

        if (!$product) {
            return api_error('Product not found', 404);
        }

        $data = $this->getRequestData();

        if (!$this->model->update($id, $data)) {
            return api_error('Failed to update product', 500);
        }

        return api_success($this->model->getProductWithDetails($id), 'Product updated');
    }

    public function delete($id = null)
    {
        $product = $this->model->find($id);

        if (!$product) {
            return api_error('Product not found', 404);
        }

        if (!$this->model->delete($id)) {
            return api_error('Failed to delete product', 500);
        }

        return api_success(null, 'Product deleted');
    }

    public function stockHistory($id = null)
    {
        $db = \Config\Database::connect();

        $movements = $db->table('stock_movements')
            ->select('stock_movements.*, users.first_name, users.last_name, warehouses.name as warehouse_name')
            ->join('users', 'users.id = stock_movements.user_id', 'left')
            ->join('warehouses', 'warehouses.id = stock_movements.warehouse_id', 'left')
            ->where('stock_movements.product_id', $id)
            ->orderBy('stock_movements.created_at', 'DESC')
            ->limit(50)
            ->get()
            ->getResultArray();

        return api_success($movements);
    }

    public function barcodeLookup($barcode = null)
    {
        $product = $this->model->searchByBarcode($barcode);

        if (!$product) {
            return api_error('Product not found', 404);
        }

        return api_success($product);
    }

    public function import()
    {
        return api_success(null, 'Import feature - upload CSV to import products');
    }

    public function addVariant($id = null)
    {
        $product = $this->model->find($id);

        if (!$product) {
            return api_error('Product not found', 404);
        }

        $data = $this->getRequestData();
        $data['product_id'] = $id;

        $db = \Config\Database::connect();
        $db->table('product_variants')->insert($data);

        return api_success(null, 'Variant added', 201);
    }

    public function updateVariant($id = null)
    {
        $db = \Config\Database::connect();
        $db->table('product_variants')->where('id', $id)->update($this->getRequestData());

        return api_success(null, 'Variant updated');
    }

    public function deleteVariant($id = null)
    {
        $db = \Config\Database::connect();
        $db->table('product_variants')->where('id', $id)->delete();

        return api_success(null, 'Variant deleted');
    }
}
