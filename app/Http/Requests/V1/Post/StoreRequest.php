<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Post;

use App\Enums\PostStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="StorePostRequest",
 *     type="object",
 *     required={"title", "content", "status"},
 *     @OA\Property(
 *         property="title",
 *         type="object",
 *         required={"en", "fr"},
 *         @OA\Property(property="en", type="string", maxLength=255, example="My First Post"),
 *         @OA\Property(property="fr", type="string", maxLength=255, example="Mon Premier Article")
 *     ),
 *     @OA\Property(
 *         property="content",
 *         type="object",
 *         required={"en", "fr"},
 *         @OA\Property(property="en", type="string", example="This is the content in English..."),
 *         @OA\Property(property="fr", type="string", example="Ceci est le contenu en français...")
 *     ),
 *     @OA\Property(property="status", type="string", enum={"draft", "published", "archived"}, example="published"),
 *     @OA\Property(property="published_at", type="string", format="date-time", nullable=true, example="2025-11-18T12:00:00Z"),
 *     @OA\Property(property="featured_image", type="string", format="binary", description="Image mise en avant (max 5MB)"),
 *     @OA\Property(
 *         property="gallery",
 *         type="array",
 *         maxItems=10,
 *         @OA\Items(type="string", format="binary"),
 *         description="Galerie d'images (max 10 images de 5MB chacune)"
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
            'title'          => ['required', 'array'],
            'title.en'       => ['required', 'string', 'max:255'],
            'title.fr'       => ['required', 'string', 'max:255'],
            'content'        => ['required', 'array'],
            'content.en'     => ['required', 'string'],
            'content.fr'     => ['required', 'string'],
            'status'         => ['required', 'string', Rule::enum(PostStatus::class)],
            'published_at'   => ['nullable', 'date'],
            'featured_image' => ['nullable', 'image', 'max:5120'], // 5MB
            'gallery'        => ['nullable', 'array', 'max:10'],
            'gallery.*'      => ['image', 'max:5120'],
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
            'title.en'       => 'titre en anglais',
            'title.fr'       => 'titre en français',
            'content.en'     => 'contenu en anglais',
            'content.fr'     => 'contenu en français',
            'status'         => 'statut',
            'published_at'   => 'date de publication',
            'featured_image' => 'image mise en avant',
            'gallery'        => 'galerie',
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
            'title.required'     => 'Le titre est obligatoire.',
            'title.*.required'   => 'Le :attribute est obligatoire.',
            'content.*.required' => 'Le :attribute est obligatoire.',
            'featured_image.max' => 'L\'image ne doit pas dépasser 5 Mo.',
            'gallery.*.max'      => 'Chaque image de la galerie ne doit pas dépasser 5 Mo.',
        ];
    }
}
