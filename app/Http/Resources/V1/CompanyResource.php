<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Http\Resources\DateTimeResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="CompanyResource",
 *     type="object",
 *     title="Company Resource",
 *     description="Resource d'une entreprise",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="companyName", type="string", example="Tech Solutions SARL"),
 *     @OA\Property(property="cfeNumber", type="string", example="CFE-2024-001"),
 *     @OA\Property(property="address", type="string", nullable=true, example="456 Business Ave, Lomé"),
 *     @OA\Property(
 *         property="description",
 *         type="object",
 *         nullable=true,
 *         @OA\Property(property="en", type="string", example="Technology solutions company"),
 *         @OA\Property(property="fr", type="string", example="Entreprise de solutions technologiques")
 *     ),
 *     @OA\Property(property="isVerified", type="boolean", example=true),
 *     @OA\Property(property="verifiedAt", ref="#/components/schemas/DateTimeResource", nullable=true),
 *     @OA\Property(property="logo", type="string", format="uri", nullable=true, example="https://example.com/logos/company-1.png"),
 *     @OA\Property(
 *         property="documents",
 *         type="array",
 *         nullable=true,
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="name", type="string", example="certificate.pdf"),
 *             @OA\Property(property="url", type="string", format="uri", example="https://example.com/documents/cert.pdf"),
 *             @OA\Property(property="size", type="integer", example=1024000)
 *         )
 *     ),
 *     @OA\Property(property="owner", ref="#/components/schemas/UserResource"),
 *     @OA\Property(property="createdAt", ref="#/components/schemas/DateTimeResource"),
 *     @OA\Property(property="updatedAt", ref="#/components/schemas/DateTimeResource"),
 *     @OA\Property(property="deletedAt", ref="#/components/schemas/DateTimeResource", nullable=true)
 * )
 *
 * @mixin \App\Models\Company
 */
final class CompanyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'companyName' => $this->company_name,
            'cfeNumber'   => $this->cfe_number,
            'address'     => $this->address,
            'description' => $this->description,
            'isVerified'  => $this->is_verified,
            'verifiedAt'  => $this->when(
                $this->verified_at !== null,
                fn() => new DateTimeResource($this->verified_at)
            ),
            'logo' => $this->when(
                $this->hasMedia('logo'),
                fn() => $this->getFirstMediaUrl('logo')
            ),
            'documents' => $this->when(
                $this->hasMedia('documents'),
                fn() => $this->getMedia('documents')->map(fn($media) => [
                    'id'   => $media->id,
                    'name' => $media->name,
                    'url'  => $media->getUrl(),
                    'size' => $media->size,
                ])
            ),
            'owner'     => new UserResource($this->whenLoaded('user')),
            'createdAt' => new DateTimeResource($this->created_at),
            'updatedAt' => new DateTimeResource($this->updated_at),
            'deletedAt' => $this->when(
                $this->deleted_at !== null,
                fn() => new DateTimeResource($this->deleted_at)
            ),
        ];
    }
}
