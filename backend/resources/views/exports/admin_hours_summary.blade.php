@php
    use App\Support\LecturerHoursSummaryReportLayout;

    $groups = LecturerHoursSummaryReportLayout::groups();
    $leafColumns = LecturerHoursSummaryReportLayout::leafColumns();
    $title = LecturerHoursSummaryReportLayout::title();
    $emptyMessage = LecturerHoursSummaryReportLayout::emptyStateMessage();
    $legend = LecturerHoursSummaryReportLayout::valueLegend();
    $widths = LecturerHoursSummaryReportLayout::columnWidths();
    $totalWidth = array_sum($widths);
    $groupStyles = collect(LecturerHoursSummaryReportLayout::headerGroupsByColumn())->keyBy('key');
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
        font-size: 8.4px;
        color: #111827;
      }

      .report-title {
        margin: 0 0 6px 0;
        text-align: center;
        font-size: 15px;
        font-weight: 700;
        letter-spacing: 0.15px;
      }

      .report-meta {
        margin-bottom: 8px;
        font-size: 9px;
      }

      .report-meta div {
        margin-bottom: 2px;
      }

      .report-note {
        margin-bottom: 10px;
        font-style: italic;
        color: #4b5563;
      }

      table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
      }

      th,
      td {
        border: 1px solid #5b6570;
        padding: 4px 3px;
        vertical-align: middle;
        word-break: break-word;
      }

      thead th {
        text-align: center;
        font-weight: 700;
        line-height: 1.2;
      }

      .major-header {
        font-size: 8.6px;
      }

      .sub-header {
        font-size: 8px;
      }

      .identity-header {
        background: #d5dee9;
      }

      .name-cell {
        text-align: left;
        font-weight: 700;
      }

      .center-cell {
        text-align: center;
      }

      .right-cell {
        text-align: right;
      }

      tbody tr:nth-child(even) td {
        background: #f9fbfd;
      }

      .total-column {
        background: #e1d9c9;
        font-weight: 700;
      }

      tbody tr:nth-child(even) td.total-column {
        background: #e1d9c9;
      }

      .empty {
        padding: 12px 8px;
        text-align: center;
      }
    </style>
  </head>
  <body>
    <h1 class="report-title">{{ $title }}</h1>

    <div class="report-meta">
      <div><strong>Năm học:</strong> {{ LecturerHoursSummaryReportLayout::metaValue($meta['academic_year_code'] ?? null, 'Tất cả') }}</div>
      <div><strong>Phạm vi:</strong> {{ LecturerHoursSummaryReportLayout::metaValue($meta['scope_label'] ?? null, 'Toàn trường') }}</div>
    </div>
    <div class="report-note">{{ $legend }}</div>

    <table>
      <colgroup>
        @foreach ($widths as $width)
          <col style="width: {{ $totalWidth > 0 ? round(($width / $totalWidth) * 100, 3) : 4.5 }}%" />
        @endforeach
      </colgroup>
      <thead>
        <tr>
          <th rowspan="2" class="major-header identity-header">TT</th>
          <th rowspan="2" class="major-header identity-header">Họ và tên</th>
          @foreach ($groups as $group)
            @php
                $style = $groupStyles->get($group['key']);
                $singleColumnGroup = count($group['columns']) === 1;
            @endphp
            <th
              colspan="{{ $singleColumnGroup ? 1 : count($group['columns']) }}"
              @if ($singleColumnGroup) rowspan="2" @endif
              class="major-header {{ ($group['is_total'] ?? false) ? 'total-column' : '' }}"
              style="background: #{{ $style['header_fill'] }}"
            >
              {{ $group['label'] }}
            </th>
          @endforeach
        </tr>
        <tr>
          @foreach ($groups as $group)
            @php
                $style = $groupStyles->get($group['key']);
            @endphp
            @foreach ($group['columns'] as $column)
              @if (count($group['columns']) > 1)
                <th
                  class="sub-header {{ ($group['is_total'] ?? false) ? 'total-column' : '' }}"
                  style="background: #{{ $style['subheader_fill'] }}"
                >
                  {!! nl2br(e($column['label'])) !!}
                </th>
              @endif
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
                  $alignmentClass = match ($column['align'] ?? 'center') {
                      'left' => 'name-cell',
                      'right' => 'right-cell',
                      default => 'center-cell',
                  };
                  $isTotalColumn = $column['key'] === 'hours_total';
              @endphp
              <td class="{{ $alignmentClass }} {{ $isTotalColumn ? 'total-column' : '' }}">
                {{ $values[$index] ?? '' }}
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
