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
    protected $description = 'Creates a new round which starts in 2 days';

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

        $this->info('Available game modes:');
        foreach($this->roundHelper->getRoundModes() as $mode)
        {
            $this->info("\t" . $mode.': '.$this->roundHelper->getRoundModeDescription(null, $mode, true));
        }

        $gameMode = $this->ask('Specify game mode:');

        if(empty($gameMode) or !in_array($gameMode, $this->roundHelper->getRoundModes()))
        {
            throw new RuntimeException('Invalid or missing game mode');
        }

        $goal = $this->ask('Specify goal: ');

        if(empty($goal) or $goal <= 0)
        {
            $this->error('No goal provided');
            return;
        }

        $this->info('Available leagues:');
        foreach(RoundLeague::all() as $league)
        {
            $this->info("\t" . $league->id . ': ' . $league->description);
        }
        $leagueId = $this->ask('Specify league ID: ');

        if(empty($leagueId) or !RoundLeague::where('id', $leagueId)->first())
        {
            $this->error('No or invalid league ID provided');
            return;
        }

        $roundName = $this->ask('Specify round name: ');

        if(empty($roundName))
        {
            $this->error('No round name provided');
            return;
        }

        $startDate = new Carbon('+2 days midnight');

        /** @var RoundLeague $roundLeague */
        $roundLeague = RoundLeague::where('id', $leagueId)->firstOrFail();

        $this->info('Creating a new ' . $gameMode . ' round with goal of ' . number_format($goal) . '.');
        $this->info('The round will start at ' . $startDate->toDateTimeString() . ' in league ' . $roundLeague->description . '.');

        $round = $this->roundFactory->create(
            $startDate,
            $gameMode,
            $goal,
            $roundLeague,
            $roundName
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

        // Create 20 Barbarians.
        for ($slot = 1; $slot <= 20; $slot++)
        {
            $this->info("Creating a Barbarian...");
            $this->barbarianService->createBarbarian($round);
        }

    }
}
