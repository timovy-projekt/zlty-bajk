<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public const int MINIMUM_AGE = 15;

    public const int ADULT_AGE = 18;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $today = today();
        $latestBirthDate = $today->copy()->subYearsNoOverflow(self::MINIMUM_AGE)->toDateString();
        $adultBirthDate = $today->copy()->subYearsNoOverflow(self::ADULT_AGE)->toDateString();
        $birthDate = $this->input('date_of_birth');
        $requiresGuardian = is_string($birthDate) && $birthDate > $adultBirthDate && $birthDate <= $latestBirthDate;
        $usesCustomerAddress = $this->boolean('guardian_same_address');
        $idNumberRules = ['string', 'size:8', 'regex:/\A[A-Za-z]{2}[0-9]{6}\z/'];
        $phoneRules = ['nullable', 'string', 'max:30', 'regex:/^\+?(?:[0-9] ?){8,14}[0-9]$/'];

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => $phoneRules,
            'email' => ['required', 'string', 'lowercase', 'email', 'max:254', Rule::unique(User::class)],
            'street_address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:20'],
            'date_of_birth' => [
                'required',
                Rule::date()->format('Y-m-d')
                    ->afterOrEqual('1000-01-01')
                    ->beforeOrEqual($latestBirthDate),
            ],
            'id_number' => ['nullable', ...$idNumberRules],
            'guardian_first_name' => [Rule::excludeIf(! $requiresGuardian), 'required', 'string', 'max:100'],
            'guardian_last_name' => [Rule::excludeIf(! $requiresGuardian), 'required', 'string', 'max:100'],
            'guardian_email' => [Rule::excludeIf(! $requiresGuardian), 'required', 'string', 'email', 'max:254'],
            'guardian_phone' => [Rule::excludeIf(! $requiresGuardian), ...$phoneRules],
            'guardian_same_address' => [Rule::excludeIf(! $requiresGuardian), 'sometimes', 'boolean'],
            'guardian_street_address' => [Rule::excludeIf(! $requiresGuardian || $usesCustomerAddress), 'nullable', 'string', 'max:255'],
            'guardian_city' => [Rule::excludeIf(! $requiresGuardian || $usesCustomerAddress), 'nullable', 'string', 'max:100'],
            'guardian_postal_code' => [Rule::excludeIf(! $requiresGuardian || $usesCustomerAddress), 'nullable', 'string', 'max:20'],
            'guardian_date_of_birth' => [
                Rule::excludeIf(! $requiresGuardian),
                'required',
                Rule::date()->format('Y-m-d')
                    ->afterOrEqual('1000-01-01')
                    ->beforeOrEqual($today->toDateString()),
            ],
            'guardian_id_number' => [Rule::excludeIf(! $requiresGuardian), 'nullable', ...$idNumberRules],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'terms' => ['accepted'],
            'gdpr_consent' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date_of_birth.before_or_equal' => 'Na registráciu musíte mať aspoň '.self::MINIMUM_AGE.' rokov.',
            'id_number.size' => 'Číslo OP musí mať presne 8 znakov.',
            'id_number.regex' => 'Číslo OP musí obsahovať dve písmená a za nimi šesť číslic, napríklad AB123456.',
            'guardian_id_number.size' => 'Číslo OP zástupcu musí mať presne 8 znakov.',
            'guardian_id_number.regex' => 'Číslo OP zástupcu musí obsahovať dve písmená a za nimi šesť číslic, napríklad AB123456.',
        ];
    }
}
