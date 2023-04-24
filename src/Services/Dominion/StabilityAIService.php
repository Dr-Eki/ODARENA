<?php

namespace OpenDominion\Services\Dominion;

use GuzzleHttp\Client;
use Exception;

class StabilityAIService
{
    private $client;
    private $api_key;
    private $api_host;

    public function __construct()
    {
        $this->api_key = env('STABILITY_API_KEY');
        $this->api_host = env('API_HOST', 'https://api.stability.ai');

        $this->client = new Client([
            'base_uri' => $this->api_host,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);
    }

    public function generateImagesFromText(string $text, int $cfg_scale = 35, string $clip_guidance_preset = "NONE", int $height = 512, int $width = 512, float $weight = 1, int $samples = 1, int $steps = 50): array
    {
        $payload = [
            'text_prompts' => [
                [
                    'text' => $text,
                    'weight' => $weight,
                ]
            ],
            'cfg_scale' => $cfg_scale,
            'clip_guidance_preset' => $clip_guidance_preset,
            'height' => $height,
            'width' => $width,
            'samples' => $samples,
            'steps' => $steps,
            'style_preset' => 'fantasy-art'
        ];

        try {
            $response = $this->client->post("/v1/generation/stable-diffusion-v1-5/text-to-image", ['json' => $payload]);
            $data = json_decode($response->getBody(), true);

            return $data;
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }
}
