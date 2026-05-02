<?php

namespace App\Http\Controllers;

use App\Services\GmailAccountService;
use App\Http\Requests\StoreGmailAccountRequest;
use App\Http\Requests\UpdateGmailAccountRequest;
use App\Http\Resources\GmailAccountResource;
use App\Http\Resources\GmailAccountResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

class GmailAccountController
{
    protected $service;

    public function __construct(GmailAccountService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the Gmail accounts (paginated).
     */
    public function index(Request $request)
    {
        try {
            $filters = $request->only([
                'page',
                'per_page',
                'search',
                'order_by',
                'sort',
            ]);

            $records = $this->service->getAll(Auth::user(), $filters);

            return response()->json([
                'success' => true,
                'data'    => new GmailAccountResourceCollection($records),
                'message' => 'Gmail accounts list retrieved successfully.'
            ]);
        } catch (Exception $e) {
            Log::error('Failed to retrieve Gmail accounts list', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(StoreGmailAccountRequest $request): JsonResponse
    {
        try {
            $account = $this->service->create($request->validated(), Auth::user());

            return response()->json([
                'success' => true,
                'data'    => new GmailAccountResource($account),
                'message' => 'Gmail account created successfully.'
            ], 201);
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23505' || str_contains($e->getMessage(), 'gmail_accounts_unique_credential')) {
                return response()->json([
                    'success' => false,
                    'message' => "A Gmail account with this email already exists for your company."
                ], 500);
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {

            Log::error('Failed to create Gmail account', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Gmail account. ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified Gmail account.
     */
    public function show(string $uuid)
    {
        try {
            $record = $this->service->findByUuid(Auth::user(), $uuid);

            return response()->json([
                'success' => true,
                'data'    => new GmailAccountResource($record),
                'message' => 'Gmail account retrieved successfully.'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gmail account not found.',
            ], 404);
        } catch (Exception $e) {
            Log::error('Failed to retrieve Gmail account', [
                'uuid'  => $uuid ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(UpdateGmailAccountRequest $request, string $uuid): JsonResponse
    {
        try {
            $account = $this->service->findByUuid(Auth::user(), $uuid); // reuse or add method

            $updated = $this->service->update($request->validated(), $account);

            return response()->json([
                'success' => true,
                'data'    => new GmailAccountResource($updated),
                'message' => 'Gmail account updated successfully.'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Account not found.'], 404);
        } catch (\Exception $e) {
            Log::error('Failed to update Gmail account', ['uuid' => $uuid, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Update failed. ' . $e->getMessage()], 500);
        }
    }

    public function destroy(string $uuid): JsonResponse
    {
        try {
            $account = $this->service->findByUuid(Auth::user(), $uuid);

            $this->service->delete($account);

            return response()->json([
                'success' => true,
                'message' => 'Gmail account deleted successfully.'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Account not found.'], 404);
        } catch (\Exception $e) {
            Log::error('Failed to delete Gmail account', ['uuid' => $uuid, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Deletion failed.'], 500);
        }
    }
}
