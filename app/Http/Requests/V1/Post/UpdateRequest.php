<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Post;

use App\Enums\PostStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="UpdatePostRequest",
 *     type="object",
 *     @OA\Property(
 *         property="title",
 *         type="object",
 *         @OA\Property(property="en", type="string", maxLength=255, example="Updated Post Title"),
 *         @OA\Property(property="fr", type="string", maxLength=255, example="Titre de l'Article Mis à Jour")
 *     ),
 *     @OA\Property(
 *         property="content",
 *         type="object",
 *         @OA\Property(property="en", type="string", example="Updated content in English..."),
 *         @OA\Property(property="fr", type="string", example="Contenu mis à jour en français...")
 *     ),
 *     @OA\Property(property="status", type="string", enum={"draft", "published", "archived"}, example="published"),
 *     @OA\Property(property="published_at", type="string", format="date-time", nullable=true, example="2025-11-18T12:00:00Z"),
 *     @OA\Property(property="featured_image", type="string", format="binary", description="Nouvelle image mise en avant (max 5MB)"),
 *     @OA\Property(
 *         property="gallery",
 *         type="array",
 *         maxItems=10,
 *         @OA\Items(type="string", format="binary"),
 *         description="Nouvelles images pour la galerie (max 10 images de 5MB chacune)"
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
        return $this->user()->can('update', $this->route('post'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title'          => ['sometimes', 'required', 'array'],
            'title.en'       => ['sometimes', 'required', 'string', 'max:255'],
            'title.fr'       => ['sometimes', 'required', 'string', 'max:255'],
            'content'        => ['sometimes', 'required', 'array'],
            'content.en'     => ['sometimes', 'required', 'string'],
            'content.fr'     => ['sometimes', 'required', 'string'],
            'status'         => ['sometimes', 'required', 'string', Rule::enum(PostStatus::class)],
            'published_at'   => ['nullable', 'date'],
            'featured_image' => ['nullable', 'image', 'max:5120'],
            'gallery'        => ['nullable', 'array', 'max:10'],
            'gallery.*'      => ['image', 'max:5120'],
        ];
    }
}
