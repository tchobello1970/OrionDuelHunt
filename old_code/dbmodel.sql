
-- ------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- OrionDuelHunt implementation : © <Your name here> <Your email address here>
--
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----

-- dbmodel.sql

CREATE TABLE IF NOT EXISTS `board` (
  `board_square` int(10) unsigned NOT NULL,
  `board_color` int(10) unsigned NOT NULL,
  `board_black_hole` int(10) unsigned NOT NULL,
  `board_galaxy` int(10) unsigned NOT NULL,
  `board_new` int(10) unsigned NOT NULL,
  `board_color_save` int(10) unsigned NOT NULL,
  PRIMARY KEY (`board_square`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;

 CREATE TABLE IF NOT EXISTS `gamestats` (
  `stat_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `stat_type` varchar(20) NOT NULL,
  `stat_save` int(2) NOT NULL,
  `stat_inc` int(2) NOT NULL,
  PRIMARY KEY (`stat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

 CREATE TABLE IF NOT EXISTS `tile` (
   `tile_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
   `tile_type` smallint(5) NOT NULL,
   `tile_location` varchar(16) NOT NULL,
   `tile_location_save` varchar(16) NOT NULL,
   PRIMARY KEY (`tile_id`)
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

 ALTER TABLE `player` ADD `player_constel_win` INT unsigned NOT NULL;
 ALTER TABLE `player` ADD `player_galaxy_win` INT unsigned NOT NULL;
 ALTER TABLE `player` ADD `player_black_hole_win` INT unsigned NOT NULL;
