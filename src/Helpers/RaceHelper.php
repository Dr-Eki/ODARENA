<?php

namespace OpenDominion\Helpers;

use LogicException;
use OpenDominion\Models\Race;
use OpenDominion\Models\RacePerkType;

class RaceHelper
{
    public function getRaceDescriptionHtml(Race $race): string
    {
        $descriptions = [];

        // COMMONWEALTH

        $descriptions['dwarf'] = <<<DWARF
<p>Dwarves are a short and ill-tempered race well-known for their majestic beards, their love of ale and tireless labour. Their spirited chants and songs echo long into the night as they hollow out entire mountains for their ore.</p>
<p>Dwarven mines are the most productive in the lands, producing a steady flow of ore used to fortify their great cities, and craft legendary Dwarven armour for their military forces.</p>
DWARF;

        $descriptions['firewalker'] = <<<FIREWALKER
<p>The first Firewalker erupted into existence from the smouldering ashes of a greedy scientist that had sought to enrich himself with forbidden alchemical practices, combining chemistry and pyromancy to forge platinum from the earth itself.</p>
<p>Turning entire networks of caverns into vast furnaces where they live as one flame, Firewalker alchemies enhanced by pyromantic magics are the most productive in the lands, and their populations spread like wildfire wherever they ignite.</p>
FIREWALKER;

        $descriptions['gnome'] = <<<GNOME
<p>These ingenious little people are the masters of invention and tinkering technology.</p>
<p>Although slow and expensive due their metallic augments, their powerful machinery can turn the tide of battles in the late game.</p>
<hr />
<p>Gnomes finish buildings in nine ticks, instead of the normal twelve.</p>
GNOME;

        $descriptions['halfling'] = <<<HALFLING
<p>A cheerful and adventurous race known for their diminutive stature and furry, bare feetses. They are exceptionally stealthy due to their size rather than grace.</p>
<p>Fiercely loyal to family and friends, they will defend their homeland with surprising fortitude.</p>
HALFLING;

        $descriptions['human'] = <<<HUMAN
<p>Among the youngest of the races, the Human empire rose swiftly and against the odds. Humans proved to be not only capable warriors, but also skilled smiths, clever engineers and above all, adaptable. Their homeland destroyed by the forces of Evil decades ago, Humans seek to rebuild and avenge their fallen brothers.</p>
<p>Humans are generally proficient in everything they set their mind to, though they are masters of no single discipline.</p>
HUMAN;

        $descriptions['merfolk'] = <<<MERFOLK
<p>An aquatic race that lives in beautiful coral reefs where food is plentiful, Merfolk are the benevolent guardians of the great oceans.</p>
<p>Though typically peaceful, Merfolk are legendary for their wrath when angered, summoning terrors of the deep to destroy entire naval fleets with the thrashing tentacles of ravenous krakens. The chilling and alluring call of the psiren might be the last thing you ever hear... before you're snatched from your ship and dragged down to the bottom of the cold, dark sea.</p>
MERFOLK;

        $descriptions['spirit'] = <<<SPIRIT
<p>These kind spirits long for a quiet and peaceful world.</p>
<p>Some of the lost souls of fallen enemies, will join their ranks in search of this goal.</p>
SPIRIT;

        $descriptions['kobold'] = <<<KOBOLD
<p>A bunch of annoying little shits.</p>
<p>You no take candle!</p>
KOBOLD;

        $descriptions['sylvan'] = <<<SYLVAN
<p>Mythical forest-dwelling creatures, which have banded together to combat the forces of evil.</p>
<p>Their affinity for nature makes them excellent at exploration, and highly proficient spellcasters.</p>
SYLVAN;

        $descriptions['wood elf'] = <<<WOODELF
<p>Graceful, slender and eerily beautiful, the Wood Elves are among the eldest of the races and keenly attuned with the natural world, seeking to protect their forests from the forces of evil.</p>
<p>Though peaceful by nature, Wood Elves are a versatile race, proficient in combat with their deadly archers and magically gifted druids that draw power from the very forest itself, and backed up by powerful wizards and skilled spies that excel at covert ops.</p>
WOODELF;

        $descriptions['beastfolk'] = <<<BEASTFOLK
<p>Arcane magic has given life to the beasts of the forest, which have been seen building villages in the forests of the Commonwealth.</p>
<p>Beastfolk take shelter in the forests and excel at fighting on the open plains.</a>
<hr />
<p>Instead of castle improvements, Beastfolk use natural resources (land types) to strengthen their dominion. All bonuses except the OP and DP bonuses from Plains and Hills are increased by prestige.</p>
<ul>
<li>Platinum production increased by Mountains % (<em>Science</em>).</li>
<li>Max population increased by Forest % (<em>Keep</em>).</li>
<li>Wizard Strength increased by 2 x Swamp % (<em>Tower</em>).</li>
<li>Defensive Power increased by Hills % (<em>Walls</em>).</li>
<li>Offensive Power increased by 0.2 x Plains % (<em>Forges</em>).</li>
<li>Food and Boat production increased by 5 x Water % (<em>Harbor</em>).</li>
<li>Spy Strength increased by Caverns %.</li>
BEASTFOLK;

        $descriptions['ants'] = <<<ANTS
<p>After the disappearance of some magic crystals deep in the forest long ago, giant swarms of ants have been seen forming enormous colonies and growing in size.</p>
<p>Their units are weak, but they are both numerous and belligerent.</p>
ANTS;

        $descriptions['sacred order'] = <<<SACREDORDER
<p>The Sacred Order was created by Humans who felt that Crusades should be lifelong struggles to reach the Divines.</p>
<p>Monks are responsible for providing stable defenses, while Fanatics are seen both defending the holy land and conquering more, strengthened by Temples to the Divines.</p>
<p>Fanatics can convert some enemy soldiers into Martyrs, which join the infantry alongside the mighty Holy Warriors.</p>
SACREDORDER;

        $descriptions['templars'] = <<<TEMPLARS
<p>Another off-shoot from Humans, the Templars are a small, pious community taking up residence in the hills.</p>
TEMPLARS;

        $descriptions['armada'] = <<<ARMADA
<p>The Commonwealth Armada traces its origin to Human naval forces aimed at taking care of increasing piracy and worrying signs of an Imperial navy.</p>
<p>They were given a great deal of autonomy in order to face challenges in a manner and with an efficiency normal human bureaucracy would not permit.</p>
<p>With increased self-governance, the Armada gradually grow into its own faction of the Commonwealth and now controls its own seafaring dominions.</p>
ARMADA;

        $descriptions['norse'] = <<<NORSE
<p>Rarely before have the Norse ventured south for anything other than trade.</p>
<p>The emergence of the Empire has forced even these northern stalwarts to pick a side, joining the Commonwealth.</p>
<p>They are fierce warriors, into death and beyond.</p>
NORSE;

        $descriptions['lux'] = <<<LUX
<p>The Lux are the children of the Suns.</p>
<p>Some believe they are elves which transcended. Others say they are not of this world at all.</p>
<hr />
<p>Essences are trained from draftees.</p>
<p>Hex and Vex are trained from Essences and cost no draftees. They both take 12 ticks to train.</p>
<p>Pax is trained by combining one Hex and one Vex. No draftee required.</p>
LUX;

        $descriptions['snow elf'] = <<<SNOWELF
<p>Snow Elves are an isolated elven people living at the highest mountain peaks overlooking the plains of the Commonwealth.</p>
<p>Hidden by the clouds, they build enormous trebuchets and use powerful magic to throw ice bou lders at great distances.</p>
<p>They have learned to live with the mighty snowmen, the Yeti, and use their hunger for gryphon eggs to trap and tame them.</p>
<hr />
<p>For every ten Gryphon Nests, one wild yeti per tick is trapped. This is doubled if Gryphon's Call is cast.</p>
<p>Up to 5% of all wild yetis in captivity escape every tick.</p>
<hr />
<p>Each Trebuchet can throw one Ice Boulder, which is destroyed upon impact. It takes one tick to set the Trebuchet up again and be ready to throw a new Ice Boulder.</p>
SNOWELF;

        // EMPIRE
        $descriptions['dark elf'] = <<<DARKELF
<p>With ashen skin, inky-black eyes and bat-like features, Dark Elves are the cave-dwelling distant cousins of the majestic Wood Elves. Corrupted long ago by the whispered promises of power from fallen demons, the Dark Elves are a cruel species who thrive on torment... be it the torment of their enemies, or even their own kin.</p>
<p>Dark Elves have black magic coursing through their veins. Capable of calling down a rain of fire and lightning upon their enemies, they are a terrifying force to reckon with - and that's before they even set foot on the battlefield.</p>
DARKELF;

        $descriptions['goblin'] = <<<GOBLIN
<p>Small in stature but great in number, Goblins are a vicious and single-minded breed that prefer to take down their enemies with overwhelming numbers - and then steal all the shinies.</p>
<p>Goblin populations can grow quickly out of control if left unchecked, and these short, green and ugly wretches have been known to completely ransack well-fortified castles in their relentless pursuit of gems, gems and more gems.</p>
GOBLIN;

        $descriptions['icekin'] = <<<ICEKIN
<p>Slow, lumbering elementals of frost and stone, Icekin emerged from the snow-capped mountains as a counterweight to unnatural the pyromancy experiments that created the Firewalkers.</p>
<p>Their creeping cold expands ever outwards, insistent, transforming the lands around them with a white permafrost and hijacking the weather with seemingly never-ending blizzards. Icekin can become an immense military threat once they become well fortified.</p>
ICEKIN;

        $descriptions['lizardfolk'] = <<<LIZARDFOLK
<p>These amphibious creatures hail from the depths of the seas, having remained mostly hidden for decades before resurfacing and joining the war.</p>
<p>Lizardfolk are highly proficient at both performing and countering espionage operations, and make for excellent incursions on unsuspecting targets.</p>
LIZARDFOLK;

        $descriptions['lycanthrope'] = <<<LYCANTHROPE
<p>Once thought to be an ancient curse that transformed men into wolves under the light of a full moon, little is understood about the Lycanthropic affliction. But one thing's for certain: once bitten, you'll never be the same again.</p>
<p>Capable of agonising transformations into half-beast monsters, Lycanthropes are a hardy and fast-growing race, turning their enemies into werewolves and regenerating non-lethal wounds mid-combat.</p>
LYCANTHROPE;

        $descriptions['orc'] = <<<ORC
<p>Known for their barbaric behavior and lack of intelligence, these warmongering creatures have an insatiable hunger for destruction.</p>
<p>Orcs are proud warriors with a strong sense of honor. As formidable as they are, their direct approach to warfare is weak against a fortified position.</p>
ORC;

        $descriptions['nomad'] = <<<NOMAD
<p>Forever wandering the plains, Nomads are Humans who have rejected sedentary culture.</p>
<p>Very similar to the Human armies they have since long rejected, except for the masterful Horse Archers.</p>
NOMAD;

        $descriptions['nox'] = <<<NOX
<p>The children of the night lurk in the shadows, striking terror in even the most powerful of rulers.</p>
<p>Nox can be found in the deepest darkness where even Dark Elves won't dare to trespass.</p>
NOX;

        $descriptions['troll'] = <<<TROLL
<p>Trolls can be found in the earliest scriptures and even they speak of trolls in ancient tales. Foul, brutish, and dull, trolls have always been feared by those wandering the lands where forest meets plain.</p>
<p>Trolls are notoriously bloodthirsty. What they lack in subtlety they more than make up for in gratuitous violence. It is not uncommon to see fully-armoured soldiers being punted up sixty feet into the air in battle with trolls. <em>[Urg smash!]</em></p>
TROLL;

        $descriptions['undead'] = <<<UNDEAD
<p>A ceaseless horde of beings that have overcome death, the undead have an insatiable desire to destroy all living creatures.</p>
<p>They are always on the offensive, increasing their number by reanimating fallen enemies.</p>
UNDEAD;

        $descriptions['void'] = <<<VOID
<p>Nothing is known about the Void except that they emerge from deep, abandoned mines high up in the mountains.</p>
<p>For reasons not yet laid bare, the Void has aligned with the Empire. For now. Rumours say not even the Empress knows why.</p>
<hr />
<ul>
<li>Starting resources consist of 200,000 plat and 0 lumber and food.</p>
<li>Start with 500 Shadows.</li>
<li>All peasants (including unemployed) produce 2.7 plat/hr raw.</li>
</ul>
VOID;

        $descriptions['dragon'] = <<<DRAGON
<p>Dragons of old have returned from beyond the Endless Sea.</p>
DRAGON;

        $descriptions['afflicted'] = <<<AFFLICTED
<p>The Afflicted are an unholy alliance of foul beasts and misfits. Barely able to act as one, the Afflicted are in a constant power struggle.</p>
AFFLICTED;

        $descriptions['growth'] = <<<GROWTH
<p>The growth is believed to originate from ancient Nox burial grounds, deep in the swamp lands.</p>
<p>Attempts at communication have failed. It shows no signs of sentience.</p>
GROWTH;

        $descriptions['imperial gnome'] = <<<IMPERIALGNOME
<p>Distressed by the failed attempt to create an Orcish Navy, the Empress exploited the largest weakness found in Gnomes: their greed.</p>
<p>By promising them gems and ore, the Empress convinced thousands of Commonwealth Gnomes to join the Empire and build war machines.</p>
IMPERIALGNOME;

        $descriptions['demon'] = <<<DEMON
<p>The Empress has struck a deal with an ancient evil and allied the Empire with Demonic beasts.</p>
<p>Demonic loyalty is always capricious, but as long as there are souls to collect, the Demons will follow the Empress.</p>
<hr />
<p>The soul of every unit killed in battle is collected and can be used to summon stronger demons.</p>
DEMON;

$descriptions['black orc'] = <<<BLACKORC
<p>Part of The Empress' elite of distinguished warriors, the Black Orc &mdash; so called for their black armour and black weapons forged with moonsoot &mdash; are the preeminent infantry of the Empire.</p>
BLACKORC;

        $key = strtolower($race->name);

        if (!isset($descriptions[$key])) {
            throw new LogicException("Racial description for {$key} needs implementing");
        }

        return $descriptions[$key];
    }

    public function getPerkDescriptionHtml(RacePerkType $perkType): string
    {
        switch($perkType->key) {
            case 'archmage_cost':
                $negativeBenefit = true;
                $description = 'archmage cost';
                break;
            case 'construction_cost':
                $negativeBenefit = true;
                $description = 'construction cost';
                break;
            case 'defense':
                $negativeBenefit = false;
                $description = 'defensive power';
                break;
            case 'extra_barren_max_population':
                $negativeBenefit = false;
                $description = 'population from barren land';
                break;
            case 'food_consumption':
                $negativeBenefit = true;
                $description = 'food consumption';
                break;
            case 'food_production':
                $negativeBenefit = false;
                $description = 'food production';
                break;
            case 'gem_production':
                $negativeBenefit = false;
                $description = ' gem production';
                break;
            case 'immortal_wizards':
                $negativeBenefit = false;
                $description = 'immortal wizards';
                break;
            case 'invest_bonus':
                $negativeBenefit = false;
                $description = 'castle bonuses';
                break;
            case 'lumber_production':
                $negativeBenefit = false;
                $description = 'lumber production';
                break;
            case 'mana_production':
                $negativeBenefit = false;
                $description = 'mana production';
                break;
            case 'max_population':
                $negativeBenefit = false;
                $description = 'max population';
                break;
            case 'offense':
                $negativeBenefit = false;
                $description = 'offensive power';
                break;
            case 'ore_production':
                $negativeBenefit = false;
                $description = 'ore production';
                break;
            case 'platinum_production':
                $negativeBenefit = false;
                $description = 'platinum production';
                break;
            case 'spy_strength':
                $negativeBenefit = false;
                $description = 'spy strength';
                break;
            case 'wizard_strength':
                $negativeBenefit = false;
                $description = 'wizard strength';
                break;
            case 'cannot_construct':
                $negativeBenefit = false;
                $description = 'difficulty: cannot construct buildings';
                break;
            case 'boat_capacity':
                $negativeBenefit = false;
                $description = 'boat capacity';
                break;
            case 'platinum_production':
                $negativeBenefit = false;
                $description = 'platinum production';
                break;
            case 'can_invest_mana':
                $negativeBenefit = false;
                $description = 'can invest mana in castle';
                break;
            case 'population_growth':
                $negativeBenefit = false;
                $description = 'population growth rate';
                break;
            case 'cannot_improve_castle':
                $negativeBenefit = false;
                $description = 'cannot use castle improvements';
                break;
            case 'cannot_explore':
                $negativeBenefit = false;
                $description = 'cannot explore';
                break;
            case 'cannot_invade':
                $negativeBenefit = false;
                $description = 'cannot explore';
                break;
            case 'cannot_train_spies':
                $negativeBenefit = false;
                $description = 'cannot train spies';
                break;
            case 'cannot_train_wizards':
                $negativeBenefit = false;
                $description = 'cannot train wizards';
                break;
            case 'cannot_train_archmages':
                $negativeBenefit = false;
                $description = 'cannot train Arch Mages';
                break;
            case 'explore_cost':
                $negativeBenefit = false;
                $description = 'cost of exploration';
                break;
            case 'reduce_conversions':
                $negativeBenefit = false;
                $description = 'reduced conversions';
                break;
            case 'exchange_bonus':
                $negativeBenefit = false;
                $description = 'better exchange rates';
                break;
            case 'guard_tax_exemption':
                $negativeBenefit = false;
                $description = 'No guard platinum tax';
                break;
            case 'tissue_improvement':
                $negativeBenefit = false;
                $description = 'Can improve tissue (only)';
                break;
            case 'does_not_kill':
                $negativeBenefit = false;
                $description = 'Does not kill enemy units';
                break;
            case 'gryphon_nests_generates_wild_yetis':
                $negativeBenefit = false;
                $description = 'Traps wild yetis';
                break;
            case 'prestige_gains':
                $negativeBenefit = false;
                $description = 'prestige gains';
                break;
            case 'draftee_dp':
                $negativeBenefit = true;
                $description = 'DP per draftee';
                break;
            case 'increased_construction_speed':
                $negativeBenefit = false;
                $description = 'increased construction speed';
                break;
            default:
                return '';
        }

        if ($perkType->pivot->value < 0) {
            if ($negativeBenefit) {
                return "<span class=\"text-green\">Decreased {$description}</span>";
            } else {
                return "<span class=\"text-red\">Decreased {$description}</span>";
            }
        } else {
            if ($negativeBenefit) {
                return "<span class=\"text-red\">Increased {$description}</span>";
            } else {
                return "<span class=\"text-green\">Increased {$description}</span>";
            }
        }
    }

    public function getPerkDescriptionHtmlWithValue(RacePerkType $perkType): array
    {
        $valueType = '%';
        $booleanValue = false;
        switch($perkType->key) {
            case 'archmage_cost':
                $negativeBenefit = true;
                $description = 'Archmage cost';
                $valueType = 'p';
                break;
            case 'construction_cost':
                $negativeBenefit = true;
                $description = 'Construction cost';
                break;
            case 'defense':
                $negativeBenefit = false;
                $description = 'Defensive power';
                break;
            case 'extra_barren_max_population':
                $negativeBenefit = false;
                $description = 'Population from barren land';
                $valueType = '';
                break;
            case 'food_consumption':
                $negativeBenefit = true;
                $description = 'Food consumption';
                break;
            case 'food_production':
                $negativeBenefit = false;
                $description = 'Food production';
                break;
            case 'gem_production':
                $negativeBenefit = false;
                $description = 'Gem production';
                break;
            case 'immortal_wizards':
                $negativeBenefit = false;
                $description = 'Immortal wizards';
                $booleanValue = true;
                break;
            case 'invest_bonus':
                $negativeBenefit = false;
                $description = 'Castle bonuses';
                break;
            case 'lumber_production':
                $negativeBenefit = false;
                $description = 'Lumber production';
                break;
            case 'mana_production':
                $negativeBenefit = false;
                $description = 'Mana production';
                break;
            case 'max_population':
                $negativeBenefit = false;
                $description = 'Max population';
                break;
            case 'offense':
                $negativeBenefit = false;
                $description = 'Offensive power';
                break;
            case 'ore_production':
                $negativeBenefit = false;
                $description = 'Ore production';
                break;
            case 'platinum_production':
                $negativeBenefit = false;
                $description = 'Platinum production';
                break;
            case 'spy_strength':
                $negativeBenefit = false;
                $description = 'Spy strength';
                break;
            case 'wizard_strength':
                $negativeBenefit = false;
                $description = 'Wizard strength';
                break;
            case 'cannot_construct':
                $negativeBenefit = true;
                $description = 'Cannot construct buildings';
                $booleanValue = true;
                break;
            case 'boat_capacity':
                $negativeBenefit = false;
                $description = 'Increased boat capacity';
                $valueType = ' units/boat';
                break;
            case 'platinum_production':
                $negativeBenefit = false;
                $description = 'Platinum production';
                $booleanValue = false;
                break;
            case 'can_invest_mana':
                $negativeBenefit = false;
                $description = 'Can invest mana in castle';
                $booleanValue = true;
                break;
            case 'population_growth':
                $negativeBenefit = false;
                $description = 'Population growth rate';
                break;
          case 'cannot_improve_castle':
                $negativeBenefit = true;
                $description = 'Cannot use castle improvements';
                $booleanValue = true;
                break;
          case 'cannot_explore':
                $negativeBenefit = true;
                $description = 'Cannot explore';
                $booleanValue = true;
                break;
          case 'cannot_invade':
                $negativeBenefit = true;
                $description = 'Cannot invade';
                $booleanValue = true;
                break;
          case 'cannot_train_spies':
                $negativeBenefit = true;
                $description = 'Cannot train spies';
                $booleanValue = true;
                break;
          case 'cannot_train_wizards':
                $negativeBenefit = true;
                $description = 'Cannot train wizards';
                $booleanValue = true;
                break;
          case 'cannot_train_archmages':
                $negativeBenefit = true;
                $description = 'Cannot train Arch Mages';
                $booleanValue = true;
                break;
          case 'explore_cost':
                $negativeBenefit = true;
                $description = 'Cost of exploration';
                break;
            case 'reduce_conversions':
                $negativeBenefit = false;
                $description = 'Reduced conversions';
                break;
            case 'exchange_bonus':
                $negativeBenefit = false;
                $description = 'Better exchange rates';
                break;
            case 'guard_tax_exemption':
                $negativeBenefit = false;
                $description = 'Exempt from guard platinum tax';
                $booleanValue = true;
                break;
          case 'tissue_improvement':
                $negativeBenefit = false;
                $description = 'Can improve tissue (only)';
                $booleanValue = true;
                break;
          case 'does_not_kill':
                $negativeBenefit = false;
                $description = 'Does not kill units.';
                $booleanValue = true;
                break;
          case 'gryphon_nests_generates_wild_yetis':
                $negativeBenefit = false;
                $description = 'Traps wild yetis';
                $booleanValue = true;
                break;
            case 'prestige_gains':
                $negativeBenefit = false;
                $description = 'Prestige gains';
                break;
            case 'draftee_dp':
                $negativeBenefit = true;
                $description = 'DP per draftee';
                $valueType = '';
                break;
            case 'increased_construction_speed':
                $negativeBenefit = false;
                $description = 'Increased construction speed';
                $valueType = ' hours';
                break;
            default:
                return null;
        }

        $result = ['description' => $description, 'value' => ''];
        $valueString = "{$perkType->pivot->value}{$valueType}";

        if ($perkType->pivot->value < 0) {

            if($booleanValue) {
                $valueString = 'No';
            }

            if ($negativeBenefit) {
                $result['value'] = "<span class=\"text-green\">{$valueString}</span>";
            } else {
                $result['value'] = "<span class=\"text-red\">{$valueString}</span>";
            }
        } else {
            $prefix = '+';
            if($booleanValue) {
                $valueString = 'Yes';
                $prefix = '';
            }

            if ($negativeBenefit) {
                $result['value'] = "<span class=\"text-red\">{$prefix}{$valueString}</span>";
            } else {
                $result['value'] = "<span class=\"text-green\">{$prefix}{$valueString}</span>";
            }
        }

        return $result;
    }

}
