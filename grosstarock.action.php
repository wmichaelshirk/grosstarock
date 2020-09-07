<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * GrossTarock implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 * 
 * grosstarock.action.php
 *
 * GrossTarock main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *       
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/grosstarock/grosstarock/myAction.html", ...)
 *
 */
  
  
class action_grosstarock extends APP_GameAction { 

    // Constructor: please do not modify
   	public function __default() {

  	    if( self::isArg( 'notifwindow') ) {
            $this->view = "common_notifwindow";
  	        $this->viewArgs['table'] = self::getArg( "table", AT_posint, true );
  	    } else {
            $this->view = "grosstarock_grosstarock";
            self::trace( "Complete reinitialization of board game" );
        }
  	} 
  	


    /*
    
    Example:
  	
    public function myAction()
    {
        self::setAjaxMode();     

        // Retrieve arguments
        // Note: these arguments correspond to what has been sent through the javascript "ajaxcall" method
        $arg1 = self::getArg( "myArgument1", AT_posint, true );
        $arg2 = self::getArg( "myArgument2", AT_posint, true );

        // Then, call the appropriate method in your game logic, like "playCard" or "myAction"
        $this->game->myAction( $arg1, $arg2 );

        self::ajaxResponse( );
    }
    
    */
    public function playCard() {
      self::setAjaxMode();
      $card_id = self::getArg("id", AT_posint, true);
      $this->game->playCard($card_id);
      self::ajaxResponse();
    }

    public function discard() {
        self::setAjaxMode();
        $arg = self::getArg( 'cards', AT_numberlist, true );
        $cards = $arg == '' ? array() : explode( ',', $arg );
        $this->game->discard( $cards );
        self::ajaxResponse();
    }

    public function nameScuse() {
        self::setAjaxMode();
        $suit = self::getArg('suit', AT_posint, true);
        $this->game->nameScuse($suit);
        self::ajaxResponse();
    }

    public function requireScuse() {
        self::setAjaxMode();
        $this->game->requireScuse();
        self::ajaxResponse();
    }

}
  

