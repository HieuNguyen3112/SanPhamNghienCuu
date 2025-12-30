<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <style>
      body {
        font-family: "DejaVu Sans", sans-serif;
        font-size: 12px;
        color: #111;
      }
      h1 {
        font-size: 16px;
        margin: 0 0 6px 0;
      }
      .filters {
        font-size: 11px;
        margin-bottom: 10px;
      }
      .filters div {
        margin-bottom: 2px;
      }
      table {
        width: 100%;
        border-collapse: collapse;
      }
      th,
      td {
        border: 1px solid #333;
        padding: 6px 8px;
      }
      th {
        background: #f0f0f0;
        font-weight: bold;
        text-align: center;
      }
      td.text-left {
        text-align: left;
      }
      td.text-right {
        text-align: right;
      }
      td.text-center {
        text-align: center;
      }
      .empty {
        text-align: center;
        padding: 12px 8px;
      }
    </style>
  </head>
  <body>
    <h1>Quản lý giờ nghiên cứu khoa học theo giảng viên</h1>
    <div class="filters">
      <div><strong>Khoa:</strong> {{ $filters['faculty'] ?? 'Tất cả' }}</div>
      <div><strong>Niên học:</strong> {{ $filters['academic_year'] ?? 'Tất cả' }}</div>
      <div><strong>Trạng thái KPI:</strong> {{ $filters['status'] ?? 'Tất cả' }}</div>
      <div><strong>Từ khóa:</strong> {{ $filters['keyword'] ?? 'Tất cả' }}</div>
    </div>

    <table>
      <thead>
        <tr>
          <th>Giảng viên</th>
          <th>Khoa</th>
          <th>Tổng giờ</th>
          <th>Định mức</th>
          <th>Chênh lệch</th>
          <th>Trạng thái</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($rows as $row)
          <tr>
            <td class="text-left">
              {{ trim(($row['lecturer_full_name'] ?? '') . ' (' . ($row['lecturer_code'] ?? '') . ')') }}
            </td>
            <td class="text-left">
              {{ $row['faculty_name'] ?? $row['department_name'] ?? '' }}
            </td>
            <td class="text-right">{{ number_format((float) ($row['hours_total'] ?? 0), 0) }}</td>
            <td class="text-right">{{ number_format((float) ($row['required_hours'] ?? 0), 0) }}</td>
            <td class="text-right">
              {{ number_format(((float) ($row['hours_total'] ?? 0)) - ((float) ($row['required_hours'] ?? 0)), 0) }}
            </td>
            <td class="text-center">
              {{ ((float) ($row['hours_total'] ?? 0)) - ((float) ($row['required_hours'] ?? 0)) >= 0 ? 'Đạt' : 'Thiếu' }}
            </td>
          </tr>
        @empty
          <tr>
            <td class="empty" colspan="6">Không có dữ liệu</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </body>
</html>
