<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Auth;

use App\Enums\AccountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="RegisterRequest",
 *     type="object",
 *     required={"name", "email", "password", "password_confirmation", "account_type"},
 *     @OA\Property(property="name", type="string", maxLength=255, example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", maxLength=255, example="john@example.com"),
 *     @OA\Property(property="password", type="string", format="password", minLength=8, example="Password123!"),
 *     @OA\Property(property="password_confirmation", type="string", format="password", example="Password123!"),
 *     @OA\Property(property="account_type", type="string", enum={"private", "professional"}, example="professional"),
 *     @OA\Property(property="address", type="string", maxLength=1000, nullable=true, example="123 Main St, Lomé, Togo"),
 *     @OA\Property(property="company_name", type="string", maxLength=255, nullable=true, example="Tech Solutions SARL", description="Requis si account_type est professional"),
 *     @OA\Property(property="cfe_number", type="string", maxLength=100, nullable=true, example="CFE-2024-001", description="Requis si account_type est professional")
 * )
 */
final class RegisterRequest extends FormRequest
{
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'              => ['required', 'string', 'confirmed', Password::defaults()],
            'password_confirmation' => ['required', 'string'],
            'account_type'          => ['required', 'string', Rule::enum(AccountType::class)],
            'address'               => ['nullable', 'string', 'max:1000'],

            // Champs requis pour les comptes professionnels
            'company_name' => ['required_if:account_type,professional', 'string', 'max:255'],
            'cfe_number'   => ['required_if:account_type,professional', 'string', 'max:100', 'unique:professional_profiles,cfe_number'],
        ];
    }
}
