<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\AccountType;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\LoginRequest;
use App\Http\Requests\V1\Auth\RegisterRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\ProfessionalProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use OpenApi\Annotations as OA;
use Spatie\QueryBuilder\QueryBuilder;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/v1/auth/register",
     *     summary="Register a new user",
     *     description="Create a new user account (private or professional). Use the `include` parameter to load specific relationships. Use the `Accept-Language` header to get responses in French (fr) or English (en).",
     *     tags={"Authentication"},
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
     *         @OA\Schema(type="string", example="professionalProfile")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/RegisterRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User successfully created",
     *         @OA\JsonContent(
     *             @OA\Property(property="user", ref="#/components/schemas/UserResource"),
     *             @OA\Property(property="access_token", type="string", example="1|abcdef123456..."),
     *             @OA\Property(property="refresh_token", type="string", example="2|abcdef123456..."),
     *             @OA\Property(property="token_type", type="string", example="Bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=1800),
     *             @OA\Property(property="message", type="string", example="Registration successful")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The email address field must be a valid email address."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="An error occurred during registration"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Créer l'utilisateur
            $user = User::create([
                'name'         => $request->name,
                'email'        => $request->email,
                'password'     => Hash::make($request->password),
                'account_type' => AccountType::from($request->account_type),
                'address'      => $request->address,
            ]);

            // Si c'est un compte professionnel, créer le profil
            if ($user->account_type === AccountType::PROFESSIONAL) {
                ProfessionalProfile::create([
                    'user_id'      => $user->id,
                    'company_name' => $request->company_name,
                    'cfe_number'   => $request->cfe_number,
                ]);
            }

            // Créer un access token (30 minutes)
            $accessToken = $user->createToken(
                $request->device_name ?? 'web-browser',
                ['*'],
                now()->addMinutes((int) config('sanctum.expiration', 30))
            )->plainTextToken;

            // Créer un refresh token (30 jours)
            $refreshToken = $user->createToken(
                'refresh-' . ($request->device_name ?? 'web-browser'),
                ['refresh'],
                now()->addMinutes((int) config('sanctum.refresh_expiration', 43200))
            )->plainTextToken;

            DB::commit();

            // Recharger avec Query Builder pour inclure les relations demandées
            $userWithRelations = QueryBuilder::for(User::where('id', $user->id))
                ->allowedIncludes(['professionalProfile', 'companies', 'posts'])
                ->first();

            return response()->json([
                'user'          => new UserResource($userWithRelations),
                'access_token'  => $accessToken,
                'refresh_token' => $refreshToken,
                'token_type'    => 'Bearer',
                'expires_in'    => (int) config('sanctum.expiration', 30) * 60, // en secondes
                'message'       => __('auth.register_success'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => __('auth.register_error'),
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/v1/auth/login",
     *     summary="Login user",
     *     description="Authenticate a user with email and password. Use the `include` parameter to load specific relationships. Use the `Accept-Language` header to get responses in French (fr) or English (en).",
     *     tags={"Authentication"},
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
     *         @OA\Schema(type="string", example="professionalProfile")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/LoginRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="user", ref="#/components/schemas/UserResource"),
     *             @OA\Property(property="access_token", type="string", example="2|abcdef123456..."),
     *             @OA\Property(property="refresh_token", type="string", example="3|abcdef123456..."),
     *             @OA\Property(property="token_type", type="string", example="Bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=1800),
     *             @OA\Property(property="message", type="string", example="Login successful")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Invalid credentials",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The provided credentials are incorrect."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="email", type="array", @OA\Items(type="string", example="The provided credentials are incorrect."))
     *             )
     *         )
     *     )
     * )
     */
    public function login(LoginRequest $request): JsonResponse
    {
        // Vérifier l'utilisateur
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.login_failed')],
            ]);
        }

        // Créer un access token (30 minutes)
        $accessToken = $user->createToken(
            $request->device_name ?? 'web-browser',
            ['*'],
            now()->addMinutes((int) config('sanctum.expiration', 30))
        )->plainTextToken;

        // Créer un refresh token (30 jours)
        $refreshToken = $user->createToken(
            'refresh-' . ($request->device_name ?? 'web-browser'),
            ['refresh'],
            now()->addMinutes((int) config('sanctum.refresh_expiration', 43200))
        )->plainTextToken;

        // Recharger avec Query Builder pour inclure les relations demandées
        $userWithRelations = QueryBuilder::for(User::where('id', $user->id))
            ->allowedIncludes(['professionalProfile', 'companies', 'posts'])
            ->first();

        return response()->json([
            'user'          => new UserResource($userWithRelations),
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type'    => 'Bearer',
            'expires_in'    => (int) config('sanctum.expiration', 30) * 60, // en secondes
            'message'       => __('auth.login_success'),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/v1/auth/logout",
     *     summary="Logout user",
     *     description="Logout the current user and revoke the current access token. Use the `Accept-Language` header to get responses in French (fr) or English (en).",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="Accept-Language",
     *         in="header",
     *         description="Language preference (fr or en)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"fr", "en"}, default="en")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Logout successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Logout successful")
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
    public function logout(Request $request): JsonResponse
    {
        // Supprimer le token actuel
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => __('auth.logout_success'),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/v1/auth/logout-all",
     *     summary="Logout from all devices",
     *     description="Logout the user from all devices by revoking all access tokens. Use the `Accept-Language` header to get responses in French (fr) or English (en).",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="Accept-Language",
     *         in="header",
     *         description="Language preference (fr or en)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"fr", "en"}, default="en")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Logged out from all devices successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Logged out from all devices successfully")
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
    public function logoutAll(Request $request): JsonResponse
    {
        // Supprimer tous les tokens de l'utilisateur
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => __('auth.logout_all_success'),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/v1/auth/refresh",
     *     summary="Refresh access token",
     *     description="Refresh an expired access token using a valid refresh token. Use the `Accept-Language` header to get responses in French (fr) or English (en).",
     *     tags={"Authentication"},
     *     @OA\Parameter(
     *         name="Accept-Language",
     *         in="header",
     *         description="Language preference (fr or en)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"fr", "en"}, default="en")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"refresh_token"},
     *             @OA\Property(property="refresh_token", type="string", example="3|abcdef123456..."),
     *             @OA\Property(property="device_name", type="string", nullable=true, example="iPhone 14 Pro")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Token refreshed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string", example="4|newtoken123456..."),
     *             @OA\Property(property="refresh_token", type="string", example="5|newrefresh123456..."),
     *             @OA\Property(property="token_type", type="string", example="Bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=1800),
     *             @OA\Property(property="message", type="string", example="Access token refreshed successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid or expired refresh token",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid or expired refresh token.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Refresh token not provided."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function refresh(Request $request): JsonResponse
    {
        $request->validate([
            'refresh_token' => ['required', 'string'],
            'device_name'   => ['nullable', 'string', 'max:255'],
        ]);

        // Extraire l'ID et le token du refresh_token
        [$id, $token] = explode('|', $request->refresh_token, 2);

        // Trouver le token dans la base de données
        $tokenModel = \Laravel\Sanctum\PersonalAccessToken::findToken($request->refresh_token);

        if (! $tokenModel || ! $tokenModel->can('refresh')) {
            return response()->json([
                'message' => __('auth.refresh_token_invalid'),
            ], 401);
        }

        // Vérifier que le token n'est pas expiré
        if ($tokenModel->expires_at && $tokenModel->expires_at->isPast()) {
            $tokenModel->delete();

            return response()->json([
                'message' => __('auth.refresh_token_invalid'),
            ], 401);
        }

        /** @var User $user */
        $user = $tokenModel->tokenable;

        // Supprimer l'ancien refresh token
        $tokenModel->delete();

        // Créer un nouvel access token (30 minutes)
        $accessToken = $user->createToken(
            $request->device_name ?? 'web-browser',
            ['*'],
            now()->addMinutes((int) config('sanctum.expiration', 30))
        )->plainTextToken;

        // Créer un nouveau refresh token (30 jours)
        $refreshToken = $user->createToken(
            'refresh-' . ($request->device_name ?? 'web-browser'),
            ['refresh'],
            now()->addMinutes((int) config('sanctum.refresh_expiration', 43200))
        )->plainTextToken;

        return response()->json([
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type'    => 'Bearer',
            'expires_in'    => (int) config('sanctum.expiration', 30) * 60, // en secondes
            'message'       => __('auth.token_refreshed'),
        ]);
    }
}
