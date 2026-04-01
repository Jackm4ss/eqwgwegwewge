<div>
  <p>Thank you we have received your report.</p>
  <p>Our officer may contact you via whatsapp or e-mail in case we need more information regarding your report.</p>
  <p>Kindly take note, We are not obligated to respond to or act upon every report submitted. Our response and any subsequent action are subject to the urgency and nature of the matter.</p>
  <p>Case id: {{ $report->case_id }}</p>
  <p>Name: {{ $report->name }}</p>
  <p>Email: {{ $report->email ?: '-' }}</p>
  <p>Phone: {{ $report->phone }}</p>
  <p>Ic / Id: {{ $report->identity_number ?: '-' }}</p>
  <p>Hari dan waktu: {{ optional($report->incident_date)->format('Y-m-d') }} {{ substr((string) $report->incident_time, 0, 5) }}</p>
  <p>Kronologi: {{ $report->chronology }}</p>
  <p>Ip address: {{ $report->ip_address ?: '-' }}</p>
</div>
