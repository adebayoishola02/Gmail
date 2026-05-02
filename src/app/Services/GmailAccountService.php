<?php

namespace App\Services;

use App\Models\GmailAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Http;

//Get auth code
//https://accounts.google.com/o/oauth2/v2/auth?client_id=40227990769-ebm08pg7i8nsca8a1lvd0f0l3qmk2iva.apps.googleusercontent.com&redirect_uri=https://cronetic.com/google/callback&response_type=code&scope=https://mail.google.com/&access_type=offline&prompt=consent

class GmailAccountService
{

    /**
     * Get all Gmail Account for the authenticated user (paginated).
     */
    public function getAll($user, array $filters = [])
    {
        $query = GmailAccount::where('company_uuid', $user->company_uuid);

        // Search functionality
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        // Sorting
        $orderBy = $filters['order_by'] ?? 'created_at';
        $sort    = $filters['sort'] ?? 'desc';

        $query->orderBy($orderBy, $sort);

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function findByUuid($user, string $uuid)
    {
        return GmailAccount::where('company_uuid', $user->company_uuid)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    public function create(array $data, $user): GmailAccount
    {
        return DB::transaction(function () use ($data, $user) {

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'code'          => $data['authorization_code'],
                'client_id'     => $data['client_id'],
                'client_secret' => $data['client_secret'],
                'redirect_uri'  => env('GOOGLE_REDIRECT_URI'), //General redirect uri
                'grant_type'    => 'authorization_code',
            ]);

            if ($response->failed()) {
                // Throw an exception instead of returning a response
                throw new \Exception('Google Token Exchange Failed: ' . json_encode($response->json()));
            }

            $token_data = $response->json();

            // Convert expires_in to proper datetime for your database
            $expiresAt = Carbon::now()->addSeconds($token_data['expires_in'] ?? 3600)->format('Y-m-d H:i:s');

            $account = new GmailAccount();
            $account->uuid = Uuid::uuid4()->toString();
            $account->company_uuid = $user->company_uuid;
            $account->created_by_uuid = $user->uuid;
            $account->name = $data['name'] ?? null;
            $account->email_address = $data['email_address'];
            $account->client_id = $data['client_id'];
            $account->client_secret = $data['client_secret'];
            $account->access_token = $token_data['access_token'];
            $account->refresh_token = $token_data['refresh_token'];
            $account->token_expires_at = $expiresAt;
            $account->is_active = true;

            $account->save();

            return $account;
        });
    }

    public function update(array $data, GmailAccount $account): GmailAccount
    {
        return DB::transaction(function () use ($data, $account) {

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'code'          => $data['authorization_code'],
                'client_id'     => $data['client_id'] ?? $account->client_id,
                'client_secret' => $data['client_secret'] ?? $account->client_secret,
                'redirect_uri'  => env('GOOGLE_REDIRECT_URI'), //General redirect uri
                'grant_type'    => 'authorization_code',
            ]);

            if ($response->failed()) {
                // Throw an exception instead of returning a response
                throw new \Exception('Google Token Exchange Failed: ' . json_encode($response->json()));
            }

            $token_data = $response->json();

            // Convert expires_in to proper datetime for your database
            $expiresAt = Carbon::now()->addSeconds($token_data['expires_in'] ?? 3600)->format('Y-m-d H:i:s');

            $account->client_id = $data['client_id'] ?? $account->client_id;
            $account->client_secret = $data['client_secret'] ?? $account->client_secret;
            $account->access_token = $token_data['access_token'];
            $account->refresh_token = $token_data['refresh_token'];
            $account->name = $data['name'] ?? $account->name;
            $account->email_address = $data['email_address'] ?? $account->email_address;
            $account->token_expires_at = $expiresAt;
            $account->is_active = $data['is_active'] ?? $account->is_active;

            $account->save();

            return $account->fresh();
        });
    }

    public function delete(GmailAccount $account): bool
    {
        return DB::transaction(function () use ($account) {
            return $account->delete(); // or forceDelete() if not soft-deleting
        });
    }
}
