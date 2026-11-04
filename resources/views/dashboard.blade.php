<x-app-layout>
  
    @php

        $activeTab = match(request('tab')) {
              'documents' => 'documents',
             'rental' => 'rental',
              default => 'personal-details',

        }
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
                <x-card
                    id="personal-details"
                    title="Osobné údaje"
                    description="Vaše kontaktné a adresné údaje uvedené pri registrácii."
                >
                    <x-slot name="actions">
                        <a
                            href="{{ route('profile.edit') }}"
                            class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300"
                        >
                            Upraviť údaje
                        </a>
                    </x-slot>

                    <x-detail-list :columns="2">
                        <x-detail-item label="Meno" :value="$profile['first_name']"/>
                        <x-detail-item label="Priezvisko" :value="$profile['last_name']"/>
                        <x-detail-item label="Dátum narodenia" :value="$profile['date_of_birth']"/>
                        <x-detail-item label="Číslo OP" :value="$profile['id_number']"/>

                        <x-detail-item label="Email">
                            <span class="inline-flex flex-wrap items-center justify-end gap-2">
                                <span>{{ $profile['email'] }}</span>

                                <x-badge :type="$profile['email_verified'] ? 'success' : 'warning'">
                                    {{ $profile['email_verified'] ? 'Overený' : 'Neoverený' }}
                                </x-badge>
                            </span>
                        </x-detail-item>

                        <x-detail-item label="Telefón" :value="$profile['phone']"/>
                        <x-detail-item label="Ulica, č. domu" :value="$profile['street_address']"/>
                        <x-detail-item label="PSČ" :value="$profile['postal_code']"/>
                        <x-detail-item label="Mesto" :value="$profile['city']"/>
                        <x-detail-item label="Registrovaný od" :value="$profile['member_since']"/>

                        <x-detail-item label="Stav účtu">
                            <x-badge :type="$profile['is_active'] ? 'success' : 'neutral'">
                                {{ $profile['is_active'] ? 'Aktívny' : 'Neaktívny' }}
                            </x-badge>
                        </x-detail-item>
                    </x-detail-list>
                </x-card>
            @endif

            @if ($activeTab === 'documents')
                <x-card
                    id="documents"
                    title="Dokumenty"
                    description="Nájomná zmluva, VOP, GDPR"
                >
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Dokumenty sa zobrazia na tomto mieste.
                    </p>
                </x-card>
            @endif

            @if ($activeTab === 'rental')
                <x-card
                    id="rental"
                    title="Prenájom"
                    description="Podrobnosti k nájomnej zmluve"
                >
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        podrobnosti k nájomnej zmluve sa zobrazia na tomto mieste.
                    </p>
                </x-card>
            @endif


        </div>
    </div>
</x-app-layout-layout-layout>
