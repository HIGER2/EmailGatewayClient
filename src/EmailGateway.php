<?php

namespace App\Service;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmailGateway
{
    /**
     * Create a new class instance.
     */
    protected $to = [];
    protected $cc = [];
    protected $bcc = [];
    protected $subject;
    protected $html;
    protected $text;
    protected $from;
    protected $attachments = [];

    public function __construct()
    {
        
    }

        public static function to($emails)
        {
            $instance = new self();
            $instance->to = (array) $emails;
            return $instance;
        }

        public function addTo($email)
        {
            $this->to[] = $email;
            return $this;
        }

            public function cc($emails)
            {
                $this->cc = (array) $emails;
                return $this;
            }

            public function bcc($emails)
            {
                $this->bcc = (array) $emails;
                return $this;
            }

            public function subject($subject)
            {
                $this->subject = $subject;
                return $this;
            }

            public function from($from)
            {
                $this->from = $from;
                return $this;
            }
            // 👉 HTML via Blade
            public function toHtml($view, $data = [])
            {
                $this->html = view($view, $data)->render();
                return $this;
            }
            // 👉 Texte brut
            public function toText($text)
            {
                $this->text = $text;
                return $this;
            }

             // 📎 fichier depuis path
                public function attach($path, $name = null)
                {
                    $this->attachments[] = [
                        'name' => $name ?? basename($path),
                        'content' => base64_encode(file_get_contents($path))
                    ];

                    return $this;
                }

                 // 📎 fichier brut (string)
                public function attachRaw($content, $name)
                {
                    $this->attachments[] = [
                        'name' => $name,
                        'content' => base64_encode($content)
                    ];
                    return $this;
                }

                public function send()
                {
                    // Log::
                   try {
                      Http::withHeaders([
                        'x-api-key' => config('services.mail_gateway.key')
                    ])
                    ->timeout(10)
                    ->retry(3, 1000)
                    ->post(config('services.mail_gateway.url'), [
                        'to' => $this->to,
                        'cc' => $this->cc,
                        'bcc' => $this->bcc,
                        'subject' => $this->subject,
                        'html' => $this->html,
                        'text' => $this->text,
                        'from' => $this->from,
                        'attachments' => $this->attachments,
                    ]);

                    Log::info('Email envoyé', ['response' => $response->json()]);
                    return $response->json();
                   } catch (\Throwable $th) {
                    Log::error('Erreur EmailGateway', ['message' => $e->getMessage()]);
                    return false;
                   }

                }
}
