<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">

        @csrf

        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 my-4">Registrácia záujemcu</h2>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <!-- First Name -->
            <div>
                <x-input-label for="first_name" value="Meno"/>
                <x-text-input id="first_name" class="block mt-1 w-full" type="text" name="first_name"
                              :value="old('first_name')" required autofocus autocomplete="given-name"
                              placeholder="Ján"/>
                <x-input-error :messages="$errors->get('first_name')" class="mt-2"/>
            </div>

            <!-- Last Name -->
            <div>
                <x-input-label for="last_name" value="Priezvisko"/>
                <x-text-input id="last_name" class="block mt-1 w-full" type="text" name="last_name"
                              :value="old('last_name')" required autocomplete="family-name" placeholder="Holý"/>
                <x-input-error :messages="$errors->get('last_name')" class="mt-2"/>
            </div>


            <!-- Phone -->
            <div>
                <x-input-label for="phone" value="Telefón"/>
                <x-text-input id="phone" class="block mt-1 w-full" type="tel" name="phone" :value="old('phone')"
                              autocomplete="tel" placeholder="+421 900 123 456" pattern="^\+?[0-9\s]{9,17}$"/>
                <x-input-error :messages="$errors->get('phone')" class="mt-2"/>

            </div>

            <!-- Email Address -->
            <div>
                <x-input-label for="email" value="Email"/>
                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')"
                              required autocomplete="username" placeholder="vas@email.com"/>
                <x-input-error :messages="$errors->get('email')" class="mt-2"/>
            </div>


            <!-- Street Address -->
            <div>
                <x-input-label for="street_address" value="Ulica, č. domu"/>
                <x-text-input id="street_address" class="block mt-1 w-full" type="text" name="street_address"
                              :value="old('street_address')" autocomplete="address-line1" placeholder="J.Herdu 1" required/>
                <x-input-error :messages="$errors->get('street_address')" class="mt-2"/>
            </div>

            <!-- Postal Code -->

            <div>
                <x-input-label for="postal_code" value="PSČ"/>
                <x-text-input id="postal_code" class="block mt-1 w-full" type="text" name="postal_code"
                              :value="old('postal_code')" placeholder="123 45" required/>
                <x-input-error :messages="$errors->get('postal_code')" class="mt-2"/>
            </div>

            <!-- City -->

            <div>
                <x-input-label for="city" value="Mesto"/>
                <x-text-input id="city" class="block mt-1 w-full" type="text" name="city"
                              :value="old('city')" placeholder="Trnava" required/>
                <x-input-error :messages="$errors->get('city')" class="mt-2"/>
            </div>

            <!-- Date of Birth -->
            <div>
                <x-input-label for="date_of_birth" value="Dátum narodenia"/>
                <x-text-input id="date_of_birth" class="block mt-1 w-full" type="date" name="date_of_birth"
                              :value="old('date_of_birth')" max="{{ now()->toDateString() }}" autocomplete="bday"
                              required/>
                <x-input-error :messages="$errors->get('date_of_birth')" class="mt-2"/>
            </div>

            <!-- ID Card Number -->

            {{--rozsirit beckend model/controller a  databazu od id_number --}}
            <div>
                <x-input-label for="id_number" value="číslo OP"/>
                <x-text-input id="id_number" class="block mt-1 w-full" type="text" name="id_number"
                              :value="old('id_number')" placeholder="AA123456"/>
                <x-input-error :messages="$errors->get('id_number')" class="mt-2"/>
            </div>

            @php
                $hasIDCard = true; //kontroller musi zvalidovat datum narodenia a poslat do registracneho formularu kvoli zobrazeniu/nezobrazeniu

            /*
             public function store(Request $request)
                    {
                        $request->validate([
                            'date_of_birth' => ['required', 'date', 'before:today'],
                        ]);

                        $vek = Carbon::parse($request->date_of_birth)->age;

                        return view('formular', [
                            'hasIDCard' => $age >= 15,
                        ]);
                    }
             */
            @endphp

            @if($hasIDCard)

                <!-- Legal Guardian (parent data required by participants under 15) -->
                <div class="sm:col-span-2">
                    <div
                        class="border border-gray-200 dark:border-gray-700 rounded-md p-4 space-y-4 bg-gray-50 dark:bg-gray-800/50">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Ak je záujemca mladší ako 15 rokov, je potrebné uviesť údaje zákonného zástupcu.
                        </p>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div>
                                <x-input-label for="guardian_first_name" value="Meno zástupcu"/>
                                <x-text-input id="guardian_first_name" class="block mt-1 w-full" type="text"
                                              name="guardian_first_name" :value="old('guardian_first_name')"
                                              autocomplete="off"/>
                                <x-input-error :messages="$errors->get('guardian_first_name')" class="mt-2"/>
                            </div>

                            <div>
                                <x-input-label for="guardian_last_name" value="Priezvisko zástupcu"/>
                                <x-text-input id="guardian_last_name" class="block mt-1 w-full" type="text"
                                              name="guardian_last_name" :value="old('guardian_last_name')"
                                              autocomplete="off"/>
                                <x-input-error :messages="$errors->get('guardian_last_name')" class="mt-2"/>
                            </div>

                            <div>
                                <x-input-label for="guardian_id_number" value="Č. OP zástupcu"/>
                                <x-text-input id="guardian_id_number" class="block mt-1 w-full" type="text"
                                              name="guardian_id_number" :value="old('guardian_id_number')"
                                              placeholder="AA123456" autocomplete="off"/>
                                <x-input-error :messages="$errors->get('guardian_id_number')" class="mt-2"/>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{--<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 my-4">Prihlasovacie údaje</h2>--}}

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 my-4">


            <!-- Password -->
            <div>
                <x-input-label for="password" value="Heslo"/>
                <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required
                              autocomplete="new-password"/>
                <x-input-error :messages="$errors->get('password')" class="mt-2"/>
            </div>

            <!-- Confirm Password -->
            <div>
                <x-input-label for="password_confirmation" value="Potvrdenie hesla"/>
                <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password"
                              name="password_confirmation" required autocomplete="new-password"/>
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2"/>
            </div>
        </div>

        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Súhlasy</h2>

        <label class="flex items-start mt-2">
            <input type="checkbox" name="terms" value="1" required
                   class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"/>
            <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">
                Súhlasím s obchodnými podmienkami a <a href="#"
                                                       class="underline text-gray-900 dark:text-gray-100 hover:text-gray-600 dark:hover:text-gray-300">VOP</a>. *
            </span>
            <x-input-error :messages="$errors->get('terms')" class="mt-2 w-full"/>
        </label>

        <label class="flex items-start mt-4">
            <input type="checkbox" name="gdpr_consent" value="1" required
                   class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"/>
            <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">
                Súhlasím so spracovaním osobných údajov podľa <a href="#"
                                                                 class="underline text-gray-900 dark:text-gray-100 hover:text-gray-600 dark:hover:text-gray-300">GDPR</a>. *
            </span>
            <x-input-error :messages="$errors->get('gdpr_consent')" class="mt-2 w-full"/>
        </label>

        <div class="flex items-center justify-end mt-6">
            <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800"
               href="{{ route('login') }}">
                Už ste zaregistrovaní?
            </a>

            <x-primary-button class="ms-4 me-4">
                Registrácia
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
