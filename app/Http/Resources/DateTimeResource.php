<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="DateTimeResource",
 *     type="object",
 *     title="DateTime Resource",
 *     description="Resource pour les dates formatées",
 *     @OA\Property(property="datetime", type="string", format="date-time", example="2025-11-18T12:00:00.000000Z"),
 *     @OA\Property(property="humanDiff", type="string", example="il y a 2 heures"),
 *     @OA\Property(property="human", type="string", example="Lun 18 Nov 2025 12:00:00")
 * )
 *
 * @mixin Carbon
 */
final class DateTimeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, string>
     */
    public function toArray(Request $request): array
    {
        return [
            'datetime'  => $this->toISOString(),
            'humanDiff' => $this->diffForHumans(),
            'human'     => $this->toDayDateTimeString(),
        ];
    }
}
