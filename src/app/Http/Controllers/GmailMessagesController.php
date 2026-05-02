<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGmailMessagesRequest;
use App\Http\Requests\GetGmailMessagesRequest;
use App\Http\Requests\GmailMessageDetailsRequest;
use App\Http\Resources\GmailMessagesResource;
use App\Http\Resources\GmailMessagesResourceCollection;
use App\Services\GmailMessagesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Http\JsonResponse;

class GmailMessagesController
{
    protected $service;

    public function __construct(GmailMessagesService $service)
    {
        $this->service = $service;
    }


    public function index(GetGmailMessagesRequest $request)
    {

        $data = $this->service->listInbox($request->validated(), Auth::user());
        // $data = $this->service->listInbox([
        //     'account_uuid'    => $request->query('account_uuid'),
        //     'next_page_token' => $request->query('next_page_token'),
        //     'per_page'        => $request->query('per_page', 10),
        // ], Auth::user());

        // Use the single resource's collection method instead of the ResourceCollection class
        // This avoids the 'total()' method requirement
        return GmailMessagesResource::collection($data['messages'])
            ->additional([
                'meta' => [
                    'next_page_token' => $data['next_page_token']
                ]
            ]);
    }

    /**
     * Display a listing of the Gmail accounts (paginated).
     */
    public function internal_sent(Request $request)
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
                'data'    => new GmailMessagesResourceCollection($records),
                'message' => 'Gmail messages list retrieved successfully.'
            ]);
        } catch (Exception $e) {
            Log::error('Failed to retrieve Gmail messages list', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(StoreGmailMessagesRequest $request): JsonResponse
    {
        try {
            $message = $this->service->sendEmail($request->validated(), Auth::user());

            return response()->json([
                'success' => true,
                'data'    => new GmailMessagesResource($message),
                'message' => 'Messsage sent successfully.'
            ], 201);
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23505' || str_contains($e->getMessage(), 'gmail_messages_unique_credential')) {
                return response()->json([
                    'success' => false,
                    'message' => "A message with this google_message_id and thread_id already exists."
                ], 500);
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {

            Log::error('Failed to send email', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to send email. ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(GmailMessageDetailsRequest $request)
    {
        $message = $this->service->getMessageDetails($request->validated(), Auth::user());
        return new GmailMessagesResource($message);
    }
}
