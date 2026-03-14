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
    <h1>Tong hop giang vien</h1>
    <p class="muted">
      Tai lieu tom tat de doc nhanh tren Google Drive.
      @if (!empty($summary['generated_at']))
        Thoi diem tao: {{ $summary['generated_at'] }}.
      @endif
    </p>

    <table class="summary-grid">
      <tr>
        <td>
          <div class="label">Giang vien</div>
          <div class="value">{{ $summary['lecturer_display_name'] }}</div>
        </td>
        <td>
          <div class="label">Don vi</div>
          <div class="value">{{ $summary['unit'] }}</div>
        </td>
      </tr>
      <tr>
        <td>
          <div class="label">Email</div>
          <div class="value">{{ $summary['email'] !== '' ? $summary['email'] : 'Khong ro' }}</div>
        </td>
        <td>
          <div class="label">Tong so cong trinh</div>
          <div class="value">{{ $summary['works_count'] }}</div>
        </td>
      </tr>
      <tr>
        <td>
          <div class="label">Tong so minh chung lien quan</div>
          <div class="value">{{ $summary['uploaded_evidence_count'] }}</div>
        </td>
        <td>
          <div class="label">Tong gio nghien cuu co the tong hop</div>
          <div class="value">{{ $summary['known_research_hours'] ?? 'Khong ro' }}</div>
        </td>
      </tr>
      <tr>
        <td colspan="2">
          <div class="label">Vai tro tham gia</div>
          <div class="value">
            @if (count($summary['roles']) > 0)
              {{ implode(', ', $summary['roles']) }}
            @else
              Khong ro
            @endif
          </div>
        </td>
      </tr>
    </table>

    <h2>Danh sach cong trinh trong snapshot</h2>
    <table>
      <thead>
        <tr>
          <th>Cong trinh</th>
          <th>Ma</th>
          <th>Vai tro</th>
          <th>So minh chung</th>
          <th>Duong dan tham chieu</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($summary['works'] as $work)
          <tr>
            <td>{{ $work['title'] !== '' ? $work['title'] : 'Khong ro' }}</td>
            <td>{{ $work['activity_code'] !== '' ? $work['activity_code'] : 'Khong ro' }}</td>
            <td>{{ $work['role'] }}</td>
            <td>{{ $work['evidence_count'] }}</td>
            <td>{{ $work['reference_path'] !== '' ? $work['reference_path'] : 'Khong ro' }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="5" class="empty">Khong co cong trinh trong snapshot nay.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </body>
</html>
