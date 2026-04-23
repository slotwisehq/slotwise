<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Booking Confirmed</title>
        <style>
            body { font-family: sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 24px; }
            h1   { font-size: 1.4rem; }
            table { width: 100%; border-collapse: collapse; margin: 16px 0; }
            th, td { text-align: left; padding: 8px 12px; border-bottom: 1px solid #e5e5e5; }
            th   { width: 140px; color: #666; font-weight: 600; }
            .footer { margin-top: 32px; font-size: 0.85rem; color: #888; }
        </style>
    </head>
    <body>
        <h1>Booking Confirmed — {{ $appointment->tenant->name }}</h1>

        <p>Hi {{ $appointment->customer->name }},</p>
        <p>Your appointment has been confirmed. Here are the details:</p>

        <table>
            <tr><th>Service</th><td>{{ $appointment->service->name }}</td></tr>
            <tr><th>Staff</th><td>{{ $appointment->staff->name }}</td></tr>
            <tr><th>Date &amp; Time</th><td>{{ $appointment->starts_at->format('l, j F Y \a\t H:i') }}</td></tr>
        </table>

        <p>
            Need to cancel?
            <a href="{{ url('/book/' . $appointment->tenant->slug . '/cancel/' . $appointment->id) }}">
                Cancel this booking
            </a>
        </p>

        <div class="footer">
            <p>{{ $appointment->tenant->name }}</p>
        </div>
    </body>
</html>
