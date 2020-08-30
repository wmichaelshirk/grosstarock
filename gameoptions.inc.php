<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & 
 * Emmanuel Colin <ecolin@boardgamearena.com>
 * GrossTarock implementation : © W Michael Shirk <wmichaelshirk@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on 
 * http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * gameoptions.inc.php
 *
 * GrossTarock game options description
 * 
 * In this file, you can define your game options (= game variants).
 *   
 * Note: If your game has no variants, you don't have to modify this file.
 *
 * Note²: All options defined in this file should have a corresponding "game 
 * state labels" with the same ID (see "initGameStateLabels" in 
 * grosstarock.game.php)
 *
 * !! It is not a good idea to modify this file when a game is running !!
 *
 */

$game_options = [

    100 => [
        'name' => totranslate( 'Game length' ),
        'values' => [
            3 => [ 'name' => totranslate( 'Very short - 3 hands' ) ],
            6 => [ 'name' => totranslate( 'Short - 6 hands' ) ],
            9 => [ 'name' => totranslate( 'Medium - 9 hands' ) ],
            12 => [ 'name' => totranslate( 'Long - 12 hands' ) ],
            15 => [ 'name' => totranslate( 'Very long - 15 hands' ) ]
        ],
        'displaycondition' => [
            [ 'type' => 'otheroptionisnot', 'id' => 201, 'value' => 1 ]
        ],
    ],
    101 => [
        'name' => totranslate('Play with Pots'),
        'values' => [
            1 => [
                'name' => totranslate('Yes'),
                'description' => totranslate('Increased base value of King and Pagat Ultimos, and Pot bonus for wins and losses.'),
                'nobeginner' => true,
            ],
            0 => [
                'name' => totranslate('No'),
                'description' => totranslate('Lower base value for King and Pagat Ultimos. Card points become more important.')
            ],
        ],
    ],
   
    // Options:
    // Presets: "Danish" scuse=>danish; cards=>french; null=> true; ultimo => true; pots => true;
    //          "French": scus=>strict; cards=>italian; null => false; utlimo => true; pots => false;
    //          "Scarto/Troccas": scus=>classic; cards=>italian; null=> false; ultimo => false; pots => false;
    //          "Mitigati" (6.8 / 8.37)
    //          "Custom" -> Enables below
    // Scuse: "Strict" (can't play until won a trick; can't lead)
    //      "classic" i.e., french
    //      "danish" - cannot win the last trick; wierdness in antipenultimate and preanti.
    // Cards: "french"
    //       "Italian
    // null: bool
    // ultimos: bool  (5/10?); "(5/10; 5/15)"; (5/10; 10/20); Last trick : 5pts (danish); (Last trick 25/30; no pots)
    // Or: Values: 
    // Pagat Ultimo: 10, "15", 45
    // King Ultimo: 10, "10", 40
    // Last Trick: 5
    // pots: bool
];


$game_preferences = [
    100 => [
        'name' => totranslate('Card style'),
        'needReload' => false,
        'values' => [
            1 =>['name' => totranslate('French deck: Modern')],
            2 =>['name' => totranslate('French deck: Grimaud')],
            3 =>['name' => totranslate('Belgian deck: Animals')],
            4 =>['name' => totranslate('French deck: "Italian"(CBD)')]
        ]
    ],

];

