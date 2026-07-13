<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;

class Categories extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $page = (int) ($this->request->getVar('page') ?? 1);
        $limit = (int) ($this->request->getVar('limit') ?? 15);
        $search = $this->request->getVar('search') ?? '';

        $builder = $this->db->table('categories');
        $builder->where('deleted_at', null);

        if ($search) {
            $builder->like('name', $search);
        }

        $total = $builder->countAllResults(false);
        $builder->orderBy('created_at', 'DESC');
        $builder->limit($limit, ($page - 1) * $limit);
        $categories = $builder->get()->getResultArray();

        return paginated_response($categories, $total, $page, $limit);
    }

    public function create()
    {
        $data = $this->getRequestData();

        $rules = [
            'name' => 'required|max_length[200]',
        ];

        if (!$this->validate($rules)) {
            return api_error('Validation failed', 422, $this->validator->getErrors());
        }

        $data['slug'] = slugify($data['name']);

        if (!empty($data['parent_id'])) {
            $parent = $this->db->table('categories')->where('id', $data['parent_id'])->where('deleted_at', null)->get()->getRowArray();
            if (!$parent) {
                return api_error('Parent category not found', 404);
            }
            $data['level'] = ($parent['level'] ?? 0) + 1;
        } else {
            $data['parent_id'] = null;
            $data['level'] = 0;
        }

        $id = $this->db->table('categories')->insert($data);

        if (!$id) {
            return api_error('Failed to create category', 500);
        }

        $category = $this->db->table('categories')->where('id', $id)->get()->getRowArray();

        return api_success($category, 'Category created', 201);
    }

    public function update($id = null)
    {
        $category = $this->db->table('categories')->where('id', $id)->where('deleted_at', null)->get()->getRowArray();

        if (!$category) {
            return api_error('Category not found', 404);
        }

        $data = $this->getRequestData();

        if (!empty($data['name'])) {
            $data['slug'] = slugify($data['name']);
        }

        if (!empty($data['parent_id'])) {
            if ($data['parent_id'] == $id) {
                return api_error('Category cannot be its own parent', 422);
            }
            $parent = $this->db->table('categories')->where('id', $data['parent_id'])->where('deleted_at', null)->get()->getRowArray();
            if (!$parent) {
                return api_error('Parent category not found', 404);
            }
            $data['level'] = ($parent['level'] ?? 0) + 1;
        }

        $this->db->table('categories')->where('id', $id)->update($data);

        $updated = $this->db->table('categories')->where('id', $id)->get()->getRowArray();

        return api_success($updated, 'Category updated');
    }

    public function delete($id = null)
    {
        $category = $this->db->table('categories')->where('id', $id)->where('deleted_at', null)->get()->getRowArray();

        if (!$category) {
            return api_error('Category not found', 404);
        }

        $children = $this->db->table('categories')->where('parent_id', $id)->where('deleted_at', null)->countAllResults();

        if ($children > 0) {
            return api_error('Cannot delete category with subcategories', 422);
        }

        $products = $this->db->table('products')->where('category_id', $id)->where('deleted_at', null)->countAllResults();

        if ($products > 0) {
            return api_error('Cannot delete category with associated products', 422);
        }

        $this->db->table('categories')->where('id', $id)->update(['deleted_at' => date('Y-m-d H:i:s')]);

        return api_success(null, 'Category deleted');
    }

    public function tree()
    {
        $categories = $this->db->table('categories')
            ->where('deleted_at', null)
            ->orderBy('name', 'ASC')
            ->get()
            ->getResultArray();

        $tree = $this->buildTree($categories);

        return api_success($tree);
    }

    private function buildTree(array $elements, ?int $parentId = null): array
    {
        $branch = [];

        foreach ($elements as $element) {
            if ($element['parent_id'] == $parentId) {
                $children = $this->buildTree($elements, $element['id']);
                if ($children) {
                    $element['children'] = $children;
                }
                $branch[] = $element;
            }
        }

        return $branch;
    }
}
