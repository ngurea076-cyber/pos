<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $order->order_number }}</title>
    <style>
        @page { margin: 38px 50px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #4b5563; font-family: DejaVu Sans, sans-serif; font-size: 9px; }
        .navy { color: #070754; }
        .red { color: #e30613; }
        .top { height: 78px; position: relative; }
        .title { color: #e30613; font-size: 35px; font-weight: 300; letter-spacing: 6px; line-height: 1; padding-top: 16px; }
        .logo { position: absolute; right: 0; top: 3px; height: 58px; width: 78px; text-align: center; }
        .logo img { max-height: 58px; max-width: 78px; }
        .logo-text { color: #070754; font-size: 12px; font-weight: bold; padding-top: 20px; }
        .order-strip { background: #070754; color: white; font-size: 7px; font-weight: bold; letter-spacing: .5px; padding: 5px 8px; }
        .order-strip .right { float: right; }
        .info { margin-top: 25px; width: 100%; }
        .info td { padding-right: 20px; vertical-align: top; width: 33.33%; }
        .info td:last-child { padding-right: 0; }
        .section-title { color: #27272a; font-size: 11px; font-weight: bold; letter-spacing: .4px; margin-bottom: 8px; }
        .line { line-height: 1.65; }
        .items { border-collapse: collapse; margin-top: 45px; width: 100%; }
        .items th { background: #070754; color: white; font-size: 7px; letter-spacing: 1px; padding: 8px 7px; text-align: left; text-transform: uppercase; }
        .items th.center, .items td.center { text-align: center; }
        .items th.money, .items td.money { text-align: right; }
        .items td { border-bottom: 1px solid #eef0f3; padding: 11px 7px; vertical-align: top; }
        .item-name { color: #4b5563; line-height: 1.5; }
        .bottom { border-top: 1px solid #eef0f3; margin-top: 145px; padding-top: 20px; width: 100%; }
        .bottom td { vertical-align: top; }
        .terms { font-size: 7px; line-height: 1.7; padding-right: 40px; width: 62%; }
        .terms-title { color: #27272a; font-size: 8px; font-weight: bold; letter-spacing: .5px; margin-bottom: 5px; }
        .totals { border-collapse: collapse; width: 100%; }
        .totals td { padding: 3px 7px; text-align: right; }
        .totals td:first-child { letter-spacing: .5px; text-transform: uppercase; }
        .total-row td { background: #ffe0e3; color: #e30613; font-size: 10px; font-weight: bold; padding-bottom: 7px; padding-top: 7px; }
        .dealer { color: #4b5563; font-size: 8px; line-height: 1.6; margin-top: 58px; text-align: center; }
        .thanks { color: #e30613; font-size: 23px; font-weight: 300; letter-spacing: 5px; margin-top: 18px; text-align: center; }
    </style>
</head>
<body>
    <div class="top">
        <div class="title">RECEIPT</div>
        <div class="logo">
            @if(extension_loaded('gd'))
                <img src="{{ public_path('logo.png') }}" alt="ShopICT logo">
            @else
                <div class="logo-text">SHOPICT</div>
            @endif
        </div>
    </div>

    <div class="order-strip">
        ORDER NO. {{ $order->order_number }}
        <span class="right">DATE: {{ $order->created_at->format('d/m/Y H:i:s') }}</span>
    </div>

    <table class="info">
        <tr>
            <td>
                <div class="section-title">{{ strtoupper(config('app.name', 'ShopICT')) }}</div>
                <div class="line">
                    Phone: +254713869018<br>
                    Nairobi, Laxmi Plaza, 3rd Flr, Room No 5<br>
                    Email: ictgadgetsshop@gmail.com<br>
                    Website: www.shopictgadgets.co.ke
                </div>
            </td>
            <td>
                <div class="section-title">BILLED TO</div>
                <div class="line">{{ $order->customer_name ?: 'Walk-in customer' }}<br>Phone: {{ $order->customer_phone ?: 'Not provided' }}</div>
            </td>
            <td>
                <div class="section-title">PAYMENT METHOD</div>
                <div class="line">{{ strtoupper($order->payment_method) }}</div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr><th class="center" style="width: 7%">No.</th><th style="width: 53%">Product / Service</th><th class="center" style="width: 10%">Quantity</th><th class="money" style="width: 15%">Unit Price</th><th class="money" style="width: 15%">Total</th></tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td class="item-name"><b>{{ $item->name_snapshot }}</b> | {{ $item->serial_snapshot ?: 'No serial' }} (Warranty {{ $item->warranty_months ?? 0 }} month{{ ($item->warranty_months ?? 0) === 1 ? '' : 's' }})</td>
                    <td class="center">{{ $item->quantity }}</td>
                    <td class="money">KES {{ number_format($item->unit_price, 2) }}</td>
                    <td class="money">KES {{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="bottom">
        <tr>
            <td class="terms">
                <div class="terms-title">TERMS &amp; CONDITIONS</div>
                Products are covered by the warranty period shown beside each item.<br>
                Warranty excludes physical or liquid damage, broken screens, and misuse.<br>
                Warranty is non-transferable and requires this receipt.<br>
                Keep this receipt for all warranty claims.
            </td>
            <td style="width: 38%">
                <table class="totals">
                    <tr><td>Subtotal</td><td>KES {{ number_format($order->subtotal, 2) }}</td></tr>
                    <tr><td>Discount</td><td>KES {{ number_format($order->discount, 2) }}</td></tr>
                    <tr class="total-row"><td>Total</td><td>KES {{ number_format($order->total, 2) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="dealer">Dealers in IT products, Electronics, Accessories, Phones, Homewear, Servers, Networking Accessories, etc.</div>
    <div class="thanks">THANK YOU</div>
</body>
</html>
