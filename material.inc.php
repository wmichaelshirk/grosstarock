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
              'insuit' => clienttranslate('in Hearts'),
              'withoutsuit' => clienttranslate('the Heart'),
              'nametr' => self::_('Heart'),
              'symbol' => '&hearts;'],
              
  2 => array( 'name' => clienttranslate('Club'),
              'nameof' => clienttranslate('Clubs'),
              'insuit' => clienttranslate('in Clubs'),
              'withoutsuit' => clienttranslate('the Club'),
              'nametr' => self::_('Club'),
              'symbol' => '&clubs;'),
              
  3 => array( 'name' => clienttranslate('Diamond'),
              'nameof' => clienttranslate('Diamonds'),
              'insuit' => clienttranslate('in Diamonds'),
              'withoutsuit' => clienttranslate('the Diamond'),
              'nametr' => self::_('Diamond'),
              'symbol' => '&diams;'),
              
  4 => array( 'name' => clienttranslate('Spade'),
              'nameof' => clienttranslate('Spades'),
              'insuit' => clienttranslate('in Spades'),
              'withoutsuit' => clienttranslate('the Spade'),
              'nametr' => self::_('Spade'),
              'symbol' => '&spades;'),
              
  5 => array( 'name' => clienttranslate('Trump'),
              'nameof' => clienttranslate('Trumps'),
              'insuit' => clienttranslate('in Trumps'),
              'nametr' => self::_('Trump'),
              'symbol' => 'T'),
];


$this->figures = [
  11 => [
    'name' => clienttranslate('Jack'), // Jack = Valet = V
    'namepl' => clienttranslate('Jacks'),
    'nametr' => self::_('Jack'),
    'withoutrank' => clienttranslate('the Jack'),
    'symbol' => clienttranslate('J')//'&#9823;'
  ],
              // Danish: knægt
  12 => [
    'name' => clienttranslate('Knight'), // Knight = Cavalier = C
    'namepl' => clienttranslate('Knights'),
    'nametr' => self::_('Knight'),
    'withoutrank' => clienttranslate('the Knight'),
    'symbol' => clienttranslate('C') //'&#9822;'
  ],
              // German: Caval or Ritter
              // Danish: kaval
  13 => [
    'name' => clienttranslate('Queen'), // Queen = Dame = D
    'namepl' => clienttranslate('Queens'),
    'nametr' => self::_('Queen'),
    'withoutrank' => clienttranslate('the Queen'),
    'symbol' => clienttranslate('Q') //'&#9819;'
  ],
              // Danish: dame
  14 => [
    'name' => clienttranslate('King'), // King = Roi = R
    'namepl' => clienttranslate('Kings'),
    'namesg' => clienttranslate('a King'),
    'nametr' => self::_('King'),
    'withoutrank' => clienttranslate('the King'),
    'symbol' => clienttranslate('K')// '&#9818;'
  ],
              // Danish: konge
];

$this->trull = [
  // French: Atous-Tarots
  // German: Trull
  1 => ['name' => clienttranslate('The <b>Pagat</b>'),
        'nameof' => clienttranslate('The Pagat'),
        'symbol' => 'I'],
        // French: le Pagad? le Paguet? le Petit?
        // German: Der Pagat
        // Danish: pagaten
  21 => ['name' => clienttranslate('The <b>Mond</b>'),
        'nameof' => clienttranslate('The Mond'),
        'symbol' => 'XXI'],
        // French: le Monde
        // German: Der Mond
        // Danish: mondo
  22 => ['name' => clienttranslate('The <b>’Scuse</b>'), // Fool = Excuse
        'nameof' => clienttranslate('The ’Scuse'),
        'symbol' => '&#9733;'] // A star
        // French: l'Excuse
        // German: der Sküs
        // Danish: scus
];



$this->bonuses = [
  // pots
  'foundation' => [
    'name' => clienttranslate('Foundation')
    // French: enjeu
    // Danish: fundering
  ],
  'dealing' => [
    'name' => clienttranslate('Dealing')
  ],

  // declarations
  'trumpswith' => [
    'name' => clienttranslate('${num} Trumps <i>avec</i>')
    // Danish: ${num} tarokker med pagat
  ],
  'trumpswithout' => [
    'name' => clienttranslate('${num} Trumps <i>sans</i>')
    // French: atouts
  ],
  'matadors' => [
    'name' => clienttranslate('${num} Matadors')
    // French: matadores
  ],
  'halfcavalry' => [
    'name' => clienttranslate('Half Cavalry ${insuit} without ${withoutrank}')
    // French: "Petite Cavalerie" or possibly "Cavalerie par excuse" or "Chevalerie"
    // German:  Halbe Kavallerie
  ],
  'fullcavalry' => [
    'name' => clienttranslate('Full Cavalry ${insuit}')
    // French: "Grande Cavalerie" or possibly "Cavalerie entière"
    // German:  Ganze Kavallerie
  ],
  'abundantcavalry' => [
    'name' => clienttranslate('Abundant Cavalry ${insuit}')
    // danish: sprøjtefulde kavaleri
  ],
  'halfkings' => [
    'name' => clienttranslate('Half Kings without ${withoutsuit}')
    // French: Petits rois"
    // Danish: halve konger
  ],
  'fullkings' => [
    'name' => clienttranslate('Full Kings')
    // French: Grands rois
    // Danish: fulde konger
    // German: Königreich
  ],
  'abundantkings' => [
    'name' => clienttranslate('Abundant Kings')
    // Danish: sprøjtefulde konger
  ],

  // bonuses
  'lostking' => [
    'name' => clienttranslate('Lost a King')
  ],
  'lostpagat' => [
    'name' => clienttranslate('Lost the Pagat')
  ],
  'wonpagat' => [
    'name' => clienttranslate('Won the Pagat')
  ],

  // last tricks
  'slam' => [
    'name' => clienttranslate('Tout')
    // German: Vole
    // Danish: Tout
  ],
  'nill' => [
    'name' => clienttranslate('Misère')
    // German: Stichfreispiel / Stichfrei sein
    // Danish: Nolo
  ],
  'kingultimo' => [
    'name' => clienttranslate('King Ultimo')
  ],
  'lostkingultimo' => [
    'name' => clienttranslate('Failed King Ultimo')
  ],
  'pagatultimo' => [
    'name' => clienttranslate('Pagat Ultimo')
  ],
  'lostpagatultimo' => [
    'name' => clienttranslate('Failed Pagat Ultimo')
    // danish: bagud
  ],
  'lasttrick' => [
    'name' => clienttranslate('Last Trick')
  ],

  'points' => [
    'name' => clienttranslate('Points')
    // German: Augen
  ]

];
