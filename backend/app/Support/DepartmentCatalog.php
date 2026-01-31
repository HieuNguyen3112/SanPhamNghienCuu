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
            ['faculty_code' => 'CNTT', 'code' => 'TT-DL', 'name' => 'Trung tâm Dữ liệu'],
            ['faculty_code' => 'TOANTIN', 'code' => 'BM-TT', 'name' => 'Bộ môn Toán – Tin học'],
        ];
    }
}
