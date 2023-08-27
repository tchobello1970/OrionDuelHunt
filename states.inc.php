<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * OrionDuelHunt implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * states.inc.php
 *
 * OrionDuelHunt game states description
 *
 */

/*
   Game state machine is a tool used to facilitate game developpement by doing common stuff that can be set up
   in a very easy way from this configuration file.

   Please check the BGA Studio presentation about game state to understand this, and associated documentation.

   Summary:

   States types:
   _ activeplayer: in this type of state, we expect some action from the active player.
   _ multipleactiveplayer: in this type of state, we expect some action from multiple players (the active players)
   _ game: this is an intermediary state where we don't expect any actions from players. Your game logic must decide what is the next game state.
   _ manager: special type for initial and final state

   Arguments of game states:
   _ name: the name of the GameState, in order you can recognize it on your own code.
   _ description: the description of the current game state is always displayed in the action status bar on
                  the top of the game. Most of the time this is useless for game state with "game" type.
   _ descriptionmyturn: the description of the current game state when it's your turn.
   _ type: defines the type of game states (activeplayer / multipleactiveplayer / game / manager)
   _ action: name of the method to call when this game state become the current game state. Usually, the
             action method is prefixed by "st" (ex: "stMyGameStateName").
   _ possibleactions: array that specify possible player actions on this step. It allows you to use "checkAction"
                      method on both client side (Javacript: this.checkAction) and server side (PHP: self::checkAction).
   _ transitions: the transitions are the possible paths to go from a game state to another. You must name
                  transitions in order to use transition names in "nextState" PHP method, and use IDs to
                  specify the next game state for each transition.
   _ args: name of the method to call to retrieve arguments for this gamestate. Arguments are sent to the
           client side to be used on "onEnteringState" or to set arguments in the gamestate description.
   _ updateGameProgression: when specified, the game progression is updated (=> call to your getGameProgression
                            method).
*/

//    !! It is not a good idea to modify this file when a game is running !!


$machinestates = array(

    // The initial state. Please do not modify.
    1 => array(
        "name" => "gameSetup",
        "description" => "",
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => array( "" => 3 )
    ),

    // Note: ID=2 => your first state

    3 => array(
        "name" => "gamePreparation",
        "description" => "",
        "type" => "game",
        "action" => "stGamePreparation",
        "transitions" => array( "playerChoice" => 11, "playerTurn" => 5 )
    ),

    5 => array(
            "name" => "playerTurn",
            "description" => clienttranslate('${actplayer} must place a Tile'),
            "descriptionmyturn" => clienttranslate('${you} must play a Tile'),
            "type" => "activeplayer",
            "possibleactions" => array( "playTile" ),
            "transitions" => array( "playTile" => 10, "endGame" => 99 )
    ),

    10 => [
        "name" => "nextPlayer",
        "description" => '',
        "type" => "game",
        "action" => "stNextPlayer",
        "transitions" => [ "playerTurn" => 5 ]
    ],


    11 => array(
            "name" => "galaxiesChoice",
            "description" => clienttranslate('${actplayer} must place Galaxies'),
            "descriptionmyturn" => clienttranslate('${you} must place Galaxies'),
            "type" => "activeplayer",
            "possibleactions" => array( "placeGalaxy" ),
            "transitions" => array( "nextPlayer" => 12 )
    ),


    12 => [
        "name" => "nextPlayerGalaxy",
        "description" => '',
        "type" => "game",
        "action" => "stNextPlayerGalaxy",
        "transitions" => [ "playerChoice" => 13 ]
    ],

    13 => array(
            "name" => "blackHolesChoice",
            "description" => clienttranslate('${actplayer} must place Black Holes'),
            "descriptionmyturn" => clienttranslate('${you} must place Black Holes'),
            "type" => "activeplayer",
            "possibleactions" => array( "placeBlackHole" ),
            "transitions" => array( "nextPlayer" => 14 )
    ),

    14 => [
        "name" => "nextPlayerBlackHole",
        "description" => '',
        "type" => "game",
        "action" => "stNextPlayerBlackHole",
        "transitions" => [ "firstPlayerTurn" => 15 ]
    ],

    15 => array(
            "name" => "firstPlayerTurn",
            "description" => clienttranslate('${actplayer} must place a Tile or pass'),
            "descriptionmyturn" => clienttranslate('${you} must place a Tile or pass'),
            "type" => "activeplayer",
            "possibleactions" => array( "placeTile", "pass" ),
            "transitions" => array( "playTile" => 5, "pass" => 10 )
    ),




    // Final state.
    // Please do not modify (and do not overload action/args methods).
    99 => array(
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    )

);



