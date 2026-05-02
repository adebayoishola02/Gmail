<?php

namespace App\Jobs;

use App\Services\SendGridService;
use App\Services\ZohoUserService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $uid;
    protected $subject;
    protected $message;
    protected $htmlBody;
    protected $tries = 3; // Number of times to retry the job
    protected $backoff = 60; // Delay in seconds before retrying the job

    /**
     * Create a new job instance.
     */
    public function __construct($uid, $subject, $message, $htmlBody = null)
    {
        $this->uid = $uid;
        $this->subject = $subject;
        $this->message = $message;
        $this->htmlBody = $htmlBody;
    }

    /**
     * Execute the job.
     */
    public function handle(SendGridService $sendGridService)
    {
        Log::info("Job started for UID: {$this->uid}");

        // Fetch contact from Zoho
        $contact = $this->getContact($this->uid);
        $errors = [];

        if (!$contact) {
            Log::error("No contact found for UID: {$this->uid}");
            $errors[] = "No contact found for UID: {$this->uid}";
        }

        // Extract contact details
        $email = $contact->Email ?? null;
        $phone = $contact->Mobile_Phone ?? null;
        $isVerified = $contact->Mobile_Verified ?? false;
        $contactName = $contact->First_Name ?? 'User';

        // Send Email
        if ($email) {
            try {
                $sendGridService->sendEmail($email, $this->subject, $this->htmlBody ?? $this->message);
                Log::info("Email sent successfully to {$email}");
            } catch (\Exception $e) {
                $errors[] = "Failed to send email to {$email}: " . $e->getMessage();
                Log::error("Failed to send email to {$email}: " . $e->getMessage());
            }
        } else {
            Log::error("No email found for contact: {$contactName} (UID: {$this->uid})");
            $errors[] = "No email found for contact: {$contactName} (UID: {$this->uid})";
        }

        // Send SMS
        if ($phone) {
            if ($isVerified) {
                try {
                    $sendGridService->sendSms($phone, $this->message);
                    Log::info("SMS sent successfully to {$phone}");
                } catch (\Exception $e) {
                    $errors[] = "Failed to send SMS to {$phone}: " . $e->getMessage();
                    Log::error("Failed to send SMS to {$phone}: " . $e->getMessage());
                }
            } else {
                $errors[] = "Mobile phone not verified for contact: {$contactName} (UID: {$this->uid})";
                Log::error("Mobile phone not verified for contact: {$contactName} (UID: {$this->uid})");
            }
        } else {
            Log::error("No phone found for contact: {$contactName} (UID: {$this->uid})");
            $errors[] = "No phone found for contact: {$contactName} (UID: {$this->uid})";
        }

        // Send admin alert if there are errors
        if (!empty($errors)) {
            Log::warning("Sending admin alert for contact: {$contactName} (UID: {$this->uid})");
            $this->sendAdminAlert($sendGridService, $errors);
        }
    }

    /**
     * Fetch contact details from Zoho.
     */
    private function getContact($uid)
    {
        $zohoService = new ZohoUserService();
        $contact = $zohoService->getUserContactDetails($uid);
        $contact = json_decode(json_encode($contact), false);
        return $contact->data[0] ?? null;
    }

    /**
     * Send an admin alert if the email or SMS wasn't sent.
     */
    private function sendAdminAlert(SendGridService $sendGridService, array $errors)
    {
        $adminEmail = env('ADMIN_EMAIL', 'support@automatedtrucking.io');
        $subject = "Notification Delivery Failure for UID: {$this->uid}";
        $message = "<h3>Notification Job Failed</h3><p>The following issues occurred:</p><ul>";

        foreach ($errors as $error) {
            Log::error($error); // Log each error
            $message .= "<li>{$error}</li>";
        }
        $message .= "</ul>";

        try {
            Log::info("Sending admin alert to {$adminEmail}");
            $sendGridService->sendEmail($adminEmail, $subject, $message);
        } catch (\Exception $e) {
            Log::error("Failed to send admin alert email: " . $e->getMessage());
        }
    }
}
