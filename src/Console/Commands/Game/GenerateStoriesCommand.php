<?php

namespace OpenDominion\Console\Commands\Game;

use Log;
use Illuminate\Console\Command;
use OpenDominion\Console\Commands\CommandInterface;
use OpenDominion\Services\Dominion\GameEventService;

# ODA
#use OpenDominion\Models\Round;

class GenerateStoriesCommand extends Command implements CommandInterface
{
    /** @var string The name and signature of the console command. */
    protected $signature = 'game:generate:stories';

    /** @var string The console command description. */
    protected $description = 'Generate stories for applicable events of all active rounds (all active rounds)';

    /** @var GameEventService */
    protected $gameEventService;

    /**
     * GameTickCommand constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->gameEventService = app(GameEventService::class);
    }

    public function handle(): void
    {
        if(env('OPENAI_API_KEY'))
        {
            $this->gameEventService->generateStories();
        }
        else
        {
            xtLog('OpenAI API key not set, skipping story generation.');
        }
    }


}
