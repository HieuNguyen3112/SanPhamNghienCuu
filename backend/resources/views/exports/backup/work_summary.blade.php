<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <style>
      body {
        font-family: "DejaVu Sans", sans-serif;
        font-size: 11px;
        color: #111827;
        line-height: 1.45;
      }
      h1 {
        font-size: 18px;
        margin: 0 0 4px 0;
      }
      h2 {
        font-size: 13px;
        margin: 16px 0 8px 0;
      }
      p {
        margin: 0;
      }
      .muted {
        color: #4b5563;
        font-size: 10px;
      }
      .summary-grid {
        width: 100%;
        margin-top: 12px;
        border-collapse: collapse;
      }
      .summary-grid td {
        width: 50%;
        vertical-align: top;
        padding: 0 10px 8px 0;
      }
      .label {
        font-size: 10px;
        font-weight: bold;
        color: #374151;
        margin-bottom: 2px;
      }
      .value {
        font-size: 11px;
      }
      table {
        width: 100%;
        border-collapse: collapse;
      }
      th,
      td {
        border: 1px solid #d1d5db;
        padding: 6px 7px;
        vertical-align: top;
      }
      th {
        background: #f3f4f6;
        text-align: left;
        font-weight: bold;
      }
      .empty {
        text-align: center;
        color: #6b7280;
      }
    </style>
  </head>
  <body>
    <h1>Thong tin cong trinh</h1>
    <p class="muted">
      Tai lieu tom tat de doc nhanh tren Google Drive.
      @if (!empty($summary['generated_at']))
        Thoi diem tao: {{ $summary['generated_at'] }}.
      @endif
    </p>

    <table class="summary-grid">
      <tr>
        <td>
          <div class="label">Ma cong trinh</div>
          <div class="value">{{ $summary['activity_code'] !== '' ? $summary['activity_code'] : 'Khong ro' }}</div>
        </td>
        <td>
          <div class="label">Nam hoc</div>
          <div class="value">{{ $summary['academic_year_code'] !== '' ? $summary['academic_year_code'] : 'Khong ro' }}</div>
        </td>
      </tr>
      <tr>
        <td>
          <div class="label">Ten cong trinh</div>
          <div class="value">{{ $summary['title'] !== '' ? $summary['title'] : 'Khong ro' }}</div>
        </td>
        <td>
          <div class="label">Loai / nhom</div>
          <div class="value">
            {{ $summary['type_name'] !== '' ? $summary['type_name'] : 'Khong ro' }}
            @if ($summary['kind_name'] !== '')
              / {{ $summary['kind_name'] }}
            @endif
          </div>
        </td>
      </tr>
      <tr>
        <td>
          <div class="label">Trang thai</div>
          <div class="value">{{ $summary['status_name'] !== '' ? $summary['status_name'] : 'Khong ro' }}</div>
        </td>
        <td>
          <div class="label">Tong gio nghien cuu</div>
          <div class="value">{{ $summary['research_hours'] ?? 'Khong ro' }}</div>
        </td>
      </tr>
      <tr>
        <td>
          <div class="label">Ngay bat dau / ket thuc</div>
          <div class="value">
            {{ $summary['start_date'] ?? 'Khong ro' }}
            @if (!empty($summary['end_date']))
              - {{ $summary['end_date'] }}
            @endif
          </div>
        </td>
        <td>
          <div class="label">Ngay nop / phe duyet</div>
          <div class="value">
            {{ $summary['submitted_at'] ?? 'Khong ro' }}
            @if (!empty($summary['approved_at']))
              - {{ $summary['approved_at'] }}
            @endif
          </div>
        </td>
      </tr>
      <tr>
        <td>
          <div class="label">Nguoi ke khai chinh</div>
          <div class="value">{{ $summary['owner_display_name'] }}</div>
        </td>
        <td>
          <div class="label">Don vi</div>
          <div class="value">{{ $summary['owner_unit'] }}</div>
        </td>
      </tr>
      <tr>
        <td>
          <div class="label">So thanh vien tham gia</div>
          <div class="value">{{ $summary['participant_count'] }}</div>
        </td>
        <td>
          <div class="label">Minh chung trong export</div>
          <div class="value">
            {{ $summary['evidence_file_count'] }} tep
            ({{ $summary['copied_evidence_count'] }} da sao chep, {{ $summary['metadata_only_evidence_count'] }} chi co metadata)
          </div>
        </td>
      </tr>
    </table>

    <h2>Thanh vien tham gia</h2>
    <table>
      <thead>
        <tr>
          <th>Giang vien</th>
          <th>Vai tro</th>
          <th>Don vi</th>
          <th>Gio phan bo</th>
          <th>Ty le dong gop</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($summary['participants'] as $participant)
          <tr>
            <td>{{ $participant['display_name'] }}</td>
            <td>{{ $participant['role'] !== '' ? $participant['role'] : 'Khong ro' }}</td>
            <td>{{ $participant['unit'] }}</td>
            <td>{{ $participant['hours_assigned'] ?? 'Khong ro' }}</td>
            <td>{{ $participant['contribution_share'] ?? 'Khong ro' }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="5" class="empty">Khong co thong tin thanh vien.</td>
          </tr>
        @endforelse
      </tbody>
    </table>

    <h2>Danh muc minh chung</h2>
    <table>
      <thead>
        <tr>
          <th>Tep</th>
          <th>Loai</th>
          <th>Nguoi tai len</th>
          <th>Ngay tai len</th>
          <th>Dung luong</th>
          <th>Trang thai export</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($summary['evidence_files'] as $file)
          <tr>
            <td>{{ $file['display_name'] }}</td>
            <td>{{ $file['file_type'] }}</td>
            <td>{{ $file['uploaded_by'] }}</td>
            <td>{{ $file['uploaded_at'] ?? 'Khong ro' }}</td>
            <td>{{ $file['size'] }}</td>
            <td>{{ $file['status'] }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="empty">Khong co minh chung trong snapshot nay.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </body>
</html>
