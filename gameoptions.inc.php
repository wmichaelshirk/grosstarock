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

$no2player = totranslate(' This option is not applicable to two-player.');
$game_options = [

    100 => [
        'name' => totranslate( 'Game length' ),
        'values' => [
            1 => ['name' => totranslate('Very short - 1 round')],
            2 => ['name' => totranslate('Short - 2 rounds')],
            3 => ['name' => totranslate('Medium - 3 rounds')],
            4 => ['name' => totranslate('Long - 4 rounds')],
            5 => ['name' => totranslate('Very long - 5 rounds')]
        ],
    ],
    101 => [
        'name' => totranslate('Play with Pots'),
        'values' => [
            1 => [
                'name' => totranslate('Yes'),
                'description' => totranslate('Increased base value of King and Pagat Ultimos, and Pot bonus for wins and losses.') . $no2player,
            ],
            0 => [
                'name' => totranslate('No'),
                'description' => totranslate('Lower base value for King and Pagat Ultimos. Card points become more important.')
            ],
        ],
        'startcondition' => [
            1 => [
                [
                    'type' => 'minplayers',
                    'value' => 3,
                    'message' => totranslate('Pots require at least 3 players')
                ]
            ],
            0 => []
        ]
    ],
    102 => [
        'name' => totranslate('Score card points'),
        'values' => [
            1 => [
                'name' => totranslate('Yes'),
                'description' => totranslate('Except in the case of Misère, card points are counted and scored at the end of each hand.')
            ],
            0 => [
                'name' => totranslate('No'),
                'description' => totranslate('Card points are not scored, placing increased emphasis on winning the last trick.')
            ]
        ],
        'displaycondition' => [
            [
                'type' => 'otheroption',
                'id' => 101,
                'value' => [1]
            ]
        ],
    ],
    103 => [
        'name' => totranslate('Winner is paid when an Ultimo fails'),
        'values' => [
            0 => [
                'name' => totranslate('No'),
                'description' => totranslate('If an Ultimo fails, the winner of the last trick is paid only for stopping the Ultimo.')
            ],
            1 => [
                'name' => totranslate('Yes'),
                'description' => totranslate('If an Ultimo fails, the failing player not only pays the other two, but the winner of the last trick is seperately paid by the other two, increasing his gains and the failed Ultimo player’s losses.')
            ]
        ],
        'displaycondition' => [
            [
                'type' => 'otheroption',
                'id' => 101,
                'value' => [1]
            ]
        ],
    ]


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
            4 =>['name' => totranslate('French deck: "Italian"')]
        ]
    ],
];
