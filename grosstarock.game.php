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


            // Pots values
            "pagat_pot" => 16,
            "king_pot" => 17,
            "score_pots" => 18,
            // Options:
            "hands_to_play" => 100,
            "play_with_pots" => 101,

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

        // Two player: The game progression is the max of the two
        // players' points.

        if ($players_number == 2) {
            $newScores = self::getCollectionFromDb("SELECT player_score FROM player", true);
            return max(array_keys($newScores));
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
                }
                if ($king_pot_value == 0) {
                    $sql = "UPDATE player SET player_score = player_score - $contribution WHERE player_id='$player_id'";
                    self::DbQuery($sql);
                    self::incGameStateValue( 'king_pot', $contribution );
                }
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

            $pagatVal = floor($self::getGameStateValue('pagat_pot') / 3);
            $kingVal = floor($self::getGameStateValue('king_val') / 3);
            self::incGameStateValue('pagat_pot', $pagatVal * 3);
            self::incGameStateValue('king_pot', $kingVal * 3);

            $total = $pagatVal + $kingVal;

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

    private function payDeclaration ($player, $bonus, $value) {
        $players = self::loadPlayersBasicInfos();
        $values = implode(["'$bonus'", $player, $value], ',');
        self::DbQuery( "INSERT INTO bonuses (bonus_name, bonus_player, bonus_value) VALUES ($values) " );

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
        $values = implode(["'$card lost'", $player, -$lostTarotValue, -$potValue], ',');
        self::DbQuery( "INSERT INTO bonuses (bonus_name, bonus_player, bonus_value, bonus_pot_value) VALUES ($values) " );
        $cardName = "";
        $potName = "";
        if ($card == "pagat") {
            $cardName = $this->trull[1]['name'];
            $potName = 'pagat_pot';
        } else {
            $cardName = $this->figures[14]['namesg'];
            $potName = 'king_pot';
        }
        self::notifyAllPlayers( 'log', clienttranslate('${name} lost ${card}'), [
            'name' => self::getPlayerName($player),
            'card' => $cardName
        ]);

        if (self::getPlayersNumber() == 3) {
            // playment happens regardless; pots are the extra.
            $payoutMultiplier = 2;
            if (self::getGameStateValue('score_pots')) {
                self::incGameStateValue($potName, $lostTarotValue);
                $payoutMultiplier = 3;
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
    }


    private function payTarotWon ($player, $card) {
        $players = self::loadPlayersBasicInfos();
        $wonTarotValue = 5;

        $values = implode(["'$card won'", $player, $wonTarotValue], ',');
        self::DbQuery( "INSERT INTO bonuses (bonus_name, bonus_player, bonus_value) VALUES ($values) " );
        $cardName = "";
        if ($card == "pagat") {
            $cardName = $this->trull[1]['name'];
        } else {
            $cardName = $this->figures[14]['namesg'];
        }
        self::notifyAllPlayers( 'log', clienttranslate('${name} won ${card}'), [
            'name' => self::getPlayerName($player),
            'card' => $cardName
        ]);

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
    }

    private function payLastTrick ($player, $outcome, $kingPotValue) {
        $players = self::loadPlayersBasicInfos();

        $highPagatValue = 45;
        $lowPagatValue = 15;

        $highKingValue = 40;
        $lowKingValue = 10;

        $highLastValue = 20;
        $lowLastValue = 5;

        if (self::getPlayersNumber() == 3) {
            // slam
            if ($outcome == "slam") {
                $valuePaid = $lowKingValue + $lowPagatValue;
                $potBonus = 0;
                if (self::getGameStateValue('score_pots')) {
                    $valuePaid = $highKingValue + $highPagatValue;
                    $potBonus = self::getGameStateValue('pagat_pot') + $kingPotValue;
                    self::setGameStateValue('pagat_pot', 0);
                    self::setGameStateValue('king_pot', 0);
                }
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
                $values = implode(["'Slam'", $player, $valuePaid, $potBonus], ',');
                self::DbQuery( "INSERT INTO bonuses (bonus_name, bonus_player, bonus_value, bonus_pot_value) VALUES ($values) " );
            }
            // null
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
                $values = implode(["'Misère'", $player, $valuePaid], ',');
                self::DbQuery( "INSERT INTO bonuses (bonus_name, bonus_player, bonus_value) VALUES ($values) " );
            }
            // pagat_won
            if ($outcome == "pagat_won") {
                $valuePaid = $lowPagatValue;
                $potBonus = 0;
                if (self::getGameStateValue('score_pots')) {
                    $valuePaid = $highPagatValue;
                    $potBonus = self::getGameStateValue('pagat_pot');
                    self::setGameStateValue('pagat_pot', 0);
                }
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
                $values = implode(["'Pagat Ultimo'", $player, $valuePaid, $potBonus], ',');
                self::DbQuery( "INSERT INTO bonuses (bonus_name, bonus_player, bonus_value, bonus_pot_value) VALUES ($values) " );
            }
            // pagat_lost
            if ($outcome == "pagat_lost") {
                $valuePaid = -$lowPagatValue;
                $potBonus = 0;
                if (self::getGameStateValue('score_pots')) {
                    $valuePaid = -$highPagatValue;
                    $potBonus = self::getGameStateValue('pagat_pot');
                    self::incGameStateValue('pagat_pot', $potBonus);
                }
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
                $values = implode(["'Failed Pagat Ultimo'", $player, $valuePaid, -$potBonus], ',');
                self::DbQuery( "INSERT INTO bonuses (bonus_name, bonus_player, bonus_value, bonus_pot_value) VALUES ($values) " );
            }
            // king_won
            if ($outcome == "king_won") {
                $valuePaid = $lowKingValue;
                $potBonus = 0;
                if (self::getGameStateValue('score_pots')) {
                    $valuePaid = $highKingValue;
                    $potBonus = $kingPotValue;
                    self::incGameStateValue('king_pot', -$potBonus);
                }
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
                $values = implode(["'King Ultimo'", $player, $valuePaid, $potBonus], ',');
                self::DbQuery( "INSERT INTO bonuses (bonus_name, bonus_player, bonus_value, bonus_pot_value) VALUES ($values) " );
            }
            // king_lost
            if ($outcome == "king_lost") {
                $valuePaid = -$lowKingValue;
                $potBonus = 0;
                if (self::getGameStateValue('score_pots')) {
                    $valuePaid = -$highKingValue;
                    $potBonus = $kingPotValue;
                    self::incGameStateValue('king_pot', $potBonus);
                }
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
                $values = implode(["'Failed King Ultimo'", $player, $valuePaid, -$potBonus], ',');
                self::DbQuery( "INSERT INTO bonuses (bonus_name, bonus_player, bonus_value, bonus_pot_value) VALUES ($values) " );
            }
            // last
            if ($outcome == "last") {
                $valuePaid = $lowLastValue;
                if (self::getGameStateValue('score_pots')) {
                    $valuePaid = $highLastValue;
                }
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
                $values = implode(["'Last Trick'", $player, $valuePaid], ',');
                self::DbQuery( "INSERT INTO bonuses (bonus_name, bonus_player, bonus_value) VALUES ($values) " );
            }
        }
    }

    private function cardsInHand( $playerId ) {
        $playerCards = $this->cards->getCardsInLocation( 'hand', $playerId );
        return $this->cardsToIds( $playerCards );
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
        $tricksPlayed = self::getGameStateValue( 'tricks_played' );
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
        }

        if (!$isInHand) {
            throw new BgaUserException(self::_("This card is not in your hand"));
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
        // TODO
        // If the fool was led, get a named suit.
        // if ($currentSuitLed == 0 && $currentCard['type_arg'] == 22) {
        //     $this->gamestate->nextState('nameFool');
        // }


        // // And notify
        $cardValueDisplayed = $currentCard ['type_arg'];
        if ($currentCard['type'] != 5 && $currentCard['type_arg'] > 10) {
            $cardValueDisplayed =  $this->figures[$cardValueDisplayed]['symbol'];
        }
        if ($currentCard['type'] == 4 && $currentCard['type_arg'] == 22) {
            $cardValueDisplayed =  $this->trull[$cardValueDisplayed]['symbol'];
        }
        

        self::notifyAllPlayers('playCard', clienttranslate('${player_name} plays ${value_displayed}${suit_displayed}'), array (
            'i18n' => array ('suit_displayed','value_displayed' ),
            'player_name' => self::getActivePlayerName(),
            'value_displayed' => $cardValueDisplayed,
            'suit_displayed' => $this->suits [$currentCard ['type']] ['symbol'],
            'player_id' => $playerId,
            'suit' => $currentCard ['type'],
            'value' => $currentCard ['type_arg'],
            'card_id' => $cardId,
        ));
        $this->gamestate->nextState('playCard');
    }


    function discard( $cards ) {
        self::checkAction( 'discard' );

        $playerId = self::getCurrentPlayerId();
        $playerCards = $this->cards->getCardsInLocation( 'hand', $playerId );
        $cardIds = $this->cardsToIds( $playerCards );
        $toDiscard = 3;

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
        // * TODO A card that would otherwise be part of a combination to be
        //   declared, except in the highly unlikely case where it is impossible
        //   to avoid doing so.
        // * a Trump, unless he thereby becomes void in trumps.
        //   For the purpose of this rule, the fool does not count as a trump.
        // * TODO The fool may not be discarded except in the rare case that the
        //   dealer wishes to play for Tout.


        // Scarto: May only discard 1 if it's his only trump.
        $discarded_cards = $this->cards->getCards($cards);
        foreach ($discarded_cards as $card) {
            // Can't discard an honor
            if (($card['type'] == 5 && ($card['type_arg'] == 1 ||
                $card['type_arg'] == 21 || $card['type_arg'] == 22)) || (
                    $card['type'] < 5 && ($card['type_arg'] == 14))) {
                throw new BgaUserException( self::_('You cannot discard honours' ));
            }
            // only discard a trump if that's all of them
            if ($card['type'] == 5 && $card['type_arg'] < 22) {
                // remove all discards from hand:
                $hand_ids_after_discard = array_diff($cardIds, $cards);
                $hand_after_discard = $this->cards->getCards($hand_ids_after_discard);
                $number_of_trumps_left = count(array_filter($hand_after_discard,
                    function($c) {
                        return $c['type'] == 5 && $c['type_arg'] < 22;
                    }));
                if ($number_of_trumps_left > 0) {
                    throw new BgaUserException( self::_('You cannot discard trumps unless you thereby become void in trumps' ));
                }
            }
        }

        $this->cards->moveCards( $cards, 'cardswon', $playerId );
        $this->notifyPlayer( $playerId, 'discarded', '', [ 'cards' => $cards ] );
        $this->dealerPay();

        self::notifyAllPlayers('discard',
            clienttranslate('${player_name} has completed the discard'),
            [ 'player_name' => self::getActivePlayerName() ]);

        $this->gamestate->nextState();
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
        self::notifyAllPlayers( 'newDeal', clienttranslate('<hr/>${player_name} deals a new hand<hr/>'), array(
            'dealer_id' => $dealer,
            'player_name' => self::getPlayerName($dealer),
            'current_hand' => $currentHand,
            'hands_to_play' => $handsToPlay,
            'eldest' => $eldest
        ) );


        // found the pots. if a pot is empty, add 20.
        $this->foundPots();

        $this->doDeal();
        // setup game state?
        self::DbQuery( 'DELETE FROM bonuses' );
        $this->gamestate->changeActivePlayer(
            self::getGameStateValue('dealer_id'));

        $this->gamestate->nextState();
    }

    /*
     * 22 - Start declarations with the dealer.
     * (21 will be when we add optional declarations)
     */
    function stNextDeclarer() {

        $player_id = self::getActivePlayerId();
        $player_hand = $this->cards->getCardsInLocation('hand', $player_id);

        // Identify melds, add them to DB, and let the other players know
        $declarations = [];

        // TRUMPS
        $trumps = array_filter($player_hand, function($card) {
            return $card['type'] == 5;
        });
        $trumpcount = count($trumps);
        if ($trumpcount >= 10) {
            $pagat = in_array(1, array_column($trumps, 'type_arg'));
            $declaration = "$trumpcount Trumps";
            if ($pagat) {
                $declaration .= ", with the Pagat";
            } else {
                $declaration .= ", without the Pagat";
            }
            $trumpscore = 10 + (($trumpcount - 10) * 5);
            $this->payDeclaration ($player_id, $declaration, $trumpscore);
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
            $trumpvals = array_column($player_hand, 'type_arg');
            while (in_array($curMat, $trumpvals)) {
                $curMat -= 1;
                $matadorCount += 1;
            }
            $matadors_declaration = "$matadorCount Matadors";
            $matadorscore = 10 + (($matadorCount - 3) * 5);
            $this->payDeclaration ($player_id, $matadors_declaration, $matadorscore);
            $declarations[] = $matadors_declaration;
        }

        // Cavallerie
        $hasFool = in_array(22, array_column($trumps, 'type_arg'));
        for ($suit = 1; $suit <= 4; $suit++) {
            $court = [11, 12, 13, 14];
            $suitName = $this->suits[$suit]['nametr'];
            $thisSuit = array_column(array_filter($player_hand, function($card) use ($suit) {
                return $card['type'] == $suit;
            }), 'type_arg');
            $suitCourtsMissing = array_diff($court, $thisSuit);
            if (count($suitCourtsMissing) <= 1) {
                $cavallerie_declaration = "";
                $cav_val = 0;
                if (count($suitCourtsMissing) == 0) {
                    if ($hasFool) {
                        $cavallerie_declaration = "Abundant cavallerie in $suitName";
                        $cav_val = 15;
                    } else {
                        $cavallerie_declaration = "Full cavallerie in $suitName";
                        $cav_val = 10;
                    }
                } else {
                    if ($hasFool) {
                        $cavallerie_declaration = "Half cavallerie in $suitName";
                        $cavallerie_declaration .= ", without the " .
                            $this->figures[reset($suitCourtsMissing)]['nametr'];
                        $cav_val = 5;
                    } else {
                        break;
                    }
                }

                $this->payDeclaration($player_id, $cavallerie_declaration, $cav_val);

                $declarations[] = $cavallerie_declaration;
            }
        }

        // KINGS
        $kings = array_column(array_filter($player_hand, function($card) {
            return $card['type'] < 5 && $card['type_arg'] == 14;
        }), 'type');
        $kingsuits = [1, 2, 3, 4];
        $suitKingsMissing = array_diff($kingsuits, $kings);
        if (count($suitKingsMissing) <= 1) {
            $kings_declaration = "";
            $kings_val = 0;
            if (count($suitKingsMissing) == 0) {
                if ($hasFool) {
                    $kings_declaration = "Abundant kings";
                    $kings_val = 15;
                } else {
                    $kings_declaration = "Full kings";
                    $kings_val = 10;
                }
            } else {
                if ($hasFool) {
                    $kings_declaration = "Half kings";
                    $kings_declaration .= ", without the " .
                        $this->suits[reset($suitKingsMissing)]['nametr'];
                    $kings_val = 5;
                }
            }
            if ($kings_val > 0) {
                $this->payDeclaration ($player_id, $kings_declaration, $kings_val);
                $declarations[] = $kings_declaration;
            }
        }


        if (count($declarations) > 0) {
            $declarations = implode($declarations, '; ');
        } else {
            $declarations = "Pass";
        }

        self::notifyAllPlayers( 'declare', clienttranslate('${name} declares ${declarations}'), [
            'name' => self::getPlayerName($player_id),
            'player_id' => $player_id,
            'declarations' => $declarations
        ]);
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
        $this->gamestate->nextState();
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
                            $this->payTarotWon($card_player_id, 'pagat');
                        } else {
                            $this->payTarotLost($card_player_id, 'pagat');
                        }
                    }
                    if (self::isKing($card) && $card_player_id != $best_value_player_id) {
                        $this->payTarotLost($card_player_id, 'king');
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


                $ultimo_or_bagud = false;
                $king_pot_value = self::getGameStateValue('king_pot');
                foreach ($cards_on_table as $card) {
                    $card_player_id = $card['location_arg'];
                    if (self::isPagat($card)) {
                        if ($card_player_id == $best_value_player_id &&
                             !$slam && !$null ) {
                            // Add saved-the-pagat bonus
                            $ultimo_or_bagud = true;
                            self::notifyAllPlayers( 'log',
                                clienttranslate('${name} won a Pagat Ultimo'),
                                ['name' => self::getPlayerName($best_value_player_id)] );
                            $this->payLastTrick ($card_player_id, 'pagat_won', 0);
                        } else {
                            // add lost-the-pagat bonus
                            $ultimo_or_bagud = true;
                            self::notifyAllPlayers( 'log', clienttranslate('${name} lost a Pagat Ultimo'), ['name' => self::getPlayerName($card_player_id)] );
                            $this->payLastTrick ($card_player_id, 'pagat_lost', 0);
                        }
                    }
                    if (self::isKing($card)) {
                        if ($card_player_id == $best_value_player_id &&
                                !$slam && !$null) {
                            // add King Ultimo bonus
                            $ultimo_or_bagud = true;
                            self::notifyAllPlayers( 'log', clienttranslate('${name} won a King Ultimo'),
                            ['name' => self::getPlayerName($card_player_id)]);

                            $this->payLastTrick ($card_player_id, 'king_won', $king_pot_value);
                        } else if ($card_player_id != $best_value_player_id) {
                            // add lost-the-king bonus
                            $ultimo_or_bagud = true;
                            self::notifyAllPlayers( 'log', clienttranslate('${name} lost a King Ultimo'),
                            ['name' => self::getPlayerName($card_player_id)]);
                            $this->payLastTrick ($card_player_id, 'king_lost', $king_pot_value);
                        }
                    }
                }
                if (!$ultimo_or_bagud && !$slam && !$null) {
                    // add last trick bonus
                    $ultimo_or_bagud = true;
                    self::notifyAllPlayers( 'log', clienttranslate('${name} won the last trick'),
                    ['name' => self::getPlayerName($best_value_player_id)]);
                    $this->payLastTrick ($best_value_player_id, 'last', 0);
                }

                if ($slam) {
                   self::notifyAllPlayers( 'log', clienttranslate('${name} won a Tout'),
                    ['name' => self::getPlayerName($best_value_player_id)]);
                    $this->payLastTrick ($card_player_id, 'slam', 0);
                }
                if ($null) {
                    self::notifyAllPlayers( 'log', clienttranslate('${name} won a Misère'),
                    ['name' => self::getPlayerName($null)]);
                    $this->payLastTrick ($null, 'null', 0);
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
            self::notifyAllPlayers('trickWin',
                clienttranslate('${player_name} wins the trick'), array(
                    'player_id' => $best_value_player_id,
                    'player_name' => self::getPlayerName($best_value_player_id),
                    'trick_won' => $tricksWon
                ));

                self::notifyAllPlayers('giveAllCardsToPlayer', '', array(
                'player_id' => $best_value_player_id
            ));

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
            $this->gamestate->nextState('nextPlayer');
        }
    }

    function stEndHand() {
        // Count and score points, then end the game or go to the next hand.
        $players_number = self::getPlayersNumber();

        $calculated_score = self::calculateScore();
        $scoring_features = $calculated_score["scoring_features"];
        $point_totals = $calculated_score["point_totals"];
        $card_points = $calculated_score["card_points"];
        $null_won = $calculated_score["null_won"];

        // Apply scores to players
        if ($players_number == 3 && !$null_won) {
            // The rest has already been payed. Card points only.
            foreach ($card_points as $player_id => $points) {
                $score = round(($points - 26) / 5) * 5;
                $sql = "UPDATE player SET player_score = player_score + $score WHERE player_id='$player_id'";
                self::DbQuery($sql);
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


        $newScores = self::getCollectionFromDb("SELECT player_id, player_score FROM player", true);
        self::notifyAllPlayers("newScores", '', array('newScores' => $newScores));


        // Is the game over?
        if ($players_number == 3) {
            if ($handsPlayed == $handsToPlay) {
                $this->emptyPots();
                $this->gamestate->nextState("endGame");
            }
        } else {
            foreach ($newScores as $player_id => $score) {
                if ($score >= 100) {
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
        self::setGameStateValue('dealer_id', $dealer_id);
        self::setGameStateValue('eldest_player_id', $eldest_player_id);
        self::setGameStateValue('players_declared', 0);
        self::setGameStateValue('tricks_played', 0);
        $this->gamestate->nextState("nextHand");
    }

    function phpVersion() {
        $this->notifyAllPlayers("log", phpversion(), []);
    }

    function logScores() {
        $players = self::loadPlayersBasicInfos();

        $calculated_score = self::calculateScore();
        $scoring_features = $calculated_score["scoring_features"];
        $point_totals = $calculated_score["point_totals"];
        $nullAchieved = $calculated_score["null_won"]; 
        // $newScores = [];
        // $newPoints = [];
        // // make keys player names.
        // foreach ($scoring_features as $key => $value) {
        //     $newScores[self::getPlayerName($key)] = $value;
        // }
        // foreach ($point_totals as $key => $value) {
        //     $newPoints[self::getPlayerName($key)] = $value;
        // }


        $footer = $nullAchieved ? clienttranslate("Successful Ultimos and Card Points are not scored in a Misère") : "";
        $this->notifyAllPlayers( "tableWindow", '', [
            "id" => 'finalScoring',
            "title" => clienttranslate("Scoring Summary"),
            "table" => $calculated_score["table"],
            // "header" => [
            //     'str' => clienttranslate('Table header with parameter ${number}'),
            //     'args' => ['number' => 3],
            // ],
            "footer" => $footer,
            "closing" => clienttranslate( "Close" )
        ]); 
        // self::notifyAllPlayers( 'handResult', '', array(
        //     'scores' => $newScores,
        //     'points' => $newPoints
        // ) );

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
            $bonus_player_id = $bonus['bonus_player'];
            $bonus_value = $bonus['bonus_value'];
            $pot_value = $bonus['bonus_pot_value'];
            if ($bonus_name == 'Misère') {
                $nullAchieved = true;
            }
            $row = [[
                'str' => $bonus_name,
                'args' => [],
            ]];
            foreach ($players as $player_id => $player) {
                if ($players_number == 3) {
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
        $row = [[
            'str' => 'Card Points',
            'args' => [],
        ]];
        foreach ($players_to_points as $player_id => $points) {
            if ($players_number == 3 && !$nullAchieved) {
                $roundedPoints = round(($points - 26) / 5) * 5;
                $players_to_scores[$player_id] [] = [ "Card Points", $roundedPoints ];
                $row[] = "$roundedPoints (<em>$points</em>)";
            } else if ($players_number == 2) {
                $players_to_scores[$player_id] [] = [ "Card Points", $points ];
                $row[] = "$points";
            }
        }
        if ($players_number == 2 || !$nullAchieved) {
            $table[] = $row;
        }

        $row = [''];
        // Total the scores
        foreach ($players_to_scores as $player_id => $scoringfeatures) {
            $players_to_score_totals[$player_id] = array_sum(array_column($scoringfeatures, 1));
            $row[] = '<span class="total">' . $players_to_score_totals[$player_id] . "</span>";
            // self::notifyAllPlayers( 'log', clienttranslate('${name} has ${results} points'), array(
            //     'name' => self::getPlayerName($player_id),
            //     'results' => $players_to_score_totals[$player_id]
            // ) );
        }
        $table[] = $row;

        return [
            "scoring_features" =>  $players_to_scores,
            "point_totals" => $players_to_score_totals,
            "card_points" => $players_to_points,
            "null_won" => $nullAchieved,
            "table" => $table
        ];
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:

        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).

        Important: your zombie code will be called when the player leaves the game. This action is triggered
        from the main site and propagated to the gameserver from a server, not from a browser.
        As a consequence, there is no current player associated to this action. In your zombieTurn function,
        you must _never_ use getCurrentPlayerId() or getCurrentPlayerName(), otherwise it will fail with a "Not logged" error message.
    */

    function zombieTurn( $state, $active_player ) {
		$statename = $state['name'];

		if ($state['type'] == 'activeplayer') {
			switch ($statename) {
				// case 'playerBid': TODO discardCards
				// 	// Always pass
				// 	$this->pass();
				// 	return;

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
