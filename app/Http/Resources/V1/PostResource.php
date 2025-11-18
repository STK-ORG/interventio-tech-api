<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Http\Resources\DateTimeResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="PostResource",
 *     type="object",
 *     title="Post Resource",
 *     description="Resource d'un post/article",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(
 *         property="title",
 *         type="object",
 *         @OA\Property(property="en", type="string", example="My First Post"),
 *         @OA\Property(property="fr", type="string", example="Mon Premier Article")
 *     ),
 *     @OA\Property(property="slug", type="string", example="my-first-post"),
 *     @OA\Property(
 *         property="content",
 *         type="object",
 *         @OA\Property(property="en", type="string", example="This is my first post content..."),
 *         @OA\Property(property="fr", type="string", example="Ceci est le contenu de mon premier article...")
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="object",
 *         @OA\Property(property="value", type="string", example="published"),
 *         @OA\Property(property="label", type="string", example="Publié"),
 *         @OA\Property(property="color", type="string", example="green")
 *     ),
 *     @OA\Property(property="viewsCount", type="integer", example=150),
 *     @OA\Property(property="publishedAt", ref="#/components/schemas/DateTimeResource", nullable=true),
 *     @OA\Property(property="featuredImage", type="string", format="uri", nullable=true, example="https://example.com/images/post-1.jpg"),
 *     @OA\Property(
 *         property="gallery",
 *         type="array",
 *         nullable=true,
 *         @OA\Items(type="string", format="uri")
 *     ),
 *     @OA\Property(property="author", ref="#/components/schemas/UserResource"),
 *     @OA\Property(property="isPublished", type="boolean", example=true),
 *     @OA\Property(property="isDraft", type="boolean", example=false),
 *     @OA\Property(property="isArchived", type="boolean", example=false),
 *     @OA\Property(property="createdAt", ref="#/components/schemas/DateTimeResource"),
 *     @OA\Property(property="updatedAt", ref="#/components/schemas/DateTimeResource"),
 *     @OA\Property(property="deletedAt", ref="#/components/schemas/DateTimeResource", nullable=true)
 * )
 *
 * @mixin \App\Models\Post
 */
final class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'      => $this->id,
            'title'   => $this->getTranslations('title'),
            'slug'    => $this->slug,
            'content' => $this->getTranslations('content'),
            'status'  => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
                'color' => $this->status->color(),
            ],
            'viewsCount'  => $this->views_count,
            'publishedAt' => $this->when(
                $this->published_at !== null,
                fn() => new DateTimeResource($this->published_at)
            ),
            'featuredImage' => $this->when(
                $this->hasMedia('featured_image'),
                fn() => $this->getFirstMediaUrl('featured_image')
            ),
            'gallery' => $this->when(
                $this->hasMedia('gallery'),
                fn() => $this->getMedia('gallery')->map(fn($media) => $media->getUrl())
            ),
            'author'      => new UserResource($this->whenLoaded('user')),
            'isPublished' => $this->isPublished(),
            'isDraft'     => $this->isDraft(),
            'isArchived'  => $this->isArchived(),
            'createdAt'   => new DateTimeResource($this->created_at),
            'updatedAt'   => new DateTimeResource($this->updated_at),
            'deletedAt'   => $this->when(
                $this->deleted_at !== null,
                fn() => new DateTimeResource($this->deleted_at)
            ),
        ];
    }
}
