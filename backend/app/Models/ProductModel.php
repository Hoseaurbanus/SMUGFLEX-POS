<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductModel extends Model
{
    protected $table = 'products';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'name', 'slug', 'sku', 'barcode', 'description',
        'category_id', 'brand_id', 'unit_id',
        'buying_price', 'selling_price', 'wholesale_price', 'minimum_price',
        'tax_rate', 'discount_rate', 'minimum_stock',
        'has_variants', 'has_serial', 'has_expiry',
        'image', 'status',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    protected $beforeInsert = ['generateSlug', 'generateSku'];
    protected $beforeUpdate = ['generateSlug'];

    protected function generateSlug(array $data): array
    {
        if (isset($data['data']['name']) && empty($data['data']['slug'])) {
            $data['data']['slug'] = slugify($data['data']['name']);
        }

        return $data;
    }

    protected function generateSku(array $data): array
    {
        if (empty($data['data']['sku'])) {
            $data['data']['sku'] = 'PRD-' . strtoupper(bin2hex(random_bytes(3)));
        }

        return $data;
    }

    public function getProductWithDetails(int $id): ?array
    {
        $builder = $this->db->table($this->table);
        $builder->select('products.*, categories.name as category_name, brands.name as brand_name, units.name as unit_name, units.short_name as unit_short_name');
        $builder->join('categories', 'categories.id = products.category_id', 'left');
        $builder->join('brands', 'brands.id = products.brand_id', 'left');
        $builder->join('units', 'units.id = products.unit_id', 'left');
        $builder->where('products.id', $id);

        return $builder->get()->getRowArray();
    }

    public function getProductsList(array $filters = []): array
    {
        $builder = $this->db->table($this->table);
        $builder->select('products.*, categories.name as category_name, brands.name as brand_name, units.name as unit_name, COALESCE(SUM(product_stocks.quantity), 0) as total_stock');
        $builder->join('categories', 'categories.id = products.category_id', 'left');
        $builder->join('brands', 'brands.id = products.brand_id', 'left');
        $builder->join('units', 'units.id = products.unit_id', 'left');
        $builder->join('product_stocks', 'product_stocks.product_id = products.id', 'left');
        $builder->where('products.deleted_at', null);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $builder->groupStart();
            $builder->like('products.name', $search);
            $builder->orLike('products.sku', $search);
            $builder->orLike('products.barcode', $search);
            $builder->groupEnd();
        }

        if (!empty($filters['category_id'])) {
            $builder->where('products.category_id', $filters['category_id']);
        }

        if (!empty($filters['brand_id'])) {
            $builder->where('products.brand_id', $filters['brand_id']);
        }

        if (!empty($filters['status'])) {
            $builder->where('products.status', $filters['status']);
        }

        $builder->groupBy('products.id');
        $builder->orderBy('products.created_at', 'DESC');

        return $builder->get()->getResultArray();
    }

    public function searchByBarcode(string $barcode): ?array
    {
        $builder = $this->db->table($this->table);
        $builder->select('products.*, COALESCE(SUM(product_stocks.quantity), 0) as total_stock');
        $builder->join('product_stocks', 'product_stocks.product_id = products.id', 'left');
        $builder->where('products.barcode', $barcode);
        $builder->groupBy('products.id');

        return $builder->get()->getRowArray();
    }
}
