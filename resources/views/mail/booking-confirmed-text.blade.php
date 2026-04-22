BOOKING CONFIRMED — {{ $appointment->tenant->name }}

Hi {{ $appointment->customer->name }},

Your appointment has been confirmed:

Service:    {{ $appointment->service->name }}
Staff:      {{ $appointment->staff->name }}
Date/Time:  {{ $appointment->starts_at->format('l, j F Y \a\t H:i') }}

To cancel this booking, visit:
{{ url('/book/' . $appointment->tenant->slug . '/cancel/' . $appointment->id) }}

Thank you,
{{ $appointment->tenant->name }}
