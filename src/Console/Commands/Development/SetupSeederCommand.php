<?php

namespace OpenDominion\Console\Commands\Development;

use DB;
use Illuminate\Console\Command;
use OpenDominion\Console\Commands\CommandInterface;
use OpenDominion\Helpers\BarbarianHelper;
use OpenDominion\Models\Race;
use OpenDominion\Models\RoundLeague;
use OpenDominion\Models\User;
use RuntimeException;

class SetupSeederCommand extends Command implements CommandInterface
{
    /** @var string The name and signature of the console command. */
    protected $signature = 'dev:seed:setup';

    /** @var string The console command description. */
    protected $description = 'Seeds test users and Barbarian users in a test environment.';

    /**
     * {@inheritdoc}
     */
    public function handle(): void
    {
        DB::transaction(function () {
            $this->createBarbarianUsers();
            $this->createTestUsers();
            $this->createRoundLeague();
        });
    }

    protected function createBarbarianUsers(): void
    {
        $barbarianHelper = app(BarbarianHelper::class);
        $barbarianNames = $barbarianHelper->getBarbarianReservedNames();
        $i = 1;

        foreach($barbarianNames as $barbarianName)
        {
            User::create([
                'email' => 'barbarian' . $i . '@odarena.local',
                'password' => bcrypt('test1234'),
                'display_name' => $barbarianName,
                'activated' => true,
                'activation_code' => str_random(),
            ]);

            $i++;
        }
    }

    protected function createTestUsers(): void
    {
        $races = Race::where('playable',1)->where('name', '!=', 'Barbarian')->get();

        foreach($races as $race)
        {
            $raceKey = str_replace(' ', '', strtolower($race->name));

            User::create([
                'email' => $raceKey . '@odarena.local',
                'password' => bcrypt('test1234'),
                'display_name' => $race->name,
                'activated' => true,
                'activation_code' => str_random(),
            ]);
        }
    }

    protected function createRoundLeague(): RoundLeague
    {
        return RoundLeague::create([
            'key' => 'dev',
            'name' => 'Dev',
            'description' => 'Dev'
        ]);
    }

}

