<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;

class Settings extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function company()
    {
        $setting = $this->db->table('settings')->where('key', 'company')->get()->getRowArray();

        if (!$setting) {
            return api_success((object) []);
        }

        $value = json_decode($setting['value'], true);

        return api_success($value);
    }

    public function index()
    {
        $settings = $this->db->table('settings')->get()->getResultArray();

        $result = [];
        foreach ($settings as $setting) {
            $result[$setting['key']] = json_decode($setting['value'], true);
        }

        return api_success($result);
    }

    public function updateCompany()
    {
        $data = $this->getRequestData();

        $rules = [
            'name'    => 'required|max_length[200]',
            'address' => 'permit_empty|max_length[500]',
            'phone'   => 'permit_empty|max_length[20]',
            'email'   => 'permit_empty|valid_email',
        ];

        if (!$this->validate($rules)) {
            return api_error('Validation failed', 422, $this->validator->getErrors());
        }

        $existing = $this->db->table('settings')->where('key', 'company')->get()->getRowArray();

        if ($existing) {
            $existingValue = json_decode($existing['value'], true);
            $merged = array_merge($existingValue, $data);
            $this->db->table('settings')->where('key', 'company')->update([
                'value'      => json_encode($merged),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        } else {
            $this->db->table('settings')->insert([
                'key'        => 'company',
                'value'      => json_encode($data),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $updated = $this->db->table('settings')->where('key', 'company')->get()->getRowArray();

        return api_success(json_decode($updated['value'], true), 'Company profile updated');
    }

    public function update()
    {
        $data = $this->getRequestData();

        $this->db->transStart();

        foreach ($data as $key => $value) {
            $existing = $this->db->table('settings')->where('key', $key)->get()->getRowArray();

            $encodedValue = is_string($value) ? $value : json_encode($value);

            if ($existing) {
                $this->db->table('settings')->where('key', $key)->update([
                    'value'      => $encodedValue,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            } else {
                $this->db->table('settings')->insert([
                    'key'        => $key,
                    'value'      => $encodedValue,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return api_error('Failed to update settings', 500);
        }

        return api_success(null, 'Settings updated');
    }

    public function backup()
    {
        $dbConfig = $this->db->getConfig();
        $backupDir = WRITEPATH . 'backups';

        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $tables = $this->db->listTables();
        $filename = 'backup_' . date('Y-m-d_His') . '.sql';
        $filepath = $backupDir . DIRECTORY_SEPARATOR . $filename;

        $sql = "-- SmugFlex POS Database Backup\n";
        $sql .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";

        foreach ($tables as $table) {
            if (strpos($table, 'migrations') !== false || strpos($table, 'cache') !== false) {
                continue;
            }

            $rows = $this->db->table($table)->get()->getResultArray();

            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";

            $createTable = $this->db->query("SHOW CREATE TABLE `{$table}`")->getRowArray();
            if ($createTable) {
                $sql .= $createTable['Create Table'] . ";\n\n";
            }

            foreach ($rows as $row) {
                $values = array_map(function ($v) {
                    return $v === null ? 'NULL' : "'" . addslashes($v) . "'";
                }, $row);

                $sql .= "INSERT INTO `{$table}` VALUES (" . implode(', ', $values) . ");\n";
            }

            $sql .= "\n\n";
        }

        file_put_contents($filepath, $sql);

        $this->db->table('backups')->insert([
            'filename'   => $filename,
            'filepath'   => $filepath,
            'size'       => filesize($filepath),
            'created_by' => get_user_id_from_token(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return api_success([
            'filename' => $filename,
            'size'     => filesize($filepath),
        ], 'Backup created successfully');
    }

    public function backups()
    {
        $backups = $this->db->table('backups')
            ->select('backups.*, users.first_name, users.last_name')
            ->join('users', 'users.id = backups.created_by', 'left')
            ->orderBy('backups.created_at', 'DESC')
            ->get()
            ->getResultArray();

        return api_success($backups);
    }

    public function restore($id = null)
    {
        $backup = $this->db->table('backups')->where('id', $id)->get()->getRowArray();

        if (!$backup) {
            return api_error('Backup not found', 404);
        }

        if (!file_exists($backup['filepath'])) {
            return api_error('Backup file not found', 404);
        }

        $sql = file_get_contents($backup['filepath']);

        $this->db->transStart();

        $statements = array_filter(array_map('trim', explode(';', $sql)));

        foreach ($statements as $statement) {
            if (!empty($statement) && $statement !== '--') {
                $this->db->query($statement);
            }
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return api_error('Failed to restore backup', 500);
        }

        return api_success(null, 'Database restored successfully');
    }

    public function uploadLogo()
    {
        $file = $this->request->getFile('logo');

        if (!$file || !$file->isValid()) {
            return api_error('No file uploaded or invalid file', 422);
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml'];

        if (!in_array($file->getMimeType(), $allowedMimes)) {
            return api_error('Invalid file type. Allowed: jpg, png, gif, svg', 422);
        }

        $maxSize = 2 * 1024 * 1024;
        if ($file->getSize() > $maxSize) {
            return api_error('File size must be less than 2MB', 422);
        }

        $newName = 'logo_' . time() . '.' . $file->getExtension();
        $file->move(ROOTPATH . 'public/uploads/logos', $newName);

        $logoPath = 'uploads/logos/' . $newName;

        $existing = $this->db->table('settings')->where('key', 'company')->get()->getRowArray();

        if ($existing) {
            $value = json_decode($existing['value'], true);
            $value['logo'] = $logoPath;
            $this->db->table('settings')->where('key', 'company')->update([
                'value'      => json_encode($value),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        } else {
            $this->db->table('settings')->insert([
                'key'        => 'company',
                'value'      => json_encode(['logo' => $logoPath]),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return api_success(['logo' => $logoPath], 'Logo uploaded successfully');
    }
}
