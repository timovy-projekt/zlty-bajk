@extends('layouts.app')

@section('title', 'Registrácia')

@section('content')
    @php
        $bikes   = ['city' => 'Mestský', 'ebike' => 'Elektrobicykel', 'trekking' => 'Trekingový'];
        $periods = ['1m' => '1 mesiac', '3m' => '3 mesiace', 'season' => 'Sezóna (apríl – október)'];
    @endphp

    <section class="alt"><div class="wrap"><div class="form-card card">
                <h2>Registrácia <em>záujemcu</em></h2>
                <p class="mut">Vyplň údaje, vyber typ bicykla a my ti ho po schválení pridelíme.</p><br>
                <div class="steps"><span class="on"></span><span class="on"></span><span></span></div>

                <form method="POST" action="#" novalidate>
                    @csrf

                    <div class="grid g2">
                        <div>
                            <label for="name">Meno *</label>
                            <input id="name" name="name" value="{{ old('name') }}" required>
                            @error('name') <small class="err">{{ $message }}</small> @enderror
                        </div>
                        <div>
                            <label for="surname">Priezvisko *</label>
                            <input id="surname" name="surname" value="{{ old('surname') }}" required>
                            @error('surname') <small class="err">{{ $message }}</small> @enderror
                        </div>
                    </div>

                    <div class="grid g2">
                        <div>
                            <label for="email">E-mail *</label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" required>
                            @error('email') <small class="err">{{ $message }}</small> @enderror
                        </div>
                        <div>
                            <label for="phone">Telefón *</label>
                            <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" placeholder="+421" required>
                            @error('phone') <small class="err">{{ $message }}</small> @enderror
                        </div>
                    </div>

                    <label for="address">Adresa trvalého pobytu *</label>
                    <input id="address" name="address" value="{{ old('address') }}" placeholder="Ulica, číslo, PSČ, mesto" required>
                    @error('address') <small class="err">{{ $message }}</small> @enderror

                    <div class="grid g2">
                        <div>
                            <label for="birth_date">Dátum narodenia *</label>
                            <input id="birth_date" type="date" name="birth_date" value="{{ old('birth_date') }}" required>
                            @error('birth_date') <small class="err">{{ $message }}</small> @enderror
                        </div>
                        <div>
                            <label for="id_number">Číslo OP *</label>
                            <input id="id_number" name="id_number" value="{{ old('id_number') }}" required>
                            @error('id_number') <small class="err">{{ $message }}</small> @enderror
                        </div>
                    </div>

                    <div class="grid g2">
                        <div>
                            <label for="bike_type">Typ bicykla</label>
                            <select id="bike_type" name="bike_type">
                                @foreach ($bikes as $val => $label)
                                    <option value="{{ $val }}" @selected(old('bike_type') === $val)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="period">Požadované obdobie</label>
                            <select id="period" name="period">
                                @foreach ($periods as $val => $label)
                                    <option value="{{ $val }}" @selected(old('period') === $val)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid g2">
                        <div>
                            <label for="password">Heslo *</label>
                            <input id="password" type="password" name="password" minlength="8" required>
                            @error('password') <small class="err">{{ $message }}</small> @enderror
                        </div>
                        <div>
                            <label for="password_confirmation">Heslo znova *</label>
                            <input id="password_confirmation" type="password" name="password_confirmation" minlength="8" required>
                        </div>
                    </div><br>

                    <label class="chk">
                        <input type="checkbox" name="terms" value="1" @checked(old('terms'))>
                        Súhlasím so všeobecnými podmienkami požičovne.
                    </label>
                    @error('terms') <small class="err">{{ $message }}</small> @enderror

                    <label class="chk">
                        <input type="checkbox" name="gdpr" value="1" @checked(old('gdpr'))>
                        Súhlasím so spracovaním osobných údajov (GDPR).
                    </label>
                    @error('gdpr') <small class="err">{{ $message }}</small> @enderror
                    <br>

                    <button class="btn pri lg" style="width:100%">Odoslať registráciu</button>
                </form>

                <p class="mut" style="text-align:center;margin-top:14px;font-size:.88rem">
                    Už máš účet? <a href="#" style="color:var(--y)">Prihlás sa</a>
                </p>
            </div></div></section>
@endsection
