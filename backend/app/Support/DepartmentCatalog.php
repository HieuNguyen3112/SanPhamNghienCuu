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
            ['faculty_code' => 'NGUVAN', 'code' => 'BM-VHVN', 'name' => 'Bộ môn Văn học Việt Nam'],
            ['faculty_code' => 'NGUVAN', 'code' => 'BM-NGONNGU', 'name' => 'Bộ môn Ngôn ngữ'],
            ['faculty_code' => 'NGUVAN', 'code' => 'BM-HANNOM', 'name' => 'Bộ môn Hán Nôm'],
            ['faculty_code' => 'NGUVAN', 'code' => 'BM-VHNN', 'name' => 'Bộ môn Văn học nước ngoài'],
            ['faculty_code' => 'NGUVAN', 'code' => 'BM-PPDHNGUVAN', 'name' => 'Bộ môn Lý luận và Phương pháp dạy học Ngữ văn'],
            ['faculty_code' => 'NGUVAN', 'code' => 'BM-VIETNAMHOC', 'name' => 'Bộ môn Việt Nam học'],
            ['faculty_code' => 'NGUVAN', 'code' => 'BM-LLVH', 'name' => 'Bộ môn Lý luận văn học'],

            ['faculty_code' => 'TOANTIN', 'code' => 'BM-TOANTIN', 'name' => 'Bộ môn Khoa Toán - Tin học'],
            ['faculty_code' => 'TOANTIN', 'code' => 'BM-GIAITICH', 'name' => 'Bộ môn Giải tích'],
            ['faculty_code' => 'TOANTIN', 'code' => 'BM-DAISO', 'name' => 'Bộ môn Đại số'],
            ['faculty_code' => 'TOANTIN', 'code' => 'BM-HINHHOC', 'name' => 'Bộ môn Hình học'],
            ['faculty_code' => 'TOANTIN', 'code' => 'BM-GIAOVU', 'name' => 'Bộ môn Giáo vụ'],
            ['faculty_code' => 'TOANTIN', 'code' => 'BM-TOANUD', 'name' => 'Bộ môn Toán ứng dụng'],
            ['faculty_code' => 'TOANTIN', 'code' => 'BM-PPDHTOAN', 'name' => 'Bộ môn Lý luận và Phương pháp dạy học Toán'],

            ['faculty_code' => 'CNTT', 'code' => 'BM-PPGDTIN', 'name' => 'Bộ môn Phương pháp Giảng dạy Tin học'],
            ['faculty_code' => 'CNTT', 'code' => 'BM-KHMT', 'name' => 'Bộ môn Khoa học Máy tính'],
            ['faculty_code' => 'CNTT', 'code' => 'BM-HTTTMTT', 'name' => 'Bộ môn Hệ thống Thông tin và Mạng truyền thông'],
            ['faculty_code' => 'CNTT', 'code' => 'BM-CNGD', 'name' => 'Bộ môn Công nghệ Giáo dục'],
            ['faculty_code' => 'CNTT', 'code' => 'BM-GIAOVU', 'name' => 'Bộ môn Giáo vụ khoa'],

            ['faculty_code' => 'VATLY', 'code' => 'BM-PPDHVATLY', 'name' => 'Bộ môn Lý luận và Phương pháp Giảng dạy Vật lý'],
            ['faculty_code' => 'VATLY', 'code' => 'BM-TOANLY', 'name' => 'Bộ môn Toán - Lý'],
            ['faculty_code' => 'VATLY', 'code' => 'BM-VATLYDAI', 'name' => 'Bộ môn Vật lý đại'],
            ['faculty_code' => 'VATLY', 'code' => 'BM-VLUDCN', 'name' => 'Bộ môn Vật lý Ứng dụng và Công nghệ'],
            ['faculty_code' => 'VATLY', 'code' => 'BM-VLLT', 'name' => 'Bộ môn Vật lý lý thuyết'],
            ['faculty_code' => 'VATLY', 'code' => 'BM-VLHN', 'name' => 'Bộ môn Vật lý hạt nhân'],

            ['faculty_code' => 'HOAHOC', 'code' => 'BM-PPDHHOAHOC', 'name' => 'Bộ môn Lí luận và Phương pháp Giảng dạy Hóa học'],
            ['faculty_code' => 'HOAHOC', 'code' => 'BM-HUUCO', 'name' => 'Bộ môn Hóa hữu cơ'],
            ['faculty_code' => 'HOAHOC', 'code' => 'BM-VOCO', 'name' => 'Bộ môn Hóa vô cơ'],
            ['faculty_code' => 'HOAHOC', 'code' => 'BM-PHANTICH', 'name' => 'Bộ môn Hóa phân tích'],
            ['faculty_code' => 'HOAHOC', 'code' => 'BM-CNMT', 'name' => 'Bộ môn Hóa Công nghệ - Môi trường'],
            ['faculty_code' => 'HOAHOC', 'code' => 'BM-HOALY', 'name' => 'Bộ môn Hóa lý'],

            ['faculty_code' => 'SINHHOC', 'code' => 'BM-STSLTVPPDH', 'name' => 'Bộ môn Sinh thái - Sinh lí - Thực vật - Phương pháp dạy học'],
            ['faculty_code' => 'SINHHOC', 'code' => 'BM-SHVSDT', 'name' => 'Bộ môn Sinh hoá - Vi sinh - Di truyền'],
            ['faculty_code' => 'SINHHOC', 'code' => 'BM-GPSLNDV', 'name' => 'Bộ môn Giải phẫu - Sinh lí người - Động vật'],
            ['faculty_code' => 'SINHHOC', 'code' => 'BM-CVPTN', 'name' => 'Bộ môn Chuyên viên phòng thí nghiệm'],
            ['faculty_code' => 'SINHHOC', 'code' => 'BM-CVVP', 'name' => 'Bộ môn Chuyên viên văn phòng'],

            ['faculty_code' => 'LICHSU', 'code' => 'BM-LSVN', 'name' => 'Bộ môn Lịch sử Việt Nam'],
            ['faculty_code' => 'LICHSU', 'code' => 'BM-LSTG', 'name' => 'Bộ môn Lịch sử Thế giới'],
            ['faculty_code' => 'LICHSU', 'code' => 'BM-PPDHLS', 'name' => 'Bộ môn Lý luận và Phương pháp dạy học Lịch sử'],
            ['faculty_code' => 'LICHSU', 'code' => 'BM-QTH', 'name' => 'Bộ môn Quốc tế học'],


            ['faculty_code' => 'DIALY', 'code' => 'BM-DLKTXH', 'name' => 'Bộ môn Địa lí Kinh tế - Xã hội'],
            ['faculty_code' => 'DIALY', 'code' => 'BM-PPDHBANDO', 'name' => 'Bộ môn Phương pháp Giảng dạy và Bản đồ'],
            ['faculty_code' => 'DIALY', 'code' => 'BM-DLTN', 'name' => 'Bộ môn Địa lý tự nhiên'],

            ['faculty_code' => 'TIENGANH', 'code' => 'BM-BPD', 'name' => 'Bộ môn Kỹ năng Biên - Phiên dịch'],
            ['faculty_code' => 'TIENGANH', 'code' => 'BM-TADC', 'name' => 'Bộ môn Tiếng Anh Đại cương'],
            ['faculty_code' => 'TIENGANH', 'code' => 'BM-TATM', 'name' => 'Bộ môn Tiếng Anh Thương mại'],
            ['faculty_code' => 'TIENGANH', 'code' => 'BM-NNH', 'name' => 'Bộ môn Ngôn ngữ học'],
            ['faculty_code' => 'TIENGANH', 'code' => 'BM-PPDHTA', 'name' => 'Bộ môn Lý luận và Phương pháp Giảng dạy Tiếng Anh'],
            ['faculty_code' => 'TIENGANH', 'code' => 'BM-VHDNH', 'name' => 'Bộ môn Văn học - Đất nước học'],

            ['faculty_code' => 'TIENGPHAP', 'code' => 'BM-PPDHTP', 'name' => 'Bộ môn Lý luận và Phương pháp Giảng dạy Tiếng Pháp'],
            ['faculty_code' => 'TIENGPHAP', 'code' => 'BM-DTTVTT', 'name' => 'Bộ môn Dịch thuật và Truyền thông'],
            ['faculty_code' => 'TIENGPHAP', 'code' => 'BM-DULICH', 'name' => 'Bộ môn Du lịch'],

            ['faculty_code' => 'TIENGTRUNG', 'code' => 'BM-THTIENG', 'name' => 'Bộ môn Thực hành tiếng'],
            ['faculty_code' => 'TIENGTRUNG', 'code' => 'BM-LTTVD', 'name' => 'Bộ môn Lý thuyết tiếng và Dịch'],
            ['faculty_code' => 'TIENGTRUNG', 'code' => 'BM-KNTIENG', 'name' => 'Bộ môn Kỹ năng tiếng'],
            ['faculty_code' => 'TIENGTRUNG', 'code' => 'BM-PPGD', 'name' => 'Bộ môn Phương pháp giảng dạy'],

            ['faculty_code' => 'GDCT', 'code' => 'BM-MACLENIN', 'name' => 'Bộ môn Mác – Lê Nin'],
            ['faculty_code' => 'GDCT', 'code' => 'BM-TTHCM-LSCSVN', 'name' => 'Bộ môn Tư tưởng Hồ Chí Minh - Lịch sử Đảng Cộng sản Việt Nam'],
            ['faculty_code' => 'GDCT', 'code' => 'BM-PPGD', 'name' => 'Bộ môn Phương pháp giảng dạy'],
            ['faculty_code' => 'GDCT', 'code' => 'BM-PHAPLUAT', 'name' => 'Bộ môn Pháp luật'],

            ['faculty_code' => 'KHGD', 'code' => 'BM-GIAODUCHOC', 'name' => 'Bộ môn Giáo dục học'],
            ['faculty_code' => 'KHGD', 'code' => 'BM-QLGD', 'name' => 'Bộ môn Quản lý giáo dục'],

            ['faculty_code' => 'GDTIEUHOC', 'code' => 'BM-NVSPTH', 'name' => 'Bộ môn Nghiệp vụ Sư phạm Tiểu học'],
            ['faculty_code' => 'GDTIEUHOC', 'code' => 'BM-NVPPDHTH', 'name' => 'Bộ môn Ngữ văn và Phương pháp dạy học ở Tiểu học'],
            ['faculty_code' => 'GDTIEUHOC', 'code' => 'BM-TOPPDHTH', 'name' => 'Bộ môn Toán và Phương pháp dạy học ở Tiểu học'],
            ['faculty_code' => 'GDTIEUHOC', 'code' => 'BM-GDNTTC', 'name' => 'Bộ môn Giáo dục Nghệ thuật - Thể chất'],

            ['faculty_code' => 'GDMN', 'code' => 'BM-SKCBNT', 'name' => 'Bộ môn Sức khỏe - Cơ bản - Nghệ thuật'],
            ['faculty_code' => 'GDMN', 'code' => 'BM-TLGDMN', 'name' => 'Bộ môn Tâm lý - Giáo dục Mầm non'],
            ['faculty_code' => 'GDMN', 'code' => 'BM-LLPPDHMN', 'name' => 'Bộ môn Lý luận - Phương pháp dạy học Mầm non'],

            ['faculty_code' => 'GDQP', 'code' => 'BM-QUANSU', 'name' => 'Bộ môn Quân sự'],
            ['faculty_code' => 'GDQP', 'code' => 'BM-CHINHTRI', 'name' => 'Bộ môn Chính trị'],

            ['faculty_code' => 'GDDB', 'code' => 'BM-COSO', 'name' => 'Bộ môn Cơ sở'],
            ['faculty_code' => 'GDDB', 'code' => 'BM-CHUYENNGANH', 'name' => 'Bộ môn Chuyên ngành'],

            ['faculty_code' => 'GDTC', 'code' => 'BM-TTCB', 'name' => 'Bộ môn Thể thao cơ bản'],
            ['faculty_code' => 'GDTC', 'code' => 'BM-TTDD', 'name' => 'Bộ môn Thể thao đồng đội'],
            ['faculty_code' => 'GDTC', 'code' => 'BM-TTCN', 'name' => 'Bộ môn Thể thao cá nhân'],
            ['faculty_code' => 'GDTC', 'code' => 'BM-LLPPDH', 'name' => 'Bộ môn Lý luận và Phương pháp dạy học'],
        ];
    }
}
