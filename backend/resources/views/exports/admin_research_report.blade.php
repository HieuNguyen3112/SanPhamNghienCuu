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
      h2 {
        font-size: 13px;
        margin: 12px 0 6px 0;
      }
      .filters {
        font-size: 11px;
        margin-bottom: 10px;
      }
      .filters div {
        margin-bottom: 2px;
      }
      .kpi-table,
      table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10px;
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
    <h1>Báo cáo công trình nghiên cứu khoa học</h1>
    <div class="filters">
      <div><strong>Năm:</strong> {{ $filters['year'] ?? 'Tất cả' }}</div>
      <div><strong>Khoa / Đơn vị:</strong> {{ $filters['department'] ?? 'Tất cả' }}</div>
      <div><strong>Loại công trình:</strong> {{ $filters['research_type'] ?? 'Tất cả' }}</div>
      <div><strong>Giảng viên:</strong> {{ $filters['lecturer'] ?? 'Tất cả' }}</div>
      <div><strong>Từ khóa:</strong> {{ $filters['keyword'] ?? 'Tất cả' }}</div>
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
          <td>Tổng công trình</td>
          <td class="text-right">{{ number_format((float) ($kpis['total_count'] ?? 0), 0) }}</td>
        </tr>
        <tr>
          <td>ISI</td>
          <td class="text-right">{{ number_format((float) ($kpis['isi_count'] ?? 0), 0) }}</td>
        </tr>
        <tr>
          <td>Scopus</td>
          <td class="text-right">{{ number_format((float) ($kpis['scopus_count'] ?? 0), 0) }}</td>
        </tr>
        <tr>
          <td>Hội nghị / Hội thảo</td>
          <td class="text-right">{{ number_format((float) ($kpis['conference_count'] ?? 0), 0) }}</td>
        </tr>
        <tr>
          <td>Đề tài / Dự án</td>
          <td class="text-right">{{ number_format((float) ($kpis['project_count'] ?? 0), 0) }}</td>
        </tr>
        <tr>
          <td>Sách / Giáo trình</td>
          <td class="text-right">{{ number_format((float) ($kpis['book_count'] ?? 0), 0) }}</td>
        </tr>
      </tbody>
    </table>

    <h2>Công trình theo khoa / đơn vị</h2>
    @php
      $deptLabels = $charts['by_department_stacked']['labels'] ?? [];
      $deptIsi = $charts['by_department_stacked']['isi'] ?? [];
      $deptScopus = $charts['by_department_stacked']['scopus'] ?? [];
      $deptConference = $charts['by_department_stacked']['conference'] ?? [];
      $deptProject = $charts['by_department_stacked']['project'] ?? [];
      $deptBook = $charts['by_department_stacked']['book'] ?? [];
    @endphp
    <table>
      <thead>
        <tr>
          <th>Khoa / Đơn vị</th>
          <th>ISI</th>
          <th>Scopus</th>
          <th>Hội nghị / Hội thảo</th>
          <th>Đề tài / Dự án</th>
          <th>Sách / Giáo trình</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($deptLabels as $index => $label)
          <tr>
            <td class="text-left">{{ $label }}</td>
            <td class="text-right">{{ number_format((float) ($deptIsi[$index] ?? 0), 0) }}</td>
            <td class="text-right">{{ number_format((float) ($deptScopus[$index] ?? 0), 0) }}</td>
            <td class="text-right">{{ number_format((float) ($deptConference[$index] ?? 0), 0) }}</td>
            <td class="text-right">{{ number_format((float) ($deptProject[$index] ?? 0), 0) }}</td>
            <td class="text-right">{{ number_format((float) ($deptBook[$index] ?? 0), 0) }}</td>
          </tr>
        @empty
          <tr>
            <td class="empty" colspan="6">Không có dữ liệu</td>
          </tr>
        @endforelse
      </tbody>
    </table>

    <h2>Phân bố theo loại công trình</h2>
    @php
      $distLabels = $charts['distribution_donut']['labels'] ?? [];
      $distValues = $charts['distribution_donut']['values'] ?? [];
    @endphp
    <table>
      <thead>
        <tr>
          <th>Loại công trình</th>
          <th>Số lượng</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($distLabels as $index => $label)
          <tr>
            <td class="text-left">{{ $label }}</td>
            <td class="text-right">{{ number_format((float) ($distValues[$index] ?? 0), 0) }}</td>
          </tr>
        @empty
          <tr>
            <td class="empty" colspan="2">Không có dữ liệu</td>
          </tr>
        @endforelse
      </tbody>
    </table>

    <h2>Công trình theo năm</h2>
    @php
      $yearLabels = $charts['by_year_line']['labels'] ?? [];
      $yearValues = $charts['by_year_line']['values'] ?? [];
    @endphp
    <table>
      <thead>
        <tr>
          <th>Năm</th>
          <th>Số lượng</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($yearLabels as $index => $label)
          <tr>
            <td class="text-left">{{ $label }}</td>
            <td class="text-right">{{ number_format((float) ($yearValues[$index] ?? 0), 0) }}</td>
          </tr>
        @empty
          <tr>
            <td class="empty" colspan="2">Không có dữ liệu</td>
          </tr>
        @endforelse
      </tbody>
    </table>

    <h2>Danh sách công trình</h2>
    <table>
      <thead>
        <tr>
          <th>Tên công trình</th>
          <th>Mã công trình</th>
          <th>Loại</th>
          <th>Nơi công bố</th>
          <th>Giảng viên</th>
          <th>Đơn vị</th>
          <th>Năm</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($rows as $row)
          <tr>
            <td class="text-left">{{ $row['title'] ?? '' }}</td>
            <td class="text-left">{{ $row['activity_code'] ?? '' }}</td>
            <td class="text-left">{{ $row['category_label'] ?? '' }}</td>
            <td class="text-left">{{ $row['venue_label'] ?? '' }}</td>
            <td class="text-left">{{ $row['lecturer_names'] ?? '' }}</td>
            <td class="text-left">{{ $row['department_name'] ?? '' }}</td>
            <td class="text-center">{{ $row['year'] ?? '' }}</td>
          </tr>
        @empty
          <tr>
            <td class="empty" colspan="7">Không có dữ liệu</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </body>
</html>
