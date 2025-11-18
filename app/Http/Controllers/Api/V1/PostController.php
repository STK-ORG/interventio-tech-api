<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Post\StoreRequest;
use App\Http\Requests\V1\Post\UpdateRequest;
use App\Http\Resources\V1\PostResource;
use App\Http\Traits\Cacheable;
use App\Models\Post;
use App\Services\CacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Annotations as OA;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

final class PostController extends Controller
{
    use Cacheable;

    /**
     * @OA\Get(
     *     path="/v1/posts",
     *     summary="List all posts",
     *     description="Get a paginated list of posts with search, filtering, sorting, and includes. Use the `Accept-Language` header to get responses in French (fr) or English (en).",
     *     tags={"Posts"},
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
     *         description="Comma-separated list of relationships to include (e.g., user)",
     *         required=false,
     *         @OA\Schema(type="string", example="user")
     *     ),
     *     @OA\Parameter(
     *         name="filter[search]",
     *         in="query",
     *         description="Search in title and content (searches in the current language)",
     *         required=false,
     *         @OA\Schema(type="string", example="technology")
     *     ),
     *     @OA\Parameter(
     *         name="filter[status]",
     *         in="query",
     *         description="Filter by status (draft, published, archived)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"draft", "published", "archived"}, example="published")
     *     ),
     *     @OA\Parameter(
     *         name="filter[user_id]",
     *         in="query",
     *         description="Filter by user ID",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Sort by field (prefix with - for descending, e.g., -published_at, -views_count)",
     *         required=false,
     *         @OA\Schema(type="string", example="-published_at")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of posts",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/PostResource")),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        // Cache la liste des posts pour 5 minutes
        $posts = $this->cacheList(
            $request,
            CacheService::PREFIX_POST,
            function () use ($request) {
                return QueryBuilder::for(Post::class)
                    ->allowedIncludes(['user'])
                    ->allowedFilters([
                        AllowedFilter::callback('search', function ($query, $value) {
                            $locale = app()->getLocale();
                            $driver = config('database.default');

                            $query->where(function ($q) use ($value, $locale, $driver): void {
                                if ($driver === 'sqlite') {
                                    // SQLite utilise json_extract (minuscule, sans UNQUOTE)
                                    $q->whereRaw("json_extract(title, '$.{$locale}') LIKE ?", ["%{$value}%"])
                                        ->orWhereRaw("json_extract(content, '$.{$locale}') LIKE ?", ["%{$value}%"]);
                                } else {
                                    // MySQL/PostgreSQL
                                    $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(title, '$.{$locale}')) LIKE ?", ["%{$value}%"])
                                        ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(content, '$.{$locale}')) LIKE ?", ["%{$value}%"]);
                                }
                            });
                        }),
                        AllowedFilter::exact('status'),
                        AllowedFilter::exact('user_id'),
                    ])
                    ->allowedSorts(['published_at', 'created_at', 'views_count'])
                    ->defaultSort('-published_at')
                    ->paginate($request->input('per_page', 15));
            },
            CacheService::TTL_SHORT // 5 minutes car les posts changent souvent
        );

        return PostResource::collection($posts);
    }

    /**
     * @OA\Post(
     *     path="/v1/posts",
     *     summary="Create a new post",
     *     description="Create a new post for the authenticated user. Use the `Accept-Language` header to get responses in French (fr) or English (en).",
     *     tags={"Posts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="Accept-Language",
     *         in="header",
     *         description="Language preference (fr or en)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"fr", "en"}, default="en")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/StorePostRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Post created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/PostResource"),
     *             @OA\Property(property="message", type="string", example="Post created successfully")
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
     *             @OA\Property(property="message", type="string", example="The title field is required."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function store(StoreRequest $request): JsonResponse
    {
        $post = Post::create([
            'user_id'      => $request->user()->id,
            'title'        => $request->title,
            'content'      => $request->content,
            'status'       => $request->status,
            'published_at' => $request->published_at,
        ]);

        // Gérer l'image mise en avant si fournie
        if ($request->hasFile('featured_image')) {
            $post->addMediaFromRequest('featured_image')
                ->toMediaCollection('featured_image');
        }

        // Gérer la galerie d'images si fournie
        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $image) {
                $post->addMedia($image)
                    ->toMediaCollection('gallery');
            }
        }

        // Invalider le cache des posts
        $this->invalidateCache(CacheService::PREFIX_POST);

        return response()->json([
            'data'    => new PostResource($post->fresh()->load('user')),
            'message' => __('posts.created'),
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/v1/posts/{slug}",
     *     summary="Get a specific post",
     *     description="Get details of a specific post by slug. Use the `Accept-Language` header to get responses in French (fr) or English (en).",
     *     tags={"Posts"},
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         description="Post slug",
     *         required=true,
     *         @OA\Schema(type="string", example="my-first-post-123")
     *     ),
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
     *         description="Comma-separated list of relationships to include (e.g., user)",
     *         required=false,
     *         @OA\Schema(type="string", example="user")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Post details",
     *         @OA\JsonContent(ref="#/components/schemas/PostResource")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Post not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Post not found")
     *         )
     *     )
     * )
     */
    public function show(string $slug): PostResource
    {
        $post = QueryBuilder::for(Post::where('slug', $slug))
            ->allowedIncludes(['user'])
            ->firstOrFail();

        // Incrémenter le compteur de vues
        $post->incrementViews();

        return new PostResource($post);
    }

    /**
     * @OA\Put(
     *     path="/v1/posts/{slug}",
     *     summary="Update a post",
     *     description="Update a post's information. Use the `Accept-Language` header to get responses in French (fr) or English (en).",
     *     tags={"Posts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         description="Post slug",
     *         required=true,
     *         @OA\Schema(type="string", example="my-first-post-123")
     *     ),
     *     @OA\Parameter(
     *         name="Accept-Language",
     *         in="header",
     *         description="Language preference (fr or en)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"fr", "en"}, default="en")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UpdatePostRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Post updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/PostResource"),
     *             @OA\Property(property="message", type="string", example="Post updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - You can only update your own posts",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="This action is unauthorized.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Post not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Post not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The title field is required."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function update(UpdateRequest $request, Post $post): JsonResponse
    {
        $post->update($request->only([
            'title',
            'content',
            'status',
            'published_at',
        ]));

        // Gérer l'image mise en avant si fournie
        if ($request->hasFile('featured_image')) {
            $post->clearMediaCollection('featured_image');
            $post->addMediaFromRequest('featured_image')
                ->toMediaCollection('featured_image');
        }

        // Gérer la galerie d'images si fournie
        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $image) {
                $post->addMedia($image)
                    ->toMediaCollection('gallery');
            }
        }

        // Invalider le cache des posts
        $this->invalidateCache(CacheService::PREFIX_POST);

        return response()->json([
            'data'    => new PostResource($post->fresh()->load('user')),
            'message' => __('posts.updated'),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/v1/posts/{slug}",
     *     summary="Delete a post",
     *     description="Soft delete a post. Use the `Accept-Language` header to get responses in French (fr) or English (en).",
     *     tags={"Posts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         description="Post slug",
     *         required=true,
     *         @OA\Schema(type="string", example="my-first-post-123")
     *     ),
     *     @OA\Parameter(
     *         name="Accept-Language",
     *         in="header",
     *         description="Language preference (fr or en)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"fr", "en"}, default="en")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Post deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Post deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - You can only delete your own posts",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="This action is unauthorized.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Post not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Post not found")
     *         )
     *     )
     * )
     */
    public function destroy(Request $request, Post $post): JsonResponse
    {
        $this->authorize('delete', $post);

        $post->delete();

        // Invalider le cache des posts
        $this->invalidateCache(CacheService::PREFIX_POST);

        return response()->json([
            'message' => __('posts.deleted'),
        ]);
    }
}
