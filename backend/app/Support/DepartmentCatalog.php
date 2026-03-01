<?php

namespace App\Support;

class DepartmentCatalog
{
    /**
     * Minimal department list used by seeders (scoped to existing CNTT/TOANTIN demo data).
     */
    public static function all(): array
    {
        return [
            ['faculty_code' => 'CNTT', 'code' => 'BM-KTPM', 'name' => 'Bộ môn Kỹ thuật Phần mềm'],
            ['faculty_code' => 'CNTT', 'code' => 'BM-KHMT', 'name' => 'Bộ môn Khoa học Máy tính'],
            ['faculty_code' => 'CNTT', 'code' => 'BM-HTTT', 'name' => 'Bộ môn Hệ thống Thông tin'],
            ['faculty_code' => 'CNTT', 'code' => 'TT-DL', 'name' => 'Trung tâm Dữ liệu và Trí tuệ nhân tạo'],
            ['faculty_code' => 'TOANTIN', 'code' => 'BM-TT', 'name' => 'Bộ môn Toán – Tin ứng dụng'],
            ['faculty_code' => 'TOANTIN', 'code' => 'BM-PTTK', 'name' => 'Bộ môn Phương pháp Toán trong Tin học'],
        ];
    }
}
