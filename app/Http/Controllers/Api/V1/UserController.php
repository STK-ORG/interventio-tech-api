<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\User\UpdateAvatarRequest;
use App\Http\Requests\V1\User\UpdateProfileRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use OpenApi\Annotations as OA;
use Spatie\QueryBuilder\QueryBuilder;

class UserController extends Controller
{
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
        $user = QueryBuilder::for(User::where('id', $request->user()->id))
            ->allowedIncludes(['professionalProfile', 'companies', 'posts'])
            ->first();

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

        // Si un nouveau mot de passe est fourni, le hacher
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        // Recharger avec Query Builder pour inclure les relations demandées
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

        // Supprimer l'ancien avatar s'il existe
        $user->clearMediaCollection('avatar');

        // Ajouter le nouvel avatar
        $media = $user->addMediaFromRequest('avatar')
            ->toMediaCollection('avatar');

        // Recharger avec Query Builder pour inclure les relations demandées
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

        // Supprimer l'avatar
        $user->clearMediaCollection('avatar');

        // Recharger avec Query Builder pour inclure les relations demandées
        $updatedUser = QueryBuilder::for(User::where('id', $user->id))
            ->allowedIncludes(['professionalProfile', 'companies', 'posts'])
            ->first();

        return response()->json([
            'user'    => new UserResource($updatedUser),
            'message' => __('profile.avatar_deleted'),
        ]);
    }
}
