<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Company\StoreRequest;
use App\Http\Requests\V1\Company\UpdateRequest;
use App\Http\Resources\V1\CompanyResource;
use App\Http\Traits\Cacheable;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Annotations as OA;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

final class CompanyController extends Controller
{
    use Cacheable;

    /**
     * @OA\Get(
     *     path="/v1/companies",
     *     summary="List all companies",
     *     description="Get a paginated list of companies with filtering, sorting, and includes. Use the `Accept-Language` header to get responses in French (fr) or English (en).",
     *     tags={"Companies"},
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
     *         description="Comma-separated list of relationships to include (e.g., user)",
     *         required=false,
     *         @OA\Schema(type="string", example="user")
     *     ),
     *     @OA\Parameter(
     *         name="filter[company_name]",
     *         in="query",
     *         description="Filter by company name (partial match)",
     *         required=false,
     *         @OA\Schema(type="string", example="Tech")
     *     ),
     *     @OA\Parameter(
     *         name="filter[is_verified]",
     *         in="query",
     *         description="Filter by verification status (1 or 0)",
     *         required=false,
     *         @OA\Schema(type="integer", enum={0, 1}, example=1)
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Sort by field (prefix with - for descending, e.g., -created_at)",
     *         required=false,
     *         @OA\Schema(type="string", example="-created_at")
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
     *         description="List of companies",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/CompanyResource")),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
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
    public function index(Request $request): AnonymousResourceCollection
    {
        $companies = QueryBuilder::for(Company::class)
            ->allowedIncludes(['user'])
            ->allowedFilters([
                AllowedFilter::partial('company_name'),
                AllowedFilter::exact('is_verified'),
            ])
            ->allowedSorts(['company_name', 'created_at', 'verified_at'])
            ->defaultSort('-created_at')
            ->paginate($request->input('per_page', 15));

        return CompanyResource::collection($companies);
    }

    /**
     * @OA\Post(
     *     path="/v1/companies",
     *     summary="Create a new company",
     *     description="Create a new company for the authenticated user. Use the `Accept-Language` header to get responses in French (fr) or English (en).",
     *     tags={"Companies"},
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
     *         @OA\JsonContent(ref="#/components/schemas/StoreCompanyRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Company created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/CompanyResource"),
     *             @OA\Property(property="message", type="string", example="Company created successfully")
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
     *         response=403,
     *         description="Forbidden - Only professional accounts can create companies",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="This action is unauthorized.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The company name field is required."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function store(StoreRequest $request): JsonResponse
    {
        $company = Company::create([
            'user_id'      => $request->user()->id,
            'company_name' => $request->company_name,
            'cfe_number'   => $request->cfe_number,
            'address'      => $request->address,
            'description'  => $request->description,
        ]);

        // Gérer le logo si fourni
        if ($request->hasFile('logo')) {
            $company->addMediaFromRequest('logo')
                ->toMediaCollection('logo');
        }

        // Gérer les documents si fournis
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $document) {
                $company->addMedia($document)
                    ->toMediaCollection('documents');
            }
        }

        return response()->json([
            'data'    => new CompanyResource($company->fresh()->load('user')),
            'message' => __('companies.created'),
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/v1/companies/{id}",
     *     summary="Get a specific company",
     *     description="Get details of a specific company. Use the `Accept-Language` header to get responses in French (fr) or English (en).",
     *     tags={"Companies"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Company ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
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
     *         description="Company details",
     *         @OA\JsonContent(ref="#/components/schemas/CompanyResource")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Company not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Company not found")
     *         )
     *     )
     * )
     */
    public function show(int $id): CompanyResource
    {
        $company = QueryBuilder::for(Company::where('id', $id))
            ->allowedIncludes(['user'])
            ->firstOrFail();

        return new CompanyResource($company);
    }

    /**
     * @OA\Put(
     *     path="/v1/companies/{id}",
     *     summary="Update a company",
     *     description="Update a company's information. Use the `Accept-Language` header to get responses in French (fr) or English (en).",
     *     tags={"Companies"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Company ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
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
     *         @OA\JsonContent(ref="#/components/schemas/UpdateCompanyRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Company updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/CompanyResource"),
     *             @OA\Property(property="message", type="string", example="Company updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - You can only update your own companies",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="This action is unauthorized.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Company not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Company not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The company name field is required."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function update(UpdateRequest $request, Company $company): JsonResponse
    {
        $company->update($request->only([
            'company_name',
            'cfe_number',
            'address',
            'description',
        ]));

        // Gérer le logo si fourni
        if ($request->hasFile('logo')) {
            $company->clearMediaCollection('logo');
            $company->addMediaFromRequest('logo')
                ->toMediaCollection('logo');
        }

        // Gérer les documents si fournis
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $document) {
                $company->addMedia($document)
                    ->toMediaCollection('documents');
            }
        }

        return response()->json([
            'data'    => new CompanyResource($company->fresh()->load('user')),
            'message' => __('companies.updated'),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/v1/companies/{id}",
     *     summary="Delete a company",
     *     description="Soft delete a company. Use the `Accept-Language` header to get responses in French (fr) or English (en).",
     *     tags={"Companies"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Company ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
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
     *         description="Company deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Company deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - You can only delete your own companies",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="This action is unauthorized.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Company not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Company not found")
     *         )
     *     )
     * )
     */
    public function destroy(Request $request, Company $company): JsonResponse
    {
        $this->authorize('delete', $company);

        $company->delete();

        return response()->json([
            'message' => __('companies.deleted'),
        ]);
    }
}
