<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Http\Resources\DateTimeResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="UserResource",
 *     type="object",
 *     title="User Resource",
 *     description="Resource de l'utilisateur",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *     @OA\Property(
 *         property="accountType",
 *         type="object",
 *         @OA\Property(property="value", type="string", example="professional"),
 *         @OA\Property(property="label", type="string", example="Compte Professionnel")
 *     ),
 *     @OA\Property(property="address", type="string", nullable=true, example="123 Main St, Lomé"),
 *     @OA\Property(property="isProfessional", type="boolean", example=true),
 *     @OA\Property(property="isPrivate", type="boolean", example=false),
 *     @OA\Property(property="emailVerifiedAt", ref="#/components/schemas/DateTimeResource", nullable=true),
 *     @OA\Property(property="professionalProfile", ref="#/components/schemas/ProfessionalProfileResource", nullable=true),
 *     @OA\Property(
 *         property="companies",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/CompanyResource")
 *     ),
 *     @OA\Property(
 *         property="posts",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/PostResource")
 *     ),
 *     @OA\Property(property="postsCount", type="integer", example=5),
 *     @OA\Property(property="companiesCount", type="integer", example=2),
 *     @OA\Property(property="createdAt", ref="#/components/schemas/DateTimeResource"),
 *     @OA\Property(property="updatedAt", ref="#/components/schemas/DateTimeResource")
 * )
 *
 * @mixin \App\Models\User
 */
final class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var \App\Enums\AccountType $accountType */
        $accountType = $this->account_type;

        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'email'       => $this->email,
            'accountType' => [
                'value' => $accountType->value,
                'label' => $accountType->label(),
            ],
            'address'        => $this->address,
            'isProfessional' => $this->isProfessional(),
            'isPrivate'      => $this->isPrivate(),
            'avatar'         => $this->hasMedia('avatar') ? [
                'url'    => $this->getFirstMediaUrl('avatar'),
                'thumb'  => $this->getFirstMediaUrl('avatar', 'thumb'),
                'medium' => $this->getFirstMediaUrl('avatar', 'medium'),
            ] : null,
            'emailVerifiedAt' => $this->when(
                $this->email_verified_at !== null,
                fn() => new DateTimeResource($this->email_verified_at)
            ),
            'professionalProfile' => new ProfessionalProfileResource($this->whenLoaded('professionalProfile')),
            'companies'           => CompanyResource::collection($this->whenLoaded('companies')),
            'posts'               => PostResource::collection($this->whenLoaded('posts')),
            'postsCount'          => $this->whenCounted('posts'),
            'companiesCount'      => $this->whenCounted('companies'),
            'createdAt'           => new DateTimeResource($this->created_at),
            'updatedAt'           => new DateTimeResource($this->updated_at),
        ];
    }
}
