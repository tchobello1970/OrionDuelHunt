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
 * gameoptions.inc.php
 *
 * OrionDuelHunt game options description
 *
 * In this file, you can define your game options (= game variants).
 *
 * Note: If your game has no variant, you don't have to modify this file.
 *
 * Note²: All options defined in this file should have a corresponding "game state labels"
 *        with the same ID (see "initGameStateLabels" in orionduelhunt.game.php)
 *
 * !! It is not a good idea to modify this file when a game is running !!
 *
 */

$game_options = [

    100 => [
        'name' => totranslate('Board Display'),
        'values' => [
                0 => [ 'name' => totranslate( 'Random'), 'tmdisplay' => totranslate('Random'), 'description' => totranslate('galaxies and black holes randomly placed') ],
                1 => [ 'name' => totranslate( 'Custom'), 'tmdisplay' => totranslate('Custom'), 'description' => totranslate('galaxies and black holes are placed by both players') ],
                2 => [ 'name' => totranslate( 'Predefined 1'), 'tmdisplay' => totranslate('Predefined 1'), 'description' => totranslate('predefined setup 1') ],
                3 => [ 'name' => totranslate( 'Predefined 2'), 'tmdisplay' => totranslate('Predefined 2'), 'description' => totranslate('predefined setup 2') ],
                ],
        'default' => 2
    ],
];


