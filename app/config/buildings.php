<?php

# app/config/game.php

return [

    'single_value_perks' => [
        'gold_production_raw',
        'food_production_raw',
        'ore_production_raw',
        'lumber_production_raw',
        'mana_production_raw',
        'gems_production_raw',
        'blood_production_raw',
        'soul_production_raw',
        'pearls_production_raw',
        'horse_production_raw',
        'mud_production_raw',
        'swamp_gas_production_raw',
        'marshling_production_raw',
        'thunderstone_production_raw',
        'miasma_production_raw',
        'yak_production_raw',
        'kelp_production_raw',
        'gunpowder_production_raw',
        'magma_production_raw',
        'obsidian_production_raw',
    
        'gunpowder_storage_raw',
        'sapling_storage_raw',
    
        'gold_upkeep_raw',
        'food_upkeep_raw',
        'ore_upkeep_raw',
        'lumber_upkeep_raw',
        'mana_upkeep_raw',
        'blood_upkeep_raw',
        'soul_upkeep_raw',
        'pearls_upkeep_raw',
        'prisoner_upkeep_raw',
        'mana_upkeep_raw_per_artefact',
    
        'gold_theft_protection',
        'food_theft_protection',
        'ore_theft_protection',
        'lumber_theft_protection',
        'mana_theft_protection',
        'gems_theft_protection',
        'blood_theft_protection',
        'soul_theft_protection',
        'pearls_theft_protection',
    
        'xp_generation_raw',
    
        // Building-specific housing
        'artillery_unit1_housing',
        'afflicted_unit1_housing',
        'aurei_unit1_housing',
        'dwarg_unit1_housing',
        'cires_unit1_housing',
        'cires_unit2_housing',
        'norse_unit1_housing',
        'sacred_order_unit2_housing',
        'sacred_order_unit3_housing',
        'sacred_order_unit4_housing',
        'snow_elf_unit1_housing',
        'troll_unit2_housing',
        'troll_unit4_housing',
        'vampires_unit1_housing',
        'revenants_unit1_housing',
        'revenants_unit2_housing',
        'revenants_unit3_housing',
    
        'spy_housing',
        'wizard_housing',
        'military_housing',
        'draftee_housing',
    
        'ammunition_units_housing',
    
        // Military
        'raw_defense',
        'dimensionalists_unit1_production_raw',
        'dimensionalists_unit2_production_raw',
        'dimensionalists_unit3_production_raw',
        'dimensionalists_unit4_production_raw',
    
        'snow_elf_unit4_production_raw',
    
        'unit_send_capacity',
    
        // Uncategorised
        'crypt_bodies_decay_protection',
        'faster_returning_units',
    ],


    'ratio_multiplier_max_perks' => [
        // OP/DP mods
        'defensive_power',
        'offensive_power',
        'attacker_offensive_power_mod',
        'target_defensive_power_mod',
        'casualties',
        'casualties_on_offense',
        'casualties_on_defense',
        'increases_enemy_casualties',
        'increases_enemy_casualties_on_offense',
        'increases_enemy_casualties_on_defense',
        'morale_gains',
        'prestige_gains',
        'base_morale',
        'faster_return',
    
        // Production and Resources mods
        'gold_production_mod',
        'food_production_mod',
        'lumber_production_mod',
        'ore_production_mod',
        'gems_production_mod',
        'mana_production_mod',
        'xp_generation_mod',
        'pearls_production_mod',
        'blood_production_mod',
        'mud_production_mod',
        'swamp_gas_production_mod',
        'miasma_production_mod',
        'exchange_rate',

        'blood_resource_conversion_mod',
    
        // Unit costs
        'unit_gold_costs',
        'unit_ore_costs',
        'unit_lumber_costs',
        'unit_mana_costs',
        'unit_food_costs',
        'unit_blood_costs',

        'machine_unit_costs',
    
        // Unit training
        'extra_units_trained',
        'drafting',
        'snow_elf_unit4_production_mod',
        'training_time_mod',
        'spy_training_time_mod',
        'wizards_training_time_mod',
    
        'dimensionalists_unit1_production_mod',
        'dimensionalists_unit2_production_mod',
        'dimensionalists_unit3_production_mod',
        'dimensionalists_unit4_production_mod',
    
        // Spy/wizard
        'spell_cost',
        'spy_losses',
        'spy_strength_recovery',
        'wizard_losses',
        'wizard_strength',
        'wizard_strength_recovery',
        'wizard_cost',
        'sorcery_damage_suffered',
        'spy_cost',
        'spell_duration_mod',
    
        // Construction/Rezoning and Land
        'construction_cost',
        'rezone_cost',
        'land_discovered',
        'construction_time',
    
        // Espionage
        'gold_theft_reduction',
        'gems_theft_reduction',
        'lumber_theft_reduction',
        'ore_theft_reduction',
        'food_theft_reduction',
        'horse_theft_reduction',
        'magma_theft_reduction',
        'obsidian_theft_reduction',

        'amount_stolen',
    
        // Improvements
        'improvements_capped',
        'improvements_interest',
        'invest_bonus',
        'gold_invest_bonus',
        'food_invest_bonus',
        'ore_invest_bonus',
        'gems_invest_bonus',
        'lumber_invest_bonus',
        'mana_invest_bonus',
        'blood_invest_bonus',
        'soul_invest_bonus',
        'obsidian_invest_bonus',
        'miasma_invest_bonus',
    
        // Other/special
        'deity_power',
        'population_capped',
        'population_growth_capped',
        'unit_pairing',
        'unit_pairing_capped'
    ],


    'ratio_multiplier_uncapped_perks' => [
        'improvements',
        'damage_from_lightning_bolt',
        'damage_from_fireball',
        'population_growth',
        'reduces_conversions',
        'reduces_attrition',
        'unit_pairing',
        'wizard_strength',
        'spy_strength',
        'wizard_strength_on_defense',
        'spy_strength_on_defense',
        'wizard_strength_on_offense',
        'spy_strength_on_offense',
        'population_uncapped'
    ],
];