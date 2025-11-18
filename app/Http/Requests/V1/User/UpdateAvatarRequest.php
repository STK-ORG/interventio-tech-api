<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\User;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="UpdateAvatarRequest",
 *     type="object",
 *     required={"avatar"},
 *     @OA\Property(
 *         property="avatar",
 *         type="string",
 *         format="binary",
 *         description="Photo de profil (JPG, PNG, GIF, max 2MB)"
 *     )
 * )
 */
final class UpdateAvatarRequest extends FormRequest
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
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,gif', 'max:2048'], // 2MB
        ];
    }
}
