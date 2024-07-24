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

$machinestates =
[
    // The initial state. Please do not modify.
    1 => [
        "name" => "gameSetup",
        "description" => "",
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => [ "" => 3 ]
    ],

    3 => [
        "name" => "gamePreparation",
        "description" => "",
        "type" => "game",
        "action" => "stGamePreparation",
        "transitions" => [ "playerChoice" => 11, "playerTurn" => 5 ]
    ],

    5 => [
        "name" => "playerTurn",
        "description" => clienttranslate('${actplayer} must place a Tile'),
        "descriptionmyturn" => clienttranslate('${you} must play a Tile'),
        "type" => "activeplayer",
        "action" => "stPlayerTurn",
        "possibleactions" => [ "placeTile" ],
        "updateGameProgression" => true,
        "transitions" => [ "nextPlayer" => 10, "zombiePass" => 10, "endGame" => 99 ]
    ],

    10 => [
        "name" => "nextPlayer",
        "description" => '',
        "type" => "game",
        "action" => "stNextPlayer",
        "transitions" => [ "playerTurn" => 5 ]
    ],

    11 => [
        "name" => "galaxiesChoice",
        "description" => clienttranslate('${actplayer} must place 8 Galaxies'),
        "descriptionmyturn" => clienttranslate('${you} must place 8 Galaxies'),
        "type" => "activeplayer",
        "possibleactions" => [ "placeGalaxy" ],
        "transitions" => [ "nextPlayer" => 12, "zombiePass" => 22 ]
    ],

    12 => [
        "name" => "nextPlayerGalaxy",
        "description" => '',
        "type" => "game",
        "action" => "stNextPlayerGalaxy",
        "transitions" => [ "playerChoice" => 13 ]
    ],

    13 => [
        "name" => "blackHolesChoice",
        "description" => clienttranslate('${actplayer} must place 7 Black Holes'),
        "descriptionmyturn" => clienttranslate('${you} must place 7 Black Holes'),
        "type" => "activeplayer",
        "possibleactions" => [ "placeBlackHole" ],
        "transitions" => [ "nextPlayer" => 14, "zombiePass" => 14 ]
    ],

    14 => [
        "name" => "nextPlayerBlackHole",
        "description" => '',
        "type" => "game",
        "action" => "stNextPlayerBlackHole",
        "transitions" => [ "firstPlayerTurn" => 15 ]
    ],

    15 => [
        "name" => "firstPlayerTurn",
        "description" => clienttranslate('${actplayer} must place a Tile or pass'),
        "descriptionmyturn" => clienttranslate('${you} must place a Tile or pass'),
        "type" => "activeplayer",
        "possibleactions" => [ "placeTile", "pass" ],
        "transitions" => [ "nextPlayer" => 5, "pass" => 5, "zombiePass" => 5 ]
    ],

    // Final state.
    // Please do not modify (and do not overload action/args methods).
    99 => [
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    ]

];



