<?php

namespace App\Services;

use App\Models\GmailAccount;
use App\Models\GmailMessages;
use Carbon\Carbon;
use Google\Client;
use Google\Service\Gmail;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use Google\Service\Exception as GoogleException;
use Illuminate\Support\Facades\Log;

class GmailMessagesService
{

    /**
     * Get a configured Google Client for a specific merchant.
     */
    private function getAuthenticatedClient(GmailAccount $account): Client
    {
        $client = new Client();
        $client->setClientId($account->client_id);
        $client->setClientSecret($account->client_secret);
        $client->setAccessToken($account->access_token);

        if ($client->isAccessTokenExpired()) {
            $newToken = $client->fetchAccessTokenWithRefreshToken($account->refresh_token);

            if (isset($newToken['error'])) {
                throw new \Exception('Could not refresh Google token: ' . $newToken['error']);
            }

            $expiresAt = Carbon::now()->addSeconds($newToken['expires_in'] ?? 3600)->format('Y-m-d H:i:s');

            $updateData = [
                'access_token'     => $newToken['access_token'],
                'token_expires_at' => $expiresAt,
            ];

            if (isset($newToken['refresh_token'])) {
                $updateData['refresh_token'] = $newToken['refresh_token'];
            }

            $account->update($updateData);
            $client->setAccessToken($newToken['access_token']);
        }

        return $client;
    }

    /**
     * Get all Gmail messages for the authenticated user (paginated).
     */
    public function getAll($user, array $filters = [])
    {
        $query = GmailMessages::where('company_uuid', $user->company_uuid);

        // Search functionality
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('google_message_id', 'like', "%{$search}%")
                    ->orWhere('thread_id', 'like', "%{$search}%")
                    ->orWhere('recipient', 'like', "%{$search}%")
                    ->orWhere('sender', 'like', "%{$search}%");
            });
        }

        // Sorting
        $orderBy = $filters['order_by'] ?? 'created_at';
        $sort    = $filters['sort'] ?? 'desc';

        $query->orderBy($orderBy, $sort);

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function sendEmail(array $data, $user)
    {

        // 1. Fetch the merchant account containing THEIR Client ID and Secret
        $account = GmailAccount::where('uuid', $data['account_uuid'])
            ->where('company_uuid', $user->company_uuid)
            ->first();

        if (!$account || empty($account->client_id) || empty($account->client_secret)) {
            throw new \Exception('Gmail Merchant credentials (ID/Secret) are missing.');
        }

        // 2. Get Authenticated Client
        $client = $this->getAuthenticatedClient($account);
        $gmail = new Gmail($client);

        // 1. Prepare the email
        $strRawMessage = "To: {$data['recipient']}\r\n";
        $strRawMessage .= "Subject: {$data['subject']}\r\n\r\n";
        $strRawMessage .= $data['body'];

        $mime = rtrim(strtr(base64_encode($strRawMessage), '+/', '-_'), '=');
        $msg = new \Google\Service\Gmail\Message();
        $msg->setRaw($mime);

        try {

            // 2. Send via Google API first
            $response = $gmail->users_messages->send('me', $msg);

            // 3. Execute Database Transaction
            return DB::transaction(function () use ($data, $response, $account, $user) {
                return GmailMessages::create([
                    'uuid'              => Uuid::uuid4()->toString(),
                    'company_uuid'      => $user->company_uuid,
                    'created_by_uuid'   => $user->uuid,
                    'account_uuid'      => $account->uuid,
                    'google_message_id' => $response->id,
                    'thread_id'         => $response->threadId,
                    'recipient'         => $data['recipient'],
                    'sender'            => $account->email_address,
                    'subject'           => $data['subject'],
                    'body'              => $data['body'],
                    'type'              => $response->labelIds[0],
                    'sent_at'           => now(),
                ]);
            });
        } catch (GoogleException $e) {
            // Log the detailed error from Google
            Log::error('Gmail API Error: ' . $e->getMessage(), [
                'code' => $e->getCode(),
                'errors' => $e->getErrors()
            ]);

            // Handle specific codes
            if ($e->getCode() == 401) {
                throw new \Exception("Gmail authentication failed. Please reconnect your account.");
            }

            throw new \Exception("Failed to send email via Gmail: " . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('General Error in Gmail Service: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Fetch Gmail inbox messages with pagination and local syncing.
     */
    public function listInbox(array $data, $user)
    {

        $account = GmailAccount::where('uuid', $data['account_uuid'])
            ->where('company_uuid', $user->company_uuid)
            ->firstOrFail();

        $client = $this->getAuthenticatedClient($account);
        $gmail = new Gmail($client);
        $labelIds = $data['label_id'] ?? "INBOX"; //e.g INBOX, SENT, DRATF

        // 1. Set up list parameters
        $params = [
            'maxResults' => $data['per_page'] ?? 10,
            'labelIds'   => [$labelIds],
            'q'          => '-is:chat', // Exclude hangouts/chats
        ];

        // 2. Handle Pagination Token
        if (!empty($data['next_page_token'])) {
            $params['pageToken'] = $data['next_page_token'];
        }

        $listResponse = $gmail->users_messages->listUsersMessages('me', $params);
        $googleMessages = $listResponse->getMessages();
        $nextPageToken = $listResponse->getNextPageToken();

        $messages = [];

        if ($googleMessages) {
            foreach ($googleMessages as $msgSummary) {
                // 🔥 CRITICAL: You must call ->get() to get the snippet, date, and headers
                $detail = $gmail->users_messages->get('me', $msgSummary->getId());
                $messages[] = $this->formatMessageDirectly($detail, $account);
            }
        }

        return [
            'messages' => $messages,
            'next_page_token' => $nextPageToken,
        ];
    }

    /**
     * Helper to parse headers into a clean array.
     */
    protected function formatMessageDirectly($googleMessage, $account)
    {
        $headers = collect($googleMessage->getPayload()->getHeaders());

        return [
            'google_message_id' => $googleMessage->getId(),
            'thread_id'         => $googleMessage->getThreadId(),
            'recipient'         => $account->email_address,
            'sender'            => $headers->firstWhere('name', 'From')->value ?? 'Unknown',
            'subject'           => $headers->firstWhere('name', 'Subject')->value ?? '(No Subject)',
            'snippet'           => $googleMessage->getSnippet(),
            'created_at'        => \Carbon\Carbon::createFromTimestampMs($googleMessage->getInternalDate())->toDateTimeString(),
        ];
    }


    /**
     * Fetch a single message with its full decoded body.
     */
    public function getMessageDetails(array $data, $user)
    {
        $account = GmailAccount::where('uuid', $data['account_uuid'])
            ->where('company_uuid', $user->company_uuid)
            ->firstOrFail();

        $client = $this->getAuthenticatedClient($account);
        $gmail = new \Google\Service\Gmail($client);

        // Fetch the message (Format 'full' is default, which includes the body)
        $message = $gmail->users_messages->get('me', $data['message_id']);

        return $this->parseFullMessage($message, $account);
    }

    /**
     * Helper to extract and decode the body content from Gmail Parts.
     */
    protected function parseFullMessage($message, $account)
    {
        $payload = $message->getPayload();
        $headers = collect($payload->getHeaders());

        // Get Body (Search for HTML first, then Plain Text)
        $body = $this->extractBody($payload);

        return [
            'uuid'            => $message->getId(),
            'thread_id'       => $message->getThreadId(),
            'subject'         => $headers->firstWhere('name', 'Subject')->value ?? '(No Subject)',
            'from'            => $headers->firstWhere('name', 'From')->value ?? 'Unknown',
            'to'              => $headers->firstWhere('name', 'To')->value ?? 'Unknown',
            'date'            => Carbon::createFromTimestampMs($message->getInternalDate())->toDateTimeString(),
            'snippet'         => $message->getSnippet(),
            'body'            => $body, // The decoded HTML/Text content
            'company_uuid'    => $account->company_uuid,
        ];
    }


    /**
     * Recursive function to find the message body in Parts.
     */
    private function extractBody($part)
    {
        $body = "";

        // Check if this part has a body (direct)
        if ($part->getBody()->getData()) {
            $body = base64_decode(strtr($part->getBody()->getData(), '-_', '+/'));
        }

        // Check sub-parts (nested)
        if ($part->getParts()) {
            foreach ($part->getParts() as $subPart) {
                // Prioritize HTML over plain text
                if ($subPart->getMimeType() === 'text/html') {
                    return base64_decode(strtr($subPart->getBody()->getData(), '-_', '+/'));
                }
                // Fallback to text/plain if HTML not found yet
                if ($subPart->getMimeType() === 'text/plain' && empty($body)) {
                    $body = base64_decode(strtr($subPart->getBody()->getData(), '-_', '+/'));
                }

                // Recurse into nested parts if necessary
                $nested = $this->extractBody($subPart);
                if (!empty($nested)) return $nested;
            }
        }

        return $body;
    }
}
