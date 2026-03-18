<?php

namespace App\Support;

class FacultyCatalog
{
    /**
     * Canonical HCMUE faculties (Vietnamese with diacritics).
     *
     * NOTE: Keep codes stable and meaningful.
     */
    public static function all(): array
    {
        return [
            [
                ['code' => 'NGUVAN', 'name' => 'Khoa Ngữ văn'],
                ['code' => 'TOANTIN', 'name' => 'Khoa Toán - Tin học'],
                ['code' => 'CNTT', 'name' => 'Khoa Công nghệ Thông tin'],
                ['code' => 'VATLY', 'name' => 'Khoa Vật lý'],
                ['code' => 'HOAHOC', 'name' => 'Khoa Hóa học'],
                ['code' => 'SINHHOC', 'name' => 'Khoa Sinh học'],
                ['code' => 'LICHSU', 'name' => 'Khoa Lịch sử'],
                ['code' => 'DIALY', 'name' => 'Khoa Địa lý'],
                ['code' => 'TIENGANH', 'name' => 'Khoa Tiếng Anh'],
                ['code' => 'TIENGPHAP', 'name' => 'Khoa Tiếng Pháp'],
                ['code' => 'TIENGNGA', 'name' => 'Khoa Tiếng Nga'],
                ['code' => 'TIENGTRUNG', 'name' => 'Khoa Tiếng Trung'],
                ['code' => 'TIENGNHAT', 'name' => 'Khoa Tiếng Nhật'],
                ['code' => 'TIENGHAN', 'name' => 'Khoa Tiếng Hàn Quốc'],
                ['code' => 'GDCT', 'name' => 'Khoa Giáo dục Chính trị'],
                ['code' => 'TAMLY', 'name' => 'Khoa Tâm lý học'],
                ['code' => 'KHGD', 'name' => 'Khoa Khoa học Giáo dục'],
                ['code' => 'GDTIEUHOC', 'name' => 'Khoa Giáo dục Tiểu học'],
                ['code' => 'GDMN', 'name' => 'Khoa Giáo dục Mầm non'],
                ['code' => 'GDQP', 'name' => 'Khoa Giáo dục Quốc phòng'],
                ['code' => 'GDDB', 'name' => 'Khoa Giáo dục Đặc biệt'],
                ['code' => 'GDTC', 'name' => 'Khoa Giáo dục Thể chất'],
                ['code' => 'TONUCONG', 'name' => 'Tổ Nữ công'],
            ]
        ];
    }

    public static function byCode(): array
    {
        return collect(self::all())->keyBy('code')->all();
    }
}
