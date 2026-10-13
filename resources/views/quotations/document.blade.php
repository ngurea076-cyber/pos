<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Quotation {{ $quotation->quotation_number }}</title>
    <style>
        @page { margin: 38px 50px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #1f2937; font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        .navy { color: #070754; }
        .red { color: #e30613; }
        .top { height: 78px; position: relative; }
        .title { color: #c9000c; font-size: 36px; font-weight: 500; letter-spacing: 6px; line-height: 1; padding-top: 16px; }
        .logo { position: absolute; right: 0; top: 3px; height: 58px; width: 78px; text-align: center; }
        .logo img { max-height: 58px; max-width: 78px; }
        .logo-text { color: #070754; font-size: 12px; font-weight: bold; padding-top: 20px; }
        .quotation-strip { background: #070754; color: white; font-size: 9px; font-weight: bold; letter-spacing: .5px; padding: 6px 8px; }
        .quotation-strip .right { float: right; }
        .info { margin-top: 25px; width: 100%; }
        .info td { padding-right: 20px; vertical-align: top; width: 31%; }\n        .info td:first-child { width: 38%; }\n        .info td:nth-child(2) { width: 31%; }
        .info td:last-child { padding-right: 0; }
        .section-title { color: #111827; font-size: 12px; font-weight: bold; letter-spacing: .4px; margin-bottom: 8px; }
        .line { line-height: 1.65; }
        .items { border-collapse: collapse; margin-top: 45px; width: 100%; }
        .items th { background: #070754; color: white; font-size: 9px; letter-spacing: .7px; padding: 9px 7px; text-align: left; text-transform: uppercase; }
        .items th.center, .items td.center { text-align: center; }
        .items th.money, .items td.money { text-align: right; }
        .items td { border-bottom: 1px solid #e1e5eb; padding: 11px 7px; vertical-align: top; }
        .item-name { color: #111827; font-size: 12px; line-height: 1.6; }
        .bottom { border-top: 1px solid #eef0f3; margin-top: 145px; padding-top: 20px; width: 100%; }
        .bottom td { vertical-align: top; }
        .terms { color: #1f2937; font-size: 9px; line-height: 1.7; padding-right: 40px; width: 62%; }
        .terms-title { color: #111827; font-size: 9px; font-weight: bold; letter-spacing: .5px; margin-bottom: 5px; }
        .totals { border-collapse: collapse; width: 100%; }
        .totals td { color: #1f2937; font-size: 10px; padding: 4px 7px; text-align: right; }
        .totals td:first-child { letter-spacing: .5px; text-transform: uppercase; }
        .total-row td { background: #ffe0e3; color: #c9000c; font-size: 12px; font-weight: bold; padding-bottom: 7px; padding-top: 7px; }
        .dealer { color: #1f2937; font-size: 9px; line-height: 1.6; margin-top: 58px; text-align: center; }
        .thanks { color: #c9000c; font-size: 24px; font-weight: 500; letter-spacing: 5px; margin-top: 18px; text-align: center; }
    </style>
</head>
<body>
    <div class="top">
        <div class="title">QUOTATION</div>
        <div class="logo">
            @if(extension_loaded('gd'))
                <img src="{{ public_path($business['logo']) }}" alt="{{ $business['business_name'] }} logo">
            @else
                <div class="logo-text">{{ strtoupper($business['business_name']) }}</div>
            @endif
        </div>
    </div>

    <div class="quotation-strip">
        QUOTATION NO. {{ $quotation->quotation_number }}
        <span class="right">DATE: {{ $quotation->issued_at->format('d/m/Y') }}</span>
    </div>

    <table class="info">
        <tr>
            <td>
                <div class="section-title">{{ strtoupper($business['business_name']) }}</div>
                <div class="line">
                    @if($business['phone'])Phone: {{ $business['phone'] }}<br>@endif
                    @if($business['po_box'])P.O. Box: {{ $business['po_box'] }}<br>@endif
                    @if($business['address']){{ $business['address'] }}<br>@endif
                    @if($business['email'])Email: {{ $business['email'] }}<br>@endif
                    @if($business['website'])Website: {{ $business['website'] }}<br>@endif
                    @if($business['registration']){{ $business['registration'] }}@endif
                </div>
            </td>
            <td>
                <div class="section-title">BILLED TO</div>
                <div class="line">{{ $quotation->bill_to }}<br>Valid until: {{ $quotation->expires_at->format('d/m/Y') }}</div>
            </td>
            <td>
                <div class="section-title">PAYMENT METHOD</div>
                <div class="line">{{ $business['payment_method'] }}<br>Paybill: {{ $business['paybill'] }}<br>Account: {{ $business['payment_account'] }}<br>{{ $business['payment_account_name'] }}</div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr><th class="center" style="width: 7%">No.</th><th style="width: 53%">Product / Service</th><th class="center" style="width: 10%">Quantity</th><th class="money" style="width: 15%">Unit Price</th><th class="money" style="width: 15%">Total</th></tr>
        </thead>
        <tbody>
            @foreach($quotation->items as $item)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td class="item-name"><b>{{ $item->name }}</b></td>
                    <td class="center">{{ $item->quantity }}</td>
                    <td class="money">KES {{ number_format($item->unit_price, 2) }}</td>
                    <td class="money">KES {{ number_format($item->amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="bottom">
        <tr>
            <td class="terms">
                <div class="section-title">SERVED BY</div>
                <div class="line">{{ $business['served_by'] }}</div>
                <div class="terms-title">TERMS &amp; CONDITIONS</div>
                {{ $quotation->terms ?: 'This quotation is valid for 5 days from the date of issue.' }}<br>
                Prices are stated in Kenyan Shillings (KES).<br>
                Thank you for considering our offer.
            </td>
            <td style="width: 38%">
                <table class="totals">
                    <tr><td>Subtotal</td><td>KES {{ number_format($quotation->subtotal, 2) }}</td></tr>
                    @if($quotation->discount > 0)<tr><td>Discount</td><td>− KES {{ number_format($quotation->discount, 2) }}</td></tr>@endif
                    @if($quotation->vat_percent)<tr><td>VAT ({{ $quotation->vat_percent }}%)</td><td>KES {{ number_format($quotation->vat_amount, 2) }}</td></tr>@endif
                    <tr class="total-row"><td>Total</td><td>KES {{ number_format($quotation->total, 2) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="dealer">{{ $business['dealer_line'] }}<br>Quotation valid for 5 days from date of issue.</div>
    <div class="thanks">THANK YOU</div>
</body>
</html>
