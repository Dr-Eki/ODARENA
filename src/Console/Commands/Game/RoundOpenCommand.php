<?php

namespace OpenDominion\Console\Commands\Game;

use DB;
use Carbon\Carbon;
use Illuminate\Console\Command;
use OpenDominion\Console\Commands\CommandInterface;
use OpenDominion\Factories\RealmFactory;
use OpenDominion\Factories\RoundFactory;
use OpenDominion\Models\RoundLeague;
use OpenDominion\Models\Race;
use OpenDominion\Helpers\RoundHelper;
use RuntimeException;

use OpenDominion\Services\Dominion\BarbarianService;

class RoundOpenCommand extends Command implements CommandInterface
{
    /** @var string The name and signature of the console command. */
    protected $signature = 'game:round:open';

    /** @var string The console command description. */
    protected $description = 'Creates a new round and prompts for conditions and settings';

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

        $counter = count($this->roundHelper->getRoundSettings());
        $counting = 1;
        foreach($this->roundHelper->getRoundSettings() as $key => $name)
        {
            $setting = $this->ask("[$counting / $counter] Enable $name (y/n) [y]: ");

            $settings[$key] = ($setting == 'n' ? false : true);

            $counting++;
        }

        $startDate = new Carbon('+2 days midnight');

        /** @var RoundLeague $roundLeague */
        $roundLeague = RoundLeague::where('id', $leagueId)->firstOrFail();

        # Confirm before proceeding
        $this->info('Creating a round with the following parameters:');
        $this->info('Game mode: ' . $gameMode);
        $this->info('Goal: ' . number_format($goal));
        $this->info('Start date: ' . $startDate->toDateTimeString());
        $this->info('League: ' . $roundLeague->description);
        $this->info('Name: ' . $roundName);
        $this->info('Settings: ');
        foreach($settings as $key => $value)
        {
            $this->info('* ' . ucfirst($key) . ': ' . ($value ? 'enabled' : 'disabled'));
        }

        if (!$this->confirm('Are you sure you want to proceed?'))
        {
            $this->info('Aborted.');
            return;
        }

        $this->info('Creating a new ' . $gameMode . ' round!');
        #$this->info('The round will start at ' . $startDate->toDateTimeString() . ' in league ' . $roundLeague->description . '.');

        DB::transaction(function () use ($startDate, $gameMode, $goal, $roundLeague, $roundName, $settings) {

            $round = $this->roundFactory->create(
                $startDate,
                $gameMode,
                $goal,
                $roundLeague,
                $roundName,
                $settings
            );

            $this->info("Round {$round->number} created in Era {$roundLeague->description}. The round starts at {$round->start_date}.");

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
            elseif($gameMode == 'factions' or $gameMode == 'factions-duration')
            {
                $this->info("Creating realms...");
                $this->realmFactory->create($round, 'npc');

                // Create a realm per playable race
                foreach(Race::where('playable',1)->orderBy('name')->get() as $race)
                {
                    $this->realmFactory->create($round, $race->key);
                }
            }
            elseif($gameMode == 'packs' or $gameMode == 'packs-duration' or $gameMode == 'artefacts-packs')
            {
                $this->info("Creating NPC realm...");
                $this->realmFactory->create($round, 'npc');
            }

            if($round->getSetting('barbarians'))
            {
                // Get starting barbarians (default 20) from user input
                $startingBarbarians = $this->ask('How many starting barbarians? [20]: ');
                if(empty($startingBarbarians))
                {
                    $startingBarbarians = 20;
                }
                
                // Create Barbarians.
                for ($slot = 1; $slot <= $startingBarbarians; $slot++)
                {
                    $this->info("Creating a Barbarian...");
                    $barbarian = $this->barbarianService->createBarbarian($round);
                    $this->info("* Barbarian {$barbarian->id} created: {$barbarian->name})");
                }
            }
            else
            {
                $this->info('Barbarians are disabled for this round.');
            }

            // Done!
            $this->info('Done! Round has been created.');
        });
    }
}
