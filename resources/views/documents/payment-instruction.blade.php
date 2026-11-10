<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="utf-8">
    <title>Predpis úhrady</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; line-height: 1.5; color: #111; }
        h1 { font-size: 18px; text-align: center; margin: 0 0 4px; }
        h2 { font-size: 12px; margin: 18px 0 6px; }
        .sub { text-align: center; color: #555; margin-bottom: 22px; }
        table.parties { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        table.parties td { width: 50%; vertical-align: top; padding: 8px 10px; border: 1px solid #ccc; }
        table.details { width: 100%; border-collapse: collapse; }
        table.details th, table.details td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; vertical-align: top; }
        table.details th { width: 35%; background: #f5f5f5; color: #444; font-weight: normal; }
        .amount { font-size: 15px; font-weight: bold; }
        .vs { font-size: 13px; font-weight: bold; letter-spacing: 1px; }
        .qr { margin-top: 22px; text-align: center; }
        .qr-placeholder { display: inline-block; width: 130px; height: 130px; border: 1px dashed #999; color: #777; font-size: 10px; padding-top: 50px; box-sizing: border-box; }
        .qr-note { margin-top: 6px; font-size: 9px; color: #777; }
        .note { margin-top: 24px; font-size: 9px; color: #777; text-align: center; }
    </style>
</head>
<body>
    @php
        $data = $data ?? [
            'customer_name' => 'Ján Novák',
            'customer_address' => 'J. Herdu 1, 917 01 Trnava',
            'customer_email' => 'jan.novak@example.com',
            'customer_phone' => '+421 900 123 456',
            'rental_id' => 1,
            'bike_type' => 'Štandardný',
            'start_date' => now(),
            'end_date' => now()->addDays(365),
            'amount' => 30.00,
            'currency' => 'EUR',
            'iban' => 'SK00 0000 0000 0000 0000 0000',
            'account_number' => '1234567890/1100',
            'swift' => 'XXXXSKBA',
            'beneficiary' => 'Bajkuj v meste',
            'beneficiary_address' => 'Trnava',
            'beneficiary_email' => 'info@bajkujvmeste.sk',
            'variable_symbol' => '0000010001',
            'constant_symbol' => '0308',
            'due_date' => now()->addDays(14),
            'note' => 'Prenájom bicykla č. 1',
            'qr_data_uri' => null,
        ];

        $formatDate = fn ($value): string => \Illuminate\Support\Carbon::parse($value)->format('d.m.Y');
    @endphp

    <h1>Predpis úhrady</h1>
    <div class="sub">Platobný dokument k žiadosti o prenájom bicykla</div>

    <table class="parties">
        <tr>
            <td>
                <strong>Prijímateľ</strong><br>
                {{ $data['beneficiary'] }}<br>
                {{ $data['beneficiary_address'] }}<br>
                {{ $data['beneficiary_email'] }}
            </td>
            <td>
                <strong>Platiteľ</strong><br>
                {{ $data['customer_name'] }}<br>
                {{ $data['customer_address'] }}<br>
                {{ $data['customer_email'] }}<br>
                {{ $data['customer_phone'] }}
            </td>
        </tr>
    </table>

    <h2>Údaje o prenájme</h2>
    <table class="details">
        <tr>
            <th>Číslo prenájmu</th>
            <td>{{ $data['rental_id'] }}</td>
        </tr>
        <tr>
            <th>Typ bicykla</th>
            <td>{{ $data['bike_type'] }}</td>
        </tr>
        <tr>
            <th>Prenájom od</th>
            <td>{{ $formatDate($data['start_date']) }}</td>
        </tr>
        <tr>
            <th>Prenájom do</th>
            <td>{{ $formatDate($data['end_date']) }}</td>
        </tr>
    </table>

    <h2>Platobné údaje</h2>
    <table class="details">
        <tr>
            <th>IBAN</th>
            <td>{{ $data['iban'] }}</td>
        </tr>
        <tr>
            <th>Číslo účtu</th>
            <td>{{ $data['account_number'] }}</td>
        </tr>
        <tr>
            <th>SWIFT / BIC</th>
            <td>{{ $data['swift'] }}</td>
        </tr>
        <tr>
            <th>Suma</th>
            <td><span class="amount">{{ number_format($data['amount'], 2, ',', ' ') }} {{ $data['currency'] }}</span></td>
        </tr>
        <tr>
            <th>Variabilný symbol</th>
            <td><span class="vs">{{ $data['variable_symbol'] }}</span></td>
        </tr>
        <tr>
            <th>Konštantný symbol</th>
            <td>{{ $data['constant_symbol'] }}</td>
        </tr>
        <tr>
            <th>Splatnosť</th>
            <td>{{ $formatDate($data['due_date']) }}</td>
        </tr>
        <tr>
            <th>Poznámka pre prijímateľa</th>
            <td>{{ $data['note'] }}</td>
        </tr>
    </table>

    <div class="qr">
        @if (! empty($data['qr_data_uri']))
            <img src="{{ $data['qr_data_uri'] }}" alt="QR kód" width="130" height="130">
        @else
            <div class="qr-placeholder">
                QR kód<br>
                <span>bude doplnený neskôr</span>
            </div>
        @endif

        <p class="qr-note">
            Po napojení backendu bude QR kód obsahovať platobné údaje pre PAY by square.
        </p>
    </div>

    <div class="note">
        Vygenerované dňa {{ now()->format('d.m.Y H:i') }} · Ukážkový dokument pre školský projekt
    </div>
</body>
</html>
