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
 * material.inc.php
 *
 * OrionDuelHunt game material description
 *
 * Here, you can describe the material of your game with PHP variables.
 *
 * This file is loaded in your game logic class constructor, ie these variables
 * are available everywhere in your game logic code.
 *
 */



$this->board_squares = [3,4,5,6,12,13,14,15,16,17,18,21,22,23,24,25,26,27,28,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,51,52,53,54,55,56,57,58,59,60,61,62,63,64,65,66,67,68,69,71,72,73,74,75,76,77,78,79,81,82,83,84,85,86,87,88,92,93,94,95,96,97,98,103,104,105,106];

$this->tiles = [1,2,3,3,3,4,4,4,5,5,5,6,6,6,7,8,9,9,9,10,10,10,11,11,11,12,12,12];

$this->galaxies_1 = [ 13,17,35,51,59,75,93,97 ];
$this->galaxies_2 = [ 3,6,35,51,59,75,103,106 ];
$this->black_holes_1 = [ 15,32,38,55,72,78,95 ];
$this->black_holes_2 = [ 12,15,18,55,92,95,98 ];

/* even or odd is about floor int division and not modulo*/
$this->adjacent_hexes_even = [ -10,-9,-1,1,10,11 ];
$this->adjacent_hexes_49 = [ -10,-1,10 ];
$this->adjacent_hexes_40_60 = [ -10,-9,1,10,11 ];
$this->adjacent_hexes_odd = [ -11,-10,-1,1,9,10 ];
$this->adjacent_hexes_39_59 = [ -11,-10,-1,9,10 ];

$this->eridanus = [69,79,88,98];
$this->canis_major = [12,21,31,40];
$this->taurus = [103,104,105,106];
$this->monoceros = [3,4,5,6];
$this->lepus = [18,28,39,49];
$this->gemini = [60,71,81,92];