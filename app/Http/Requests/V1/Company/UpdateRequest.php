<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Company;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="UpdateCompanyRequest",
 *     type="object",
 *     @OA\Property(property="company_name", type="string", maxLength=255, example="Tech Solutions SARL Updated"),
 *     @OA\Property(property="cfe_number", type="string", maxLength=100, example="CFE-2024-001"),
 *     @OA\Property(property="address", type="string", maxLength=1000, nullable=true, example="789 New Business St, Lomé, Togo"),
 *     @OA\Property(
 *         property="description",
 *         type="object",
 *         nullable=true,
 *         @OA\Property(property="en", type="string", example="Updated technology solutions company"),
 *         @OA\Property(property="fr", type="string", example="Entreprise de solutions technologiques mise à jour")
 *     ),
 *     @OA\Property(property="logo", type="string", format="binary", description="Nouveau logo de l'entreprise (max 2MB)"),
 *     @OA\Property(
 *         property="documents",
 *         type="array",
 *         maxItems=5,
 *         @OA\Items(type="string", format="binary"),
 *         description="Nouveaux documents de l'entreprise (max 5 fichiers de 10MB chacun)"
 *     )
 * )
 */
final class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('company'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var \App\Models\Company $company */
        $company = $this->route('company');

        return [
            'company_name' => ['sometimes', 'required', 'string', 'max:255'],
            'cfe_number'   => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('companies', 'cfe_number')->ignore($company->id),
            ],
            'address'        => ['nullable', 'string', 'max:1000'],
            'description'    => ['nullable', 'array'],
            'description.en' => ['nullable', 'string'],
            'description.fr' => ['nullable', 'string'],
            'logo'           => ['nullable', 'image', 'max:2048'],
            'documents'      => ['nullable', 'array', 'max:5'],
            'documents.*'    => ['file', 'mimes:pdf,doc,docx', 'max:10240'],
        ];
    }
}
