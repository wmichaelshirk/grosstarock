/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel
 * Colin <ecolin@boardgamearena.com>
 * GrossTarock implementation : © W Michael Shirk <wmichaelshirk@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on
 * http://boardgamearena.com. See http://en.boardgamearena.com/#!doc/Studio
 * for more information.
 * -----
 *
 * grosstarock.js
 *
 * GrossTarock user interface script
 *
 * In this file, you are describing the logic of your user interface, in
 * Javascript.
 */

define([
    "dojo","dojo/_base/declare",
    "dojo/fx/easing",
    "ebg/core/gamegui",
    "ebg/counter",
    "ebg/stock"
],
function (dojo, declare, easing) {

    return declare("bgagame.grosstarock", ebg.core.gamegui, {

        constructor: function() {
            console.log('grosstarock constructor');

            // others
            this.card_width = 70;
            this.card_height = 129;

            // // TDM
            this.tdm_card_width = 72;
            this.tdm_card_height = 139;

        },

        /*
            Setup:

            This method must set up the game user interface according to current 
            game situation specified in parameters.

            The method is called each time the game interface is displayed to a 
            player, ie:
            _ when the game starts
            _ when a player refreshes the game page (F5)

            "gamedatas" argument contains all datas retrieved by your 
            "getAllDatas" PHP method.
        */

        setup: function (gamedatas) {
            console.log( "Starting game setup" );
            console.log(gamedatas)
            this.canPlayCard = false;

            this.numberOfPlayers = Object.keys(this.gamedatas.players)
                .length
            this.dealer = gamedatas.dealer
            this.scoreWithPots = !!Number(gamedatas.score_with_pots)


            // setup pots - if there are 3 players, and that option is
            // selected.
            if (this.numberOfPlayers == 2 || !this.scoreWithPots) {
                dojo.style('ultimo_pot_wrap', 'display', 'none')
            } else {
                this.pagatPot = new ebg.counter()
                this.pagatPot.create('pagatpot')
                this.pagatPot.setValue(gamedatas.pagatPot)
                this.kingPot = new ebg.counter()
                this.kingPot.create('kingpot')
                this.kingPot.setValue(gamedatas.kingPot)
            }

            // Setting up player boards
            for ( let player_id in gamedatas.players )  {
                var player = gamedatas.players[player_id];
                this.updatePlayerTrickCount(player.id, player.tricks)
                // TODO: Setting up players boards if needed
            }

            // TODO: Set up your game interface here, according to "gamedatas"

            // wire up preferences
            this.current_style_id = this.prefs[100].value - 1;
            dojo.query('.playerTables__card, #myhand_wrap')
                .addClass(`tarot_${this.current_style_id}`)
            dojo.connect($('preference_control_100'), 'onchange', this, 
                'onChangeForCardStyle')
            dojo.connect($('preference_fontrol_100'), 'onchange', this, 
                'onChangeForCardStyle')

            // Player hand widget
            this.playerHand = new ebg.stock();
            this.playerHand
                .create( this, $('myhand'), 
                this.current_style_id === 3 ? this.tdm_card_width : this.card_width,
                this.current_style_id === 3 ? this.tdm_card_height : this.card_height);
            this.playerHand.image_items_per_row = 14;
            this.playerHand.centerItems = true;
            this.playerHand.setOverlap( 75, 0 )
            this.playerHand.setSelectionAppearance('class');
            this.playerHand.setSelectionMode(1)
            dojo.connect(this.playerHand, 'onChangeSelection', this,
                'onPlayerHandSelectionChanged');


            for (let suit = 1; suit <= 5; suit++) {
                for (let value = 1; value <= (suit == 5 ? 23 : 14); value++) {
                    // Build card type id
                    let cardId = this.getCardUniqueId(suit, value);

                    // reverse the red suits
                    let positionInSprite = ((suit === 1 || suit === 3) &&
                            value <= 10) ?
                        (suit - 1) * 14 + (10 - value) :
                        (suit - 1) * 14 + value - 1

                    this.playerHand.addItemType(
                        cardId, // item Id 
                        cardId, // sorting "weight"
                        `${g_gamethemeurl}img/tarot_${this.current_style_id}.png`, // URL
                        positionInSprite);
                }
            }

            // Cards in player's hand
            for (let i in this.gamedatas.hand) {
                const card = this.gamedatas.hand[i];
                const suit = card.type;
                const value = card.type_arg;
                this.playerHand.addToStockWithId(
                    this.getCardUniqueId(suit, value),
                    card.id);
            }

            // Cards played on table
            for (let i in this.gamedatas.cardsontable) {
                const card = this.gamedatas.cardsontable[i];
                const suit = card.type;
                const value = card.type_arg;
                const player_id = card.location_arg;
                this.playCardOnTable(player_id, suit, value, card.id);
            }

            // move pots to dealer
            if (this.scoreWithPots) {
                const pots = dojo.byId('ultimo_pot_wrap')
                let dealerTarget
                if (Number(gamedatas.gamestate.id) <= 11) {
                    dealerTarget = this.getPlayerTableEl(this.dealer, 'pre-status')
                } else {
                    dealerTarget = this.getPlayerTableEl(this.dealer, 'status')
                }
                this.slideToObject(pots, dealerTarget).play()
            }

            // Hand counter
            this.updateHandCounter(gamedatas.current_hand, gamedatas.hands_to_play);
            
            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();



            console.log( "Ending game setup" );
        },


        ///////////////////////////////////////////////////
        //// Game & client states

        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function( stateName, args )
        {
            console.log( 'Entering state: '+stateName , args);

            if (stateName == 'playerTurn') {
                // if (!this.firstCardPlayed) {
                //     this.firstCardPlayed = true;
                //     var me = this;
                //     setTimeout(function() { me.hideAllBubbles(); }, 2000);
                // }
                if (this.isCurrentPlayerActive()) {
                    this.canPlayCard = true;
                    this.playerHand.setSelectionMode(1);
                }
            }

            if (stateName == 'nextPlayer') {
                if (this.isCurrentPlayerActive()) {
                    this.canPlayCard = true;
                    this.playerHand.setSelectionMode(1);
                }
            }

            // Highlight active player
			dojo.query('.playertable')
                .removeClass('playerTables__table--active')
            if (args.active_player) {
                this.getPlayerTableEl(args.active_player).classList
                    .add('playerTables__table--active')
        }


        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function (stateName) {
            console.log( 'Leaving state: '+stateName );

            switch( stateName )
            {

            /* Example:

            case 'myGameState':

                // Hide the HTML block we are displaying only during this game state
                dojo.style( 'my_html_block_id', 'display', 'none' );

                break;
           */


            case 'dummmy':
                break;
            }
        },

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //
        onUpdateActionButtons: function( stateName, args )
        {
            console.log( 'onUpdateActionButtons: '+stateName );

            if( this.isCurrentPlayerActive() ) {

                switch( stateName ) {
                    case 'discardCards':
                        // this.mustNotDiscard = args.cards;
                        this.canPlayCard = false;
                        // $('pagemaintitletext').innerHTML = _("Discard 3 cards")
                        this.addActionButton("btn_discard", _("Discard"), 
                            'onButtonClickForDiscard', null, false, 'blue');
                        this.playerHand.setSelectionMode(2);
                        break;


/*
                 Example:

                 case 'myGameState':

                    // Add 3 action buttons in the action status bar:

                    this.addActionButton( 'button_1_id', _('Button 1 label'), 'onMyMethodToCall1' );
                    this.addActionButton( 'button_2_id', _('Button 2 label'), 'onMyMethodToCall2' );
                    this.addActionButton( 'button_3_id', _('Button 3 label'), 'onMyMethodToCall3' );
                    break;
*/
                }
            }
        },

        ///////////////////////////////////////////////////
        //// Utility methods

        /*

            Here, you can defines some utility methods that you can use everywhere in your javascript
            script.

        */

        // Get card unique identifier based on its suit and value
        getCardUniqueId: function(suit, value) {
            return (suit-1) * 14 + (value-1)
        },

        setPreselectMode: function() {
            this.playerHand.setSelectionMode(this.prefs[100].value == 1 ? 2 : 0)
        },

        getCardsInHand: function() {
            return Object.keys(this.playerHand.getPresentTypeList())
                .map(Number)
                .sort((a, b) => a - b)
        },

        getSelectedCards: function() {
            return this.playerHand.getSelectedItems().map(item => item.id)
        },

        makeAjaxCall: function(methodName, args) {
            $('pagemaintitletext').innerHTML = _('Sending move to server...')
            $('generalactions').innerHTML = ''
            args.lock = true
            this.ajaxcall(
                `/${this.game_name}/${this.game_name}/${methodName}.html`,
                args, this, result=>{}, is_error=>{})
        },

        /*
         * Card management
         */
        
        // This function is called any time the selection changes, or if a 
        // automatic play can be checked
        // If only one card is selected and it is time to play it, the move is
        // sent to the server, Otherwise, nothing happens
        checkIfPlay: function(no_state_check) {
            const items = this.playerHand.getSelectedItems();

            if (!this.canPlayCard) return

            if (items.length === 1) {
                const action='playCard'

                if (no_state_check || this.checkAction(action, true)) {
                    // Can play a card
                    const card_id = items[0].id;
                    this.makeAjaxCall(action, { id: card_id })
                    this.playerHand.unselectAll();
                }
            }
        },

        playCardOnTable : function(playerId, suit, value, card_id) {
            // (nx, ny) : indices in sprite
            var nx = value - 1;
            var ny = suit - 1;
            if ((suit == 1 || suit == 3) && value <= 10) {
                nx = 9 - nx
            }
            if (value > 14) {
                nx %= 14;
                ny += 1;
            }
            const cardWidth = this.current_style_id == 3 ? this.tdm_card_width : this.card_width
            const cardHeight = this.current_style_id == 3 ? this.tdm_card_height : this.card_height

            let target = this.getPlayerTableEl(playerId, 'card')
            let cardEl = dojo.place(
                this.format_block('jstpl_cardontable', {
                    'x': cardWidth * nx,
                    'y': cardHeight * ny,
                    'player_id': playerId
                }), target)

            // player_id => direction
            
            if (playerId != this.player_id) {
                // Some opponent played a card
                // Move card from player panel
                let from = this.getPlayerTableEl(playerId, 'avatar-wrapper')
                this.placeOnObject(cardEl, from)
            } else {
                // You played a card. If it exists in your hand, move card from there and remove the corresponding item
                if($(`myhand_item_${card_id}`)) {
                    this.placeOnObject(cardEl, `myhand_item_${card_id}`);
                    this.playerHand.removeFromStockById(card_id);
                }
            }

            // In any case: move it to its final destination
            this.slideToObject(cardEl, target).play();
        },

        updatePlayerTrickCount: function (playerId, tricksWon) {
			// Update value
            this.getPlayerTableEl(playerId, 'tricksWonValue')
                .innerHTML = tricksWon
			// Update 'notempty' class
			const cls = 'playerTables__tricksWon--notEmpty'
			const method = tricksWon > 0 ? 'add' : 'remove'
			this.getPlayerTableEl(playerId, 'tricksWon').classList[method](cls)
        },
        
        updateHandCounter: function(current, total) {
            if (this.numberOfPlayers == 2) {
                dojo.style('hand_count_wrap', 'display', 'none');
            } else {
                $('hand_count_wrap').innerHTML = '<strong>' +
                        dojo.string.substitute( _('Hand ${n} of ${t}'), { n: current, t: total } ) +
                        '</strong>';
            }
        },

        /*
         * Bubble management
         */
        showBubble: function(player, message) {
            // 
             
            const itemId = this.getPlayerTableEl(player, 'bubble')
            $(itemId).innerHTML = message;
            dojo.style(itemId, { display: 'block', opacity: 1 });
        },

        hideBubble: function(player) {
            const itemId = this.getPlayerTableEl(player, 'bubble')
            if ($(itemId).style.opacity > 0) {
                dojo.fadeOut({ node: itemId, duration: 300 }).play()
            }
        },

        hideAllBubbles: function() {
            for (var player in this.gamedatas.players) {
                this.hideBubble(player)
            }
        },


		// Return a player element (with class .playerTables__<suffix>)
		// or the table wrapper if no suffix is given
		getPlayerTableEl: function(playerId, suffix) {
			let selector = '#playertable_' + playerId
			if (suffix) {
				selector += ' .playerTables__' + suffix
			}
			return dojo.query(selector)[0]
        },
        
        ///////////////////////////////////////////////////
        //// Player's action

        /*

            Here, you are defining methods to handle player's action (ex: results of mouse click on
            game objects).

            Most of the time, these methods:
            _ check the action is possible at this game state.
            _ make a call to the game server

        */
        onPlayerHandSelectionChanged: function () {
            this.checkIfPlay(false)
        },

        onButtonClickForDiscard: function(event) {
            if (this.checkAction('discard')) {
                var cardsInHand = this.getCardsInHand()
                console.log(cardsInHand)
                var toDiscard = 3
                var selected = this.getSelectedCards()
                console.log('selected ', selected)

                if (toDiscard != selected.length) {
                    var message = _('You must discard exactly 3')
                    this.showMessage(message, 'error');
                    return;
                }
                // TODO
                // for (var key in this.mustNotDiscard) {
                //     var keep = this.mustNotDiscard[key];
                //     if (selected.includes(String(keep))) {
                //         this.showMessage(_('You cannot discard kings, honours, or the card about to be called'), 'error');
                //         return;
                //     }
                // }
                // var discardableNonTrumps = this.countDiscardableNonTrumps();
                // var trumpsToDiscard = this.countTrumps(selected);
                // if (trumpsToDiscard > 0 && trumpsToDiscard > toDiscard - discardableNonTrumps) {
                //     this.confirmationDialog( _('Do you really want to discard more trumps than you are forced to?'),
                //         dojo.hitch(this, function() {
                //             this.makeAjaxCall('discard', { 'cards': selected.join(',') });
                //         })
                //     );
                
                // } else {
                    this.makeAjaxCall('discard', { 
                        'cards': selected.join(',') 
                    })
                // }
            }
        },

        /* Example:

        onMyMethodToCall1: function( evt )
        {
            console.log( 'onMyMethodToCall1' );

            // Preventing default browser reaction
            dojo.stopEvent( evt );

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'myAction' ) )
            {   return; }

            this.ajaxcall( "/grosstarock/grosstarock/myAction.html", {
                                                                    lock: true,
                                                                    myArgument1: arg1,
                                                                    myArgument2: arg2,
                                                                    ...
                                                                 },
                         this, function( result ) {

                            // What to do after the server call if it succeeded
                            // (most of the time: nothing)

                         }, function( is_error) {

                            // What to do after the server call in anyway (success or failure)
                            // (most of the time: nothing)

                         } );
        },

        */

        onChangeForCardStyle : function(event) {
            // Get the current style and the target style
            var select = event.currentTarget;
            var current_style = 'tarot_' + this.current_style_id;
            var new_style_id = select.options[select.selectedIndex].value - 1;
            var new_style = 'tarot_' + new_style_id;
            
            // Change style of cards on table.
            // Easy - change spritesheet. Tougher - realign.
            dojo.query('.' + current_style).addClass(new_style)
                .removeClass(current_style)
            dojo.query('.cardontable').forEach(card => {
                const oldWidth = this.current_style_id !== 3 ? this.card_width :
                    this.tdm_card_width
                const oldHeight = this.current_style_id !== 3 ? this.card_height :
                    this.tdm_card_height
                const newWidth = new_style_id !== 3 ? this.card_width :
                    this.tdm_card_width
                const newHeight = new_style_id !== 3 ? this.card_height :
                    this.tdm_card_height
                
                const [_, oldX, xunit] = card.style.backgroundPositionX
                    .match(/^(-?\d+)(.*)$/)
                const [__, oldY, yunit] = card.style.backgroundPositionY
                    .match(/^(-?\d+)(.*)$/)
                const newX =( oldX / oldWidth) * newWidth + xunit
                const newY = (oldY / oldHeight) * newHeight + yunit
                card.style.backgroundPositionX = newX
                card.style.backgroundPositionY = newY
            })
            
            // Set the new style for cards which will appear in the stocks
            var stock = this.playerHand
            for (j in stock.item_type) {
                var item = stock.item_type[j];
                if (j == 0) {
                    var image = item.image.replace(current_style, new_style);
                }
                item.image = image;
            }
            
            // Change style of the current visible cards in the stocks
            image = `url(${image})`
            dojo.query('.stockitem').style({
                backgroundImage: image,
                width: (new_style_id === 3 ? this.tdm_card_width :
                    this.card_width) + 'px',
                height: (new_style_id === 3 ? this.tdm_card_height : 
                    this.card_height) + 'px',
                
            })
            stock.updateDisplay();
            this.current_style_id = new_style_id;
        },
        

        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:

            In this method, you associate each of your game notifications with your local method to handle it.

            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your grosstarock.game.php file.

        */
        setupNotifications: function() {
            console.log( 'notifications subscriptions setup' );

            dojo.subscribe('newDeal', this, 'notifyNewDeal')
            dojo.subscribe('newHand', this, 'notifyNewHand')
            dojo.subscribe('discard', this, 'notifyDiscard')
            dojo.subscribe('discarded', this, 'notifyDealerDiscard')
            dojo.subscribe('declare', this, 'notifyDeclarations')
            this.notifqueue.setSynchronous('declare', 1000)
            dojo.subscribe('checkForAutomaticPlay', this,  "notifCheckForAutomaticPlay")
            dojo.subscribe('playCard', this, 'notifyPlayCard')
            dojo.subscribe('trickWin', this, 'notifyTrickWin')
            this.notifqueue.setSynchronous('trickWin', 1000)
            dojo.subscribe('giveAllCardsToPlayer', this, 'notifyGiveAllCardsToPlayer')
            dojo.subscribe('newScores', this, 'notifyNewScores')
            dojo.subscribe('handResult', this, 'notifyHandResult');


            dojo.subscribe('log', this, 'notifyLog')
        },

        notifyNewDeal: function (notif) {
            this.updateHandCounter(notif.args.current_hand, 
                notif.args.hands_to_play);
            for ( let player_id in this.gamedatas.players) {
                this.updatePlayerTrickCount(player_id, 0)
            }
            this.dealer = notif.args.dealer_id
            if (this.scoreWithPots) {
                const pots = dojo.byId('ultimo_pot_wrap');
                const target = this.getPlayerTableEl(this.dealer, 'pre-status')
                this.slideToObject(pots, target, 1000).play()
            }
        },

        notifyNewHand: function (notif) {
            this.playerHand.removeAll()
            for (let i in notif.args.cards) {
                let card = notif.args.cards[i]
                let suit = card.type
                let value = card.type_arg
                this.playerHand.addToStockWithId(
                    this.getCardUniqueId(suit, value),
                    card.id)
            }
        },

        notifyDiscard: function (notif) {
            if (this.scoreWithPots) {
                const pots = dojo.byId('ultimo_pot_wrap');
                const target = this.getPlayerTableEl(this.dealer, 'status')
                this.slideToObject(pots, target, 1000).play()
            }
        },

        notifyDealerDiscard: function (notif) {
            console.log(notif)
            notif.args.cards.forEach(card => 
                this.playerHand.removeFromStockById(
                    card, this.getPlayerTableEl(this.player_id, 'card')))
            this.setPreselectMode();
        },

        notifyDeclarations: function (notif) {
            const player = notif.args.player_id
            const message = notif.args.declarations
            this.showBubble(player, message)
        },

        notifyPlayCard: function (notif) {
            // play card on the table
            this.playCardOnTable(notif.args.player_id, notif.args.suit, 
                notif.args.value, notif.args.card_id);
        },

        notifCheckForAutomaticPlay: function(notif) {
            // Check if the player asked for an automatic move, having 
            // preselected his card to play before
            // Automatic play if a card is selected, with error message if this 
            // is not a legal move
            this.checkIfPlay(true);
        },

        notifyTrickWin : function(notif) {
            this.hideAllBubbles()

            this.updatePlayerTrickCount(notif.args.player_id,
                notif.args.trick_won)

            // BELOTE COINCHE: clear the old tricks from logs.
            // var me = this
			// setTimeout(function() {
			// 	me.giveAllCardsToPlayer(notif.args.player_id).then(function() {
			// 		me.clearOldTricksLogs(notif.args.trick_count_value - 1)
			// 		me.updatePlayerTrickCount(notif.args.player_id, notif.args.trick_won)
			// 	})
			// }, 1500)
            
            
            // We do nothing here (just wait in order players can view the 4
            // cards played before they're gone.
        },

        notifyGiveAllCardsToPlayer : function(notif) {
            // Move all cards on table to given table, then destroy them
            const winner_id = notif.args.player_id;

            for ( let player_id in this.gamedatas.players) {
                let b_winning_card = (winner_id == player_id);
                console.log(b_winning_card, 11+b_winning_card);
                // Ensure that the winning card stays on top
                let cardEl = dojo.byId(`cardontable_${player_id}`)
                dojo.style(cardEl, 'z-index', 11+b_winning_card); 

                // Move all cards to winner - except, possibly, the fool.
                let playerToId
                if (notif.args.fool_owner_id !== undefined && 
                        player_id == notif.args.fool_owner_id) {
                    playerToId = notif.args.fool_to_id;
                } else {
                    playerToId = winner_id;
                }
                const target = this.getPlayerTableEl(playerToId, 'avatar')

                let anim = this.slideToObject(cardEl, target, 1000);

                if (b_winning_card || player_id == notif.args.fool_owner_id) {
                    self = this;
                    // 2. Delete it (1 second for the top card)
                    dojo.connect(anim, 'onEnd', function(node) {self.fadeOutAndDestroy(cardEl, 1000)})
                } else {
                    // 2. Delete it (immediately for card under the top card)
                    dojo.connect(anim, 'onEnd', function(node) {dojo.destroy(cardEl)});
                }

                // dojo.connect(anim, 'onEnd', function(node) {
                //     dojo.destroy(cardEl)
                // });
                anim.play();
            }
        },

        notifyNewScores: function(notif) {
            // Update players' scores
            console.log('new scores', notif)
            for (let player_id in notif.args.newScores) {
                this.scoreCtrl[player_id].toValue(notif.args.newScores[player_id])
            }
            if (this.kingPot && notif.args.kingPot) {
                this.kingPot.setValue(notif.args.kingPot)
            }
            if (this.pagatPot && notif.args.pagatPot) {
                this.pagatPot.setValue(notif.args.pagatPot)
            }
        },

        notifyHandResult: function(notification) {
            this.showResultDialog(notification.args);
        },

        showResultDialog: function(args) {

            console.log(args)
            // Announcements
            // in play
            // ultimos
            // nill / slam
            // cards
            const table = this.buildScoreTable(args)
            var dialogHtml = `<div style="text-align: center;">
                <div style="margin-top: 25px; margin-bottom: 15px;">testing</div>
                ${table}
                <a class="bgabutton bgabutton_blue" id="btn_scoringDialog_ok">
                    ${_('OK')}
                </a>
            </div>`

            const scoreDialogue = new ebg.popindialog();
            scoreDialogue.create('scoringDialog');
            scoreDialogue.setTitle( _("Result of this hand") );
            scoreDialogue.setContent( dialogHtml );
            scoreDialogue.show();

            dojo.connect($('btn_scoringDialog_ok'), 'onclick', this, evt => {
                evt.preventDefault()
                scoreDialogue.destroy()
            })
        },

        notifyLog: function(notif) {
            console.log(notif)
        },

        /* */
        buildScoreTable: function(args) {
            const scoringFeatures = Object.values(args.scores)[0].map(s => s[0])
            const players = Object.keys(args.scores)
            console.log(scoringFeatures)
            return `<table class="scoringTable">
                <thead>
                    <tr><th></th>
                        ${players.map(p => `<th>${p}</th>`)}
                    </tr>
                </thead>
                <tbody>
                ${scoringFeatures.map((feature, i) => 
                    `<tr>
                        <th>${_(feature)}</th>
                        ${players.map(p => `<td>${args.scores[p][i][1] || ''}</td>`)}
                    </tr>`)
                }
                </tbody>
                <tfoot>
                    <tr><th>${_('Total')}</th>
                        ${players.map(p => `<td>${args.points[p] || ''}</td>`)}
                    </tr>
                </tfoot>
            </table>`
        },
   });
});
