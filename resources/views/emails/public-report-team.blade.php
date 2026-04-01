<div>
  <p>New public report received.</p>
  <p>Case id: {{ $report->case_id }}</p>
  <p>Type of Report: {{ $reportTypeLabel }}</p>
  <p>Name: {{ $report->name }}</p>
  <p>Email: {{ $report->email ?: '-' }}</p>
  <p>Phone: {{ $report->phone }}</p>
  <p>Ic / Id: {{ $report->identity_number ?: '-' }}</p>
  <p>Hari dan waktu: {{ optional($report->incident_date)->format('Y-m-d') }} {{ substr((string) $report->incident_time, 0, 5) }}</p>
  <p>Kronologi: {{ $report->chronology }}</p>
  <p>Staff Name: {{ $report->staff_name ?: '-' }}</p>
  <p>Ip address: {{ $report->ip_address ?: '-' }}</p>
</div>
