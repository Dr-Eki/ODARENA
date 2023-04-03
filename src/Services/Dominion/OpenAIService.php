<?php

namespace OpenDominion\Services\Dominion;

use GuzzleHttp\Client;

class OpenAIService
{
    private $client;
    private $api_key;

    public function __construct()
    {
        $this->api_key = env('OPENAI_API_KEY');

        $this->client = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function sendMessageAndGetCompletion(string $storyteller, string $message, int $maxTokens = 50)
    {
        $payload = [
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $storyteller,
                ],
                [
                    'role' => 'user',
                    'content' => $message,
                ],
            ],
            'max_tokens' => $maxTokens,
        ];

        try {
            $response = $this->client->post('chat', ['json' => $payload]);
            $data = json_decode($response->getBody(), true);

            $assistantMessage = $data['choices'][0]['message']['content'];

            return [
                'userMessage' => $message,
                'assistantMessage' => $assistantMessage,
            ];
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }
}