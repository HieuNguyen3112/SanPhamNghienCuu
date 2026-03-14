<?php
require __DIR__ . '/../../../../vendor/autoload.php';
$app = require __DIR__ . '/../../../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$renderer = $app->make(App\Services\Backup\ReadableExportPdfRenderer::class);
$workPayload = [
    'snapshot' => ['generated_at' => '2026-03-14T10:30:00+07:00'],
    'activity' => [
        'activity_code' => 'CT-001',
        'title' => 'Nghien cuu ung dung tri tue nhan tao',
        'start_date' => '2025-09-01',
        'end_date' => '2026-05-30',
        'submitted_at' => '2026-01-10 08:00:00',
        'approved_at' => '2026-01-20 14:30:00',
        'total_hours_calc' => 120,
        'academic_year' => ['code' => '2025-2026'],
        'kind' => ['name' => 'De tai'],
        'type' => ['name' => 'Cap truong'],
        'status' => ['name' => 'Da phe duyet'],
    ],
    'owner' => [
        'code' => 'GV001',
        'full_name' => 'Nguyen Van A',
        'department' => ['name' => 'Bo mon CNTT'],
        'faculty' => ['name' => 'Khoa CNTT'],
    ],
    'participants' => [
        [
            'code' => 'GV001',
            'full_name' => 'Nguyen Van A',
            'department' => ['name' => 'Bo mon CNTT'],
            'faculty' => ['name' => 'Khoa CNTT'],
            'is_owner' => true,
            'hours_assigned' => 60,
            'contribution_share' => 50,
        ],
        [
            'code' => 'GV002',
            'full_name' => 'Tran Thi B',
            'department' => ['name' => 'Bo mon He thong thong tin'],
            'faculty' => ['name' => 'Khoa CNTT'],
            'member_role_name' => 'Thanh vien',
            'hours_assigned' => 60,
            'contribution_share' => 50,
        ],
    ],
    'evidence_files' => [
        [
            'stored_filename' => 'bao-cao.pdf',
            'file_type' => 'Bao cao',
            'uploaded_by' => ['code' => 'GV001', 'full_name' => 'Nguyen Van A'],
            'uploaded_at' => '2026-02-01 10:15:00',
            'size_bytes' => 245760,
            'export_status' => 'copied',
        ],
    ],
    'stats' => [
        'participant_count' => 2,
        'evidence_file_count' => 1,
        'copied_evidence_count' => 1,
        'metadata_only_evidence_count' => 0,
    ],
];

$lecturerPayload = [
    'lecturer' => [
        'code' => 'GV001',
        'full_name' => 'Nguyen Van A',
        'email' => 'a@example.com',
        'department' => ['name' => 'Bo mon CNTT'],
        'faculty' => ['name' => 'Khoa CNTT'],
    ],
    'snapshot' => [
        'generated_at' => '2026-03-14T10:30:00+07:00',
        'works_count' => 1,
        'uploaded_evidence_count' => 1,
    ],
    'works' => [
        [
            'activity_code' => 'CT-001',
            'title' => 'Nghien cuu ung dung tri tue nhan tao',
            'cong_trinh_relative_path' => 'cong-trinh/ct-001-nghien-cuu-1',
            'uploaded_evidence_count' => 1,
            'participation' => [
                'is_owner' => true,
                'member_role_name' => 'Chu nhiem',
                'hours_assigned' => 60,
            ],
        ],
    ],
];

$workPdfPath = __DIR__ . '/work.pdf';
$lecturerPdfPath = __DIR__ . '/lecturer.pdf';
file_put_contents($workPdfPath, $renderer->renderWorkSummary($workPayload));
file_put_contents($lecturerPdfPath, $renderer->renderLecturerSummary($lecturerPayload));

echo json_encode([
    'work_pdf_bytes' => filesize($workPdfPath),
    'lecturer_pdf_bytes' => filesize($lecturerPdfPath),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
