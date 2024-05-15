<?php

namespace OpenDominion\Services\Dominion;

use Exception;

use GuzzleHttp\Client;
use OpenDominion\Models\Race;
use OpenDominion\Models\User;


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

    public function sendMessageAndGetCompletion(string $storyteller, string $message, int $maxTokens = 1000)
    {
        $payload = [
            'model' => 'gpt-4o',
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
            'n' => 1,
        ];
    
        try {
            $response = $this->client->post('chat/completions', [
                'json' => $payload
            ]);
    
            $data = json_decode($response->getBody(), true);
    
            if (isset($data['choices'][0]['message']['content'])) {
                $assistantMessage = $data['choices'][0]['message']['content'];
                return [
                    'userMessage' => $message,
                    'assistantMessage' => $assistantMessage,
                ];
            } else {
                throw new Exception('Invalid response from API');
            }
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }
    

    public function generateImagesFromText(string $text, int $n = 1, string $size = '512x512'): array
    {
        $payload = [
            'prompt' => $text,
            'n' => $n,
            'size' => $size,
            'response_format' => 'b64_json',
        ];

        try {
            $response = $this->client->post('images/generations', ['json' => $payload]);
            $data = json_decode($response->getBody(), true);

            return $data;
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    public function generateAvatar(User $user, $n = 1, string $size = '512x512', array $keywords): array
    {

        if(empty($keywords)) {
            $keywords = ['fantasy', 'warrior', 'wizard', 'hero', 'champion'];
        }

        # Get a random key from Race:all()
        $randomRace = Race::all()->random();

        $payload = [
            'prompt' => "Draw an avatar of a $randomRace->name warrior. There should be no text in the image.",
            'n' => $n,
            'size' => $size,
            'response_format' => 'b64_json',
        ];

        try {
            $response = $this->client->post('images/generations', ['json' => $payload]);
            $data = json_decode($response->getBody(), true);

            return $data;
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }
}
