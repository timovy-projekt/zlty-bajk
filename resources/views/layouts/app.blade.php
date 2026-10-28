<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Bajkuj v meste')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        try { document.documentElement.dataset.theme = localStorage.getItem('theme') || 'light'; } catch (e) {}
    </script>
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('img/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('img/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('img/favicon-16x16.png') }}">
    <link rel="manifest" href="{{ asset('img/site.webmanifest') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

<header>
    <div class="wrap">
        <a class="brand" href="{{ url('/') }}"><img src="{{ asset('img/logo.png') }}" alt="Logo">Bajkuj v meste</a>
        <nav>
            <a href="{{ url('/') }}">Domov</a>
            <a href="{{ url('/') }}#postup">Ako to funguje</a>
            <a href="{{ url('/') }}#cennik">Cenník</a>
            <a href="#kontakt">Kontakt</a>
        </nav>
        <div class="acts">
            <button class="theme" type="button" id="themeBtn" aria-label="Zmeniť tému">◐</button>
            <a class="btn" href="#">Prihlásiť sa</a>
            <a class="btn pri" href="{{ route('registracia') }}">Registrovať sa</a>
        </div>
    </div>
</header>

<main>
    @yield('content')
</main>

<footer id="kontakt">
    <div class="wrap">
        <div><b>Bajkuj v meste</b><p>Mestská požičovňa bicyklov v Trnave.</p></div>
        <div><b>Kontakt</b><p>info@bajkujvmeste.sk<br>Trnava</p></div>
        <div><b>Projekt</b><p>Tímový projekt UCM FPV</p></div>
    </div>
    <div class="copy">© {{ date('Y') }} Bajkuj v meste · Tímový projekt UCM FPV</div>
</footer>

<script>
    document.getElementById('themeBtn').addEventListener('click', function () {
        var dark = document.documentElement.dataset.theme !== 'dark';
        document.documentElement.dataset.theme = dark ? 'dark' : 'light';
        try { localStorage.setItem('theme', dark ? 'dark' : 'light'); } catch (e) {}
    });
</script>

</body>
</html>
