<?php

namespace App\Services;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpKernel\Exception\HttpException;


/**
 * HTTP Status Code Message Utility
 *
 * Provides standardized HTTP status code messages through static access.
 *
 * @package App\Services
 */
class HTTPMessage {

    /**
     * HTTP status code to message mapping
     *
     * @var array<int, string>
     */
    protected static array $messages = [
        // Success Codes
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        204 => 'No Content',

        // Client Errors
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        409 => 'Conflict',
        410 => 'Gone',
        422 => 'Unprocessable Entity',

        // Server Errors
        500 => 'Internal Server Error',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
    ];

    /**
     * Get the HTTP status message for a given code
     *
     * @param int $code HTTP status code
     * @return string Corresponding status message or 'Unknown Status'
     */
    public static function code(int $code): string {
        return self::$messages[$code] ?? 'Unknown Status';
    }
}


/**
 * API Response Builder
 *
 * Standardized JSON response generation for API endpoints with consistent structure,
 * error handling, and pagination support.
 *
 * @package App\Services
 */
class ResponseBuilder
{

    /**
     * Generate a standardized success response
     *
     * @param mixed $data Main response payload
     * @param string|null $message Optional success message
     * @param int $statusCode HTTP status code (default: 200)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function success(
        $data = null,
        ?string $message = null,
        int $statusCode = Response::HTTP_OK
    ): JsonResponse {
        $response = [
            'status' => 'success',
            'message' => $message ?? HTTPMessage::code(200),
            'data' => $data instanceof JsonResource ? $data->resolve() : $data,
        ];

        // Check if the resource wraps a paginator
        if ($data instanceof JsonResource && $this->isPaginator($data->resource)) {
            $response = array_merge($response, $this->getPaginationMeta($data->resource));
        }

        return response()->json($response, $statusCode, [], JSON_UNESCAPED_SLASHES);
    }


    /**
     * Generate a resource creation success response
     *
     * @param mixed $data Created resource data
     * @param string|null $message Optional creation message
     * @param int $statusCode HTTP status code (default: 201)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function created(
        $data = null,
        ?string $message = null,
        int $statusCode = Response::HTTP_CREATED
    ): JsonResponse {
        return $this->success($data, $message ?? HTTPMessage::code(201), $statusCode);
    }


    /**
     * Generate a standardized error response
     *
     * @param \Exception|null $exception Optional exception object
     * @param string|null $message Custom error message
     * @param int $statusCode HTTP status code (default: 500)
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @note Automatically handles HttpException status codes
     */
    public function error(
        ?Exception $exception = null,
        ?string $message = null,
        int $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR
    ): JsonResponse {
        $statusCode = $exception instanceof HttpException
            ? $exception->getStatusCode()
            : $statusCode;

        $finalMessage = $message ?? HTTPMessage::code($statusCode);

        return response()->json([
            'status' => 'error',
            'message' => $exception ? ($exception->getMessage() ?: $finalMessage) : $finalMessage,
            'error' => $exception ? get_class($exception) : 'RuntimeError'
        ], $statusCode);
    }


    /**
     * Generate an unauthorized access response
     *
     * @param \Exception|null $exception Optional authorization exception
     * @param string|null $message Custom authorization message
     * @param int $statusCode HTTP status code (default: 401)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function unauthorized(
        ?Exception $exception = null,
        ?string $message = null,
        int $statusCode = Response::HTTP_UNAUTHORIZED
    ): JsonResponse {
        $message = $message ?? HTTPMessage::code(401);
        $message = $exception?->getMessage() ?: $message;

        return response()->json([
            'status' => 'error',
            'message' => $message,
            'error' => $exception ? get_class($exception) : 'AuthorizationException'
        ], $statusCode);
    }


    /**
     * Determine if the given data is a paginator instance
     *
     * @param mixed $data Data to check for pagination
     * @return bool True if the data is an AbstractPaginator instance, false otherwise
     *
     * @see \Illuminate\Pagination\AbstractPaginator
     */
    protected function isPaginator($data): bool
    {
        return $data instanceof AbstractPaginator;
    }


    /**
     * Extract pagination metadata from a paginator instance
     *
     * @param AbstractPaginator $paginator The paginator instance
     * @return array<string, mixed> Structured pagination metadata including:
     *         - meta:
     *           - current_page: Current page number
     *           - last_page: Last available page number
     *           - per_page: Items per page
     *           - total: Total items available
     *           - links: Navigation URLs
     *             - first: First page URL
     *             - last: Last page URL
     *             - prev: Previous page URL (nullable)
     *             - next: Next page URL (nullable)
     *
     * @throws \InvalidArgumentException If the input is not a valid paginator
     */
    protected function getPaginationMeta(AbstractPaginator $paginator): array
    {
        return [
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'links' => [
                    'first' => $paginator->url(1),
                    'last' => $paginator->url($paginator->lastPage()),
                    'prev' => $paginator->previousPageUrl(),
                    'next' => $paginator->nextPageUrl(),
                ]
            ]
        ];
    }
}
