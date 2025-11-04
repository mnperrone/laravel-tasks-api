<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Auth Controller
 *
 * Handles JWT authentication for API endpoints.
 */
class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get a JWT via given credentials.
     *
     * @param LoginRequest $request
     * @return JsonResponse
     *
     * @OA\Post(
     *     path="/auth/login",
     *     summary="Inicia sesión con email y password",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", example="admin@example.com"),
     *             @OA\Property(property="password", type="string", example="password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login exitoso: devuelve access y refresh token",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJh..."),
     *             @OA\Property(property="refresh_token", type="string", example="eyJhbGciOiJIUzI1NiIsInR..."),
     *             @OA\Property(property="token_type", type="string", example="bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=900)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Credenciales inválidas"
     *     ),
     *     @OA\Response(
     *         response=429,
     *         description="Demasiados intentos fallidos (rate limit)"
     *     )
     * )
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Build a refresh token with a longer TTL (use config('jwt.refresh_ttl'))
        $user = auth('api')->user();

        // store original ttl and set refresh ttl temporarily
        $factory = auth('api')->factory();
        $originalTtl = $factory->getTTL();
        $refreshTtl = (int) config('jwt.refresh_ttl', $originalTtl);

        try {
            $factory->setTTL($refreshTtl);
            // add a claim to identify this as a refresh token (optional)
            $refreshToken = auth('api')->claims(['typ' => 'refresh'])->fromUser($user);
        } finally {
            // restore original ttl
            $factory->setTTL($originalTtl);
        }

        return $this->respondWithToken($token, $refreshToken);
    }

    /**
     * Get the authenticated User.
     *
     * @return JsonResponse
     */
    public function me(): JsonResponse
    {
        return response()->json(auth('api')->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        auth('api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return JsonResponse
     */
    public function refresh(Request $request): JsonResponse
    {
        // Accept a refresh token in the request body (refresh_token)
        $refreshToken = $request->input('refresh_token');

        if (empty($refreshToken)) {
            return response()->json(['error' => 'refresh_token is required'], 422);
        }

        try {
            // Set the token to the provided refresh token and refresh it into a new access token
            $newAccessToken = auth('api')->setToken($refreshToken)->refresh();

            return $this->respondWithToken($newAccessToken);
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['error' => 'Refresh token expired'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['error' => 'Invalid refresh token'], 401);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Could not refresh token'], 500);
        }
    }

    /**
     * Get the token array structure.
     *
     * @param string $token
     * @return JsonResponse
     */
    protected function respondWithToken(string $token, ?string $refreshToken = null): JsonResponse
    {
        $response = [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => auth('api')->user()
        ];

        if (!is_null($refreshToken)) {
            $response['refresh_token'] = $refreshToken;
        }

        return response()->json($response);
    }
}
