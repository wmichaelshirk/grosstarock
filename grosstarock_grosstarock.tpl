{OVERALL_GAME_HEADER}

<!-- 
--------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel 
-- Colin <ecolin@boardgamearena.com>
-- GrossTarock implementation : © W Michael Shirk <wmichaelshirk@gmail.com>
-- 
-- This code has been produced on the BGA studio platform for use on 
-- http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------

-->
<div id="wrap">
    <div id="table">
        <div id="playertables" class="{NBR}">
            <!-- BEGIN player -->
            <div id="playertable_{PLAYER_ID}" 
                class="playertable  playertable_{DIR}">  <!--whiteblock-->
                <div class="playerTables__card"></div>
                <div class="playerTables__status"></div>
                <div class="playerTables__pre-status"></div>
                <div class="playerTables__tricksWon">
                    <span class="playerTables__tricksWonIcon"></span>
                    <span class="playerTables__tricksWonValue"></span>
                </div>

                <div class="playerTables__avatar-wrapper">
                    <div class="playerTables__firstMarker"></div>
                    <div class="playerTables__counterMarker"></div>
                    <div class="playerTables__avatar"
                        style="background-image: url({PLAYER_AVATAR_URL_184})"
                    ></div>
                    <div class="playerTables__bubble"></div>
                    <div class="playerTables__name"
                        style="background-color:#{PLAYER_COLOR}b0">
                        <span>{PLAYER_NAME}</span>
                    </div>
                </div>

               <!-- <div class="playertablename" style="color:#{PLAYER_COLOR}">
                    {PLAYER_NAME}
                    <div id="speech_bubble_{PLAYER_ID}" class="speech_bubble"></div>
                </div> 
                <div id="taker_icon_spot_{PLAYER_ID}" class="taker icon_spot"></div>
                <div id="playertablecard_{PLAYER_ID}" class="playertablecard"></div>
                <div id="dealer_icon_spot_{PLAYER_ID}" class="dealer icon_spot"></div>-->
            </div>
            <!-- END player -->

            <div id="turn_order">
                ↺
            </div>
            <div id='ultimo_pot_wrap'>
                <div class="pot">
                    <span id="kingpot"></span>
                    <span>&#x2654;</span>
                </div>
                <div class="pot">
                    <span id="pagatpot"></span>
                    <span style="font-family: serif; font-weight: bold">I</span>
                </div>
            </div>
        </div>
        
    </div>
    <div class="scusePanel">
        <div class="scusePanel__title">
            {NAME_SCUSE}
        </div>
        <div class="scusePanel__suits">
            <a class="scusePanel__btn scusePanel__btn--suit" data-suit="1">
                <span class="card-suit-icon card-suit-icon--heart"/>
            </a>
            <a class="scusePanel__btn scusePanel__btn--suit" data-suit="2">
                <span class="card-suit-icon card-suit-icon--club"/>
            </a>
            <a class="scusePanel__btn scusePanel__btn--suit" data-suit="3">
                <span class="card-suit-icon card-suit-icon--diamond"/>
            </a>
            <a class="scusePanel__btn scusePanel__btn--suit" data-suit="4">
                <span class="card-suit-icon card-suit-icon--spade"/>
            </a>
            <a class="scusePanel__btn scusePanel__btn--suit" data-suit="5">
                <span class="card-suit-icon card-suit-icon--trump"/>
            </a>
        </div>
    </div>
    <div id="right">
        <div id='hand_count_wrap' class='whiteblock tarok_sidebox'></div>
    </div>
</div>
  
<div id="myhand_wrap">
    <div id="myhand"></div>
</div>

<script type="text/javascript">

// Javascript HTML templates

var jstpl_cardontable = '<div class="cardontable" id="cardontable_${player_id}" style="background-position:-${x}px -${y}px">\
                        </div>';
</script>

{OVERALL_GAME_FOOTER}
