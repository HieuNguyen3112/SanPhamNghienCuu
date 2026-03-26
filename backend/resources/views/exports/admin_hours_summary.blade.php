@php
    use App\Support\LecturerHoursSummaryReportLayout;

    $groups = LecturerHoursSummaryReportLayout::groups();
    $leafColumns = LecturerHoursSummaryReportLayout::leafColumns();
    $title = LecturerHoursSummaryReportLayout::title();
    $emptyMessage = LecturerHoursSummaryReportLayout::emptyStateMessage();
@endphp
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <style>
      @page {
        margin: 14px 18px;
      }

      body {
        font-family: "DejaVu Sans", sans-serif;
        font-size: 8.5px;
        color: #111827;
      }

      .report-title {
        text-align: center;
        font-weight: 700;
        font-size: 15px;
        margin: 0 0 6px 0;
      }

      .report-meta {
        margin-bottom: 10px;
        font-size: 9px;
      }

      .report-meta div {
        margin-bottom: 2px;
      }

      table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
      }

      th,
      td {
        border: 1px solid #374151;
        padding: 4px 3px;
        vertical-align: middle;
        word-wrap: break-word;
      }

      thead th {
        background: #d9eaf7;
        text-align: center;
        font-weight: 700;
        line-height: 1.25;
      }

      tbody td {
        line-height: 1.2;
      }

      .text-left {
        text-align: left;
      }

      .text-center {
        text-align: center;
      }

      .text-right {
        text-align: right;
      }

      .empty {
        text-align: center;
        padding: 10px 8px;
      }
    </style>
  </head>
  <body>
    <h1 class="report-title">{{ $title }}</h1>

    <div class="report-meta">
      <div><strong>Năm học:</strong> {{ $meta['academic_year_code'] ?? 'Tất cả' }}</div>
      <div><strong>Phạm vi:</strong> {{ $meta['scope_label'] ?? 'Toàn trường' }}</div>
    </div>

    <table>
      <colgroup>
        <col style="width: 4%" />
        <col style="width: 16%" />
        @for ($i = 0; $i < count($leafColumns) - 2; $i++)
          <col style="width: 4.44%" />
        @endfor
      </colgroup>
      <thead>
        <tr>
          <th rowspan="2">TT</th>
          <th rowspan="2">Họ và tên</th>
          @foreach ($groups as $group)
            <th colspan="{{ count($group['columns']) }}">{{ $group['label'] }}</th>
          @endforeach
        </tr>
        <tr>
          @foreach ($groups as $group)
            @foreach ($group['columns'] as $column)
              <th>{{ $column['label'] }}</th>
            @endforeach
          @endforeach
        </tr>
      </thead>
      <tbody>
        @forelse ($rows as $row)
          @php
            $values = LecturerHoursSummaryReportLayout::dataRow($row);
          @endphp
          <tr>
            @foreach ($leafColumns as $index => $column)
              @php
                $isTextColumn = $column['format'] === 'text';
                $alignmentClass = $isTextColumn ? 'text-left' : ($index === 0 ? 'text-center' : 'text-right');
              @endphp
              <td class="{{ $alignmentClass }}">
                {{ LecturerHoursSummaryReportLayout::formatValue($values[$index] ?? null, $column['format']) }}
              </td>
            @endforeach
          </tr>
        @empty
          <tr>
            <td class="empty" colspan="{{ count($leafColumns) }}">{{ $emptyMessage }}</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </body>
</html>
