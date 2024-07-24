<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * OrionDuel implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * stats.inc.php
 *
 * OrionDuel game statistics description
 *
 */

$stats_type =
[
    // Statistics global to table
    "table" =>
    [
        "turns_nb" => [
            "id"=> 10,
            "name" => totranslate("Number of turns"),
            "type" => "int" ],
    ],

    // Statistics existing for each player
    "player" =>
    [
        "turns_nb" => [
            "id"=> 10,
            "name" => totranslate("Number of turns"),
            "type" => "int" ],
        "galaxies_taken" => [
            "id"=> 11,
            "name" => totranslate("Number of Galaxies picked"),
            "type" => "int" ],
        "black_holes_taken" => [
            "id"=> 12,
            "name" => totranslate("Number of Black Holes given"),
            "type" => "int" ],
        "constel_road_win" => [
            "id"=> 13,
            "name" => totranslate("Constellations Link"),
            "type" => "int" ],
        "galaxy_win" => [
            "id"=> 14,
            "name" => totranslate("Galaxies Connection"),
            "type" => "int" ],
        "black_hole_win" => [
            "id"=> 15,
            "name" => totranslate("Black Holes Connection"),
            "type" => "int" ],
    ]
];