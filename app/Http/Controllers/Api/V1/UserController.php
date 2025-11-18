<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\User\UpdateAvatarRequest;
use App\Http\Requests\V1\User\UpdateProfileRequest;
use App\Http\Resources\V1\UserResource;
use App\Http\Traits\Cacheable;
use App\Models\User;
use App\Services\CacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use OpenApi\Annotations as OA;
use Spatie\QueryBuilder\QueryBuilder;

class UserController extends Controller
{
    use Cacheable;

    /**
     * @OA\Get(
     *     path="/v1/users/me",
     *     summary="Get authenticated user",
     *     description="Get the authenticated user's profile. Use the `include` parameter to load specific relationships. Use the `Accept-Language` header to get responses in French (fr) or English (en).",
     *     tags={"User Profile"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="Accept-Language",
     *         in="header",
     *         description="Language preference (fr or en)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"fr", "en"}, default="en")
     *     ),
     *     @OA\Parameter(
     *         name="include",
     *         in="query",
     *         description="Comma-separated list of relationships to include (e.g., professionalProfile,companies,posts)",
     *         required=false,
     *         @OA\Schema(type="string", example="professionalProfile,companies,posts")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Authenticated user",
     *         @OA\JsonContent(ref="#/components/schemas/UserResource")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function current(Request $request): UserResource
    {
        $userId = $request->user()->id;
        $includes = $request->input('include', '');
        $locale = app()->getLocale();

        $cacheKey = sprintf(
            '%s.current.user_%d.includes_%s.locale_%s',
            CacheService::PREFIX_USER,
            $userId,
            md5($includes),
            $locale
        );

        $user = Cache::remember($cacheKey, CacheService::TTL_SHORT, function () use ($userId) {
            return QueryBuilder::for(User::where('id', $userId))
                ->allowedIncludes(['professionalProfile', 'companies', 'posts'])
                ->first();
        });

        return new UserResource($user);
    }

    /**
     * @OA\Put(
     *     path="/v1/users/me",
     *     summary="Update authenticated user profile",
     *     description="Update the authenticated user's profile information. Use the `include` parameter to load specific relationships. Use the `Accept-Language` header to get responses in French (fr) or English (en).",
     *     tags={"User Profile"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="Accept-Language",
     *         in="header",
     *         description="Language preference (fr or en)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"fr", "en"}, default="en")
     *     ),
     *     @OA\Parameter(
     *         name="include",
     *         in="query",
     *         description="Comma-separated list of relationships to include (e.g., professionalProfile,companies,posts)",
     *         required=false,
     *         @OA\Schema(type="string", example="professionalProfile,companies")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UpdateProfileRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="user", ref="#/components/schemas/UserResource"),
     *             @OA\Property(property="message", type="string", example="Profile updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The email has already been taken."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->only(['name', 'email', 'address']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        $this->invalidateUserCache($user->id);

        $updatedUser = QueryBuilder::for(User::where('id', $user->id))
            ->allowedIncludes(['professionalProfile', 'companies', 'posts'])
            ->first();

        return response()->json([
            'user'    => new UserResource($updatedUser),
            'message' => __('profile.profile_updated'),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/v1/users/me/avatar",
     *     summary="Upload profile picture",
     *     description="Upload or update the authenticated user's profile picture. Use the `include` parameter to load specific relationships. Use the `Accept-Language` header to get responses in French (fr) or English (en).",
     *     tags={"User Profile"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="Accept-Language",
     *         in="header",
     *         description="Language preference (fr or en)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"fr", "en"}, default="en")
     *     ),
     *     @OA\Parameter(
     *         name="include",
     *         in="query",
     *         description="Comma-separated list of relationships to include (e.g., professionalProfile,companies)",
     *         required=false,
     *         @OA\Schema(type="string", example="professionalProfile")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(ref="#/components/schemas/UpdateAvatarRequest")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Avatar updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="user", ref="#/components/schemas/UserResource"),
     *             @OA\Property(property="avatar_url", type="string", format="url", example="http://example.com/storage/avatars/user-avatar.jpg"),
     *             @OA\Property(property="message", type="string", example="Profile picture updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The avatar field must be an image."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function uploadAvatar(UpdateAvatarRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->clearMediaCollection('avatar');

        $media = $user->addMediaFromRequest('avatar')
            ->toMediaCollection('avatar');

        $this->invalidateUserCache($user->id);

        $updatedUser = QueryBuilder::for(User::where('id', $user->id))
            ->allowedIncludes(['professionalProfile', 'companies', 'posts'])
            ->first();

        return response()->json([
            'user'       => new UserResource($updatedUser),
            'avatar_url' => $media->getUrl(),
            'message'    => __('profile.avatar_updated'),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/v1/users/me/avatar",
     *     summary="Delete profile picture",
     *     description="Delete the authenticated user's profile picture. Use the `include` parameter to load specific relationships. Use the `Accept-Language` header to get responses in French (fr) or English (en).",
     *     tags={"User Profile"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="Accept-Language",
     *         in="header",
     *         description="Language preference (fr or en)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"fr", "en"}, default="en")
     *     ),
     *     @OA\Parameter(
     *         name="include",
     *         in="query",
     *         description="Comma-separated list of relationships to include (e.g., professionalProfile,companies)",
     *         required=false,
     *         @OA\Schema(type="string", example="professionalProfile")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Avatar deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="user", ref="#/components/schemas/UserResource"),
     *             @OA\Property(property="message", type="string", example="Profile picture deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function deleteAvatar(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->clearMediaCollection('avatar');

        $this->invalidateUserCache($user->id);

        $updatedUser = QueryBuilder::for(User::where('id', $user->id))
            ->allowedIncludes(['professionalProfile', 'companies', 'posts'])
            ->first();

        return response()->json([
            'user'    => new UserResource($updatedUser),
            'message' => __('profile.avatar_deleted'),
        ]);
    }

    /**
     * Invalidate cache for a specific user
     * Note: With file driver, this flushes all cache. Use Redis tags in production for granular control.
     */
    private function invalidateUserCache(int $userId): void
    {
        Cache::flush();
    }
}
