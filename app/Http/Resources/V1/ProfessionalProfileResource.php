<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Http\Resources\DateTimeResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="ProfessionalProfileResource",
 *     type="object",
 *     title="Professional Profile Resource",
 *     description="Resource du profil professionnel",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="companyName", type="string", example="Tech Solutions SARL"),
 *     @OA\Property(property="cfeNumber", type="string", example="CFE-2024-001"),
 *     @OA\Property(property="user", ref="#/components/schemas/UserResource"),
 *     @OA\Property(property="createdAt", ref="#/components/schemas/DateTimeResource"),
 *     @OA\Property(property="updatedAt", ref="#/components/schemas/DateTimeResource")
 * )
 *
 * @mixin \App\Models\ProfessionalProfile
 */
final class ProfessionalProfileResource extends JsonResource
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
            'user'        => new UserResource($this->whenLoaded('user')),
            'createdAt'   => new DateTimeResource($this->created_at),
            'updatedAt'   => new DateTimeResource($this->updated_at),
        ];
    }
}
