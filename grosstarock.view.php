<?php
/**
*------
* BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel
* Colin <ecolin@boardgamearena.com>
* GrossTarock implementation : © W Michael Shirk <wmichaelshirk@gmail.com>
*
* This code has been produced on the BGA studio platform for use on
* http://boardgamearena.com.  See http://en.boardgamearena.com/#!doc/Studio
* for more information.
* -----
*/

require_once( APP_BASE_PATH."view/common/game.view.php" );

class view_grosstarock_grosstarock extends game_view {

    function getGameName() {
        return "grosstarock";
    }

    function build_page( $viewArgs ) {

        // Get players & players number
        $players = $this->game->loadPlayersBasicInfos();
        $players_nbr = count( $players );

        $players_nbr_class = "two_players";
        if ($players_nbr == 3) {
            $players_nbr_class = "three_players";
        } else if ($players_nbr == 4) {
            $players_nbr_class = "four_players";
        }

        $template = self::getGameName() . "_" . self::getGameName();

        // Rotat players so that I'm in the south
        $player_to_dir = $this->game->getPlayerRelativePositions();

        $this->page->begin_block( $template, "player" );
        foreach( $player_to_dir as $player_id => $dir ) {
            $this->page->insert_block( "player", array(
                "PLAYER_ID" => $player_id,
                "PLAYER_NAME" => $players[$player_id]['player_name'],
                "PLAYER_COLOR" => $players[$player_id]['player_color'],
                "PLAYER_AVATAR_URL_184" => $this->getPlayerAvatar(
                    $players[$player_id], '184'
                ),
                "DIR" => $dir )
            );
        }

        $this->tpl['NAME_SCUSE'] = self::_("Please name the ’Scuse");
        $this->tpl['HAND'] = self::_("Hand");
        $this->tpl['NBR'] = $players_nbr_class;
        $this->tpl['CHANGE_STYLE'] = self::_("Change card style");
    }

    /* From "coinche" */
    private function getPlayerAvatar($player, $size) {
		return get_avatar_filename($player['player_id'], $player['player_avatar'], $size);
	}
}


