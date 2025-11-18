<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="Interventio Tech API Documentation",
 *     version="1.0.0",
 *     description="API pour la plateforme Interventio Tech",
 *
 *     @OA\Contact(
 *         email="contact@interventio-tech.com"
 *     )
 * )
 *
 * @OA\Server(
 *     url="http://interventio-tech.test",
 *     description="Serveur de développement local"
 * )
 * @OA\Server(
 *     url="https://api.interventio-tech.com",
 *     description="Serveur de production"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
abstract class Controller
{
    use AuthorizesRequests;
}
