<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & 
 * Emmanuel Colin <ecolin@boardgamearena.com>
 * GrossTarock implementation : © W Michael Shirk <wmichaelshirk@gmail.com>
 * 
 * This code has been produced on the BGA studio platform for use on 
 * http://boardgamearena.com. See http://en.boardgamearena.com/#!doc/Studio for 
 * more information.
 * -----
 *
 * material.inc.php
 *
 * GrossTarock game material description
 *
 * Here, you can describe the material of your game with PHP variables.
 *   
 * This file is loaded in your game logic class constructor, ie these variables
 * are available everywhere in your game logic code.
 *
 */


$this->suits = [
  1 => ['name' => clienttranslate('Heart'),
              'nameof' => clienttranslate('Hearts'),
              'nametr' => self::_('Heart'),
              'symbol' => '&hearts;'],
              
  2 => array( 'name' => clienttranslate('Club'),
              'nameof' => clienttranslate('Clubs'),
              'nametr' => self::_('Club'),
              'symbol' => '&clubs;'),
              
  3 => array( 'name' => clienttranslate('Diamond'),
              'nameof' => clienttranslate('Diamonds'),
              'nametr' => self::_('Diamond'),
              'symbol' => '&diams;'),
              
  4 => array( 'name' => clienttranslate('Spade'),
              'nameof' => clienttranslate('Spades'),
              'nametr' => self::_('Spade'),
              'symbol' => '&spades;'),
              
  5 => array( 'name' => clienttranslate('Trump'),
              'nameof' => clienttranslate('Trumps'),
              'nametr' => self::_('Trump'),
              'symbol' => ''),
];


$this->figures = [
  11 => array('name' => clienttranslate('Jack'), // Jack = Valet = V
              'namepl' => clienttranslate('Jacks'),
              'nametr' => self::_('Jack'),
              'symbol' => '&#9823;'),
  12 => array('name' => clienttranslate('Knight'), // Knight = Cavalier = C
              'namepl' => clienttranslate('Knights'),
              'nametr' => self::_('Knight'),
              'symbol' => '&#9822;'),
  13 => array('name' => clienttranslate('Queen'), // Queen = Dame = D
              'namepl' => clienttranslate('Queens'),
              'nametr' => self::_('Queen'),
              'symbol' => '&#9819;'),
  14 => array('name' => clienttranslate('King'), // King = Roi = R
              'namepl' => clienttranslate('Kings'),
              'namesg' => clienttranslate('a King'),
              'nametr' => self::_('King'),
              'symbol' => '&#9818;'),
];

$this->trull = array(
  1 => ['name' => clienttranslate('the Pagat'),
        'nameof' => clienttranslate('The Pagat'),
        'symbol' => 'I'],
  21 => ['name' => clienttranslate('the Mond'),
        'nameof' => clienttranslate('The Mond'),
        'symbol' => 'XXI'],
  22 => ['name' => clienttranslate('the ’Scuse'), // Fool = Excuse
        'nameof' => clienttranslate('The ’Scuse'),
        'symbol' => '&#9733;'] // A star
);

$this->games = [
    1 => [
      'name' => clienttranslate('Null'), // nill, null, nolo, misère?
      'nameof' => clienttranslate('Null')
    ]
];
