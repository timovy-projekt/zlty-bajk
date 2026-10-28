@extends('layouts.app')

@section('content')

    <section class="hero"><div class="wrap"><div><div class="eyebrow line">Trnava · Slovensko</div>
                <h1>Požičaj si bicykel <em>v Trnave jednoducho</em> online</h1>
                <p class="mut">Moderná platforma pre mestskú požičovňu bicyklov. Registrácia, rezervácia aj platba – všetko online, kdekoľvek a kedykoľvek.</p><br>
                <a class="btn pri lg" href="{{ route('registracia') }}">Registrovať sa</a> <a class="btn lg" href="#postup">Zistiť viac</a>
                <div class="stats"><div><b>120+</b><small>bicyklov v meste</small></div><div><b>2 400+</b><small>registrovaných užívateľov</small></div><div><b>15 km</b><small>priemerná jazda</small></div></div></div>
            <img src="{{ asset('img/hero.jpg') }}" alt="Žena na bicykli v Trnave"></div></section>

    <section class="alt" id="postup"><div class="wrap"><div class="eyebrow">Postup</div><h2>Ako to funguje</h2><br>
            <div class="grid g3"><div class="card acc"><div class="num">01</div><h3>Registrácia</h3><p class="mut">Vytvorte si účet jednoducho online. Stačí vyplniť základné údaje a potvrdiť e-mail.</p></div>
                <div class="card acc"><div class="num">02</div><h3>Rezervácia bicykla</h3><p class="mut">Vyberte si bicykel z dostupných v meste Trnava a rezervujte ho na požadovaný termín.</p></div>
                <div class="card acc"><div class="num">03</div><h3>Úhrada a jazda</h3><p class="mut">Zaplaťte jednoducho QR kódom, podpíšte zmluvu online a vyrazte na bicykli!</p></div></div></div></section>

    <section><div class="wrap grid g2" style="align-items:center"><div><div class="eyebrow">Výhody</div><h2>Všetko, čo potrebuješ</h2><p class="mut">Naša platforma ti ponúka kompletný digitálny zážitok – od registrácie až po vrátenie bicykla.</p><div class="bar"></div></div>
            <div><div class="feat"><div class="ic">📄</div><div><b>Online zmluva</b><p class="mut">Celý zmluvný proces prebieha digitálne.</p></div></div>
                <div class="feat"><div class="ic">▦</div><div><b>QR kód pre platbu</b><p class="mut">Rýchla a bezpečná platba cez QR kód priamo vo vašom telefóne.</p></div></div>
                <div class="feat"><div class="ic">🗂</div><div><b>Prehľad údajov a dokumentov</b><p class="mut">Rezervácie, zmluvy a faktúry na jednom mieste.</p></div></div>
                <div class="feat"><div class="ic">🔔</div><div><b>Notifikácie</b><p class="mut">Upozornenia o stave rezervácie a termínoch vrátenia.</p></div></div></div></div></section>

    <section class="cta" id="cennik"><div class="wrap"><h2>Začni bajkovať ešte dnes</h2><p>Zaregistruj sa zadarmo a vyrazte na bicykli po Trnave.</p><br><a class="btn lg" href="{{ route('registracia') }}">Registrovať sa</a></div></section>

@endsection
