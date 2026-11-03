<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">

        @csrf

        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 my-4">Registrácia záujemcu</h2>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <!-- First Name -->
            <div>
                <x-input-label for="first_name" value="Meno" />
                <x-text-input id="first_name" class="block mt-1 w-full" type="text" name="first_name"
                    :value="old('first_name')" required autofocus autocomplete="given-name"
                    placeholder="Ján" />
                <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
            </div>

            <!-- Last Name -->
            <div>
                <x-input-label for="last_name" value="Priezvisko" />
                <x-text-input id="last_name" class="block mt-1 w-full" type="text" name="last_name"
                    :value="old('last_name')" required autocomplete="family-name" placeholder="Holý" />
                <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
            </div>


            <!-- Phone -->
            <div>
                <x-input-label for="phone" value="Telefón" />
                <x-text-input id="phone" class="block mt-1 w-full" type="tel" name="phone" :value="old('phone')"
                    autocomplete="tel" placeholder="+421 900 123 456" pattern="^\+?[0-9\s]{9,17}$" />
                <x-input-error :messages="$errors->get('phone')" class="mt-2" />

            </div>

            <!-- Email Address -->
            <div>
                <x-input-label for="email" value="Email" />
                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')"
                    required autocomplete="username" placeholder="jan.holy@gmail.com" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>


            <!-- Street Address -->
            <div>
                <x-input-label for="street_address" value="Ulica, č. domu" />
                <x-text-input id="street_address" class="block mt-1 w-full" type="text" name="street_address"
                    :value="old('street_address')" autocomplete="address-line1" placeholder="J.Herdu 1" required />
                <x-input-error :messages="$errors->get('street_address')" class="mt-2" />
            </div>

            <!-- Postal Code -->

            <div>
                <x-input-label for="postal_code" value="PSČ" />
                <x-text-input id="postal_code" class="block mt-1 w-full" type="text" name="postal_code"
                    :value="old('postal_code')" placeholder="123 45" required pattern="^[0-9]{3}\s?[0-9]{2}$" />
                <x-input-error :messages="$errors->get('postal_code')" class="mt-2" />
            </div>

            <!-- City -->

            <div>
                <x-input-label for="city" value="Mesto" />
                <x-text-input id="city" class="block mt-1 w-full" type="text" name="city"
                    :value="old('city')" placeholder="Trnava" required />
                <x-input-error :messages="$errors->get('city')" class="mt-2" />
            </div>

            <!-- Date of Birth -->
            <div>
                <x-input-label for="date_of_birth" value="Dátum narodenia" />
                <x-text-input id="date_of_birth" class="block mt-1 w-full" type="date" name="date_of_birth"
                    :value="old('date_of_birth')" max="{{ now()->toDateString() }}" autocomplete="bday"
                    required />
                <x-input-error :messages="$errors->get('date_of_birth')" class="mt-2" />
            </div>



            @php
            $hasIDCard = true; //kontroller musi zvalidovat datum narodenia a poslat do registracneho formularu kvoli zobrazeniu/nezobrazeniu


            @endphp

            @if($hasIDCard)

            <!-- Legal Guardian (parent data required by participants under 18) -->
            <div class="sm:col-span-2">
                <div
                    class="border border-gray-200 dark:border-gray-700 rounded-md p-4 space-y-4 bg-gray-50 dark:bg-gray-800/50">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Ak je záujemca mladší ako 18 rokov, je potrebné uviesť údaje zákonného zástupcu.
                    </p>

                    <!-- Checkbox rovnaká adresa-->
                    <label class="flex items-center gap-2 font-medium text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" id="guardian_same_address" name="guardian_same_address" value="1"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900"
                            @checked(old('guardian_same_address'))>
                        <span class="text-sm">Adresa zákonného zástupcu je rovnaká</span>
                    </label>


                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                        <!--Guardian First Name-->
                        <div>
                            <x-input-label for="guardian_first_name" value="Meno zástupcu" />
                            <x-text-input id="guardian_first_name" class="block mt-1 w-full" type="text"
                                name="guardian_first_name" :value="old('guardian_first_name')"
                                autocomplete="off" placeholder="Mária" />
                            <x-input-error :messages="$errors->get('guardian_first_name')" class="mt-2" />
                        </div>

                        <!--Guardian Last Name-->
                        <div>
                            <x-input-label for="guardian_last_name" value="Priezvisko zástupcu" />
                            <x-text-input id="guardian_last_name" class="block mt-1 w-full" type="text"
                                name="guardian_last_name" :value="old('guardian_last_name')"
                                autocomplete="off" placeholder="Holá" />
                            <x-input-error :messages="$errors->get('guardian_last_name')" class="mt-2" />
                        </div>



                        <!-- Guardian Phone -->
                        <div>
                            <x-input-label for="guardian_phone" value="Telefón" />
                            <x-text-input id="guardian_phone" class="block mt-1 w-full" type="tel" name="guardian_phone" :value="old('guardian_phone')"
                                autocomplete="tel" placeholder="+421 900 123 456" pattern="^\+?[0-9\s]{9,17}$" />
                            <x-input-error :messages="$errors->get('guardian_phone')" class="mt-2" />

                        </div>



                        <!--Guardian Email Address -->
                        <div>
                            <x-input-label for="guardian_email" value="Email" />
                            <x-text-input id="guardian_email" class="block mt-1 w-full" type="email" name="guardian_email" :value="old('guardian_email')"
                                autocomplete="username" placeholder="zastupca@email.com" />
                            <x-input-error :messages="$errors->get('guardian_email')" class="mt-2" />
                        </div>

                        <!-- Guardian Adress Block -->

                        <div class="contents guardian-address">

                            <!-- Guardian Street Address -->
                            <div>
                                <x-input-label for="guardian_street_address" value="Ulica, č. domu" />
                                <x-text-input id="guardian_street_address" class="block mt-1 w-full" type="text" name="guardian_street_address"
                                    :value="old('guardian_street_address')" autocomplete="address-line1" placeholder="J.Herdu 1" />
                                <x-input-error :messages="$errors->get('guardian_street_address')" class="mt-2" />
                            </div>

                            <!--Guardian Postal Code -->

                            <div>
                                <x-input-label for="guardian_postal_code" value="PSČ" />
                                <x-text-input id="guardian_postal_code" class="block mt-1 w-full" type="text" name="guardian_postal_code"
                                    :value="old('guardian_postal_code')" placeholder="123 45" pattern="^[0-9]{3}\s?[0-9]{2}$" />
                                <x-input-error :messages="$errors->get('guardian_postal_code')" class="mt-2" />
                            </div>

                            <!--Guardian City -->

                            <div>
                                <x-input-label for="guardian_city" value="Mesto" />
                                <x-text-input id="guardian_city" class="block mt-1 w-full" type="text" name="guardian_city"
                                    :value="old('guardian_city')" placeholder="Trnava" />
                                <x-input-error :messages="$errors->get('guardian_city')" class="mt-2" />
                            </div>


                        </div>

                        <!--Guardian Date of Birth -->
                        <div>
                            <x-input-label for="guardian_date_of_birth" value="Dátum narodenia" />
                            <x-text-input id="guardian_date_of_birth" class="block mt-1 w-full" type="date" name="guardian_date_of_birth"
                                :value="old('guardian_date_of_birth')" max="{{ now()->toDateString() }}" autocomplete="bday"
                                required />
                            <x-input-error :messages="$errors->get('guardian_date_of_birth')" class="mt-2" />
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
                <x-input-label for="password" value="Heslo" />
                <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required
                    autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <!-- Confirm Password -->
            <div>
                <x-input-label for="password_confirmation" value="Potvrdenie hesla" />
                <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password"
                    name="password_confirmation" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>
        </div>

        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Súhlasy</h2>

        <label class="flex items-start mt-2">
            <input type="checkbox" name="terms" value="1" required
                class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800" />
            <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">
                Súhlasím s obchodnými podmienkami a <a href="#"
                    class="underline text-gray-900 dark:text-gray-100 hover:text-gray-600 dark:hover:text-gray-300">VOP</a>. *
            </span>
        </label>
        <x-input-error :messages="$errors->get('terms')" class="mt-2" />

        <label class="flex items-start mt-4">
            <input type="checkbox" name="gdpr_consent" value="1" required
                class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800" />
            <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">
                Súhlasím so spracovaním osobných údajov podľa <a href="#"
                    class="underline text-gray-900 dark:text-gray-100 hover:text-gray-600 dark:hover:text-gray-300">GDPR</a>. *
            </span>
        </label>
        <x-input-error :messages="$errors->get('gdpr_consent')" class="mt-2" />

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