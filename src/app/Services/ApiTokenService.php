<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\ApiTokenCreated;
use App\Notifications\ApiTokenRevoked;
use App\Notifications\ApiTokenRenewed;
use Illuminate\Support\Facades\Log;

class ApiTokenService
{
    /**
     * Create a new API token for the user
     *
     * @param User $user
     * @param string $name
     * @param \DateTime|null $expirationDate
     * @return object{plainTextToken: string, accessToken: \Laravel\Sanctum\PersonalAccessToken}
     */
    public function createToken(User $user, string $name, ?\DateTime $expirationDate = null): object
    {
        $abilities = ['*'];

        $tokenResult = $user->createToken($name, $abilities, $expirationDate);

        Log::info('API token created', [
            'user_id' => $user->id,
            'token_name' => $name,
            'expires_at' => $expirationDate?->format('Y-m-d H:i:s'),
        ]);

        $user->notify(new ApiTokenCreated($name, $expirationDate));

        return $tokenResult;
    }

    /**
     * Revoke a specific token for the user
     *
     * @param User $user
     * @param int $tokenId
     * @return bool
     */
    public function revokeToken(User $user, int $tokenId): bool
    {
        $token = $user->tokens()->where('id', $tokenId)->first();

        if (!$token) {
            return false;
        }

        $tokenName = $token->name;
        $token->delete();

        Log::info('API token revoked', [
            'user_id' => $user->id,
            'token_id' => $tokenId,
        ]);

        $user->notify(new ApiTokenRevoked($tokenName));

        return true;
    }

    /**
     * Renew a token's expiration date
     *
     * @param User $user
     * @param int $tokenId
     * @param \DateTime $newExpirationDate
     * @return bool
     */
    public function renewToken(User $user, int $tokenId, \DateTime $newExpirationDate): bool
    {
        $token = $user->tokens()->where('id', $tokenId)->first();

        if (!$token) {
            return false;
        }

        $tokenName = $token->name;
        $token->expires_at = $newExpirationDate;
        $token->save();

        Log::info('API token renewed', [
            'user_id' => $user->id,
            'token_id' => $tokenId,
            'new_expires_at' => $newExpirationDate->format('Y-m-d H:i:s'),
        ]);

        $user->notify(new ApiTokenRenewed($tokenName, $newExpirationDate));

        return true;
    }

    /**
     * Get all tokens for a user
     *
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken>
     */
    public function getUserTokens(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return $user->tokens()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get a specific token by ID
     *
     * @param User $user
     * @param int $tokenId
     * @return \Laravel\Sanctum\PersonalAccessToken|null
     */
    public function getToken(User $user, int $tokenId): ?\Laravel\Sanctum\PersonalAccessToken
    {
        return $user->tokens()->where('id', $tokenId)->first();
    }
}
