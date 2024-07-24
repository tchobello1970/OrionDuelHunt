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
 * material.inc.php
 *
 * OrionDuel game material description
 *
 * Here, you can describe the material of your game with PHP variables.
 *
 * This file is loaded in your game logic class constructor, ie these variables
 * are available everywhere in your game logic code.
 *
 */

$this->board_squares = [3,4,5,6,12,13,14,15,16,17,18,21,22,23,24,25,26,27,28,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,51,52,53,54,55,56,57,58,59,60,61,62,63,64,65,66,67,68,69,71,72,73,74,75,76,77,78,79,81,82,83,84,85,86,87,88,92,93,94,95,96,97,98,103,104,105,106];

$this->tiles = [1,2,3,3,3,4,4,4,5,5,5,6,6,6,7,8,9,9,9,10,10,10,11,11,11,12,12,12];

$this->constel_names = [
    'canis_major' => clienttranslate( "Canis Major" ),
    'gemini' => clienttranslate( "Gemini" ),
    'monoceros' => clienttranslate( "Monoceros" ),
    'taurus' => clienttranslate( "Taurus" ),
    'lepus' => clienttranslate( "Lepus" ),
    'eridanus' => clienttranslate( "Eridanus" )
    ];

$this->galaxies_1 = [ 13,17,35,51,59,75,93,97 ];
$this->galaxies_2 = [ 3,6,35,51,59,75,103,106 ];
$this->galaxies_3 = [ 4,32,36,39,71,74,78,105 ];
$this->black_holes_1 = [ 15,32,38,55,72,78,95 ];
$this->black_holes_2 = [ 12,15,18,55,92,95,98 ];
$this->black_holes_3 = [ 17,24,47,55,62,85,93 ];

// 3 is first up on Taurus left

$this->random_galaxies = [
   0 => [ 5,15,22,27,28,33,51,55,56,60,71,78,79,83,93,96,106],
   1 => [ 5,12,15,21,28,38,44,47,51,52,55,78,79,83,93,96,106],
   2 => [ 4,14,18,27,32,42,49,55,59,65,71,72,88,94,98,104],
   3 => [14,17,18,24,31,32,39,49,64,71,74,78,79,81,105,106], 
   4 => [ 4,14,17,18,23,40,41,45,56,59,68,73,82,97,98,104],
   5 => [ 5, 6,12,16,22,39,49,51,52,57,75,81,85,92,98], 
   6 => [ 3, 4,14,18,28,31,40,46,62,63,69,76,79,103,104,105], 
   7 => [ 3,13,14,18,27,31,41,49,54,55,81,82,87,88,95,104], 
   8 => [ 3,12,13,17,18,35,40,41,44,58,59,75,76,82,92,105,106], 
   9 => [ 4, 5, 6,12,21,28,31,46,53,57,79,82,92,96,105,106]    
    ];

$this->random_black_holes = [
   0 => [ 3,31,35,36,48,53,58,62,75,76,98],
   1 => [23,26,33,36,59,63,66,71,76,81,104], 
   2 => [16,34,40,46,47,51,63,74,77,86,92],
   3 => [ 5,12,37,44,47,52,58,62,76,86,93,94], 
   4 => [12,21,25,38,43,47,54,60,61,77,84],
   5 => [14,24,31,37,54,55,77,78,83,105,106], 
   6 => [25,26,33,42,55,58,67,71,83,84,97,98], 
   7 => [ 5, 6,33,36,58,61,62,68,75,76,106], 
   8 => [ 5,15,33,38,56,62,63,78,87,94], 
   9 => [25,26,33,48,59,60,61,64,75,87,103] 
    ];


/* even or odd is about floor int division and not modulo*/
$this->adjacent_hexes_even = [ -10,-9,-1,1,10,11 ];
$this->adjacent_hexes_49_69 = [ -10,-1,10 ];
$this->adjacent_hexes_40_60 = [ -10,-9,1,10,11 ];
$this->adjacent_hexes_odd = [ -11,-10,-1,1,9,10 ];
$this->adjacent_hexes_39_59 = [ -11,-10,-1,9,10 ];

$this->eridanus = [69,79,88,98];
$this->canis_major = [12,21,31,40];
$this->taurus = [103,104,105,106];
$this->monoceros = [3,4,5,6];
$this->lepus = [18,28,39,49];
$this->gemini = [60,71,81,92];