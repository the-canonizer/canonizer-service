<?php

namespace App\Swagger;

use OpenApi\Annotations as OA;

/**
 * @OA\OpenApi(
 *     @OA\Info(
 *         version="1.0.0",
 *         title="Canonizer API",
 *         description="API documentation for Canonizer",
 *         termsOfService="http://canonizer.com/terms",
 *         @OA\Contact(
 *             email="support@canonizer.com"
 *         )
 *     ),
 *     @OA\Server(
 *         description="Canonizer Service API",
 *         url="https://service.canonizer.com/api/v1/"
 *     )
 *     @OA\Components(
 *         @OA\SecurityScheme(
 *             securityScheme="bearerAuth",
 *             type="http",
 *             scheme="bearer",
 *             bearerFormat="JWT"
 *         )
 *     )
 * )
 */
class OpenApiSpec
{
}

