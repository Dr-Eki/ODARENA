<?php

namespace OpenDominion\Console\Commands\Game;

use Carbon\Carbon;
use Illuminate\Console\Command;
use OpenDominion\Console\Commands\CommandInterface;
use OpenDominion\Factories\RealmFactory;
use OpenDominion\Factories\RoundFactory;
use OpenDominion\Models\RoundLeague;
use OpenDominion\Helpers\RoundHelper;
use RuntimeException;

use OpenDominion\Services\Dominion\BarbarianService;

class RoundOpenCommand extends Command implements CommandInterface
{
    /** @var string The name and signature of the console command. */
    protected $signature = 'game:round:open
                             {--gamemode= : Round game mode}
                             {--goal= : Goal land or ticks (-duration gamemodes)}
                             {--leagueId= : League ID (optional)}';

    /** @var string The console command description. */
    protected $description = 'Creates a new round which starts in 5 days';

    /** @var RealmFactory */
    protected $realmFactory;

    /** @var RoundFactory */
    protected $roundFactory;

    /** @var RoundHelper */
    protected $roundHelper;

    /** @var BarbarianService */
    protected $barbarianService;

    /**
     * RoundOpenCommand constructor.
     *
     * @param RoundFactory $roundFactory
     * @param RealmFactory $realmFactory
     */
    public function __construct(
        BarbarianService $barbarianService,
        RealmFactory $realmFactory,
        RoundFactory $roundFactory,
        RoundHelper $roundHelper
    ) {
        parent::__construct();

        $this->roundFactory = $roundFactory;
        $this->roundHelper = $roundHelper;
        $this->realmFactory = $realmFactory;
        $this->barbarianService = $barbarianService;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(): void
    {
        $gameMode = $this->option('gamemode');
        $goal = $this->option('goal');
        $leagueId = $this->option('leagueId') ?: 1;

        if(!$gameMode or !in_array($gameMode, $this->roundHelper->getRoundModes()))
        {
            throw new RuntimeException('Invalid or missing game mode');
        }

        if(!$goal or $goal <= 0)
        {
            throw new RuntimeException('Invalid or missing goal');
        }

        $startDate = new Carbon('+2 days midnight');

        /** @var RoundLeague $roundLeague */
        $roundLeague = RoundLeague::where('id', $leagueId)->firstOrFail();

        $this->info('Creating a new ' . $gameMode . ' round with goal of ' . number_format($goal) . '.');

        $round = $this->roundFactory->create(
            $startDate,
            $gameMode,
            $goal,
            $roundLeague
        );

        $this->info("Round {$round->number} created in Era {$roundLeague->key}. The round starts at {$round->start_date}.");

        // Prepopulate round with #1 Barbarian, #2 Commonwealth, #3 Empire, #4 Independent
        if($gameMode == 'standard' or $gameMode == 'standard-duration' or $gameMode == 'artefacts')
        {
            $this->info("Creating realms...");
            $this->realmFactory->create($round, 'npc');
            $this->realmFactory->create($round, 'good');
            $this->realmFactory->create($round, 'evil');
            $this->realmFactory->create($round, 'independent');
        }
        elseif($gameMode == 'deathmatch' or $gameMode == 'deathmatch-duration')
        {
            $this->info("Creating realms...");
            $this->realmFactory->create($round, 'npc');
            $this->realmFactory->create($round, 'players');
        }

        // Create 18 Barbarians.
        for ($slot = 1; $slot <= 18; $slot++)
        {
            $this->info("Creating a Barbarian...");
            $this->barbarianService->createBarbarian($round);
        }

    }
}
