<?php
 /**
  *------
  * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel
  * Colin <ecolin@boardgamearena.com>
  * GrossTarock implementation : © W Michael Shirk <wmichaelshirk@gmail.com>
  *
  * This code has been produced on the BGA studio platform for use on
  * http://boardgamearena.com.
  * See http://en.boardgamearena.com/#!doc/Studio for more information.
  * -----
  */


require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );


class GrossTarock extends Table {

	function __construct( ) {
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with
        // getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();

        self::initGameStateLabels([

            "hands_played" => 10,
            "dealer_id" => 11,
            "eldest_player_id" => 12,
            "players_declared" => 13,
            "suit_led" => 14,
            "tricks_played" => 15,
            "scuse_required" => 16,
            "scuse_played" => 17,

            // Pots values
            "pagat_pot" => 18,
            "king_pot" => 19,
            "score_pots" => 20,

            "trumps_discarded" => 21,

            // Options:
            "hands_to_play" => 100,
            "play_with_pots" => 101,
            "score_card_points" => 102,
            "expensive_fail" => 103,

            // "no_pots"
            // "french" (after Gébelin)
            // ladons, leapingScus?
            // Mitigati?

        ] );
        $this->cards = self::getNew( "module.common.deck" );
        $this->cards->init( "card" );

	}

    protected function getGameName( ) {
		// Used for translations and stuff. Please do not modify.
        return "grosstarock";
    }

    /*
        setupNewGame:

        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so
        that the game is ready to be played.
    */
    protected function setupNewGame( $players, $options = array() ) {
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum
        // number of players allowed for the gams
        $gameinfos = self::getGameinfos();
        $default_colors = $gameinfos['player_colors'];

        // Create players
        // Note: if you added some extra field on "player" table in the database
        // (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        foreach( $players as $player_id => $player )
        {
            $color = array_shift( $default_colors );
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."')";
        }
        $sql .= implode( $values, ',' );
        self::DbQuery( $sql );
        self::reattributeColorsBasedOnPreferences( $players, $gameinfos['player_colors'] );
        self::reloadPlayersBasicInfos();

        /************ Start the game initialization *****/

        // Init global values with their initial values

        // Set current suit led to zero (= no suit)
        self::setGameStateInitialValue( 'suit_led', 0 );

        // Create cards
        $cards = array ();
        foreach ( $this->suits as $suit_id => $suit ) {
            // spade, heart, diamond, club, trump
            for ($value = 1; $value <= ($suit_id == 5 ? 22 : 14); $value ++) {
                //  2, 3, 4, ... R; ..., 21, Excuse
                $cards [] = array ('type' => $suit_id,'type_arg' => $value,'nbr' => 1 );
            }
        }

        $this->cards->createCards( $cards, 'deck' );

        // Compute the card values (counted in half points) in DB
        self::DbQuery("UPDATE card SET card_points = CASE card_type
            WHEN 5 THEN
                CASE card_type_arg
                    WHEN 1 THEN 4
                    WHEN 21 THEN 4
                    WHEN 22 THEN 4
                END
            ELSE
                CASE card_type_arg
                    WHEN 14 THEN 4
                    WHEN 13 THEN 3
                    WHEN 12 THEN 2
                    WHEN 11 THEN 1
                END
            END");

        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        //self::initStat( 'table', 'table_teststat1', 0 );    // Init a table statistics
        //self::initStat( 'player', 'player_teststat1', 0 );  // Init a player statistics (for all players)

        // TODO: setup the initial game situation here

        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();

        self::setGameStateInitialValue('scuse_required', 0);
        self::setGameStateInitialValue('scuse_played', 0);

        // Empty the pots
        self::setGameStateInitialValue('pagat_pot', 0);
        self::setGameStateInitialValue('king_pot', 0);
        self::setGameStateInitialValue('score_pots',
            self::getGameStateValue('play_with_pots') &&
            count($players) === 3
        );


        // Get the first dealer and the first player
        $dealer_id = self::getActivePlayerId();
        $eldest_player_id = self::getPlayerAfter( $dealer_id );
        self::setGameStateInitialValue('dealer_id', $dealer_id);
        self::setGameStateInitialValue('eldest_player_id', $eldest_player_id);
        self::setGameStateInitialValue('players_declared', 0);
        self::setGameStateInitialValue('hands_played', 0);
        self::setGameStateInitialValue('trumps_discarded', 0);

       // Init game statistics
		self::initStat('table', 'pagat_ultimo', 0);
		self::initStat('table', 'king_ultimo', 0);
		self::initStat('table', 'failed_ultimo', 0);
		self::initStat('table', 'nill', 0);
		self::initStat('table', 'slam', 0);
		self::initStat('table', 'avg_declarations', 0);
		self::initStat('player', 'ultimos_tried', 0);
        self::initStat('player', 'won_ultimos', 0);
        self::initStat('player', 'won_last_tricks', 0);
		self::initStat('player', 'avg_declarations', 0);
        self::initStat('player', 'avg_card_points', 0);

        /************ End of the game initialization *****/
    }

    /*
        getAllDatas:

        Gather all informations about current game situation (visible by the current player).

        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas() {
        $result = array();

        $result['bonuses'] = $this->bonuses;
        $result['figures'] = $this->figures;
        $result['suits'] = $this->suits;
        $result['trull'] = $this->trull;

        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!

        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score, player_trick_number tricks FROM player ";
        $result['players'] = self::getCollectionFromDb( $sql );

        $result['hands_to_play'] = self::getGameStateValue( 'hands_to_play' );
        $result['current_hand'] = self::getGameStateValue( 'hands_played' ) + 1;

        // Cards in player hand
        $result['hand'] = $this->cards->getCardsInLocation( 'hand', $current_player_id );

        // Cards played on the table
        $result['cardsontable'] = $this->cards->getCardsInLocation( 'cardsontable' );

        // Pot values
        $result['pagatPot'] = self::getGameStateValue('pagat_pot');
        $result['kingPot'] = self::getGameStateValue('king_pot');

        $result['score_with_pots'] = self::getGameStateValue('score_pots');
        $result['dealer'] = self::getGameStateValue('dealer_id');
        return $result;
    }

    /*
        getGameProgression:

        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started)
        and 100 (= the game is finished or almost finished).

        This method is called each time we are in a game state with the
        "updateGameProgression" property set to true (see states.inc.php)
    */
    function getGameProgression() {

        $players_number = self::getPlayersNumber();

        if ($players_number == 2) {
            $newScores = self::getCollectionFromDb("SELECT player_score FROM player", true);
            $total = ceil(self::getGameStateValue( 'hands_to_play' ) * 6.66);
            $progress = max(array_keys($newScores));
            return (int) ( ( 100 * $progress ) / $total );
        }

        // Three player:
        $handsPlayed = self::getGameStateValue( 'hands_played' );
        $handsToPlay = self::getGameStateValue( 'hands_to_play' );
        $tricksPlayed = self::getGameStateValue( 'tricks_played' );

        $progress = ($handsPlayed * 25) + $tricksPlayed;
        $total = $handsToPlay * 25;

        return (int) ( ( 100 * $progress ) / $total );
    }



//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////

    /*
        In this space, you can put any utility methods useful for your game logic
    */

    /**
     * @returns { player_id => 0-2 } with 0 being 'South' on the table.
     */
    function getPlayerRelativePositions() {
        $result = array();

        $players = self::loadPlayersBasicInfos();
        $nextPlayer = self::createNextPlayerTable(array_keys($players));

        $current_player = self::getCurrentPlayerId();

        if(!isset($nextPlayer[$current_player])) {
            // Spectator mode: take any player for south
            $player_id = $nextPlayer[0];
        } else {
            // Normal mode: current player is on south
            $player_id = $current_player;
        }
        $result[$player_id] = 0;

        for ($i = 1; $i < count($players); $i++) {
            $player_id = $nextPlayer[$player_id];
            $result[$player_id] = $i;
        }
        return $result;
    }

    function getPlayerName($player_id) {
        $players = self::loadPlayersBasicInfos();
        return $players[$player_id]['player_name'];
    }

    // Deal 25 cards to each player - and 28 to the dealer.
    private function doDeal () {
        $dealer_id = self::getGameStateValue( 'dealer_id' );

        // deal a ridonculous hand for testing:
        // $trumps = $this->cards->getCardsOfType( 5 );
        // $pagat = array_shift($trumps);
        // $trumps = array_slice($trumps, 2);
        // // give player one all the trumps, and all the kings.
        // $kh = $this->cards->getCardsOfType(1,14);
        // // $ks = $this->cards->getCardsOfType(2,14);
        // $kd = $this->cards->getCardsOfType(3,14);
        // $kc = $this->cards->getCardsOfType(4,14);
        // $qc = $this->cards->getCardsOfType(4,13);
        // $cc = $this->cards->getCardsOfType(4,12);
        // $monster = array_map(function($c) { return $c['id']; },
        //     array_merge([$pagat], $trumps, $kh, $kd, $kc, $qc, $cc));

        // $players = self::loadPlayersBasicInfos();
        // $notDealer = 0;
        // foreach ($players as $player_id => $player ) {
        //     self::notifyAllPlayers("log", "$player_id", []);
        //     if ($player_id != $dealer_id) {
        //         $nonDealer = $player_id;
        //         break;
        //     }
        // }
        // $notDealerIds = array_filter(array_keys($players), function($id) use ($dealer_id) {
        //     return $id != $dealer_id;
        // });
        // $notDealer = array_shift($notDealerIds);
        // self::notifyAllPlayers("log", clienttranslate(' ${notDealer} Dealer:${dealer_id}, playerIds:${playerIds} '), [
        //     'notDealer' => $notDealer,
        //     'dealer_id' => $dealer_id,
        //     'playerIds' => $notDealer
        // ]);

        // $this->cards->moveAllCardsInLocation( null, 'deck' );
        // $this->cards->moveCards( $monster, 'hand', $notDealer);
        // $this->cards->shuffle( 'deck' );

        // foreach( $players as $player_id => $player ) {
        //     if ($player_id == $notDealer) {
        //         $this->cards->pickCards(1, 'deck', $player_id );
        //     } else {
        //         $this->cards->pickCards(25, 'deck', $player_id );
        //     }
        // }

        $players = self::loadPlayersBasicInfos();
        $this->cards->moveAllCardsInLocation( null, 'deck' );
        $this->cards->shuffle( 'deck' );
        foreach( $players as $player_id => $player ) {
            $num_cards = $player_id == $dealer_id ? 28 : 25;
            $this->cards->pickCards(
                $num_cards, 'deck', $player_id
            );
        }

        foreach ($players as $player_id => $player) {
            $cards = $this->cards->getCardsInLocation( 'hand', $player_id );
            $this->notifyPlayer( $player_id, 'newHand', '',
                [ 'cards' => $cards ] );
        }
    }

    // Found the pots - if they're empty, and the option is selected.
    private function foundPots () {
        if (self::getGameStateValue('score_pots')) {
            $contribution = 20;

            $players = self::loadPlayersBasicInfos();
            $pagat_pot_value = self::getGameStateValue('pagat_pot');
            $king_pot_value = self::getGameStateValue('king_pot');
            foreach ($players as $player_id => $unused ) {
                if ($pagat_pot_value == 0) {
                    $sql = "UPDATE player SET player_score = player_score - $contribution WHERE player_id='$player_id'";
                    self::DbQuery($sql);
                    self::incGameStateValue( 'pagat_pot', $contribution );
                    $this->notifyPlayer($player_id, 'log', clienttranslate('You pay <b>${contribution}</b> to the empty Pagat Pot'), [ 'contribution' => $contribution ]);
                }
                if ($king_pot_value == 0) {
                    $sql = "UPDATE player SET player_score = player_score - $contribution WHERE player_id='$player_id'";
                    self::DbQuery($sql);
                    self::incGameStateValue( 'king_pot', $contribution );
                    $this->notifyPlayer($player_id, 'log', clienttranslate('You pay <b>${contribution}</b> to the empty King Pot'), [ 'contribution' => $contribution ]);
                }
            }

            // record payments into scoresheet.
            $paid = 0;
            if ($pagat_pot_value == 0) {
                $paid -= $contribution;
            }
            if ($king_pot_value == 0) {
               $paid -= $contribution;
            }
            if ($paid != 0) {
                $bonus = $this->bonuses['foundation']['name'];
                $dealer = self::getGameStateValue('dealer_id');
                $values = implode(["'$bonus'", $dealer, 0, $paid], ',');
                self::DbQuery( "INSERT INTO bonuses (bonus_name, bonus_player, bonus_value, bonus_pot_value) VALUES ($values)");
            }

            $newScores = self::getCollectionFromDb("SELECT player_id, player_score FROM player", true);
            self::notifyAllPlayers("newScores", '', [
                'newScores' => $newScores,
                'pagatPot' => self::getGameStateValue('pagat_pot'),
                'kingPot' => self::getGameStateValue('king_pot')
            ]);
        }
    }

    private function dealerPay () {
        if (self::getGameStateValue('score_pots')) {
            $contribution = 5;
            $contributions = $contribution * 2;

            $dealer = self::getGameStateValue('dealer_id');
            $sql = "UPDATE player SET player_score = player_score - $contributions WHERE player_id='$dealer'";
            self::DbQuery($sql);

            $bonus = $this->bonuses['dealing']['name'];
            $dealer = self::getGameStateValue('dealer_id');
            $values = implode(["'$bonus'", $dealer, 0, -$contributions], ',');
            self::DbQuery( "INSERT INTO bonuses (bonus_name, bonus_player, bonus_value, bonus_pot_value) VALUES ($values)");

            $this->notifyPlayer($dealer, 'log', clienttranslate('You pay <b>${contribution}</b> to each pot as Dealer'), [ 'contribution' => $contribution ]);

            self::incGameStateValue( 'pagat_pot', $contribution );
            self::incGameStateValue( 'king_pot', $contribution );

            $newScores = self::getCollectionFromDb("SELECT player_id, player_score FROM player", true);
            self::notifyAllPlayers("newScores", '', [
                'newScores' => $newScores,
                'pagatPot' => self::getGameStateValue('pagat_pot'),
                'kingPot' => self::getGameStateValue('king_pot')
            ]);
        }
    }

    private function emptyPots () {
        if (self::getGameStateValue('score_pots')) {
            $players = self::loadPlayersBasicInfos();

            self::notifyAllPlayers('endGamePotPayments',
                clienttranslate('The Pagat Pot contained ${pagatVal}, and the King Pot contained ${kingVal}'), [
                    'pagatVal' => self::getGameStateValue('pagat_pot'),
                    'kingVal' => self::getGameStateValue('king_pot')
                ]);

            $pagatVal = floor(self::getGameStateValue('pagat_pot') / 15);
            $kingVal = floor(self::getGameStateValue('king_pot') / 15);
            self::incGameStateValue('pagat_pot', -($pagatVal * 15));
            self::incGameStateValue('king_pot', -($kingVal * 15));
            $pagatVal *= 5;
            $kingVal *= 5;

            $total = $pagatVal + $kingVal;
            self::notifyAllPlayers('endGamePotPayments',
                clienttranslate('Everyone receives <b>${pagatVal}</b> from the Pagat Pot, and <b>${kingVal}</b> from the King Pot: ${total}'), [
                    'pagatVal' => $pagatVal,
                    'kingVal' => $kingVal,
                    'total' => $total
                ]);

            foreach ($players as $player_id => $player) {
                $sql = "UPDATE player SET player_score = player_score + $total WHERE player_id='$player_id'";
                self::DbQuery($sql);
            }

            $newScores = self::getCollectionFromDb("SELECT player_id, player_score FROM player", true);
            self::notifyAllPlayers("newScores", '', [
                'newScores' => $newScores,
                'pagatPot' => self::getGameStateValue('pagat_pot'),
                'kingPot' => self::getGameStateValue('king_pot')
            ]);
        }
    }

    private function payDeclaration ($player, $bonus, $value, $arg, $arg2=NULL) {
        $players = self::loadPlayersBasicInfos();
        $values = implode(["'$bonus'", $player, $value, $arg ?? 'NULL', $arg2 ?? 'NULL'], ',');
        self::DbQuery( "INSERT INTO bonuses (bonus_name, bonus_player, bonus_value, bonus_arg, bonus_arg2) VALUES ($values)");

        if (self::getPlayersNumber() == 3) {
            foreach ($players as $player_id => $unused) {
                if ($player_id == $player) {
                    $adjustment = $value * 2;
                } else {
                    $adjustment = -$value;
                }
                $sql = "UPDATE player SET player_score = player_score + $adjustment WHERE player_id='$player_id'";
                self::DbQuery($sql);
            }
        }
    }

    private function payTarotLost ($player, $card) {
        $players = self::loadPlayersBasicInfos();
        $lostTarotValue = 5;

        $potValue = 0;
        if (self::getGameStateValue('score_pots')) {
            $potValue = $lostTarotValue;
        }
        if ($card == "pagat") {
            $bonusName = $this->bonuses['lostpagat']['name'];
            $potName = 'pagat_pot';
        } else {
            $bonusName = $this->bonuses['lostking']['name'];
            $potName = 'king_pot';
        }
        $values = implode(["'$bonusName'", $player, -$lostTarotValue, -$potValue], ',');
        self::DbQuery( "INSERT INTO bonuses (bonus_name, bonus_player, bonus_value, bonus_pot_value) VALUES ($values) " );


        if (self::getPlayersNumber() == 3) {
            // playment happens regardless; pots are the extra.
            $payoutMultiplier = 2;
            if (self::getGameStateValue('score_pots')) {
                self::incGameStateValue($potName, $lostTarotValue);
                $payoutMultiplier = 3;
                $this->notifyPlayer($player, 'log', clienttranslate('You pay <b>${value}</b> to the pot'), [ 'value' => $lostTarotValue ]);
            }

            foreach ($players as $player_id => $unused) {
                $adjustment = 0;
                if ($player_id == $player) {
                    $adjustment = $lostTarotValue * -$payoutMultiplier;
                } else {
                    $adjustment = $lostTarotValue;
                }
                $sql = "UPDATE player SET player_score = player_score + $adjustment WHERE player_id='$player_id'";
                self::DbQuery($sql);
            }

            $newScores = self::getCollectionFromDb("SELECT player_id, player_score FROM player", true);
            self::notifyAllPlayers("newScores", '', [
                'newScores' => $newScores,
                'pagatPot' => self::getGameStateValue('pagat_pot'),
                'kingPot' => self::getGameStateValue('king_pot')
            ]);
        }
        return $lostTarotValue;
    }


    private function payTarotWon ($player, $card) {
        $players = self::loadPlayersBasicInfos();
        $wonTarotValue = 5;

        if ($card == "pagat") {
            $bonusName = $this->bonuses['wonpagat']['name'];
        }
        $values = implode(["'$bonusName'", $player, $wonTarotValue], ',');
        self::DbQuery( "INSERT INTO bonuses (bonus_name, bonus_player, bonus_value) VALUES ($values) " );

        if (self::getPlayersNumber() == 3) {
            // playment happens regardless; pots are the extra.
            $payoutMultiplier = 2;

            foreach ($players as $player_id => $unused) {
                $adjustment = -$wonTarotValue;
                if ($player_id == $player) {
                    $adjustment = $wonTarotValue * $payoutMultiplier;
                }
                $sql = "UPDATE player SET player_score = player_score + $adjustment WHERE player_id='$player_id'";
                self::DbQuery($sql);
            }

            $newScores = self::getCollectionFromDb("SELECT player_id, player_score FROM player", true);
            self::notifyAllPlayers("newScores", '', [
                'newScores' => $newScores,
                'pagatPot' => self::getGameStateValue('pagat_pot'),
                'kingPot' => self::getGameStateValue('king_pot')
            ]);
        }
        return $wonTarotValue;
    }

    private function payLastTrick ($player, $outcome, $kingPotValue) {
        $players = self::loadPlayersBasicInfos();

        $highPagatValue = 45;
        $lowPagatValue = 15;

        $highKingValue = 40;
        $lowKingValue = 10;

        $highLastValue = 20;
        $lowLastValue = 5;

        $valuePaid = 0;


        // slam
        if ($outcome == "slam") {
            $valuePaid = $lowKingValue + $lowPagatValue;
            $potBonus = 0;
            if (self::getGameStateValue('score_pots')) {
                $valuePaid = $highKingValue + $highPagatValue;
                $potBonus = self::getGameStateValue('pagat_pot') + $kingPotValue;
                self::setGameStateValue('pagat_pot', 0);
                self::setGameStateValue('king_pot', 0);
                $this->notifyPlayer($player, 'log', clienttranslate('You receive <b>${potBonus}</b> from the pots!'), [ 'potBonus' => $potBonus ]);
            }
            if (self::getPlayersNumber() == 3) {
                foreach ($players as $player_id => $unused) {
                    $adjustment = 0;
                    if ($player_id == $player) {
                        $adjustment = ($valuePaid * 2) + $potBonus;
                    } else {
                        $adjustment = -$valuePaid;
                    }
                    $sql = "UPDATE player SET player_score = player_score + $adjustment WHERE player_id='$player_id'";
                    self::DbQuery($sql);
                }
            }
            $bonusName = $this->bonuses['slam']['name'];
            $values = implode(["'$bonusName'", $player, $valuePaid, $potBonus], ',');
            self::DbQuery( "INSERT INTO bonuses (bonus_name, bonus_player, bonus_value, bonus_pot_value) VALUES ($values) " );

            //stats
            self::incStat(1, 'slam');
        }
        // null
        if (self::getPlayersNumber() == 3) {
            if ($outcome == "null") {
                $valuePaid = 25;
                foreach ($players as $player_id => $unused) {
                    $adjustment = 0;
                    if ($player_id == $player) {
                        $adjustment = $valuePaid * 2;
                    } else {
                        $adjustment = -$valuePaid;
                    }
                    $sql = "UPDATE player SET player_score = player_score + $adjustment WHERE player_id='$player_id'";
                    self::DbQuery($sql);
                }
                $bonusName = $this->bonuses['nill']['name'];
                $values = implode(["'$bonusName'", $player, $valuePaid], ',');
                self::DbQuery( "INSERT INTO bonuses (bonus_name, bonus_player, bonus_value) VALUES ($values) " );

                //stats
                self::incStat(1, 'nill');
            }
        }
        // pagat_won
        if ($outcome == "pagat_won") {
            $valuePaid = $lowPagatValue;
            $potBonus = 0;
            if (self::getGameStateValue('score_pots')) {
                $valuePaid = $highPagatValue;
                $potBonus = self::getGameStateValue('pagat_pot');
                self::setGameStateValue('pagat_pot', 0);
                $this->notifyPlayer($player, 'log', clienttranslate('You receive <b>${potBonus}</b> from the Pagat Pot!'), [ 'potBonus' => $potBonus ]);
            }
            if (self::getPlayersNumber() == 3) {
                foreach ($players as $player_id => $unused) {
                    $adjustment = 0;
                    if ($player_id == $player) {
                        $adjustment = ($valuePaid * 2) + $potBonus;
                    } else {
                        $adjustment = -$valuePaid;
                    }
                    $sql = "UPDATE player SET player_score = player_score + $adjustment WHERE player_id='$player_id'";
                    self::DbQuery($sql);
                }
            }
            $bonusName = $this->bonuses['pagatultimo']['name'];
            $values = implode(["'$bonusName'", $player, $valuePaid, $potBonus], ',');
            self::DbQuery( "INSERT INTO bonuses (bonus_name, bonus_player, bonus_value, bonus_pot_value) VALUES ($values) " );

            //stats
            self::incStat(1, 'pagat_ultimo');
            self::incStat(1, 'ultimos_tried', $player);
            self::incStat(1, 'won_ultimos', $player);
        }
        // pagat_lost
        if ($outcome == "pagat_lost") {
            $valuePaid = -$lowPagatValue;
            $potBonus = 0;
            if (self::getGameStateValue('score_pots')) {
                $valuePaid = -$highPagatValue;
                $potBonus = self::getGameStateValue('pagat_pot');
                self::incGameStateValue('pagat_pot', $potBonus);
                $this->notifyPlayer($player, 'log', clienttranslate('You pay <b>${potBonus}</b> to the Pagat Pot!'), [ 'potBonus' => $potBonus ]);
            }
            if (self::getPlayersNumber() == 3) {
                foreach ($players as $player_id => $unused) {
                    $adjustment = 0;
                    if ($player_id == $player) {
                        $adjustment = ($valuePaid * 2) - $potBonus;
                    } else {
                        $adjustment = -$valuePaid;
                    }
                    $sql = "UPDATE player SET player_score = player_score + $adjustment WHERE player_id='$player_id'";
                    self::DbQuery($sql);
                }
            }
            $bonusName = $this->bonuses['lostpagatultimo']['name'];
            $values = implode(["'$bonusName'", $player, $valuePaid, -$potBonus], ',');
            self::DbQuery( "INSERT INTO bonuses (bonus_name, bonus_player, bonus_value, bonus_pot_value) VALUES ($values) " );

            //stats
            self::incStat(1, 'failed_ultimo');
            self::incStat(1, 'ultimos_tried', $player);
        }
        // king_won
        if ($outcome == "king_won") {
            $valuePaid = $lowKingValue;
            $potBonus = 0;
            if (self::getGameStateValue('score_pots')) {
                $valuePaid = $highKingValue;
                $potBonus = $kingPotValue;
                self::incGameStateValue('king_pot', -$potBonus);
                $this->notifyPlayer($player, 'log', clienttranslate('You receive <b>${potBonus}</b> from the King Pot!'), [ 'potBonus' => $potBonus ]);
            }
            if (self::getPlayersNumber() == 3) {
                foreach ($players as $player_id => $unused) {
                    $adjustment = 0;
                    if ($player_id == $player) {
                        $adjustment = ($valuePaid * 2) + $potBonus;
                    } else {
                        $adjustment = -$valuePaid;
                    }
                    $sql = "UPDATE player SET player_score = player_score + $adjustment WHERE player_id='$player_id'";
                    self::DbQuery($sql);
                }
            }
            $bonusName = $this->bonuses['kingultimo']['name'];
            $values = implode(["'$bonusName'", $player, $valuePaid, $potBonus], ',');
            self::DbQuery( "INSERT INTO bonuses (bonus_name, bonus_player, bonus_value, bonus_pot_value) VALUES ($values) " );

            //stats
            self::incStat(1, 'king_ultimo');
            self::incStat(1, 'ultimos_tried', $player);
            self::incStat(1, 'won_ultimos', $player);
        }
        // king_lost
        if ($outcome == "king_lost") {
            $valuePaid = -$lowKingValue;
            $potBonus = 0;
            if (self::getGameStateValue('score_pots')) {
                $valuePaid = -$highKingValue;
                $potBonus = $kingPotValue;
                self::incGameStateValue('king_pot', $potBonus);
                $this->notifyPlayer($player, 'log', clienttranslate('You pay <b>${potBonus}</b> to the King Pot!'), [ 'potBonus' => $potBonus ]);
            }
            if (self::getPlayersNumber() == 3) {
                foreach ($players as $player_id => $unused) {
                    $adjustment = 0;
                    if ($player_id == $player) {
                        $adjustment = ($valuePaid * 2) - $potBonus;
                    } else {
                        $adjustment = -$valuePaid;
                    }
                    $sql = "UPDATE player SET player_score = player_score + $adjustment WHERE player_id='$player_id'";
                    self::DbQuery($sql);
                }
            }
            $bonusName = $this->bonuses['lostkingultimo']['name'];
            $values = implode(["'$bonusName'", $player, $valuePaid, -$potBonus], ',');
            self::DbQuery( "INSERT INTO bonuses (bonus_name, bonus_player, bonus_value, bonus_pot_value) VALUES ($values) " );

            //stats
            self::incStat(1, 'failed_ultimo');
            self::incStat(1, 'ultimos_tried', $player);
        }
        // last
        if ($outcome == "last") {
            $valuePaid = $lowLastValue;
            if (self::getGameStateValue('score_pots')) {
                $valuePaid = $highLastValue;
            }
            if (self::getPlayersNumber() == 3) {
                foreach ($players as $player_id => $unused) {
                    $adjustment = 0;
                    if ($player_id == $player) {
                        $adjustment = $valuePaid * 2;
                    } else {
                        $adjustment = -$valuePaid;
                    }
                    $sql = "UPDATE player SET player_score = player_score + $adjustment WHERE player_id='$player_id'";
                    self::DbQuery($sql);
                }
            }
            $bonusName = $this->bonuses['lasttrick']['name'];
            $values = implode(["'$bonusName'", $player, $valuePaid], ',');
            self::DbQuery( "INSERT INTO bonuses (bonus_name, bonus_player, bonus_value) VALUES ($values) " );

            //stats
            self::incStat(1, 'last_trick');
        }


        return $valuePaid;
    }

    private function cardsInHand( $playerId ) {
        $playerCards = $this->cards->getPlayerHand($playerId);
        return $this->cardsToIds($playerCards);
    }

    private function cardsToIds( $cards ) {
        return array_map( function( $card ) { return $card['id']; }, $cards );
    }

    private function isKing ($card) {
        return $card['type'] != 5 && $card['type_arg'] == 14;
    }
    private function isPagat ($card) {
        return $card['type'] == 5 && $card['type_arg'] == 1;
    }


    /*
     * Identify all melds - and the cards that are used for them.
     */
    private function cardMelds ($playerId) {
        $playerHand = $this->cards->getCardsInLocation('hand', $playerId);

        $meldedCards = [];

        // Identify melds, add them to DB, and let the other players know
        $declarations = [];

        // TRUMPS
        $trumps = array_filter($playerHand, function($card) {
            return $card['type'] == 5;
        });
        $trumpcount = count($trumps);
        if ($trumpcount >= 10) {
            $meldedCards = array_merge($meldedCards, $trumps);

            $pagat = in_array(1, array_column($trumps, 'type_arg'));
            $declaration = [];
            if ($pagat) {
                $declaration = [
                    'bonus_name' => $this->bonuses['trumpswith']['name'],
                    'bonus_arg' => $trumpcount
                ];
            } else {
                $declaration = [
                    'bonus_name' => $this->bonuses['trumpswithout']['name'],
                    'bonus_arg' => $trumpcount
                ];
            }
            $declarations[] = $declaration;
        }

        // MATADORS
        $matadors = array_filter($trumps, function($card) {
            return $card['type_arg'] == 1 || $card['type_arg'] == 21 ||
                $card['type_arg'] == 22;
        });
        if (count($matadors) == 3) {
            $matadorCount = 3;
            $curMat = 20;

            $meldedCards = array_merge($meldedCards, $matadors);
            $trumpvals = array_column($trumps, 'type_arg');

            while (in_array($curMat, $trumpvals) && $curMat > 1) {
                $meldedCards[] = $curMat;
                $matadorCount += 1;
                $curMat -= 1;
            }
            $matadors_declaration = $declaration = [
                'bonus_name' => $this->bonuses['matadors']['name'],
                'bonus_arg' => $matadorCount
            ];
            $declarations[] = $matadors_declaration;
        }

        // Cavallerie
        $hasFool = in_array(22, array_column($trumps, 'type_arg'));
        for ($suit = 1; $suit <= 4; $suit++) {
            $court = [11, 12, 13, 14];
            $thisSuit = array_filter($playerHand,
                function($card) use ($suit, $court) {
                    return $card['type'] == $suit &&
                    in_array($card['type_arg'], $court);
                });
            $suitCourtsMissing = array_diff($court, array_column($thisSuit, 'type_arg'));
            if (count($suitCourtsMissing) <= 1) {
                // note the present cards
                $cavallerieDeclaration = [];
                $cav_val = 0;
                if (count($suitCourtsMissing) == 0) {
                    $meldedCards = array_merge($meldedCards, $thisSuit);
                    if ($hasFool) {
                        $cavallerieDeclaration = [
                            'bonus_name' => $this->bonuses['abundantcavalry']['name'],
                            'bonus_arg' => $suit
                        ];
                    } else {
                        $cavallerieDeclaration = [
                            'bonus_name' => $this->bonuses['fullcavalry']['name'],
                            'bonus_arg' => $suit
                        ];
                    }
                } else {
                    if ($hasFool) {
                        $meldedCards = array_merge($meldedCards, $thisSuit);
                        $cavallerieDeclaration = [
                            'bonus_name' => $this->bonuses['halfcavalry']['name'],
                            'bonus_arg' => $suit,
                            'bonus_arg2' => reset($suitCourtsMissing)
                        ];
                    }
                }
                if (count($cavallerieDeclaration) > 0) {
                    $declarations[] = $cavallerieDeclaration;
                }
            }
        }

        // KINGS
        $kings = array_filter($playerHand, function($card) {
            return $card['type'] < 5 && $card['type_arg'] == 14;
        });
        $kingsuits = [1, 2, 3, 4];
        $suitKingsMissing = array_diff($kingsuits, array_column($kings, 'type'));
        if (count($suitKingsMissing) <= 1) {
            // note the present cards
            $kingsDeclaration = [];
            if (count($suitKingsMissing) == 0) {
                $meldedCards = array_merge($meldedCards, $kings);
                if ($hasFool) {
                    $kingsDeclaration = [
                        'bonus_name' => $this->bonuses['abundantkings']['name']
                    ];
                } else {
                    $kingsDeclaration = [
                        'bonus_name' => $this->bonuses['fullkings']['name']
                    ];
                }
            } else {
                if ($hasFool) {
                    $meldedCards = array_merge($meldedCards, $kings);
                    $kingsDeclaration = [
                        'bonus_name' => $this->bonuses['halfkings']['name'],
                        'bonus_arg' => reset($suitKingsMissing)
                    ];
                }
            }
            if (count($kingsDeclaration) > 0) {
                $declarations[] = $kingsDeclaration;
            }
        }
        return [
            'declarations' => $declarations,
            'meldedCards' => $meldedCards
        ];
    }


    /**
	 * Assert that the card can be played.
	 * Throws an exception if not.
	 */
	private function assertCardPlay($cardId) {
        $playerId = self::getActivePlayerId();
        $playerHand = $this->cards->getCardsInLocation('hand', $playerId);

        // card must be in hand.
        $isInHand = false;
        $currentSuitLed = self::getGameStateValue('suit_led');
        $tricksPlayed = self::getGameStateValue('tricks_played');
        $scuseRequired = self::getGameStateValue('scuse_required');
        $hasScuse = false;
        $atLeastOneOfSuitLed = false;
        $atLeastOneTrump = false;
        $card = null;

        foreach($playerHand as $currentCard) {
            if ($currentCard['id'] == $cardId) {
                $isInHand = true;
                $card = $currentCard;
            }
            if ($currentCard['type'] == $currentSuitLed &&
                $currentCard['type_arg'] != 22) {
                $atLeastOneOfSuitLed = true;
            }
            /* Trump (not the Fool) */
            if ($currentCard['type'] == 5 && $currentCard['type_arg'] != 22) {
                $atLeastOneTrump = true;
            }
            if ($currentCard['type_arg'] == 22) {
                $hasScuse = true;
            }
        }

        if (!$isInHand) {
            throw new BgaUserException(self::_("This card is not in your hand"));
        }
        // Scuse MUST be played if required.
        if ($scuseRequired && $hasScuse && $card['type_arg'] != 22) {
            throw new BgaUserException(self::_("You must play the ’Scuse if requested"));
        }
        if ($card['type_arg'] == 22) { // Fool
            // Fool can be played anytime except 2nd to last trick.
            if ($tricksPlayed == 23) {
                throw new BgaUserException(self::_("The ’Scuse may not be played in the penultimate trick"));
            }
        } else if ($currentSuitLed != 0) {
            if ($card['type'] != $currentSuitLed) {
                // The card does not match the suit led, and
                // the player has at least one card of the needed suit
                if ($atLeastOneOfSuitLed) {
                    throw new BgaUserException(sprintf(self::_("You must play a %s"),
                        $this->suits[$currentSuitLed]['nametr']), true);
                } else if($card['type'] != 5 && $atLeastOneTrump) {
                    // The player has at least one trump
                    throw new BgaUserException(
                        sprintf(self::_("You must play a %s"),
                        $this->suits[5]['nametr']), true);
                }
            }
        }
    }

    private function getPossibleCardsToDiscard($playerId) {
        $playerCards = $this->cards->getCardsInLocation('hand', $playerId);
        $meldedCards = array_column(self::cardMelds($playerId)['meldedCards'], 'id');

        $noHonours = array_filter($playerCards, function($c) {
            return !(($c['type'] == 5 && (in_array($c['type_arg'], [1, 21, 22]))) ||
                self::isKing($c));
        });

        $trumps = array_filter($playerCards, function($c) {
            return $c['type'] == 5;
        });
        $trumpCount = count($trumps);
        // get trump + king count: if it's > 23? 22? They'll have to discard one..
        $noTrumps = $noHonours;
        if ($trumpCount > 3 ||
            count(array_diff([1, 21, 22], array_column($trumps, 'type_arg'))) > 0) {
            $noTrumps = array_filter($noHonours, function($c) {
                return $c['type'] != 5;
            });
        }
        if (count($noTrumps) < 3) {
            $noTrumps = $noHonours;
        }

        $noMelds = array_filter($noTrumps, function($c) use ($meldedCards) {
            return !in_array($c['id'], $meldedCards);
        });
        if (count($noMelds) < 3) {
            $noMelds = $noTrumps;
        }

        $possibleCards = [];
		foreach ($noMelds as $playerCard) {
			$possibleCards[] = $playerCard;
		}
		return $possibleCards;
    }

    private function getPossibleCardsToPlay($playerId) {
		// Loop the player hand, stopping at the first card which can be played
		$playerCards = $this->cards->getCardsInLocation('hand', $playerId);
		$possibleCards = [];
		foreach ($playerCards as $playerCard) {
			try {
				$this->assertCardPlay($playerCard['id']);
			} catch (\Exception $e) {
				continue;
			}
			$possibleCards[] = $playerCard;
		}
		return $possibleCards;
    }

    private function getPossibleSuitsToName($playerId) {
        // Get the other players hands, map out just the suits, and
        // reduce to a set.
        $players = self::loadPlayersBasicInfos();
        $cards = [];
        foreach ($players as $id => $unused) {
            if ($id != $playerId) {
                $cards = array_merge($cards, $this->cards->getCardsInLocation('hand', $id));
            }
        }
        $suits = array_map(function($card) {
            return $card['type'];
        }, $cards);
        return array_unique($suits);
    }

    private function playerHasScuse($playerId) {
        $cards = $this->cards->getPlayerHand($playerId);
        $hasScuse = false;
        foreach ($cards as $card) {
            if ($card['type_arg'] == 22) {
                $hasScuse = true;
            }
        }
        return $hasScuse;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in grosstarock.action.php)
    */

    function playCard ($cardId) {
        // Check that this is the player's turn and that it is a "possible
        // action" at this game state (see states.inc.php),
        // and that it's in the player's hand, and legal to play.
        self::checkAction('playCard');
		$playerId = self::getActivePlayerId();

		// Check Rules
        $this->assertCardPlay($cardId);

		$this->cards->moveCard($cardId, 'cardsontable', $playerId);
		$currentCard = $this->cards->getCard($cardId);


        // Set the trick color if it hasn't been set yet
        $currentSuitLed = self::getGameStateValue('suit_led');
        if ($currentSuitLed == 0 && $currentCard['type_arg'] != 22) {
            self::setGameStateValue('suit_led', $currentCard['type']);
        }
        if ($currentCard['type_arg'] == 22) {
            self::setGameStateValue('scuse_played', 1);;
        }

        $dealer = self::getGameStateValue('dealer_id');
        $trumpsDiscarded = self::getGameStateValue('trumps_discarded');
        $cardsPlayed = $this->cards->countCardInLocation('cardsontable');
        // If: 1) not lead and 2) you don't follow suit but 3) don't trump and
        // 4) are dealer and 5) discarded trumps: Announce as much.
        if ($dealer == $playerId && $cardsPlayed > 1 &&
                ($currentSuitLed != $currentCard['type'] || $currentSuitLed != 5) &&
                $currentCard['type'] != 5 && $trumpsDiscarded > 0)  {
            self::setGameStateValue('trumps_discarded', 0);
            self::notifyAllPlayers('discardedTrumps',
                clienttranslate('${player_name} discarded trumps'), [
                    'player_name' => self::getActivePlayerName(),
                    'player_id' => $playerId
                ]
            );
        }

        // And notify
        self::notifyAllPlayers('playCard',
            clienttranslate('${player_name} plays ${card_name}'), [
                'player_name' => self::getActivePlayerName(),
                'player_id' => $playerId,
                'card' => $currentCard,
                'card_name' => '',
                'cardNo' => $cardsPlayed
            ]
        );

        $tricksPlayed = self::getGameStateValue( 'tricks_played' );

        // If the fool was led, (and it's not the last trick) get a named suit.
        if ($currentSuitLed == 0 && $currentCard['type_arg'] == 22 && $tricksPlayed != 24) {
            $this->gamestate->nextState('leadScuse');
        } else {
            $this->gamestate->nextState('playCard');
        }
    }


    function discard ($cards) {
        self::checkAction('discard');

        $playerId = self::getCurrentPlayerId();
        $playerCards = $this->cards->getCardsInLocation( 'hand', $playerId );
        $cardIds = $this->cardsToIds( $playerCards );
        $toDiscard = 3;

        $meldedCards = self::cardMelds($playerId)['meldedCards'];

        array_unique($cardIds);
        // Are we discarding the correct number of cards?
        if( count( $cards ) != $toDiscard ) {
            throw new BgaUserException( self::_( 'You must discard exactly three cards') );
        }

        // Does this player actually hold the cards they're trying to discard?
        if( count( array_intersect( $cards, $cardIds ) ) != $toDiscard ) {
            throw new BgaUserException( self::_( 'You can only discard what you hold' ) );
        }

        // Can these cards be discarded?
        // The dealer may never discard:
        // * Ultimo cards (the kings and the pagat).
        // * The mond (trump 21).
        // * A card that would otherwise be part of a combination to be
        //   declared, except in the highly unlikely case where it is impossible
        //   to avoid doing so.
        // * a Trump, unless he thereby becomes void in trumps.
        //   For the purpose of this rule, the fool does not count as a trump.

        $discarded_cards = $this->cards->getCards($cards);
        $cardsToAnnounce = [];
        foreach ($discarded_cards as $card) {
            // Can't discard an honor
            if (($card['type'] == 5 && ($card['type_arg'] == 1 ||
                $card['type_arg'] == 21 || $card['type_arg'] == 22)) || (
                    $card['type'] < 5 && ($card['type_arg'] == 14))) {
                throw new BgaUserException( self::_('You cannot discard honours' ));
            }
            // only discard a trump if that's all of them
            // or in the unlikely even that your trumps + kings make up >=23
            if ($card['type'] == 5 && $card['type_arg'] < 22) {
                // remove all discards from hand:
                $hand_ids_after_discard = array_diff($cardIds, $cards);
                $hand_after_discard = $this->cards->getCards($hand_ids_after_discard);
                $number_of_trumps_left = count(array_filter($hand_after_discard,
                    function($c) {
                        return $c['type'] == 5 && $c['type_arg'] <= 22;
                    }));
                $numberOfKingsLeft = count(array_filter($hand_after_discard,
                    function($c) {
                        return $c['type'] != 5 && $c['type_arg'] == 14;
                    }
                ));
                $trumpsAndKings = $number_of_trumps_left + $numberOfKingsLeft;
                if ($number_of_trumps_left > 0 && $trumpsAndKings < 25) {
                    throw new BgaUserException( self::_('You cannot discard trumps unless you thereby become void in trumps' ));
                }
                self::incGameStateValue('trumps_discarded', 1);
            }
            // only discard a meld card if necessary
            if (in_array($card['id'], array_column($meldedCards, 'id'))) {
                $hand_ids_after_discard = array_diff($cardIds, $cards);
                $hand_after_discard = $this->cards->getCards($hand_ids_after_discard);
                $numberOfNonMeldsLeft = count(array_filter($hand_after_discard,
                    function($c) use ($meldedCards) {
                        return $c['type'] != 5 && !in_array($c['id'], array_column($meldedCards, 'id'));
                    }
                ));
                if ($numberOfNonMeldsLeft > 0) {
                    throw new BgaUserException( self::_('You cannot discard cards belong to a declaration unless you have no other options' ));
                } else {
                    $cardsToAnnounce[] = $card;
                }
            }
        }
        foreach ($cardsToAnnounce as $card) {
            $bonus = $this->bonuses['discarded']['name'];
            $values = implode(["'$bonus'", $playerId, $card['type'], $card['type_arg']], ',');
            self::DbQuery( "INSERT INTO bonuses (bonus_name, bonus_player, bonus_arg, bonus_arg2) VALUES ($values) " );
        }


        $this->cards->moveCards( $cards, 'cardswon', $playerId );
        $this->notifyPlayer( $playerId, 'discarded', '', [ 'cards' => $cards ] );
        $this->dealerPay();

        self::notifyAllPlayers('discard',
            clienttranslate('${player_name} has completed the discard'), [
                'player_name' => self::getActivePlayerName()
            ]);

        $this->gamestate->nextState();
    }

    function nameScuse($suit) {
        self::checkAction('nameScuse');

        $playerId = self::getCurrentPlayerId();
        $suits = $this->getPossibleSuitsToName($playerId);
        if (!in_array($suit, $suits)) {
            throw new BgaUserException( self::_('You must name a suit that remains in the other players’ hands' ));
        }
        self::setGameStateValue('suit_led', $suit);
        self::notifyAllPlayers( 'nameSuit', clienttranslate('${name} names ${display_suit}'), [
            'name' => self::getPlayerName($playerId),
            'playerId' => $playerId,
            'display_suit' => $suit
        ]);
        $this->gamestate->nextState();
    }

    function requireScuse() {
        self::checkAction('requireScuse');

        $playerId = self::getCurrentPlayerId();

        if (self::getGameStateValue('scuse_played') == 1) {
            throw new BgaUserException( self::_('The ’Scuse has already been played' ));
        }

        self::setGameStateValue('scuse_required', 1);
        self::notifyAllPlayers( 'requireScuse', clienttranslate('${name} requests that the ’Scuse be played'), [
            'name' => self::getPlayerName($playerId),
            'playerId' => $playerId
        ]);
        $this->gamestate->nextState('requireScuse');
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see
        "args" property in states.inc.php). These methods function is to return
        some additional information that is specific to the current
        game state.
    */

    function argPlayerDiscard() {
        // On player's turn, list possible cards to discard
        return [
            '_private' => [
                'active' => [
                    'possibleCards' => $this->getPossibleCardsToDiscard(
                        self::getActivePlayerId()
                    )
                ]
            ]
        ];
    }

    function argPlayerTurn() {
		// On player's turn, list possible cards
		return [
			'_private' => [
				'active' => [
					'possibleCards' => $this->getPossibleCardsToPlay(
						self::getActivePlayerId()
					),
				],
			],
		];
    }

    function argNameScuse() {
        // List possible suits to name the ’Scuse to
        return [
            '_private' => [
                'active' => [
                    'possibleSuits' => $this->getPossibleSuitsToName(
                        self::getActivePlayerId()
                    )
                ]
            ]
                    ];
    }

    function argGiveCards() {
        return array();
    }
    /*

    Example for game state "MyGameState":

    function argMyGameState()
    {
        // Get some values from the current game situation in database...

        // return values:
        return array(
            'variable1' => $value1,
            'variable2' => $value2,
            ...
        );
    }
    */

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */


    /*
     * 10 - Start a new hand -
     * Take back all cards (from any location) to deck and deal.
     */
    function stNewHand() {
        $eldest = self::getGameStateValue( 'eldest_player_id' );
        $currentHand = self::getGameStateValue( 'hands_played' ) + 1;
        $handsToPlay = self::getGameStateValue( 'hands_to_play' );
        $dealer = self::getGameStateValue( 'dealer_id' );
        self::notifyAllPlayers( 'newDeal', clienttranslate('<hr/>${player_name} deals a new hand<hr/>'), [
            'dealer_id' => $dealer,
            'player_name' => self::getPlayerName($dealer),
            'current_hand' => $currentHand,
            'hands_to_play' => $handsToPlay,
            'eldest' => $eldest
        ]);

        self::DbQuery( 'DELETE FROM bonuses' );

        // found the pots. if a pot is empty, add 20.
        $this->foundPots();
        $this->doDeal();

        // calculate dealer announcements - in case one is partially discarded.
        $dealerId = self::getGameStateValue('dealer_id');
        $declarations = self::cardMelds($dealerId)['declarations'];
        foreach ($declarations as $declaration) {
            $name = $declaration['bonus_name'] ?? null;
            $arg = $declaration['bonus_arg'] ?? 'NULL';
            $arg2 = $declaration['bonus_arg2'] ?? 'NULL';
            $values = implode(["'$name'", $dealerId, $arg, $arg2], ',');
            self::debug($values);
            self::DbQuery( "INSERT INTO bonuses (bonus_name, bonus_player, bonus_arg, bonus_arg2) VALUES ($values) " );
        }

        $this->gamestate->changeActivePlayer($dealerId);
        $this->gamestate->nextState();
    }

    /*
     * 22 - Start declarations with the dealer.
     * (21 will be when we add optional declarations)
     */
    function stNextDeclarer() {

        $playerId = self::getActivePlayerId();
        $player_hand = $this->cards->getCardsInLocation('hand', $playerId);

        // Identify melds, add them to DB, and let the other players know
        $declarations = self::cardMelds($playerId)['declarations'];
        // If it's the dealer - pull up his saved melds instead.
        $saveBonuses = self::getCollectionFromDB(
            "SELECT * FROM bonuses WHERE bonus_player=$playerId AND bonus_pot_value=0"
        );

        self::DbQuery("DELETE FROM bonuses WHERE bonus_player=$playerId AND bonus_pot_value=0");
        if (count($saveBonuses) > count($declarations)) {
            $declarations = array_values($saveBonuses);
        }

        $total = 0;
        foreach ($declarations as $key => $declaration) {
            $name = $declaration['bonus_name'] ?? NULL;
            $arg = $declaration['bonus_arg'] ?? NULL;
            $arg2 = $declaration['bonus_arg2'] ?? NULL;

            // trumps
            if ($name == $this->bonuses['trumpswith']['name'] ||
                    $name == $this->bonuses['trumpswithout']['name'] ) {
                $trumpscore = 10 + (($arg - 10) * 5);
                $this->payDeclaration ($playerId, $name, $trumpscore, $arg);
                $total += $trumpscore;
            }
            // matadors
            if ($name == $this->bonuses['matadors']['name']) {
                $matadorscore = 10 + (($arg - 3) * 5);
                $this->payDeclaration ($playerId, $name, $matadorscore, $arg);
                $total += $matadorscore;
            }
            // cavalries & kings
            if ($name == $this->bonuses['halfcavalry']['name'] ||
                    $name == $this->bonuses['halfkings']['name'] ) {
                $halfscore = 5;
                $this->payDeclaration ($playerId, $name, $halfscore, $arg, $arg2);
                $total += $halfscore;
            }
            if ($name == $this->bonuses['fullcavalry']['name'] ||
                    $name == $this->bonuses['fullkings']['name'] ) {
                $fullscore = 10;
                $this->payDeclaration ($playerId, $name, $fullscore, $arg);
                $total += $fullscore;
            }
            if ($name == $this->bonuses['abundantcavalry']['name'] ||
                    $name == $this->bonuses['abundantkings']['name'] ) {
                $abundantscore = 15;
                $this->payDeclaration ($playerId, $name, $abundantscore, $arg);
                $total += $abundantscore;
            }
        }

        // Stats
        $handsPlayed = self::getGameStateValue('hands_played') + 1;
        $old = self::getStat('avg_declarations', $playerId) * $handsPlayed;
        $new = ($old + $total) / $handsPlayed;
        self::setStat($new, 'avg_declarations', $playerId);

        if ($total) {
            self::notifyAllPlayers('declare', clienttranslate('${player_name} declares "${declarations}", worth <b>${value}</b>'), [
                'player_name' => self::getPlayerName($playerId),
                'player_id' => $playerId,
                'declarations' => $declarations,
                'value' => $total
            ]);
        } else {
            self::notifyAllPlayers('declare', clienttranslate('${player_name} passes'), [
                'player_name' => self::getPlayerName($playerId),
                'player_id' => $playerId,
                'declarations' => $declarations,
                'value' => $total
            ]);
        }

        $newScores = self::getCollectionFromDb("SELECT player_id, player_score FROM player", true);
        self::notifyAllPlayers("newScores", '', [
            'newScores' => $newScores,
            'pagatPot' => self::getGameStateValue('pagat_pot'),
            'kingPot' => self::getGameStateValue('king_pot')
        ]);

        $players_number = self::getPlayersNumber();
        $players_declared = self::incGameStateValue( 'players_declared', 1 );

        $this->activeNextPlayer();
        if ($players_number == $players_declared) {
            $this->gamestate->nextState( 'firstTrick' );
        } else {
            $this->gamestate->nextState( 'loopback' );
        }
    }


    /*
     * 29 - Start the play, eldest leads
     */
    function stEldestLeads() {
        $this->gamestate->changeActivePlayer(
            self::getGameStateValue('eldest_player_id'));
        $this->gamestate->nextState();
    }

    /*
     * 30 - Start a new trick -
     * Clear the suit led; set the next player to the previous winner.
     */
    function stNewTrick() {
        self::setGameStateValue('suit_led', 0);
        $tricksPlayed = self::getGameStateValue('tricks_played');
        $scuseRequired = self::getGameStateValue('scuse_required');
        $scusePlayed = self::getGameStateValue('scuse_played');
        $player_id = self::getActivePlayerId();
        $playerHasScuse = $this->playerHasScuse($player_id);

        if ($tricksPlayed == 22 && !$scuseRequired &&
                !$scusePlayed && !$playerHasScuse) {
            $this->gamestate->nextState("trick23");
        } else {
            $this->gamestate->nextState("playerTurn");
        }
    }

    function stPlayerTurn() {
        // Send the signal that a check for automatic play can be done on JS
        // The player will automatically play if he selected one card before
        // (an error will be displayed if invalid)
		$player_id = self::getActivePlayerId();

        $debug = false; // [D] Set this to false in production
        if ($debug) {
            $card = self::pickRandomPlayableCard($player_id); // Pick a random card among thosse which can be played
            self::_playCard($card); // Play that card directly
            $this->gamestate->nextState('playCard');
        }
        else {
            // Send a signal that a preselected card can now be played
            self::notifyPlayer($player_id, 'checkForAutomaticPlay', '', []);
        }
    }

    /*
     * 32 - Activate the next [trick?] player,
     * OR end the trick and go to the next trick
     * OR end the hand
     */
    function stNextPlayer() {

        $players_number = self::getPlayersNumber();

        if ($this->cards->countCardInLocation('cardsontable') == $players_number) {
            // This is the end of the trick

            $cards_on_table = $this->cards->getCardsInLocation('cardsontable');
            $best_value = 0;
            $best_value_player_id = null;
            $currentSuitLed = self::getGameStateValue('suit_led');

            $last_trick = $this->cards->countCardInLocation('hand') == 0;

            $fool_played = false;
            $fool_owner_id = null;
            $fool_lost = false;

            // Who won the trick?
            foreach ($cards_on_table as $card) {

                // The Fool is played:
                if ($card['type_arg'] == 22) {
                    $fool_played = true;
                    $fool_owner_id = $card['location_arg'];

                    if ($last_trick) {
                        // TODO
                        // A "fool slam" is only allowed in the french rules.
                        // The fool is always lost in Grosstarock
                        // $playersSql = implode(" OR ", array_map(
                        //     function($id) { return "player_id=$id";},
                        //     array_filter(
                        //         array_keys(self::loadPlayersBasicInfos()),
                        //         function($id) { return $id != $fool_owner_id; }
                        //     )
                        // ));

                        // // slam if the other player(s) took no tricks
                        // $foolslam = self::getUniqueValueFromDB( "SELECT sum(player_trick_number) num FROM player WHERE $playersSql" ) == 0;
                        // if ($foolslam) {
                        //     // The team of the owner of the Fool has achieved a
                        //     // Slam. The Fool wins the trick
                        //     $winningColor = 5;
                        //     $best_value_player_id = $fool_owner_id;
                        //     $best_value = 22; // 22: nothing can beat that
                        //     $fool_lost = false;
                        // } else {
                            $fool_lost = true;
                            continue; // The fool can't win the trick
                        // }
                    } else {
                        $fool_lost = false;
                        continue;
                    }
                }

                // A trump has been played: this is the first one
                if ($card['type'] == 5 && $currentSuitLed != 5) {

                    $currentSuitLed = 5; // Now trumps are needed to win
                    $best_value_player_id = $card['location_arg'];
                    $best_value = $card['type_arg'];
                }
                // otherwise:
                if ($card['type'] == $currentSuitLed) {
                    if ($best_value_player_id === null ||
                            $card['type_arg'] > $best_value) {
                        $best_value_player_id = $card ['location_arg'];
                        $best_value = $card ['type_arg'];
                     }
                }
            }

            // Process the fool
            if ($fool_played) { // The Fool (which can't win the trick) has been played
                $fool = $this->cards->getCardsOfType(5, 22);
                $fool = reset($fool);
                if ($fool_lost) {
                    // The Fool has been played on the last turn => his owner lose it
                    $UI_keep_fool = false;
                } else {
                    // The Fool has been played before the last turn => his
                    // owner keeps it and exchange it with 1/2 point if needed
                    // TODO exchange.
                    $this->cards->moveCard($fool['id'], 'cardswon', $fool_owner_id);
                    $UI_keep_fool = true;
                }
            }

            // Activate the winning player
            $this->gamestate->changeActivePlayer($best_value_player_id);
            self::giveExtraTime($best_value_player_id);
            self::incGameStateValue( 'tricks_played', 1 );


            // Was a Tarot won or lost? (more than one can happen!)
            // TODO extract this into a function to use the settings.
            if (!$last_trick) {
                foreach ($cards_on_table as $card) {
                    $card_player_id = $card['location_arg'];
                    if (self::isPagat($card)) {
                        if ($card_player_id == $best_value_player_id) {
                            $value = $this->payTarotWon($card_player_id, 'pagat');
                            self::notifyAllPlayers('log',
                                clienttranslate('${player_name} saved the Pagat, worth <b>${value}</b>'), [
                                    'player_name' => self::getPlayerName($best_value_player_id),
                                    'value' => $value
                                ]);
                        } else {
                            $value = $this->payTarotLost($card_player_id, 'pagat');
                            self::notifyAllPlayers('log',
                                clienttranslate('${player_name} loses the Pagat, worth <b>${value}</b>'), [
                                    'player_name' => self::getPlayerName($card_player_id),
                                    'value' => $value
                                ]);
                        }
                    }
                    if (self::isKing($card) && $card_player_id != $best_value_player_id) {
                        $value = $this->payTarotLost($card_player_id, 'king');
                        self::notifyAllPlayers('log',
                            clienttranslate('${player_name} loses a King, worth <b>${value}</b>'), [
                                'player_name' => self::getPlayerName($card_player_id),
                                'value' => $value
                            ]);
                    }
                }
            } else {
                // LAST TRICK
                // Slam/ Null, Nill, ULTIMOS / BAGUD
                // TODO extract into function as above.
                $slam = false;
                $null = false;
                $trickCount = self::getCollectionFromDb("SELECT player_id, player_trick_number FROM player ");
                foreach ($trickCount as $player_id => $player_trick) {
                    if ($player_trick['player_trick_number'] == 24 &&
                            $player_id == $best_value_player_id) {
                        $slam = $player_id;
                    }
                }
                if ($slam == false) {
                    foreach ($trickCount as $player_id => $player_trick) {
                        if ($player_trick['player_trick_number'] == 0) {
                            $null = $player_id;
                        }
                    }
                }


                $ultimo = false;
                $bagud = false;
                $king_pot_value = self::getGameStateValue('king_pot');
                foreach ($cards_on_table as $card) {
                    $card_player_id = $card['location_arg'];
                    if (self::isPagat($card)) {
                        if ($card_player_id == $best_value_player_id &&
                             !$slam && !$null ) {
                            // Add saved-the-pagat bonus
                            $ultimo = true;
                            $valuePaid = $this->payLastTrick ($card_player_id, 'pagat_won', 0);
                            self::notifyAllPlayers('log',
                                clienttranslate('${player_name} wins a Pagat Ultimo, worth <b>${value}</b>!'), [
                                    'player_name' => self::getPlayerName($best_value_player_id),
                                    'value' => $valuePaid
                                ]);
                        } else {
                            // add lost-the-pagat bonus
                            $bagud = true;
                            $valuePaid = $this->payLastTrick ($card_player_id, 'pagat_lost', 0);
                            self::notifyAllPlayers('log',
                                clienttranslate('${player_name} loses a Pagat Ultimo, worth <b>${value}</b>!'), [
                                    'player_name' => self::getPlayerName($card_player_id),
                                    'value' => $valuePaid
                            ]);
                        }
                    }
                    if (self::isKing($card)) {
                        if ($card_player_id == $best_value_player_id &&
                                !$slam && !$null) {
                            // add King Ultimo bonus
                            $ultimo = true;
                            $valuePaid = $this->payLastTrick($card_player_id, 'king_won', $king_pot_value);
                            self::notifyAllPlayers('log', clienttranslate('${player_name} wins a King Ultimo, worth <b>${value}</b>!'), [
                                'player_name' => self::getPlayerName($card_player_id),
                                'value' => $valuePaid
                            ]);
                        } else if ($card_player_id != $best_value_player_id) {
                            // add lost-the-king bonus
                            $bagud = true;
                            $valuePaid = $this->payLastTrick ($card_player_id, 'king_lost', $king_pot_value);
                            self::notifyAllPlayers('log', clienttranslate('${player_name} loses a King Ultimo, worth <b>${value}</b>!'), [
                                'player_name' => self::getPlayerName($card_player_id),
                                'value' => -$valuePaid
                            ]);
                        }
                    }
                }
                if (!$ultimo && !$slam && !$null &&
                        (!$bagud || self::getGameStateValue('expensive_fail'))) {
                                        // add last trick bonus
                    $valuePaid = $this->payLastTrick ($best_value_player_id, 'last', 0);
                    self::notifyAllPlayers( 'log', clienttranslate('${player_name} wins the last trick, worth <b>${value}</b>!'), [
                        'player_name' => self::getPlayerName($best_value_player_id),
                        'value' => $valuePaid
                    ]);
                }

                if ($slam) {
                    $valuePaid = $this->payLastTrick ($card_player_id, 'slam', 0);
                    self::notifyAllPlayers( 'log', clienttranslate('${player_name} wins a <i>Tout<i>, worth <b>${value}</b>!'), [
                       'player_name' => self::getPlayerName($best_value_player_id),
                       'value' => $valuePaid
                    ]);
                }
                if ($null) {
                    $valuePaid = $this->payLastTrick ($null, 'null', 0);
                    self::notifyAllPlayers( 'log', clienttranslate('${name} wins a <i>Misère</i>, worth <b>${value}</b>!'), [
                        'name' => self::getPlayerName($null),
                        'value' => $valuePaid
                    ]);
                }

                if (!$null) {
                    self::incStat(1,'won_last_tricks', $best_value_player_id);
                }
            }


            // Move all cards to "cardswon" of the given playe
            self::DbQuery("UPDATE player SET player_trick_number = player_trick_number+1 WHERE player_id='$best_value_player_id'");
            $tricksWon = self::getUniqueValueFromDb(
                "SELECT player_trick_number FROM player WHERE player_id='$best_value_player_id'"
            );
            $this->cards->moveAllCardsInLocation('cardsontable', 'cardswon',
                null, $best_value_player_id);
            $players = self::loadPlayersBasicInfos();
            $message = $last_trick ? '' : clienttranslate('${player_name} wins the trick');
            self::notifyAllPlayers('trickWin', $message, [
                'player_id' => $best_value_player_id,
                'player_name' => self::getPlayerName($best_value_player_id),
                'trick_won' => $tricksWon
            ]);

            self::notifyAllPlayers('giveAllCardsToPlayer', '', [
                'player_id' => $best_value_player_id
            ]);

            // The animation must show the fool remains the property of the
            // player who played it
            if ($fool_played) {
                self::notifyAllPlayers('giveAllCardsToPlayer',
                    clienttranslate('${player_name} keeps the ’Scuse'), array(
                    'player_id' => $best_value_player_id,
                    'fool_owner_id' => $fool_owner_id,
                    'fool_to_id' => $UI_keep_fool ? $fool_owner_id : $best_value_player_id,
                    'player_name' => self::getPlayerName($fool_owner_id)
                ));
            }
            else {
                self::notifyAllPlayers('giveAllCardsToPlayer', '', array(
                    'player_id' => $best_value_player_id,
                ));
            }


            if ($this->cards->countCardInLocation('hand') == 0) {
                // End of the hand
                $this->gamestate->nextState("endHand");
            } else {
                // End of the trick
                $this->gamestate->nextState("nextTrick");
            }
        } else {
            // Standard case (not the end of the trick)
            // => just active the next player
            $player_id = self::activeNextPlayer();
            self::giveExtraTime($player_id);

            $tricksPlayed = self::getGameStateValue('tricks_played');
            $scuseRequired = self::getGameStateValue('scuse_required');
            $scusePlayed = self::getGameStateValue('scuse_played');
            $playerHasScuse = $this->playerHasScuse($player_id);

            // TODO
            if ($tricksPlayed == 22 && !$scuseRequired &&
                    !$scusePlayed && !$playerHasScuse) {
                $this->gamestate->nextState('nextPlayer23');
            } else {
                $this->gamestate->nextState('nextPlayer');
            }
        }
    }



    /*
     * 40
     * Name the ’Scuse
     */
    function stNameScuse() {
        $playerId = self::getActivePlayerId();
        self::notifyPlayer($playerId, 'nameScuse', '', []);
    }

    function stUnwindTrick23 () {
        $playerId = self::getActivePlayerId();
        $cardsOnTable = $this->cards->getCardsInLocation('cardsontable');

        $scuseInHandOfPlayerWhosPlayed = 0;
        foreach ($cardsOnTable as $card) {
            $player = $card['location_arg'];
            if ($this->playerHasScuse($player)) {
                $scuseInHandOfPlayerWhosPlayed = $player;
            }
        }

        if ($scuseInHandOfPlayerWhosPlayed) {
            $player = $playerId;
            while ($player != $scuseInHandOfPlayerWhosPlayed) {
                $player = self::getPlayerBefore($player);
                $card = array_values($this->cards->getCardsInLocation('cardsontable', $player))[0];
                $this->cards->moveCard($card['id'], 'hand', $player);
                self::notifyAllPlayers('returnToHand', '', [
                    'player' => $player,
                    'card' => $card
                ]);
            }
            if ($this->cards->countCardInLocation('cardsontable') == 0) {
                self::setGameStateValue('suit_led', 0);
            }
            $this->gamestate->changeActivePlayer($player);
        }
        $this->gamestate->nextState();
    }

    /*
     * 50
     */
    function stEndHand() {
        // Count and score points, then end the game or go to the next hand.
        $players_number = self::getPlayersNumber();

        $calculated_score = self::calculateScore();
        $scoring_features = $calculated_score["scoring_features"];
        $point_totals = $calculated_score["point_totals"];
        $card_points = $calculated_score["card_points"];
        $rounded_points = $calculated_score['rounded_points'];
        $null_won = $calculated_score["null_won"];

        // Apply scores to players
        if ($players_number == 3 && !$null_won) {
            // The rest has already been paid. Card points only.
            if (self::getGameStateValue('score_card_points') == 1) {
                foreach ($rounded_points as $player_id => $score) {
                    $sql = "UPDATE player SET player_score = player_score + $score WHERE player_id='$player_id'";
                    self::DbQuery($sql);
                }
            }
        } else if ($players_number == 2) {
            // sum the scores first.
            $winner = array_keys($point_totals, max($point_totals))[0];
            $scores = array_values($point_totals);
            $finalscore = abs($scores[0] - $scores[1]);
            $sql = "UPDATE player SET player_score = player_score + $finalscore WHERE player_id='$winner'";
            self::DbQuery($sql);
        }


        // Report the new scores, and the hands so far
        $handsToPlay = self::getGameStateValue('hands_to_play');
        $handsPlayed = self::incGameStateValue( 'hands_played', 1 );
        self::logScores();

        // update stats
        $declarations = 0;
        foreach ($card_points as $player_id => $points) {
            $old = self::getStat('avg_card_points', $player_id) * ($handsPlayed - 1);
            $new = ($old + $points) / $handsPlayed;
            self::setStat($new, 'avg_card_points', $player_id);
            $declarations += self::getStat('avg_declarations', $player_id);
        }
        self::setStat($declarations / 3, 'avg_declarations');



        $newScores = self::getCollectionFromDb("SELECT player_id, player_score FROM player", true);
        $pagatPot = 0;
        $kingPot = 0;
        if (self::getGameStateValue('score_pots')) {
            $pagatPot = self::getGameStateValue('pagat_pot');
            $kingPot = self::getGameStateValue('king_pot');
        }
        self::notifyAllPlayers("newScores", '', [
            'newScores' => $newScores,
            'pagatPot' => $pagatPot,
            'kingPot' => $kingPot
        ]);


        // Is the game over?
        if ($players_number == 3) {
            if ($handsPlayed == $handsToPlay) {
                $this->emptyPots();
                $this->gamestate->nextState("endGame");
                return;
            }
        } else {
            $targetPoints = ceil($handsToPlay * 6.66);
            foreach ($newScores as $player_id => $score) {
                if ($score >= $targetPoints) {
                    $this->gamestate->nextState("endGame");
                    return;
                }
            }
        }


        // reset trick count
        self::DbQuery("UPDATE player SET player_trick_number = 0");

        $oldDealer = self::getGameStateValue( 'dealer_id' );
        $dealer_id = self::getPlayerAfter( $oldDealer );
        $eldest_player_id = self::getPlayerAfter( $dealer_id );
        self::setGameStateValue('scuse_required', 0);
        self::setGameStateValue('scuse_played', 0);
        self::setGameStateValue('dealer_id', $dealer_id);
        self::setGameStateValue('eldest_player_id', $eldest_player_id);
        self::setGameStateValue('players_declared', 0);
        self::setGameStateValue('tricks_played', 0);
        self::setGameStateValue('trumps_discarded', 0);
        $this->gamestate->nextState("nextHand");
    }

    // function phpVersion() {
    //     $this->notifyAllPlayers("log", phpversion(), []);
    // }

    function logScores() {
        $players = self::loadPlayersBasicInfos();

        $calculated_score = self::calculateScore();
        $scoring_features = $calculated_score["scoring_features"];
        $point_totals = $calculated_score["point_totals"];
        $card_points = $calculated_score['card_points'];
        $nullAchieved = $calculated_score["null_won"];

        $footer = $nullAchieved ? clienttranslate("Successful Ultimos and Card Points are not scored in a Misère") : "";
        // 'tableWindow' is automatic; I'm changing it to catch the results and
        //  be able to re-show them.
        $handsPlayed = self::getGameStateValue( 'hands_played');
        $seeResult = [
            "id" => 'finalScoring',
            "title" => clienttranslate("Scoring Summary"),
            "table" => $calculated_score["table"],
            // "header" => [
            //     'str' => clienttranslate('Table header with parameter ${number}'),
            //     'args' => ['number' => 3],
            // ],
            "footer" => $footer,
            "closing" => clienttranslate( "Close" )
            ];
        self::notifyAllPlayers( 'scoreTable', clienttranslate('Hand #${n} is completed - ${seeResult}'), array(
            'n' => $handsPlayed,
            'seeResult' => $seeResult
        ));
    }

    /*
     * Helper function to calculate scores that can be called at any time for
     * Debugging.
     */
    function calculateScore() {
        $players = self::loadPlayersBasicInfos();
        $players_number = self::getPlayersNumber();

        $player_to_points = array();
        $player_to_cards = array();
        foreach ($players as $player_id => $player) {
            $players_to_points[$player_id] = 0;
            $players_to_cards[$player_id] = 0;
            $players_to_scores[$player_id] = [];
            $players_to_score_totals[$player_id] = 0;
        }

        $table = [];
        // name headers
        $table[] = array_merge([''], array_map(function($player) {
            return [
                'str' => '${player_name}',
                'args' => ['player_name' => $player['player_name']],
                'type' => 'header'];
        }, $players));


        // Get the bonuses, announcements etc., add those to the $players_to_scores
        $nullAchieved = false;
        $bonuses = self::getCollectionFromDB( 'SELECT * FROM bonuses ORDER BY bonus_id' );
        foreach ($bonuses as $bonus) {
            $bonus_name = $bonus['bonus_name'];
            if ($bonus_name == $this->bonuses['discarded']['name']) continue;
            $bonus_player_id = $bonus['bonus_player'];
            $bonus_value = $bonus['bonus_value'];
            $pot_value = $bonus['bonus_pot_value'];
            if ($bonus_name == 'Misère') {
                $nullAchieved = true;
            }
            // interpolate
            $args = [];
            if (strpos($bonus_name, "{num}")) {
                $args['num'] = $bonus['bonus_arg'];
            }
            if (strpos($bonus_name, "{insuit}")) {
                $args['insuit'] = $this->suits[$bonus['bonus_arg']]['insuit'];
            }
            if (strpos($bonus_name, "{withoutrank}")) {
                $args['withoutrank'] = $this->figures[$bonus['bonus_arg2']]['withoutrank'];
            }
            if (strpos($bonus_name, "{withoutsuit}")) {
                $args['withoutsuit'] = $this->suits[$bonus['bonus_arg']]['withoutsuit'];
            }
            $row = [[
                'str' => $bonus_name,
                'args' => $args
            ]];
            foreach ($players as $player_id => $player) {
                if ($players_number == 3) {
                    // pot foundation is everyone:
                    if ($bonus_name == $this->bonuses['foundation']['name']) {
                        $formattedscore = " (" . $pot_value .")";
                        $score = $pot_value;
                    } else if ($bonus_name == $this->bonuses['dealing']['name']) {
                        if ($bonus_player_id == $player_id) {
                            $formattedscore = " (" . $pot_value .")";
                            $score = $pot_value;
                        } else {
                            $score = 0;
                            $formattedscore = '';
                        }
                    } else {
                        $score = -$bonus_value;
                        $formattedscore = $score;
                        if ($bonus_player_id == $player_id) {
                            $score = $bonus_value * 2 + $pot_value;
                            $formattedscore = "<b>" . ($bonus_value * 2);
                            if ($pot_value) {
                                if ($pot_value > 0) {
                                    $formattedscore .= " (+" . $pot_value .")";
                                } else {
                                    $formattedscore .= " (" . $pot_value .")";
                                }
                            }
                            $formattedscore .="</b>";
                        }
                    }
                    $players_to_scores[$player_id] [] = [ $bonus_name, $score];
                    $row[] = $formattedscore;
                } else {
                    $score = $bonus_player_id == $player_id ? $bonus_value : 0;
                    $players_to_scores[$player_id] [] = [ $bonus_name, $score];
                    $row[] = $score;
                }
            }
            $table[] = $row;
        }

        // Grab card points from the DB
        foreach ($players as $player_id => $player) {
            $card_points = self::getUniqueValueFromDB( "SELECT SUM(card_points) num FROM card WHERE
            card_location = 'cardswon' AND card_location_arg = $player_id" );
            $num_cards = self::getUniqueValueFromDB( "SELECT count(card_id) num FROM card WHERE
            card_location = 'cardswon' AND card_location_arg = $player_id" );
            $trick_points = intval(round($num_cards / $players_number));
            $players_to_points[$player_id] = $card_points + $trick_points;
        }

        // In 3 player: everyone scores the differece from 26.
        // In 2 player, the winner scores the difference between their scores.
        // Add the card points:
        // No card points in a null.
        // N.B. The new dealer's cards are not counted, but summed from the others
        //   due to the effects of the rounding.
        // "score_card_points" => 102,
        // "expensive_fail" => 103,

        $player_to_rounded_points = [];
        // card points row.
        if (!$nullAchieved) {
            $row = [[
                'str' => '${points}',
                'args' => [ 'points' => $this->bonuses['points']['name'] ],
            ]];

            // two player is easy.
            if ($players_number == 2) {
                foreach ($players_to_points as $player_id => $points) {
                    $players_to_scores[$player_id] [] = [ $this->bonuses['points']['name'], $points ];
                    $row[] = "$points";
                }
            } else {
                // Three is a little more involved.
                $oldDealer = self::getGameStateValue('dealer_id');
                $newDealer = self::getPlayerAfter($oldDealer);
                $dealerRounded = 0;

                // figure the rounded points
                foreach ($players_to_points as $player_id => $points) {
                    if ($player_id != $newDealer) {
                        $roundedPoints = round(($points - 26) / 5) * 5;
                        $dealerRounded += $roundedPoints;
                        if (self::getGameStateValue('score_card_points') == 1) {
                            $players_to_scores[$player_id] [] = [ $this->bonuses['points']['name'], $roundedPoints ];
                        }
                    }
                }
                // now add new dealers points
                $dealerRounded = -$dealerRounded;
                if (self::getGameStateValue('score_card_points') == 1) {
                    $players_to_scores[$newDealer] [] = [ $this->bonuses['points']['name'], $dealerRounded ];
                }
                // now build the row, and save the rounded points.
                foreach ($players_to_points as $player_id => $points) {
                    if ($player_id == $newDealer) {
                        $row[] = "(<em>$points</em>) $dealerRounded";
                        $player_to_rounded_points[$player_id] = $dealerRounded;
                    } else if ($players_number == 3 && !$nullAchieved && $player_id != $newDealer) {
                        $roundedPoints = round(($points - 26) / 5) * 5;
                        $player_to_rounded_points[$player_id] = $roundedPoints;
                        $row[] = "(<em>$points</em>) $roundedPoints";
                    }
                }
            }
            if (self::getGameStateValue('score_card_points') == 1) {
                $table[] = $row;
            }
        }

        $row = [''];
        // Total the scores
        foreach ($players_to_scores as $player_id => $scoringfeatures) {
            $players_to_score_totals[$player_id] = array_sum(array_column($scoringfeatures, 1));
            $row[] = '<span class="total">' . $players_to_score_totals[$player_id] . "</span>";
        }
        $table[] = $row;

        return [
            "scoring_features" =>  $players_to_scores,
            "point_totals" => $players_to_score_totals,
            "card_points" => $players_to_points,
            "rounded_points" => $player_to_rounded_points,
            "null_won" => $nullAchieved,
            "table" => $table
        ];
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:

        This method is called each time it is the turn of a player who has quit
        the game (= "zombie" player).  You can do whatever you want in order to
        make sure the turn of this player ends appropriately (ex: pass).

        Important: your zombie code will be called when the player leaves the
        game. This action is triggered from the main site and propagated to the
        gameserver from a server, not from a browser. As a consequence, there
        is no current player associated to this action. In your zombieTurn
        function, you must _never_ use getCurrentPlayerId() or
        getCurrentPlayerName(), otherwise it will fail with a "Not logged"
        error message.
    */

    function zombieTurn( $state, $activePlayer ) {
		$statename = $state['name'];

		if ($state['type'] == 'activeplayer') {
			switch ($statename) {

				case 'playerTurn':
					// Loop the player hand, stopping at the first card which can be played
					$playerCards = $this->cards->getCardsInLocation(
						'hand', $activePlayer
					);
					foreach ($playerCards as $playerCard) {
						try {
							$this->assertCardPlay($playerCard['id']);
						} catch (\Exception $e) {
							continue;
						}
						break;
					}
					$this->playCard($playerCard['id']);
                    return;

                case 'discardCards':
                    // Discard the first 3 cards permitted.
                    $cards = $this->getPossibleCardsToDiscard($activePlayer);
                    $this->discard(array_slice(array_column($cards, 'id'), 0, 3));
                    return;

                case 'nameScuse':
                    // Loop the player hand, stopping at the first card which can be played
                    $suits = $this->getPossibleSuitsToName($activePlayer);
                    $this->nameScuse($suits[0]);
                    return;

                case 'playerTurn23':
                    $this->requireScuse();
                    return;

			}
		}

		throw new feException(
			'Zombie mode not supported at this game state: ' . $statename // NOI18N
		);
    }

///////////////////////////////////////////////////////////////////////////////////:
////////// DB upgrade
//////////

    /*
        upgradeTableDb:

        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.

    */

    function upgradeTableDb( $from_version )
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345

        // Example:
//        if( $from_version <= 1404301345 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        if( $from_version <= 1405061421 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        // Please add your future database scheme changes here
//
//


    }
}
