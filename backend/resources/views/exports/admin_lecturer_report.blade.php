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
    <h1>Báo cáo nhân sự giảng viên</h1>
    <div class="filters">
      <div><strong>Khoa:</strong> {{ $filters['faculty'] ?? 'Tất cả' }}</div>
      <div><strong>Trình độ:</strong> {{ $filters['degree'] ?? 'Tất cả' }}</div>
      <div><strong>Học hàm:</strong> {{ $filters['academic_rank'] ?? 'Tất cả' }}</div>
      <div><strong>Giới tính:</strong> {{ $filters['gender'] ?? 'Tất cả' }}</div>
    </div>

    <table>
      <thead>
        <tr>
          <th>Họ và tên</th>
          <th>Khoa</th>
          <th>Giới tính</th>
          <th>Trình độ</th>
          <th>Học hàm</th>
          <th>Thâm niên (năm)</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($rows as $row)
          <tr>
            <td class="text-left">{{ $row['full_name'] ?? '' }}</td>
            <td class="text-left">{{ $row['faculty']['name'] ?? '' }}</td>
            <td class="text-center">{{ $row['gender'] ?? '' }}</td>
            <td class="text-left">{{ $row['degree']['name'] ?? '' }}</td>
            <td class="text-left">{{ $row['academic_rank']['name'] ?? '' }}</td>
            <td class="text-center">{{ (int) ($row['seniority_years'] ?? 0) }}</td>
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
