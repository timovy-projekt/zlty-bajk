<x-app-layout>

    @php

        $activeTab = match (request('tab')) {
            'documents' => 'documents',
            'rental' => 'rental',
            default => 'personal-details',
        };
    @endphp

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                    Dobrý deň, {{ $profile['first_name'] }}
                </h1>

                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Prehľad vášho účtu.
                </p>
            </div>

            @if ($activeTab === 'personal-details')
                <x-card id="personal-details" title="Osobné údaje"
                    description="Vaše kontaktné a adresné údaje uvedené pri registrácii.">
                    <x-slot name="actions">
                        <a href="{{ route('profile.edit') }}"
                            class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300">
                            Upraviť údaje
                        </a>
                    </x-slot>

                    <x-detail-list :columns="2">
                        <x-detail-item label="Meno" :value="$profile['first_name']" />
                        <x-detail-item label="Priezvisko" :value="$profile['last_name']" />
                        <x-detail-item label="Dátum narodenia" :value="$profile['date_of_birth']->format('d.m.Y')" />
                        <x-detail-item label="Číslo OP" :value="$profile['id_number']" />

                        <x-detail-item label="Email">
                            <span class="inline-flex flex-wrap items-center justify-end gap-2">
                                <span>{{ $profile['email'] }}</span>

                                <x-badge :type="$profile['email_verified'] ? 'success' : 'warning'">
                                    {{ $profile['email_verified'] ? 'Overený' : 'Neoverený' }}
                                </x-badge>
                            </span>
                        </x-detail-item>

                        <x-detail-item label="Telefón" :value="$profile['phone']" />
                        <x-detail-item label="Ulica, č. domu" :value="$profile['street_address']" />
                        <x-detail-item label="PSČ" :value="$profile['postal_code']" />
                        <x-detail-item label="Mesto" :value="$profile['city']" />
                        <x-detail-item label="Registrovaný od" :value="$profile['created_at']->format('d.m.Y')" />

                        <x-detail-item label="Stav účtu">
                            <x-badge :type="$profile['is_active'] ? 'success' : 'neutral'">
                                {{ $profile['is_active'] ? 'Aktívny' : 'Neaktívny' }}
                            </x-badge>
                        </x-detail-item>
                    </x-detail-list>
                </x-card>
            @endif

            @if ($activeTab === 'documents')
                <x-card id="documents" title="Dokumenty" description="Nájomná zmluva, VOP, GDPR">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                    <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                        <li class="flex items-center justify-between py-3">
                            <span class="text-gray-900 dark:text-gray-100">Nájomná zmluva</span>
                            <a href="{{ route('documents.rental') }}" target="_blank"
                                class="text-sm font-semibold text-brand-600 hover:text-brand-500">
                                Zobraziť PDF
                            </a>
                        </li>
                        <li class="flex items-center justify-between py-3">
                            <span class="text-gray-900 dark:text-gray-100">Všeobecné obchodné podmienky</span>
                            <a href="{{ route('documents.vop') }}" target="_blank"
                                class="text-sm font-semibold text-brand-600 dark:text-brand-500 hover:text-brand-500">Zobraziť
                                PDF</a>
                        </li>
                        <li class="flex items-center justify-between py-3">
                            <span class="text-gray-900 dark:text-gray-100">Informácie o spracúvaní osobných údajov
                                (GDPR)</span>
                            <a href="{{ route('documents.gdpr') }}" target="_blank"
                                class="text-sm font-semibold text-brand-600 dark:text-brand-500 hover:text-brand-500">Zobraziť
                                PDF</a>
                        </li>
                        @if (file_exists(public_path('payment-preview.pdf')))
                            <li class="flex items-center justify-between py-3">
                                <span class="text-gray-900 dark:text-gray-100">
                                    Predpis úhrady
                                    <span class="text-xs font-normal text-gray-500 dark:text-gray-400">(náhľad s ukážkovými údajmi)</span>
                                </span>
                                <a href="{{ asset('payment-preview.pdf') }}" target="_blank"
                                    class="text-sm font-semibold text-brand-600 dark:text-brand-500 hover:text-brand-500">Zobraziť
                                    PDF</a>
                            </li>
                        @endif
                    </ul>
                    </p>
                </x-card>
            @endif

            @if ($activeTab === 'rental')
                @php
                    $today = \Illuminate\Support\Carbon::today();
                    $requestedStart = request('start_date');
                    $rentalStartDate = $today->copy();

                    if (is_string($requestedStart)) {
                        try {
                            $candidate = \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $requestedStart)->startOfDay();

                            if ($candidate->format('Y-m-d') === $requestedStart && $candidate->gte($today)) {
                                $rentalStartDate = $candidate;
                            }
                        } catch (\Throwable) {
                            // Neplatný dátum – zostane dnešok.
                        }
                    }

                    $rentalEndDate = $rentalStartDate->copy()->addDays(365);
                    $rentalBirthDate = $profile['date_of_birth']
                        ? \Illuminate\Support\Carbon::parse($profile['date_of_birth'])
                        : null;
                    $needsGuardian = $rentalBirthDate
                        && $rentalBirthDate->gt($rentalStartDate->copy()->subYearsNoOverflow(18))
                        && $rentalBirthDate->lte($rentalStartDate);
                @endphp

                <x-card id="rental" title="Nový prenájom bicykla"
                    description="Žiadosť o prenájom bicykla na 1 rok.">
                    <div class="space-y-6">
                        <div class="flex flex-wrap items-start gap-6">

                            <form method="GET" action="{{ route('dashboard') }}">
                                <input type="hidden" name="tab" value="rental">

                                <div>
                                    <x-input-label for="rental_start_date" value="Prenájom od" />
                                    <x-text-input id="rental_start_date" class="block mt-1 w-full" type="date"
                                        name="start_date" :value="$rentalStartDate->toDateString()"
                                        min="{{ $today->toDateString() }}" required />
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        Môžete zvoliť aj budúci dátum.
                                    </p>
                                </div>

                                <div class="mt-3">
                                    <x-secondary-button type="submit">Prepočítať dátum</x-secondary-button>
                                </div>
                            </form>

                            <div>
                                <x-input-label for="rental_end_date" value="Prenájom do" />
                                <x-text-input id="rental_end_date" class="block mt-1 w-full" type="date"
                                    :value="$rentalEndDate->toDateString()" readonly />
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Automaticky 365 dní od dátumu začiatku.
                                </p>
                            </div>
                        </div>

                        <form method="POST" action="#" class="space-y-6">
                            @csrf
                            <input type="hidden" name="start_date" value="{{ $rentalStartDate->toDateString() }}">
                            <input type="hidden" name="end_date" value="{{ $rentalEndDate->toDateString() }}">

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div class="sm:col-span-2">
                                    <x-input-label for="bike_type" value="Typ bicykla" />
                                    <select id="bike_type" name="bike_type" required
                                        class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 dark:focus:border-brand-600 focus:ring-brand-500 dark:focus:ring-brand-600 rounded-md shadow-sm">
                                        <option value="standard">Štandardný</option>
                                        <option value="junior">Juniorský (26" kolesá, menší rám)</option>
                                        <option value="senior">Seniorský (nižší posed)</option>
                                    </select>
                                </div>

                                <div>
                                    <x-input-label for="id_card" value="Číslo OP" />
                                    <x-text-input id="id_card" class="block mt-1 w-full" type="text"
                                        name="id_card" pattern="^[A-Za-z]{2}[0-9]{6}$"
                                        placeholder="AB123456" autocomplete="off" required />
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        Formát: 2 písmená a 6 číslic, napr. AB123456.
                                    </p>
                                </div>

                                @if ($needsGuardian)
                                    <div>
                                        <x-input-label for="guardian_id_card" value="Číslo OP zákonného zástupcu" />
                                        <x-text-input id="guardian_id_card" class="block mt-1 w-full" type="text"
                                            name="guardian_id_card" pattern="^[A-Za-z]{2}[0-9]{6}$"
                                            placeholder="AB123456" autocomplete="off" required />
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            Povinné pre žiadateľov mladších ako 18 rokov.
                                        </p>
                                    </div>
                                @endif
                            </div>

                            <div class="flex flex-wrap items-center gap-3">
                                <x-primary-button type="button">Odoslať žiadosť</x-primary-button>
                            </div>
                        </form>
                    </div>
                </x-card>
            @endif


        </div>
    </div>
    </x-app-layout>
