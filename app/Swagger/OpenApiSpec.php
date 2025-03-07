<?php

namespace App\Swagger;

use OpenApi\Annotations as OA;

/**
 * @OA\OpenApi(
 * 
 *   @OA\Info(
 *     version="1.0.0",
 *     title="Canonizer Service",
 *     description="API documentation for Canonizer Service", 
 *     termsOfService="http://canonizer.com/terms", 
 *     @OA\Contact(email="support@canonizer.com")
 *   ),
 * 
 *   @OA\Server(url="https://ux-service.canonizer.com/api", description="Canonizer Service API"),
 * 
 *   @OA\Components(
 * 
 *     @OA\Response(response="400BadRequest", description="Bad Request",
 *       @OA\JsonContent(type="object",
 *         @OA\Property(property="status_code", type="integer", example=400),
 *         @OA\Property(property="message", type="string", example="Invalid request parameters"),
 *         @OA\Property(property="error", type="string", nullable=true)
 *       )
 *     ),
 * 
 *     @OA\Response(response="404NotFound", description="Resource Not Found",
 *       @OA\JsonContent(type="object",
 *         @OA\Property(property="status_code", type="integer", example=404),
 *         @OA\Property(property="message", type="string", example="Record not found"),
 *         @OA\Property(property="error", type="string", nullable=true)
 *       )
 *     )
 *   )
 * )
 */
class OpenApiSpec {}
