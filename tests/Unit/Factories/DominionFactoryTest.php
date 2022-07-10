<?php

namespace OpenDominion\Tests\Unit\Factories;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Factories\DominionFactory;
use OpenDominion\Factories\RealmFactory;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Models\User;
use OpenDominion\Services\PackService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class DominionFactoryTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    /** @var User */
    protected $user;

    /** @var Round */
    protected $round;

    /** @var Race */
    protected $race;

    /** @var Realm */
    protected $realm;

    /** @var DominionFactory */
    protected $dominionFactory;

    /** @var PackService */
    protected $packService;

    /** @var RealmFactory */
    protected $realmFactory;

    protected function setUp()
    {
        parent::setUp();

        $this->user = $this->createUser();
        $this->round = $this->createRound();
        $this->race = Race::firstOrFail();
        $this->realm = $this->createRealm($this->round, $this->race->alignment);

        $this->dominionFactory = $this->app->make(DominionFactory::class);
        $this->packService =  $this->app->make(PackService::class);
        $this->realmFactory = $this->app->make(RealmFactory::class);
    }

    public function testCreate()
    {
        $this->assertEquals(0, $this->round->dominions()->count());

        $dominion = $this->dominionFactory->create(
            $this->user,
            $this->realm,
            $this->race,
            'Ruler Name',
            'Dominion Name'
        );

        $this->assertEquals(1, $this->round->dominions()->count());
        $this->assertEquals($dominion->id, $this->round->dominions()->first()->id);
    }
}
