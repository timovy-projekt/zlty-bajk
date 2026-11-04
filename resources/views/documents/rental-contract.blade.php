<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="utf-8">
    <title>Nájomná zmluva</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; line-height: 1.5; color: #111; }
        h1 { font-size: 18px; text-align: center; margin: 0 0 4px; }
        .sub { text-align: center; color: #555; margin-bottom: 22px; }
        h2 { font-size: 12px; margin: 18px 0 6px; }
        table.parties { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        table.parties td { width: 50%; vertical-align: top; padding: 8px 10px; border: 1px solid #ccc; }
        ol { padding-left: 18px; margin: 4px 0; }
        li { margin-bottom: 4px; }
        .sign { width: 100%; margin-top: 46px; }
        .sign td { width: 50%; text-align: center; padding-top: 4px; }
        .line { border-top: 1px solid #111; margin: 0 24px; padding-top: 4px; }
        .note { margin-top: 24px; font-size: 9px; color: #777; text-align: center; }
    </style>
</head>
<body>

    <h1>Nájomná zmluva</h1>
    <div class="sub">na krátkodobý prenájom bicykla · č. {{ str_pad($user->id, 6, '0', STR_PAD_LEFT) }}</div>

    <table class="parties">
        <tr>
            <td>
                <strong>Prenajímateľ</strong><br>
                Bajkuj v meste<br>
                Trnava<br>
                info@bajkujvmeste.sk
            </td>
            <td>
                <strong>Nájomca</strong><br>
                {{ $user->first_name }} {{ $user->last_name }}<br>
                @if($user->street_address){{ $user->street_address }}<br>@endif
                @if($user->postal_code || $user->city){{ $user->postal_code }} {{ $user->city }}<br>@endif
                @if($user->date_of_birth)Dátum narodenia: {{ \Illuminate\Support\Carbon::parse($user->date_of_birth)->format('d.m.Y') }}<br>@endif
                E-mail: {{ $user->email }}
                @if($user->phone)<br>Telefón: {{ $user->phone }}@endif
            </td>
        </tr>
    </table>

    <h2>I. Predmet zmluvy</h2>
    <ol>
        <li>Prenajímateľ prenecháva nájomcovi na dočasné užívanie bicykel: <strong>[typ a označenie bicykla]</strong>, evidenčné číslo <strong>[číslo]</strong>.</li>
        <li>Nájomca sa zaväzuje zaplatiť nájomné a bicykel po skončení nájmu vrátiť v stave zodpovedajúcom bežnému opotrebeniu.</li>
    </ol>

    <h2>II. Doba nájmu</h2>
    <ol>
        <li>Nájom sa dojednáva od <strong>[dátum a čas začiatku]</strong> do <strong>[dátum a čas konca]</strong>.</li>
        <li>Ak nájomca bicykel nevráti včas, uhradí nájomné aj za čas omeškania.</li>
    </ol>

    <h2>III. Nájomné a záloha</h2>
    <ol>
        <li>Nájomné je <strong>[suma] €</strong> podľa platného cenníka prenajímateľa.</li>
        <li>Záloha je <strong>[suma] €</strong>. Vráti sa pri vrátení bicykla bez poškodenia.</li>
    </ol>

    <h2>IV. Práva a povinnosti nájomcu</h2>
    <ol>
        <li>Nájomca používa bicykel v súlade s jeho určením a pravidlami cestnej premávky.</li>
        <li>Bicykel nesmie požičať tretej osobe ani použiť na pretekárske alebo komerčné účely.</li>
        <li>Pri parkovaní je nájomca povinný bicykel riadne zamknúť.</li>
        <li>Poškodenie, stratu alebo krádež bicykla je nájomca povinný bezodkladne oznámiť prenajímateľovi.</li>
    </ol>

    <h2>V. Zodpovednosť za škodu</h2>
    <ol>
        <li>Nájomca zodpovedá za poškodenie, stratu alebo krádež bicykla počas nájmu a uhradí prenajímateľovi preukázanú škodu.</li>
        <li>Bežné opotrebenie sa za poškodenie nepovažuje.</li>
    </ol>

    <h2>VI. Záverečné ustanovenia</h2>
    <ol>
        <li>Súčasťou zmluvy sú Všeobecné obchodné podmienky prenajímateľa a informácie o spracúvaní osobných údajov (GDPR), s ktorými nájomca vyjadruje súhlas.</li>
        <li>Zmluva sa vyhotovuje elektronicky a nadobúda platnosť dňom jej uzavretia.</li>
    </ol>

    <table class="sign">
        <tr>
            <td><div class="line">Prenajímateľ</div></td>
            <td><div class="line">Nájomca</div></td>
        </tr>
    </table>

    <div class="note">
        Vygenerované dňa {{ now()->format('d.m.Y H:i') }} · Ukážkový dokument pre školský projekt
    </div>

</body>
</html>