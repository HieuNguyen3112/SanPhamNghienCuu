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
      .kpi-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10px;
      }
      .kpi-table th,
      .kpi-table td {
        border: 1px solid #333;
        padding: 6px 8px;
      }
      .kpi-table th {
        background: #f0f0f0;
        font-weight: bold;
        text-align: left;
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
    <h1>Báo cáo thống kê giờ NCKH</h1>
    <div class="filters">
      <div><strong>Khoa:</strong> {{ $filters['faculty'] ?? 'Tất cả' }}</div>
      <div><strong>Năm học:</strong> {{ $filters['academic_year'] ?? 'Tất cả' }}</div>
      <div><strong>Trạng thái:</strong> {{ $filters['status'] ?? 'Tất cả' }}</div>
    </div>

    <table class="kpi-table">
      <thead>
        <tr>
          <th>Chỉ số</th>
          <th>Giá trị</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Tổng giảng viên</td>
          <td class="text-right">{{ number_format((float) ($kpis['lecturer_count'] ?? 0), 0) }}</td>
        </tr>
        <tr>
          <td>Tổng giờ NCKH</td>
          <td class="text-right">{{ number_format((float) ($kpis['total_hours'] ?? 0), 2) }}</td>
        </tr>
        <tr>
          <td>Giờ NCKH trung bình/giảng viên</td>
          <td class="text-right">{{ number_format((float) ($kpis['avg_hours'] ?? 0), 2) }}</td>
        </tr>
        <tr>
          <td>Giảng viên đạt chuẩn</td>
          <td class="text-right">{{ number_format((float) ($kpis['met_count'] ?? 0), 0) }}</td>
        </tr>
        <tr>
          <td>Giảng viên chưa đạt</td>
          <td class="text-right">{{ number_format((float) ($kpis['not_met_count'] ?? 0), 0) }}</td>
        </tr>
        <tr>
          <td>Tỉ lệ đạt chuẩn (%)</td>
          <td class="text-right">{{ number_format((float) ($kpis['compliance_rate'] ?? 0), 2) }}</td>
        </tr>
      </tbody>
    </table>

    <table>
      <thead>
        <tr>
          <th>Giảng viên</th>
          <th>Khoa</th>
          <th>Năm học</th>
          <th>Tổng giờ NCKH</th>
          <th>Giờ chuẩn</th>
          <th>Trạng thái</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($rows as $row)
          <tr>
            <td class="text-left">{{ $row['lecturer_name'] ?? '' }}</td>
            <td class="text-left">{{ $row['faculty_name'] ?? '' }}</td>
            <td class="text-left">{{ $row['academic_year_code'] ?? '' }}</td>
            <td class="text-right">{{ number_format((float) ($row['total_hours'] ?? 0), 2) }}</td>
            <td class="text-right">{{ number_format((float) ($row['required_hours'] ?? 0), 2) }}</td>
            <td class="text-center">
              {{ ($row['status'] ?? '') === 'met' ? 'Đạt chuẩn' : 'Chưa đạt' }}
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
