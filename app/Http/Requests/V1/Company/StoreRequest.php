<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Company;

use App\Enums\AccountType;
use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="StoreCompanyRequest",
 *     type="object",
 *     required={"company_name", "cfe_number"},
 *     @OA\Property(property="company_name", type="string", maxLength=255, example="Tech Solutions SARL"),
 *     @OA\Property(property="cfe_number", type="string", maxLength=100, example="CFE-2024-001"),
 *     @OA\Property(property="address", type="string", maxLength=1000, nullable=true, example="456 Business Ave, Lomé, Togo"),
 *     @OA\Property(
 *         property="description",
 *         type="object",
 *         nullable=true,
 *         @OA\Property(property="en", type="string", example="Technology solutions company"),
 *         @OA\Property(property="fr", type="string", example="Entreprise de solutions technologiques")
 *     ),
 *     @OA\Property(property="logo", type="string", format="binary", description="Logo de l'entreprise (max 2MB)"),
 *     @OA\Property(
 *         property="documents",
 *         type="array",
 *         maxItems=5,
 *         @OA\Items(type="string", format="binary"),
 *         description="Documents de l'entreprise (max 5 fichiers de 10MB chacun, formats: PDF, DOC, DOCX)"
 *     )
 * )
 */
final class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->account_type === AccountType::PROFESSIONAL;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_name'   => ['required', 'string', 'max:255'],
            'cfe_number'     => ['required', 'string', 'max:100', 'unique:companies,cfe_number'],
            'address'        => ['nullable', 'string', 'max:1000'],
            'description'    => ['nullable', 'array'],
            'description.en' => ['nullable', 'string'],
            'description.fr' => ['nullable', 'string'],
            'logo'           => ['nullable', 'image', 'max:2048'], // 2MB
            'documents'      => ['nullable', 'array', 'max:5'],
            'documents.*'    => ['file', 'mimes:pdf,doc,docx', 'max:10240'], // 10MB
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'company_name'   => 'nom de l\'entreprise',
            'cfe_number'     => 'numéro CFE',
            'address'        => 'adresse',
            'description.en' => 'description en anglais',
            'description.fr' => 'description en français',
            'logo'           => 'logo',
            'documents'      => 'documents',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'company_name.required' => 'Le nom de l\'entreprise est obligatoire.',
            'cfe_number.required'   => 'Le numéro CFE est obligatoire.',
            'cfe_number.unique'     => 'Ce numéro CFE est déjà utilisé.',
            'logo.max'              => 'Le logo ne doit pas dépasser 2 Mo.',
            'documents.*.max'       => 'Chaque document ne doit pas dépasser 10 Mo.',
        ];
    }
}
