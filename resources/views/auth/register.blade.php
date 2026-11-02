@extends('layouts.public')

@section('title', 'Registrácia')

@section('content')
    <section class="alt"><div class="wrap"><div class="form-card card">
                <div class="eyebrow line">Nový účet</div>
                <h2>Registrácia <em>záujemcu</em></h2>
                <p class="mut">Vytvor si účet a my ti po schválení pridelíme bicykel.</p><br>

                <form method="POST" action="{{ route('register') }}">
                    @csrf

                    <label for="name">Meno *</label>
                    <input id="name" name="name" value="{{ old('name') }}" required autofocus autocomplete="name">
                    @error('name') <small class="err">{{ $message }}</small> @enderror

                    <label for="email">E-mail *</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username">
                    @error('email') <small class="err">{{ $message }}</small> @enderror

                    <div class="grid g2">
                        <div>
                            <label for="password">Heslo *</label>
                            <input id="password" type="password" name="password" required autocomplete="new-password">
                            @error('password') <small class="err">{{ $message }}</small> @enderror
                        </div>
                        <div>
                            <label for="password_confirmation">Heslo znova *</label>
                            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
                        </div>
                    </div><br>

                    <button class="btn pri lg" style="width:100%">Zaregistrovať sa</button>
                </form>

                <p class="mut" style="text-align:center;margin-top:14px;font-size:.88rem">
                    Už máš účet? <a href="{{ route('login') }}" style="color:var(--y)">Prihlás sa</a>
                </p>
            </div></div></section>
@endsection
