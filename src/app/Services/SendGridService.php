<?php

namespace App\Services;

use GuzzleHttp\Client;
use Twilio\Rest\Client as TwilioClient;

class SendGridService
{
    protected $httpClient;
    protected $sendgridApiKey;
    protected $twilioClient;
    protected $twilioSid;
    protected $twilioAuthToken;
    protected $twilioPhoneNumber;

    public function __construct()
    {
        // SendGrid setup
        $this->httpClient = new Client();
        $this->sendgridApiKey = env('SENDGRID_API_KEY');

        // Twilio setup
        $this->twilioSid = env('TWILIO_SID');
        $this->twilioAuthToken = env('TWILIO_AUTH_TOKEN');
        $this->twilioPhoneNumber = env('TWILIO_PHONE_NUMBER');
        $this->twilioClient = new TwilioClient($this->twilioSid, $this->twilioAuthToken);
    }

    public function sendEmail($to, $subject, $htmlContent)
    {
        $response = $this->httpClient->post('https://api.sendgrid.com/v3/mail/send', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->sendgridApiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'personalizations' => [
                    [
                        'to' => [['email' => $to]],
                        'subject' => $subject,
                    ],
                ],
                'from' => [
                    'email' => env('MAIL_FROM_ADDRESS'),
                    'name' => env('MAIL_FROM_NAME', 'Your App Name'),
                ],
                'content' => [['type' => 'text/html', 'value' => $htmlContent]],
            ],
        ]);

        return $response->getStatusCode(); // 202 if successful
    }

    public function sendSms($to, $message)
    {
        $message = $this->twilioClient->messages->create(
            $to, // Recipient's phone number
            [
                'from' => $this->twilioPhoneNumber, // Twilio phone number
                'body' => $message,
            ]
        );

        return $message->sid; // Return Twilio message SID
    }
}
