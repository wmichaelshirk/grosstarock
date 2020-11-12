
-- ------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & 
-- Emmanuel Colin <ecolin@boardgamearena.com>
-- GrossTarock implementation : © W Michael Shirk <wmichaelshirk@gmail.com>
-- 
-- This code has been produced on the BGA studio platform for use on 
-- http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----

-- dbmodel.sql

-- This is the file where you are describing the database schema of your game
-- Basically, you just have to export from PhpMyAdmin your table structure and 
-- copy/paste this export here.
-- Note that the database itself and the standard tables ("global", "stats", 
-- "gamelog" and "player") are already created and must not be created here

-- Note: The database schema is created from this file when the game starts. 
--   If you modify this file, you have to restart a game to see your changes in
--   the database.


-- Standard schema to manage cards
CREATE TABLE IF NOT EXISTS `card` (
  `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `card_type` varchar(16) NOT NULL COMMENT 'Suit',
  `card_type_arg` int(11) NOT NULL COMMENT 'Rank',
  `card_location` varchar(16) NOT NULL COMMENT 'Deck, hand, cardontable, taken',
  `card_location_arg` int(11) NOT NULL COMMENT 'Id of owner',
  `card_points` int(11) NOT NULL DEFAULT 0 COMMENT 'Card point value',
  PRIMARY KEY (`card_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- Table to manages bonuses as they're completed
CREATE TABLE IF NOT EXISTS `bonuses` (
  `bonus_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `bonus_name` varchar(64) NOT NULL COMMENT '${arg} Matadors, etc',
  `bonus_arg` varchar(16) NULL COMMENT '3, 10, in Hearts, etc',
  `bonus_arg2` varchar(16) NULL COMMENT 'without the Queen, etc',
  `bonus_player` int(11) NOT NULL COMMENT 'Id of owner',
  `bonus_value` int(11) NULL COMMENT '5, 10, 15, etc',
  `bonus_pot_value` int(11) NOT NULL DEFAULT 0 COMMENT 'Pot value if applicable',
  PRIMARY KEY (`bonus_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


-- Add a custom field to the standard "player" table
ALTER TABLE `player` ADD `player_trick_number` int(10) NOT NULL DEFAULT 0 COMMENT 'Number of tricks collected by the player during this hand';


