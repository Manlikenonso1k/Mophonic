{{-- Shared by the customer PDF and the staff screen. Kept to a narrow column
     with plain borders so it prints legibly on A4 and on 80mm thermal roll. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $order->reference }}</title>
    <style>
        @page { margin: 18mm 14mm; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.45;
            color: #111;
        }

        .sheet { max-width: 78mm; margin: 0 auto; }
        .centre { text-align: center; }
        .muted { color: #555; }
        .rule { border-top: 1px dashed #999; margin: 10px 0; }

        h1 { font-size: 15px; margin: 0 0 2px; letter-spacing: 1px; text-transform: uppercase; }
        h2 { font-size: 11px; margin: 12px 0 4px; text-transform: uppercase; letter-spacing: 1px; }

        table { width: 100%; border-collapse: collapse; }
        td { padding: 2px 0; vertical-align: top; }
        td.right { text-align: right; white-space: nowrap; }
        tr.total td { border-top: 1px solid #111; font-weight: bold; padding-top: 6px; }

        .badge { display: inline-block; border: 1px solid #111; padding: 1px 6px; text-transform: uppercase; }

        @media print {
            .no-print { display: none !important; }
            body { font-size: 11px; }
        }
    </style>
</head>
<body>
<div class="sheet">
    <div class="centre">
        <h1>{{ $business['name'] }}</h1>
        @if ($business['address'])
            <div class="muted">{{ $business['address'] }}</div>
        @endif
        @if ($business['phone'])
            <div class="muted">{{ $business['phone'] }}</div>
        @endif
        @if ($business['email'])
            <div class="muted">{{ $business['email'] }}</div>
        @endif
    </div>

    <div class="rule"></div>

    <table>
        <tr><td>Receipt</td><td class="right">{{ $order->reference }}</td></tr>
        <tr><td>Date</td><td class="right">{{ $order->created_at?->format('d M Y, H:i') }}</td></tr>
        <tr>
            <td>Payment</td>
            <td class="right"><span class="badge">{{ $order->payment_status ?? 'unpaid' }}</span></td>
        </tr>
        @if ($order->paid_at)
            <tr><td>Paid</td><td class="right">{{ $order->paid_at->format('d M Y, H:i') }}</td></tr>
        @endif
        @if ($order->payment_reference)
            <tr><td>Gateway ref</td><td class="right">{{ $order->payment_reference }}</td></tr>
        @endif
        @if ($staff)
            <tr><td>Order status</td><td class="right">{{ \App\Models\Order::STATUSES[$order->status] ?? $order->status }}</td></tr>
            @if ($order->updated_at)
                <tr><td>Last updated</td><td class="right">{{ $order->updated_at->format('d M Y, H:i') }}</td></tr>
            @endif
        @endif
    </table>

    <h2>Customer</h2>
    <div>{{ $order->customer_name }}</div>
    @if ($order->customer_phone)
        <div class="muted">{{ $order->customer_phone }}</div>
    @endif
    @if ($staff && $order->customer_email)
        <div class="muted">{{ $order->customer_email }}</div>
    @endif
    @if ($order->delivery_address)
        <div class="muted">{{ $order->delivery_address }}</div>
    @endif

    <div class="rule"></div>

    <h2>Items</h2>
    <table>
        @foreach ($order->items as $item)
            <tr>
                <td>
                    {{ $item->product_name }}<br>
                    <span class="muted">{{ $item->quantity }} × {{ $money($item->unit_price_kobo) }}</span>
                </td>
                <td class="right">{{ $money($item->line_total_kobo) }}</td>
            </tr>
        @endforeach
    </table>

    <div class="rule"></div>

    <table>
        <tr><td>Subtotal</td><td class="right">{{ $money($order->subtotal_kobo) }}</td></tr>
        <tr><td>Delivery</td><td class="right">{{ $money($order->delivery_fee_kobo) }}</td></tr>
        <tr class="total"><td>Total</td><td class="right">{{ $money($order->total_kobo) }}</td></tr>
    </table>

    @if ($staff && $order->notes)
        <h2>Notes</h2>
        <div class="muted">{{ $order->notes }}</div>
    @endif

    <div class="rule"></div>
    <div class="centre muted">Thank you.</div>
</div>
</body>
</html>
