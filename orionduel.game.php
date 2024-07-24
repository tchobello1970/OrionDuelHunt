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
  * orionduel.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */


require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );

define('BLUE_COLOR','0eb0cc');
define('ORANGE_COLOR','fba930');

class OrionDuel extends Table
{
    function __construct( )
    {
        parent::__construct();

        self::initGameStateLabels( [
            "board_display"        => 100
        ] );
    }

    protected function getGameName( )
    {
        // Used for translations and stuff. Please do not modify.
        return "orionduel";
    }

    /*
        setupNewGame:

        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame( $players, $options = array() )
    {
        self::createPlayers( $players );
        self::createStatistics();
        self::initializeGame();

        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();

        /************ End of the game initialization *****/
    }

    function createPlayers ( $players )
    {
        // Set the colors of the players with HTML color code
        $gameinfos = self::getGameinfos();
        $default_colors = $gameinfos['player_colors'];

        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = [];
        foreach( $players as $player_id => $player )
        {
            $color = array_shift( $default_colors );
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."')";
        }
        $sql .= implode( ',', $values);
        self::DbQuery( $sql );
        self::reattributeColorsBasedOnPreferences( $players, $gameinfos['player_colors'] );
        self::reloadPlayersBasicInfos();
    }

    function createStatistics()
    {
/*
    initializes official stats and gamestats table for undo mode
*/

        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        self::initStat( 'table', 'turns_nb', 0 );

        self::initStat( 'player', 'turns_nb', 0);
        self::initStat( 'player', 'galaxies_taken', 0 );
        self::initStat( 'player', 'black_holes_taken', 0 );
        self::initStat( 'player', 'constel_road_win', 0);
        self::initStat( 'player', 'galaxy_win', 0 );
        self::initStat( 'player', 'black_hole_win', 0 );

        $sql = "INSERT INTO gamestats ( stat_type, stat_save, stat_inc ) VALUES ";
        $sql_values = [];
        $sql_values[] = "( 'galaxies_taken',0,0 )";
        $sql_values[] = "( 'black_holes_taken',0,0 )";
        $sql_values[] = "( 'constel_road_win',0,0 )";
        $sql_values[] = "( 'galaxy_win',0,0 )";
        $sql_values[] = "( 'black_hole_win',0,0 )";
        $sql .= implode( ',', $sql_values );
        self::DbQuery( $sql );
    }

    function updateStatistics()
    {
/*
    update official game stats with table gamestats.
    reset gamestats table
*/
        $player_id = self::getActivePlayerId();
        $stats_inc = self::getCollectionFromDb( "SELECT stat_type, stat_inc FROM gamestats WHERE stat_inc!=0" , TRUE );
        foreach( $stats_inc as $type => $inc )
        {
            self::incStat( $inc, $type, $player_id );
        }
        self::DbQuery("UPDATE gamestats SET stat_inc=0 WHERE stat_inc!=0" );
    }

    function resetStatistics()
    {
/*
    reset gamestats table (when cancelling action)
*/
        self::DbQuery("UPDATE gamestats SET stat_inc=0 WHERE stat_inc!=0" );
    }

    function initializeGame()
    {
        self::createSquares();
        self::createTiles();
        self::createGalaxiesAndBlackHoles();
        self::notifyAllPlayers('info', clienttranslate( 'Welcome to ORION DUEL!' ), [] );
    }

    function stGamePreparation()
    {
/*
    check for Player's Choice mode
*/
        if( self::getGameStateValue('board_display' ) != 1 )
        {
            $this->gamestate->nextState( 'playerTurn' );
        }
        else
        {
            $this->gamestate->nextState( 'playerChoice' );
        }
    }

    function createTiles()
    {
/* blue tiles 1 to 6, orange tiles 7 to 12 */

        $sql = "INSERT INTO tile ( tile_type,tile_location,tile_location_save ) VALUES ";
        $sql_values = [];

        foreach( $this->tiles as $tile )
        {
            $sql_values[] = "( '$tile','hand','hand' )";
        }
        $sql .= implode( ',', $sql_values );
        self::DbQuery( $sql );
    }

    function createSquares()
    {
        $sql = "INSERT INTO board ( board_square,board_color,board_black_hole, board_galaxy, board_new,board_color_save ) VALUES ";
        $sql_values = [];
        foreach( $this->board_squares as $square )
        {
            $sql_values[] = "( '$square','0','0','0','0','0' )";
        }
        $sql .= implode( ',', $sql_values );
        self::DbQuery( $sql );
    }

    function createGalaxiesAndBlackHoles()
    {
 /* predefined modes 1, 2 and random */
        if( self::getGameStateValue('board_display' ) == 2 )
        { // predef 1
            foreach( $this->galaxies_1 as $square )
            {
                self::DbQuery( "UPDATE board SET board_galaxy=1 WHERE board_square='$square'" );
            }
            foreach( $this->black_holes_1 as $square )
            {
                self::DbQuery( "UPDATE board SET board_black_hole=1 WHERE board_square='$square'" );
            }
        }
        else if( self::getGameStateValue('board_display' ) == 3 )
        { // predef 2
            foreach( $this->galaxies_2 as $square )
            {
                self::DbQuery( "UPDATE board SET board_galaxy=1 WHERE board_square='$square'" );
            }
            foreach( $this->black_holes_2 as $square )
            {
                self::DbQuery( "UPDATE board SET board_black_hole=1 WHERE board_square='$square'" );
            }
        }
        else if( self::getGameStateValue('board_display' ) == 4 )
        { // predef 3
            foreach( $this->galaxies_3 as $square )
            {
                self::DbQuery( "UPDATE board SET board_galaxy=1 WHERE board_square='$square'" );
            }
            foreach( $this->black_holes_3 as $square )
            {
                self::DbQuery( "UPDATE board SET board_black_hole=1 WHERE board_square='$square'" );
            }
        }
        else if( self::getGameStateValue('board_display' ) == 0 )
        { // random
            self::createRandomBoardLayout();
        }
		else if( self::getGameStateValue('board_display' ) == 5 )
        { // random
            self::createRandomBoardLayout2();
        }			
    }

    function createRandomBoardLayout()
    {
        $indice = bga_rand( 0, 9 );
		$available_galaxy_hexes = $this->random_galaxies[$indice];
		$available_black_hole_hexes = $this->random_black_holes[$indice];
		$galaxies = [];
        $black_holes = [];
		for( $i=0;$i<8;$i++)
		{
			shuffle( $available_galaxy_hexes );
			$galaxy = array_shift( $available_galaxy_hexes );
			$galaxies[] = $galaxy;

			$two_adjacent = self::getTwoAdjacentToHex( $galaxy );
			$two_adjacent[] = $galaxy;
			self::updateGlobal( $two_adjacent, $available_galaxy_hexes );
		}

		for( $i=0;$i<7;$i++)
		{
			shuffle( $available_black_hole_hexes );
			$black_hole = array_shift( $available_black_hole_hexes );
			$black_holes[] = $black_hole;

			$two_adjacent = self::getTwoAdjacentToHex( $black_hole );
			$two_adjacent[] = $black_hole;
			self::updateGlobal( $two_adjacent, $available_black_hole_hexes );
		}
        foreach( $galaxies as $galaxy )
        {
            self::DbQuery( "UPDATE board SET board_galaxy=1 WHERE board_square='$galaxy'" );
        }
        foreach( $black_holes as $black_hole )
        {
            self::DbQuery( "UPDATE board SET board_black_hole=1 WHERE board_square='$black_hole'" );
        }
	}

    function updateGlobal( $list, &$global )
    { // update random_hexes_galaxy & random_hexes_black_hole
        $hexes_to_remove = array_intersect( $list, $global );
        foreach( $hexes_to_remove as $hex )
        {
            if( in_array( $hex, $global ) )
            {
                array_splice( $global, array_search($hex, $global ), 1);
            }
        }
    }


    /*
        getAllDatas:

        Gather all informations about current game situation (visible by the current player).

        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = [];
        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!

        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score FROM player ";
        $result['players'] = self::getCollectionFromDb( $sql );
        $result['gamestate'] = $this->gamestate->state( );

        $result['constellations'] = $this->constel_names;
        $result['squares'] = $this->board_squares;
        $result['no_tile_squares'] = self::getNoTileSquares();
        $result['galaxies'] = self::getGalaxies();
        $result['forbidden_galaxies'] = self::getForbiddenGalaxies();
        $result['black_holes'] = self::getBlackHoles();
        $result['orange_tiles'] = self::getOrangeJSTiles();
        $result['blue_tiles'] = self::getBlueJSTiles();
        $result['tiles_in_hands'] = self::getTilesInHands();
        return $result;
    }

    /*
        getGameProgression:

        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).

        This method is called each time we are in a game state with the "updateGameProgression" property set to TRUE
        (see states.inc.php)
    */
    function getGameProgression()
    {
        $elements = self::getUniqueValueFromDb( "SELECT COUNT(board_square) FROM board WHERE (board_galaxy=1 OR board_black_hole=1) AND board_color>0" );
        $hexes = self::getUniqueValueFromDb( "SELECT COUNT(board_square) FROM board WHERE board_color>0" );
        return min( 100, $elements + $hexes);
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////

    /*
        In this space, you can put any utility methods useful for your game logic
    */

/* usual functions */

    function getOpponentId( $player_id )
    {
        $players = self::loadPlayersBasicInfos();
        foreach( $players as $opponent_id => $opponent )
        {
            if( $opponent_id != $player_id )
                return $opponent_id;
        }
    }

    function getStateName()
    {
        $state = $this->gamestate->state();
        return $state['name'];
    }

    function updateNbTurns()
    {
        $player_id = self::getActivePlayerId();
        $this->incStat(1, 'turns_nb', $player_id );
        if( self::getPlayerNoById($player_id) == 1 )
        {
            $this->incStat(1, 'turns_nb');
        }
    }

 /* getters */

    function getGalaxies()
    {
        return self::getObjectListFromDb( "SELECT board_square square FROM board WHERE board_galaxy=1",TRUE );
    }

    function getBlackHoles()
    {
        return self::getObjectListFromDb( "SELECT board_square square FROM board WHERE board_black_hole=1",TRUE );
    }

    function getOrangeTiles()
    {
        return self::getObjectListFromDb( "SELECT board_square square FROM board WHERE board_color=2",TRUE );
    }

    function getBlueTiles()
    {
        return self::getObjectListFromDb( "SELECT board_square square FROM board WHERE board_color=1",TRUE );
    }

    function getOrangeJSTiles()
    {
        return self::getObjectListFromDb( "SELECT board_square square, board_new new FROM board WHERE board_color=2" );
    }

    function getBlueJSTiles()
    {
        return self::getObjectListFromDb( "SELECT board_square square, board_new new FROM board WHERE board_color=1" );
    }

    function getNewOrangeTiles()
    {
        return self::getObjectListFromDb( "SELECT board_square square, board_new new FROM board WHERE board_color=2 AND board_new = 1" );
    }

    function getNewBlueTiles()
    {
        return self::getObjectListFromDb( "SELECT board_square square, board_new new FROM board WHERE board_color=1 AND board_new = 1" );
    }

    function getNoTileSquares()
    {
        return self::getObjectListFromDb( "SELECT board_square square FROM board WHERE board_color=0", TRUE );
    }

    function getTilesinHands()
    {
        return self::getcollectionFromDb( "SELECT tile_type type, count(tile_type) FROM tile WHERE tile_location='hand' GROUP BY tile_type", TRUE );
    }

    function getBluePlayerId()
    {
        return self::getUniqueValueFromDB( "SELECT player_id FROM player WHERE player_color='0eb0cc' LIMIT 1");
    }

    function getOrangePlayerId()
    {
        return self::getUniqueValueFromDB( "SELECT player_id FROM player WHERE player_color='fba930' LIMIT 1");
    }

/* game utilities */


    function getAdjacentToHex( $hex )
    {
/*
    return adjacent hexes with special cases 49, 40, 60, 39 and 59
*/
        $adjacents = [];
        if( (floor($hex/10)) % 2 == 0 )
        {
            if( in_array( $hex, [49,69] ) )
            {
                $delta_array = $this->adjacent_hexes_49_69;
            }
            else
            {
                $delta_array = in_array( $hex, [40,60] ) ? $this->adjacent_hexes_40_60 : $this->adjacent_hexes_even;
            }
        }
        else
        {
            $delta_array = in_array( $hex, [39,59] ) ? $this->adjacent_hexes_39_59 : $this->adjacent_hexes_odd;
        }
        foreach( $delta_array as $delta )
        {
            if( in_array( $hex+$delta, $this->board_squares ) )
            {
                $adjacents[] = $hex+$delta;
            }
        }
        return $adjacents;
    }

    function getTwoAdjacentToHex( $hex )
    {
/*
    return adjacent hexes with special cases 49, 40, 60, 39 and 59
*/
        $two_adjacent = [];
        $one_adjacent = self::getAdjacentToHex( $hex );
        foreach( $one_adjacent as $hex )
        {
            if( !in_array( $hex, $two_adjacent ) )
            {
                $two_adjacent[] = $hex;
            }
            $adjacent_bis = self::getAdjacentToHex( $hex );
            foreach( $adjacent_bis as $hex_bis )
            {
                if( !in_array( $hex_bis, $two_adjacent ) )
                {
                    $two_adjacent[] = $hex_bis;
                }
            }
        }
        return $two_adjacent;
    }

    function getForbiddenGalaxies()
    {
/*
    return hexes adjacent to a galaxy
*/
        if (in_array(self::getStateName(), ['blackHolesChoice', 'galaxiesChoice']))
        {
            $forbidden = [];
            $galaxies = self::getGalaxies();
            foreach( $galaxies as $galaxy )
            {
                $one_hex = self::getAdjacentToHex( $galaxy );
                foreach( $one_hex as $hex )
                {
                    if( !in_array( $hex, $forbidden ) )
                    {
                        $forbidden[] = $hex;
                    }
                }
            }
            return $forbidden;
        }
        return [];
    }

    function checkConstellationRoad( )
    {
/*
    check if constellations are linked and updates score, stats
    red constell: 1
    green constel: 3
    purple constel: 7
*/
        $constell_points = self::getObjectListFromDb( "SELECT player_color color, player_constel_win constel_win FROM player WHERE player_constel_win<11" );
        foreach( $constell_points as $constell_point )
        {
             $color_tiles = ( $constell_point['color'] == BLUE_COLOR ) ? self::getBlueTiles() : self::getOrangeTiles();
            // useless if less than 11 squares on board
            if( count( $color_tiles ) > 10 )
            {
                if( in_array( $constell_point['constel_win'], [0,3,7,10] ) )
                {
                    $redRoad = self::checkRoad( $this->canis_major, $this->eridanus, $color_tiles );
                    if( !empty( $redRoad ) )
                    {
                        foreach( $redRoad as $link )
                        {
                            self::DbQuery( "UPDATE board SET board_new=2 WHERE board_square='$link'" );
                        }
                        self::DbQuery( "UPDATE player SET player_constel_win=player_constel_win+1, player_score=player_score+1 WHERE player_color='".$constell_point['color']."'" );
                        self::DbQuery( "UPDATE gamestats SET stat_inc=1 WHERE stat_type='constel_road_win'" );

                        self::notifyAllPlayers( 'constellation_road', clienttranslate( '${player_name} has linked ${constel_name} to ${constel2_name}' ), [
                            'i18n' => [ 'constel_name','constel2_name' ],
                            'preserve' => [ 'constel_color','constel_name','constel2_name' ],
                            'player_name' => self::getActivePlayerName(),
                            'constel_name' => $this->constel_names['canis_major'],
                            'constel2_name' => $this->constel_names['eridanus'],
                            'constel_color' => 'd7582f',
                            'player_id' => self::getActivePlayerId(),
                            'road' => $redRoad ] );
                    }
                }
                if( in_array( $constell_point['constel_win'], [0,1,7,8] ) )
                {
                    $greenRoad = self::checkRoad( $this->lepus, $this->gemini, $color_tiles );
                    if( !empty($greenRoad) )
                    {
                        self::DbQuery( "UPDATE player SET player_constel_win=player_constel_win+3, player_score=player_score+1 WHERE player_color='".$constell_point['color']."'" );
                        self::DbQuery( "UPDATE gamestats SET stat_inc=1 WHERE stat_type='constel_road_win'" );
                        foreach( $greenRoad as $link )
                        {
                            self::DbQuery( "UPDATE board SET board_new=2 WHERE board_square='$link'" );
                        }
 
 self::notifyAllPlayers( 'constellation_road', clienttranslate( '${player_name} has linked ${constel_name} to ${constel2_name}' ), [
                        'i18n' => [ 'constel_name','constel2_name' ],
                        'preserve' => [ 'constel_color','constel_name','constel2_name' ],
                        'player_name' => self::getActivePlayerName(),
                        'constel_name' => $this->constel_names['gemini'],
                        'constel2_name' => $this->constel_names['lepus'],
                        'constel_color' => '008c89',
                        'player_id' => self::getActivePlayerId(),
                        'road' => $greenRoad ] );
                    }
                }
                if( in_array( $constell_point['constel_win'], [0,1,3,4] ) )
                {
                    $purpleRoad = self::checkRoad( $this->taurus, $this->monoceros, $color_tiles );

                    if( !empty($purpleRoad) )
                    {
                        self::DbQuery( "UPDATE player SET player_constel_win=player_constel_win+7, player_score=player_score+1 WHERE player_color='".$constell_point['color']."'" );
                        self::DbQuery( "UPDATE gamestats SET stat_inc=1 WHERE stat_type='constel_road_win'" );
                        foreach( $purpleRoad as $link )
                        {
                            self::DbQuery( "UPDATE board SET board_new=2 WHERE board_square='$link'" );
                        }

                        self::notifyAllPlayers( 'constellation_road', clienttranslate( '${player_name} has linked ${constel_name} to ${constel2_name}' ), [
                            'i18n' => [ 'constel_name','constel2_name' ],
                            'preserve' => [ 'constel_color','constel_name','constel2_name' ],
                            'player_name' => self::getActivePlayerName(),
                            'constel_name' => $this->constel_names['monoceros'],
                            'constel2_name' => $this->constel_names['taurus'],
                            'constel_color' => '9a1278',
                            'player_id' => self::getActivePlayerId(),
                            'road' => $purpleRoad ] );
                    }
                }
            }
        }
    }

    function checkRoad( $start, $finish, $color_tiles )
    {
/*
    makes a road from a constellation and checks if other constellation is reached
    // TODO remove useless from starters
*/
        $starters = array_intersect( $start, $color_tiles );
        $finishers = array_intersect( $finish, $color_tiles );
        if( count( $starters ) == 0 || count( $finishers ) == 0 )
        {
           return [];
        }
        while( count( $starters ) > 0 )
        {
            $starter = array_shift($starters);
            $hexes = [];
            $hexes[ $starter ] = [
                'id' => $starter,
                'distance' => 0,
                'path' => [$starter] ];
            $hexes_to_explore = [[
                'id' => $starter,
                'distance' => 0,
                'path' => [$starter] ]];
            $hexes_explored_ids = [ $starter ];
            while( count( $hexes_to_explore ) > 0 )
            {
                $hex_explored = array_shift( $hexes_to_explore );
                $one_hexes = self::getAdjacentToHex( $hex_explored['id'] );
                $possibles = array_intersect( $color_tiles, $one_hexes );
                foreach( $possibles as $new_hex )
                {
                    $path = $hex_explored['path'];
                    if( !in_array( $new_hex, $hexes_explored_ids ) )
                    {
                        $path[] = $new_hex;
                        $sortofmove = (in_array( $new_hex, $finishers )) ? 'finish' : 'move';
                        if( $sortofmove == 'finish' )
                        {
                             return $path;
                        }
                        if( $sortofmove == 'move' )
                        {
                            $hexes_to_explore[] = [
                                'id' =>$new_hex,
                                'distance' => $hex_explored['distance']+1,
                                'path' => $path ];
                        }
                        if( !empty( $hexes[$new_hex]['cost'] ) )
                        {
                            if( $squares[$new_hex]['cost'] > ( $hex_explored['cost']+1 ) )
                            {
                                $hexes[$new_hex] = [
                                    'id' =>$new_hex,
                                    'distance' => $hex_explored['distance']+1,
                                    'path' => $path ];
                            }
                        }
                        else
                        {
                            $hexes[$new_hex] = [
                               'id' => $new_hex,
                               'distance' => $hex_explored['distance']+1,
                               'path' => $path ];
                            $hexes_explored_ids[] = $new_hex;
                        }
                    }
                }
            }
        }
        return[];
    }

    function checkGalaxyBonus()
    {
/*
    check if 4 or 8 galaxies are connected on one color path
    update score, stats, board
    //TODO check for useless ones
*/

        $nb_galaxies_blue = self::getUniqueValueFromDb( "SELECT COUNT(board_square) FROM board WHERE board_galaxy=1 AND board_color=1" );
        $nb_galaxies_orange = self::getUniqueValueFromDb( "SELECT COUNT(board_square) FROM board WHERE board_galaxy=1 AND board_color=2" );
        $galaxy_win = ( $nb_galaxies_blue == 8 || $nb_galaxies_orange == 8 ) ? 2 : 1;

        $galaxy_points = self::getObjectListFromDb( "SELECT player_color color, player_galaxy_win galaxy_win, player_id FROM player WHERE player_galaxy_win<'$galaxy_win'" );
        foreach( $galaxy_points as $galaxy_point )
        {
            $color = ( $galaxy_point['color'] == BLUE_COLOR ) ? 1 : 2;
            $color_tiles = ( $galaxy_point['color'] == BLUE_COLOR ) ? self::getBlueTiles() : self::getOrangeTiles();
            $galaxies = self::getObjectListFromDb( "SELECT board_square square FROM board WHERE board_galaxy=1 AND board_color='$color'",TRUE );
            $nb_4 = 0;
            $nb_8 = 0;

            while( count( $galaxies ) > 3 )
            {
                $chain = [];
                $chain[] = array_shift( $galaxies );
                $galaxies_in = [$chain[0]];
                $galaxies_linked = 1;
                $hexes_to_explore = [$chain[0]];
                while( count( $hexes_to_explore ) > 0 )
                {
                    $hex_explored = array_shift( $hexes_to_explore );
                    $one_hexes = self::getAdjacentToHex( $hex_explored );
                    $possibles = array_intersect( $color_tiles, $one_hexes );
                    foreach( $possibles as $new_hex )
                    {
                        if( !in_array( $new_hex, $chain ) )
                        {
                            $hexes_to_explore[] = $new_hex;
                            $chain[] = $new_hex;
                            if( in_array( $new_hex, $galaxies ) )
                            {
                                array_splice($galaxies, array_search( $new_hex, $galaxies ), 1);
                                $galaxies_linked ++;
                                if( $galaxies_linked == 4 )
                                {
                                    $nb_4 ++;
                                }
                                else if( $galaxies_linked == 8 )
                                {
                                    $nb_8 ++;
                                }
                                $galaxies_in[] = $new_hex;
                            }
                        }
                    }
                }
                if( $galaxies_linked > 3 && $galaxy_point['galaxy_win'] == 0 )
                {
                    self::notifyAllPlayers( 'galaxy_win', clienttranslate( '${player_name} has connected ${galaxies_linked} Galaxies' ), [
                        'player_name' => self::getActivePlayerName(),
                        'player_id' => $galaxy_point['player_id'],
                        'chain' => $galaxies_in,
                        'galaxies_linked' => $galaxies_linked ] );
                    self::DbQuery( "UPDATE player SET player_galaxy_win=1, player_score=player_score+1 WHERE player_color='".$galaxy_point['color']."'" );
                    self::DbQuery( "UPDATE gamestats SET stat_inc=1 WHERE stat_type='galaxy_win'" );
                    foreach( $galaxies_in as $link )
                    {
                        self::DbQuery( "UPDATE board SET board_new=5 WHERE board_square='$link'" );
                    }
                }
                else if( $galaxies_linked == 8 && $galaxy_point['galaxy_win'] == 1 )
                {
                    self::notifyAllPlayers( 'galaxy_win', clienttranslate( '${player_name} has connected ${galaxies_linked} Galaxies' ), [
                        'player_name' => self::getActivePlayerName(),
                        'player_id' => $galaxy_point['player_id'],
                        'chain' => $galaxies_in,
                        'galaxies_linked' => $galaxies_linked ] );
                    self::DbQuery( "UPDATE player SET player_galaxy_win=2, player_score=player_score+1 WHERE player_color='".$galaxy_point['color']."'" );
                    self::DbQuery( "UPDATE gamestats SET stat_inc=1 WHERE stat_type='galaxy_win'" );
                    foreach( $galaxies_in as $link )
                    {
                        self::DbQuery( "UPDATE board SET board_new=5 WHERE board_square='$link'" );
                    }
                }
                else if( $nb_4 == 2 && $galaxy_point['galaxy_win'] == 1 )
                {
                    self::notifyAllPlayers( 'galaxy_win', clienttranslate( '${player_name} has connected another ${galaxies_linked} Galaxies' ), [
                        'player_name' => self::getActivePlayerName(),
                        'player_id' => $galaxy_point['player_id'],
                        'chain' => $galaxies_in,
                        'galaxies_linked' => $galaxies_linked ] );
                    self::DbQuery( "UPDATE player SET player_galaxy_win=2, player_score=player_score+1 WHERE player_color='".$galaxy_point['color']."'" );
                    self::DbQuery( "UPDATE gamestats SET stat_inc=1 WHERE stat_type='galaxy_win'" );
                    foreach( $galaxies_in as $link )
                    {
                        self::DbQuery( "UPDATE board SET board_new=5 WHERE board_square='$link'" );
                    }
                }
            }
        }
    }

    function checkBlackHolesMalus()
    {
/*
    check if 3 black holes are connected on one color path
    update score, stats, board
*/
        $nb_black_holes_blue = self::getUniqueValueFromDb( "SELECT COUNT(board_square) FROM board WHERE board_black_hole=1 AND board_color=1" );
        $nb_black_holes_orange = self::getUniqueValueFromDb( "SELECT COUNT(board_square) FROM board WHERE board_black_hole=1 AND board_color=2" );
        $black_hole_win = ( $nb_black_holes_blue == 6 || $nb_black_holes_orange == 6 ) ? 2 : 1;

        $black_holes_points = self::getObjectListFromDb( "SELECT player_color color, player_black_hole_win black_hole_win, player_id FROM player WHERE player_black_hole_win<'$black_hole_win'" );
        foreach( $black_holes_points as $black_hole_point )
        {
            $color = ( $black_hole_point['color'] == BLUE_COLOR ) ? 2 : 1;
            $color_tiles = ( $black_hole_point['color'] == BLUE_COLOR ) ? self::getOrangeTiles() : self::getBlueTiles();
            $black_holes = self::getObjectListFromDb( "SELECT board_square square FROM board WHERE board_black_hole=1 AND board_color='$color'",TRUE );
            $nb_3 = 0;
            $nb_6 = 0;

            while( count( $black_holes ) > 2 )
            {
                $chain = [];
                $chain[] = array_shift( $black_holes );
                $black_holes_in = [$chain[0]];
                $black_holes_linked = 1;
                $hexes_to_explore = [$chain[0]];
                while( count( $hexes_to_explore ) > 0 )
                {
                    $hex_explored = array_shift( $hexes_to_explore );
                    $one_hexes = self::getAdjacentToHex( $hex_explored );
                    $possibles = array_intersect( $color_tiles, $one_hexes );
                    foreach( $possibles as $new_hex )
                    {
                        if( !in_array( $new_hex, $chain ) )
                        {
                            $hexes_to_explore[] = $new_hex;
                            $chain[] = $new_hex;
                            if( in_array( $new_hex, $black_holes ) )
                            {
                                array_splice($black_holes, array_search( $new_hex, $black_holes ), 1);
                                $black_holes_linked ++;
                                if( $black_holes_linked == 3 )
                                {
                                    $nb_3 ++;
                                }
                                else if( $black_holes_linked == 6 )
                                {
                                    $nb_6 ++;
                                }
                                $black_holes_in[] = $new_hex;
                            }
                        }
                    }
                }
                if( $black_holes_linked > 2 && $black_hole_point['black_hole_win'] == 0 )
                {
                    self::notifyAllPlayers( 'black_hole_win', clienttranslate( '${player_name} has connected ${black_holes_linked} Black Holes' ), [
                        'player_name' => self::getActivePlayerName(),
                        'player_id' => $black_hole_point['player_id'],
                        'chain' => $black_holes_in,
                        'black_holes_linked' => $black_holes_linked ] );
                    self::DbQuery( "UPDATE player SET player_black_hole_win=1, player_score=player_score+1 WHERE player_color='".$black_hole_point['color']."'" );
                    self::DbQuery( "UPDATE gamestats SET stat_inc=1 WHERE stat_type='black_hole_win'" );
                    foreach( $black_holes_in as $link )
                    {
                        self::DbQuery( "UPDATE board SET board_new=3 WHERE board_square='$link'" );
                    }
                }
                else if( $black_holes_linked == 6 && $black_hole_point['black_hole_win'] == 1 )
                {
                    self::notifyAllPlayers( 'black_hole_win', clienttranslate( '${player_name} has connected ${black_holes_linked} Black Holes' ), [
                        'player_name' => self::getActivePlayerName(),
                        'player_id' => $black_hole_point['player_id'],
                        'chain' => $black_holes_in,
                        'black_holes_linked' => $black_holes_linked ] );
                    self::DbQuery( "UPDATE player SET player_black_hole_win=2, player_score=player_score+1 WHERE player_color='".$black_hole_point['color']."'" );
                    self::DbQuery( "UPDATE gamestats SET stat_inc=1 WHERE stat_type='black_hole_win'" );
                    foreach( $black_holes_in as $link )
                    {
                        self::DbQuery( "UPDATE board SET board_new=3 WHERE board_square='$link'" );
                    }
                }
                else if( $nb_3 == 2 && $black_hole_point['black_hole_win'] == 1 )
                {
                    self::notifyAllPlayers( 'black_hole_win', clienttranslate( '${player_name} has connected another ${black_holes_linked} Black Holes' ), [
                        'player_name' => self::getActivePlayerName(),
                        'player_id' => $black_hole_point['player_id'],
                        'chain' => $black_holes_in,
                        'black_holes_linked' => $black_holes_linked ] );
                    self::DbQuery( "UPDATE player SET player_black_hole_win=2, player_score=player_score+1 WHERE player_color='".$black_hole_point['color']."'" );
                    self::DbQuery( "UPDATE gamestats SET stat_inc=1 WHERE stat_type='galaxy_win'" );
                    foreach( $black_holes_in as $link )
                    {
                        self::DbQuery( "UPDATE board SET board_new=3 WHERE board_square='$link'" );
                    }
                }
            }
        }
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in orionduel.action.php)
    */



    function chooseGalaxies( $galaxies )
    {
/*
    checks and DB update
*/
        self::checkAction( 'placeGalaxy' );

        $pieces = explode("_", $galaxies);
        $last_piece = array_pop( $pieces ); //remove useless _

        if( count( $pieces ) != 8 )
            throw new BgaUserException( self::_("You must place 8 Galaxies") );
        if( self::checkValidGalaxies( $pieces ) == FALSE )
            throw new BgaUserException( self::_("Galaxies spacing is not valid") );
        foreach( $pieces as $piece )
        {
            self::DbQuery( "UPDATE board SET board_galaxy=1 WHERE board_square='$piece'" );
        }
        self::notifyAllPlayers( 'galaxiesChoice', clienttranslate('${player_name} places 8 Galaxies'), [
            'player_name' => self::getActivePlayerName(),
            'player_id' => self::getActivePlayerId(),
            'forbidden_galaxies' => self::getForbiddenGalaxies(),
            'galaxies' => self::getGalaxies() ] );
        $this->gamestate->nextState( 'nextPlayer' );
    }

    function checkValidGalaxies( $galaxies )
    {
/*
    checks if spacing between galaxies is valid
*/
        $available_galaxy_hexes = $this->board_squares;

        foreach( $galaxies as $galaxy )
        {
            if( !in_array( $galaxy, $available_galaxy_hexes ) )
            {
                return FALSE;
            }
            $one_hex = self::getAdjacentToHex( $galaxy );
            foreach( $one_hex as $hex )
            { // remove adjacents to galaxies
                if( in_array( $hex, $available_galaxy_hexes ) )
                {
                    array_splice($available_galaxy_hexes, array_search($hex, $available_galaxy_hexes ), 1);
                }
            }
            foreach( $one_hex as $hex )
            {
                // remove 2-space for other galaxies
                $two_hex = self::getAdjacentToHex( $hex );
                foreach( $two_hex as $hex2 )
                {
                   if( in_array( $hex2, $available_galaxy_hexes ) )
                    {
                        array_splice($available_galaxy_hexes, array_search($hex2, $available_galaxy_hexes ), 1);
                    }
                }
            }
        }
        return TRUE;
    }

    function chooseBlackHoles( $black_holes )
    {
/*
    checks and DB update
*/
        self::checkAction( 'placeBlackHole' );

        $pieces = explode("_", $black_holes);
        $last_piece = array_pop( $pieces ); //remove useless _

        if( count( $pieces ) != 7 )
            throw new BgaUserException( self::_("You must place 7 Black Holes" ) );
        if( self::checkValidBlackHoles( $pieces ) == FALSE )
            throw new BgaUserException( self::_("Black Holes spacing is not valid") );
        foreach( $pieces as $piece )
        {
            self::DbQuery( "UPDATE board SET board_black_hole=1 WHERE board_square='$piece'" );
        }
        self::notifyAllPlayers( 'blackHolesChoice', clienttranslate('${player_name} places 7 Black Holes'), [
            'player_name' => self::getActivePlayerName(),
            'player_id' => self::getActivePlayerId(),
            'black_holes' => self::getBlackHoles() ]);
        $this->gamestate->nextState( 'nextPlayer' );
    }

    function checkValidBlackHoles( $black_holes )
    {
/*
    checks of spacing between galaxies and black holes is valid
*/
        $available_black_hole_hexes = $this->board_squares;
        $galaxies = self::getGalaxies();

        foreach( $galaxies as $galaxy )
        {
            array_splice($available_black_hole_hexes, array_search($galaxy, $available_black_hole_hexes ), 1);
            $one_hex = self::getAdjacentToHex( $galaxy );
            foreach( $one_hex as $hex )
            { // remove adjacents to galaxies
                if( in_array( $hex, $available_black_hole_hexes ) )
                {
                   array_splice($available_black_hole_hexes, array_search($hex, $available_black_hole_hexes ), 1);
                }
            }
        }
        foreach( $black_holes as $black_hole )
        {
            if( !in_array( $black_hole, $available_black_hole_hexes ) )
            {
                return FALSE;
            }
            $one_hex = self::getAdjacentToHex( $black_hole );
            foreach( $one_hex as $hex )
            { // remove adjacents to black holes
                if( in_array( $hex, $available_black_hole_hexes ) )
                {
                    array_splice($available_black_hole_hexes, array_search($hex, $available_black_hole_hexes ), 1);
                }
            }
            foreach( $one_hex as $hex )
            {
                // remove 2-space for other black holes
                $two_hex = self::getAdjacentToHex( $hex );
                foreach( $two_hex as $hex2 )
                {
                    if( in_array( $hex2, $available_black_hole_hexes ) )
                    {
                        array_splice($available_black_hole_hexes, array_search($hex2, $available_black_hole_hexes ), 1);
                    }
                }
            }
        }
        return TRUE;
    }

    function playerPass()
    {
/*
    pass
*/
        self::checkAction( 'pass' );
        self::notifyAllPlayers( 'playerPass', clienttranslate('${player_name} passes'), [
            'player_name' => self::getActivePlayerName() ]);
        $this->gamestate->nextState( 'pass' );
    }

    function placeTileOnBoard( $blue_tiles, $orange_tiles )
    {
/*
    check tile is valid and returns type
    check tile is in hand
*/
        self::checkAction( 'placeTile' );

        $blue_pieces = explode("_", $blue_tiles);
        $last_piece = array_pop( $blue_pieces ); //remove useless _

        $orange_pieces = explode("_", $orange_tiles);
        $last_piece = array_pop( $orange_pieces ); //remove useless _

        $type = self::getPlacedTileType( $blue_pieces, $orange_pieces );
        if( $type == 0 )
            throw new BgaUserException( self::_("You must place a valid Tile") );
        $nb_type = self::getUniqueValueFromDb( "SELECT COUNT(tile_id) FROM tile WHERE tile_type='$type' AND tile_location='hand'" );
        if( $nb_type == 0 )
            throw new BgaUserException( self::_("You have no more Tile of this type") );
        if( self::checkValidPlaceOnBoard( $blue_pieces, $orange_pieces ) == FALSE )
            throw new BgaUserException( self::_("There is a Galaxy or a Black Hole not connected to a Chain of the same color") );

        $player_id = self::getActivePlayerId( );
        $opponent_id = self:: getOpponentId( $player_id );
        $blue_player_id = self::getBluePlayerId();
        $orange_player_id = self::getOrangePlayerId();
        $galaxies = self::getGalaxies();
        $black_holes = self::getBlackHoles();

        self::DbQuery( "UPDATE tile SET tile_location='board' WHERE tile_type='$type' AND tile_location='hand' LIMIT 1" );
        $nb_type = self::getUniqueValueFromDb( "SELECT COUNT(tile_id) FROM tile WHERE tile_type='$type' AND tile_location='hand'" );

        self::DbQuery( "UPDATE board SET board_new=0 WHERE 1" );

        foreach( $blue_pieces as $piece )
        {
            if( self::getUniqueValueFromDb( "SELECT board_color FROM board WHERE board_square='$piece'" ) > 0 )
                throw new BgaUserException( self::_("This space is already occupied") );

            self::DbQuery( "UPDATE board SET board_color=1, board_new=1 WHERE board_square='$piece'" );
            if( in_array( $piece, $galaxies ) && ( $player_id == $blue_player_id ) )
            {
                self::DbQuery( "UPDATE gamestats SET stat_inc=1 WHERE stat_type='galaxies_taken'" );
            }
            if( in_array( $piece, $black_holes ) && ( $opponent_id == $blue_player_id ) )
            {
                self::DbQuery( "UPDATE gamestats SET stat_inc=1 WHERE stat_type='black_holes_taken'" );
            }
        }
        foreach( $orange_pieces as $piece )
        {
            if( self::getUniqueValueFromDb( "SELECT board_color FROM board WHERE board_square='$piece'" ) > 0 )
                throw new BgaUserException( self::_("This space is already occupied") );

            self::DbQuery( "UPDATE board SET board_color=2, board_new=1 WHERE board_square='$piece'" );
            if( in_array( $piece, $galaxies ) && ( $player_id == $orange_player_id ) )
            {
                self::DbQuery( "UPDATE gamestats SET stat_inc=1 WHERE stat_type='galaxies_taken'" );
            }
            if( in_array( $piece, $black_holes ) && ( $opponent_id == $orange_player_id ) )
            {
                self::DbQuery( "UPDATE gamestats SET stat_inc=1 WHERE stat_type='black_holes_taken'" );
            }
        }
        self::notifyAllPlayers( 'placeTiles', clienttranslate('${player_name} places a Tile'), [
            'player_name' => self::getActivePlayerName(),
            'player_id' => $player_id,
            'blue_pieces' => self::getNewBlueTiles(),
            'orange_pieces' => self::getNewOrangeTiles(),
            'type' => $type,
            'nb_type' => $nb_type
            ]);

        self::checkConstellationRoad();
        self::checkGalaxyBonus();
        self::checkBlackHolesMalus();

        self::updateNbTurns();
        self::updateStatistics();

        $victory_points  = self::getCollectionFromDb( "SELECT player_id, player_score score FROM player WHERE 1", TRUE );

        if( $victory_points[$player_id] > $victory_points[$opponent_id] )
        {
            self::notifyAllPlayers( 'info', clienttranslate('${player_name} wins'), [ 'player_name' => self::getPlayerNameById( $player_id ) ] );
            $this->gamestate->nextState( 'endGame' );
        }
        else if( $victory_points[$player_id] < $victory_points[$opponent_id] )
        {
            self::notifyAllPlayers( 'info', clienttranslate('${player_name} wins'), [ 'player_name' => self::getPlayerNameById( $opponent_id ) ] );
            $this->gamestate->nextState( 'endGame' );
        }
        else
        {
            $remaining_tiles = self::getUniqueValueFromDb( "SELECT COUNT(tile_id) FROM tile WHERE tile_location='hand'" );
            if( $remaining_tiles == 0 )
            {
                $galaxies = self::getGalaxies();
                $black_holes = self::getBlackHoles();
                $orange_tiles_on_board = self::getOrangeTiles();
                $blue_tiles_on_board = self::getBlueTiles();
                $blue_galaxies = array_intersect(  $blue_tiles_on_board, $galaxies );
                $orange_galaxies = array_intersect( $orange_tiles_on_board, $galaxies );
                $blue_black_holes = array_intersect( $blue_tiles_on_board, $black_holes );
                $orange_black_holes = array_intersect( $orange_tiles_on_board, $black_holes );
                $blue_points = count( $blue_galaxies ) - count( $blue_black_holes );
                $orange_points = count( $orange_galaxies ) - count( $orange_black_holes );
                self::DbQuery( "UPDATE player SET player_score_aux=$blue_points WHERE player_id='$blue_player_id'" );
                self::DbQuery( "UPDATE player SET player_score_aux=$orange_points WHERE player_id='$orange_player_id'" );

                if( $blue_points > $orange_points )
                {
                    self::notifyAllPlayers( 'info', clienttranslate('There are no more Tiles and ${player_name} wins'), [ 'player_name' => self::getPlayerNameById( $blue_player_id ) ] );
                }
                else if( $blue_points < $orange_points )
                {
                    self::notifyAllPlayers( 'info', clienttranslate('There are no more Tiles and ${player_name} wins'), [ 'player_name' => self::getPlayerNameById( $orange_player_id ) ] );
                }
                else
                {
                    self::notifyAllPlayers( 'info', clienttranslate('There are no more Tiles and game ends as a Tie'), [ ] );
                }
                $this->gamestate->nextState( 'endGame' );
            }
            $this->gamestate->nextState( 'nextPlayer' );
        }
    }

    function getPlacedTileType( $blue_pieces, $orange_pieces )
    {
/*
    tile must contain less than 3 pieces including one of active player color

*/
        $player_id = self::getActivePlayerId();
        $player_color = self::getPlayerColorById( $player_id );
        $bBluePlayer = ($player_color == BLUE_COLOR);
        $nb_blue = count( $blue_pieces );
        $nb_orange = count( $orange_pieces );
        $nb_pieces = $nb_blue + $nb_orange;
        $type = 0;

        if( $bBluePlayer )
        {
            if( $nb_blue < 1 || $nb_pieces > 3 || $nb_blue < $nb_orange )
                throw new BgaUserException( self::_("You must place a valid Blue Tile") );
        }
        else
        {
            if( $nb_orange < 1 || $nb_pieces > 3 || $nb_blue > $nb_orange )
                throw new BgaUserException( self::_("You must place a valid Orange Tile") );
        }
        if( $nb_pieces == 1 )
        {
            $type = 1+6*$bBluePlayer;
        }
        else if( $nb_pieces == 2 )
        {
            if( $nb_blue == $nb_orange )
            {
                if( self::checkAdjacentPieces( $blue_pieces[0], $orange_pieces[0] ) )
                {
                    $type = 3+6*$bBluePlayer;
                }
            }
            else
            {
                if( $bBluePlayer )
                {
                    if( self::checkAdjacentPieces( $blue_pieces[0], $blue_pieces[1] ) )
                    {
                        $type = 8;
                    }
                    else
                       throw new BgaUserException( self::_("You must place a valid 2 Tiles Blue Piece") );
                }
                else
                {
                    if( self::checkAdjacentPieces( $orange_pieces[0], $orange_pieces[1] ) )
                    {
                        $type = 2;
                    }
                    else
                       throw new BgaUserException( self::_("You must place a valid 2 Tiles Orange Piece") );
                }
            }
        }
        else
        {
            if( count( $blue_pieces ) == 0 || count( $orange_pieces ) == 0 )
                throw new BgaUserException( self::_("You must place a valid 3 Tiles Piece") );

            $type = self::getTripleType( $bBluePlayer, $blue_pieces, $orange_pieces );
        }
        return $type;
    }

    function checkAdjacentPieces( $piece1, $piece2 )
    {
        $adj_piece1 = self::getAdjacentToHex( $piece1 );
        return in_array( $piece2, $adj_piece1 );
    }

    function getTripleType( $bBluePlayer, $blue_pieces, $orange_pieces )
    {
        $first_piece = $bBluePlayer ? $orange_pieces[0] : $blue_pieces[0];
        $adj_pieces = self::getAdjacentToHex( $first_piece );

        $second_piece_array = array_intersect($adj_pieces,$bBluePlayer ? $blue_pieces : $orange_pieces );
        if( count( $second_piece_array ) == 0 )
            throw new BgaUserException( self::_("You must place a valid 3 Tiles Piece") );
        $second_piece = array_shift( $second_piece_array );
        $adj_pieces_2 = self::getAdjacentToHex( $second_piece );

        $third_piece_array = array_intersect($adj_pieces_2,$bBluePlayer ? $blue_pieces : $orange_pieces );
        if( count( $third_piece_array ) == 0 )
            throw new BgaUserException( self::_("You must place a valid 3 Tiles Piec") );
        $third_piece = array_shift( $third_piece_array );
        $adj_pieces_3 = self::getAdjacentToHex( $third_piece );

        $first_piece_array = array_intersect($adj_pieces_3,$bBluePlayer ? $orange_pieces : $blue_pieces );
        if( count( $first_piece_array ) == 1 )
        {
            return( 6+6*$bBluePlayer );
        }
        else if( in_array( abs( $first_piece - $third_piece ), [ 2, 19, 21 ] ) )
        {
            return( 4+6*$bBluePlayer );
        }
        else if( ( floor($third_piece/10 ) % 2 == 0 ) && in_array( $first_piece - $third_piece , [ -20, -11, -8, 9, 12, 20 ] ) )
        {
            return( 5+6*$bBluePlayer );
        }
        else if( ( floor($third_piece/10 ) % 2 == 1 ) && in_array( $first_piece - $third_piece , [ -20, -12, -9, 8, 11, 20 ] ) )
        {
            return( 5+6*$bBluePlayer );
        }
        else
            throw new BgaUserException( self::_("You must place a valid 3-Tiles Piece") );
    }

    function checkValidPlaceOnBoard( $blue_tiles, $orange_tiles )
    {
        $galaxies = self::getGalaxies();
        $black_holes = self::getBlackHoles();
        $orange_tiles_on_board = self::getOrangeTiles();
        $blue_tiles_on_board = self::getBlueTiles();

        $blue_galaxies = array_intersect(  $galaxies, $blue_tiles );
        $orange_galaxies = array_intersect( $orange_tiles, $galaxies );
        $blue_black_holes = array_intersect( $blue_tiles, $black_holes );
        $orange_black_holes = array_intersect( $orange_tiles, $black_holes );

        if( count($blue_galaxies) > 0 || count($blue_black_holes) > 0 )
        {
            $bLink = FALSE;
            foreach( $blue_tiles as $piece )
            {
                $adj_pieces = self::getAdjacentToHex( $piece );
                $blue_link = array_intersect( $adj_pieces, $blue_tiles_on_board );
                {
                    if( count( $blue_link ) > 0 )
                    {
                        $bLink = TRUE;
                    }
                }
            }
            if( $bLink == FALSE )
            {
                return FALSE;
            }
        }
        if( count($orange_galaxies) > 0 || count($orange_black_holes) > 0 )
        {
            $bLink = FALSE;
            foreach( $orange_tiles as $piece )
            {
                $adj_pieces = self::getAdjacentToHex( $piece );
                $orange_link = array_intersect( $adj_pieces, $orange_tiles_on_board );
                {
                    if( count( $orange_link ) > 0 )
                    {
                        $bLink = TRUE;
                    }
                }
            }
            if( $bLink == FALSE )
            {
                return FALSE;
            }
        }
        return TRUE;
    }

    function checkPossiblePlace()
    {
        $player_id = self::getActivePlayerId();
        if( self::getPlayerColorById( $player_id ) == BLUE_COLOR )
        {
            $pieces = self::getObjectListFromDb( "SELECT DISTINCT tile_type FROM tile WHERE tile_location='hand' AND tile_type>6 ORDER BY tile_type",TRUE );
        }
        else
        {
            $pieces = self::getObjectListFromDb( "SELECT DISTINCT tile_type FROM tile WHERE tile_location='hand' AND tile_type<7 ORDER BY tile_type",TRUE );
        }

        $empty_spaces = self::getObjectListFromDb( "SELECT board_square FROM board WHERE board_color=0" , TRUE );
        $empty_duos = self::getEmptyDuos( $empty_spaces );

        foreach( $pieces as $piece )
        {
            if( $piece == 1 ) //1 orange
            {
                foreach( $empty_spaces as $space )
                {
                    if( self::checkValidPlaceOnBoard( [], [$space] ) )
                    {
                        return TRUE;
                    }
                }
            }
            else if( $piece == 2 ) // 2 orange
            {
                foreach( $empty_duos as $duo )
                {
                    if( self::checkValidPlaceOnBoard( [], [$duo[0],$duo[1]] ) )
                    {
                        return TRUE;
                    }
                }
            }
            else if( $piece == 3 || $piece == 9 ) //duos bue orange
            {
                foreach( $empty_duos as $duo )
                {
                    if( self::checkValidPlaceOnBoard( [$duo[0]], [$duo[1]] )
                        || self::checkValidPlaceOnBoard( [$duo[1]], [$duo[0]] ) )
                    {
                        return TRUE;
                    }
                }
            }
            else if( $piece == 4 ) //line3 orange
            {
                $lines = self::getLines( $empty_spaces, $empty_duos );
                foreach( $lines as $line )
                {
                    if( self::checkValidPlaceOnBoard( [$line[1],$line[2]], [$line[0]] )
                        || self::checkValidPlaceOnBoard( [$line[0],$line[1]], [$line[2]] ) )
                    {
                        return TRUE;
                    }
                }
            }
            else if( $piece == 5 ) // V orange
            {
                $hats = self::getHats( $empty_spaces, $empty_duos );
                foreach( $hats as $hat )
                {
                    if( self::checkValidPlaceOnBoard( [$hat[1],$hat[2]], [$hat[0]] )
                        || self::checkValidPlaceOnBoard( [$hat[0],$hate[1]], [$hat[2]] ) )
                    {
                        return TRUE;
                    }
                }
            }
            else if( $piece == 6 ) // triangle orange
            {
                $triangles = self::getTriangles( $empty_spaces, $empty_duos );
                foreach( $triangles as $triangle )
                {
                    if( self::checkValidPlaceOnBoard( [$triangle[1],$triangle[2]], [$triangle[0]] )
                        || self::checkValidPlaceOnBoard( [$triangle[0],$triangle[2]], [$triangle[1]] )
                        || self::checkValidPlaceOnBoard( [$triangle[0],$triangle[1]], [$triangle[2]] ) )
                    {
                        return TRUE;
                    }
                }
            }
            else if( $piece == 7 ) //1 blue
            {
                foreach( $empty_spaces as $space )
                {
                    if( self::checkValidPlaceOnBoard( [$space], [] ) )
                    {
                        return TRUE;
                    }
                }
            }
            else if( $piece == 8 ) // 2 blue
            {
                foreach( $empty_duos as $duo )
                {
                    if( self::checkValidPlaceOnBoard( [$duo[0],$duo[1]], [] ) )
                    {
                        return TRUE;
                    }
                }
            }
            else if( $piece == 10 ) // line blue
            {
                $lines = self::getLines( $empty_spaces, $empty_duos );
                foreach( $lines as $line )
                {
                    if( self::checkValidPlaceOnBoard( [$line[0]], [$line[1],$line[2]] )
                        || self::checkValidPlaceOnBoard( [$line[2]], [$line[0],$line[1]] ) )
                    {
                        return TRUE;
                    }
                }
            }
            else if( $piece == 11 ) // V blue
            {
                $hats = self::getHats( $empty_spaces, $empty_duos );
                foreach( $hats as $hat )
                {
                    if( self::checkValidPlaceOnBoard( [$hat[0]], [$hat[1],$hat[2]] )
                        || self::checkValidPlaceOnBoard( [$hat[2]], [$hat[0],$hat[1] ] ) )
                    {
                        return TRUE;
                    }
                }
            }
            else if( $piece == 12 ) // triangle blue
            {
                $triangles = self::getTriangles( $empty_spaces, $empty_duos );
                foreach( $triangles as $triangle )
                {
                    if( self::checkValidPlaceOnBoard( [$triangle[0]], [$triangle[1],$triangle[2]] )
                        || self::checkValidPlaceOnBoard( [$triangle[1]], [$triangle[0],$triangle[2]] )
                        || self::checkValidPlaceOnBoard( [$triangle[2]], [$triangle[0],$triangle[1]] ) )
                    {
                        return TRUE;
                    }
                }
            }
        }
        return FALSE;
    }

    function getEmptyDuos( $empty_spaces )
    {
        $empty_duos = [];
        foreach( $empty_spaces as $space )
        {
            $adjac_hexes = self::getAdjacentToHex( $space );
            foreach( $adjac_hexes as $adjac_hex )
            {
                if( in_array( $adjac_hex, $empty_spaces ) )
                {
                    $min = min( $space, $adjac_hex );
                    $max = max( $space, $adjac_hex );
                    if( !in_array( [ $min, $max ], $empty_duos ) )
                    {
                        $empty_duos[] = [ $min, $max ];
                    }
                }
            }
        }
        return $empty_duos;
    }

    function getLines ( $empty_spaces, $empty_duos )
    {
        $lines = [];
        foreach( $empty_duos as $duo )
        {
            if( ( $duo[1] - $duo[0] ) == 1 )
            {
                if( in_array( $duo[0]-1, $empty_spaces ) && !in_array( [ $duo[0]-1, $duo[0], $duo[1] ], $lines )
                &&( self::checkAdjacentPieces( $duo[0]-1, $duo[0] ) || self::checkAdjacentPieces( $duo[0]-1, $duo[1] ) ) )
                {
                    $lines[] = [ $duo[0]-1, $duo[0], $duo[1] ];
                }
                if( in_array( $duo[1]+1, $empty_spaces ) && !in_array( [ $duo[0], $duo[1], $duo[1]+1 ], $lines )
                &&( self::checkAdjacentPieces( $duo[1]+1, $duo[0] ) || self::checkAdjacentPieces( $duo[1]+1, $duo[1] ) ) )
                {
                    $lines[] = [ $duo[0], $duo[1], $duo[1]+1 ];
                }
            }
            else if( ( $duo[1] - $duo[0] ) == 9 || ( $duo[1] - $duo[0] ) == 11 )
            {
                if( in_array( $duo[0]-10, $empty_spaces ) && !in_array( [ $duo[0]-10, $duo[0], $duo[1] ], $lines )
                &&( self::checkAdjacentPieces( $duo[0]-10, $duo[0] ) || self::checkAdjacentPieces( $duo[0]-10, $duo[1] ) ) )
                {
                    $lines[] = [ $duo[0]-10, $duo[0], $duo[1] ];
                }
                if( in_array( $duo[1]+10, $empty_spaces ) && !in_array( [ $duo[0], $duo[1], $duo[1]+10 ], $lines )
                &&( self::checkAdjacentPieces( $duo[1]+10, $duo[0] ) || self::checkAdjacentPieces( $duo[1]+10, $duo[1] ) ) )
                {
                    $lines[] = [ $duo[0], $duo[1], $duo[1]+10 ];
                }
            }
            else if( ( $duo[1] - $duo[0] ) == 10 )
            {
                if( (floor($duo[0]/10))%2 == 0 )
                {
                    if( in_array( $duo[0]-9, $empty_spaces ) && !in_array( [ $duo[0]-9, $duo[0], $duo[1] ], $lines )
                    &&( self::checkAdjacentPieces( $duo[0]-9, $duo[0] ) || self::checkAdjacentPieces( $duo[0]-9, $duo[1] ) ) )
                    {
                        $lines[] = [ $duo[0]-9, $duo[0], $duo[1] ];
                    }
                    if( in_array( $duo[1]+9, $empty_spaces ) && !in_array( [ $duo[0], $duo[1], $duo[1]+9 ], $lines )
                    &&( self::checkAdjacentPieces( $duo[1]+9, $duo[0] ) || self::checkAdjacentPieces( $duo[1]+9, $duo[1] ) ) )
                    {
                        $lines[] = [ $duo[0], $duo[1], $duo[1]+9 ];
                    }
                }
                else
                {
                    if( in_array( $duo[0]-11, $empty_spaces ) && !in_array( [ $duo[0]-11, $duo[0], $duo[1] ], $lines )
                    &&( self::checkAdjacentPieces( $duo[0]-11, $duo[0] ) || self::checkAdjacentPieces( $duo[0]-9, $duo[1] ) ) )
                    {
                        $lines[] = [ $duo[0]-11, $duo[0], $duo[1] ];
                    }
                    if( in_array( $duo[1]+11, $empty_spaces ) && !in_array( [ $duo[0], $duo[1], $duo[1]+11 ], $lines )
                    &&( self::checkAdjacentPieces( $duo[1]+11, $duo[0] ) || self::checkAdjacentPieces( $duo[1]+11, $duo[1] ) ) )
                    {
                        $lines[] = [ $duo[0], $duo[1], $duo[1]+11 ];
                    }
                }
            }
        }
        return $lines;
    }

    function getTriangles ( $empty_spaces, $empty_duos )
    {
        $triangles = [];
        foreach( $empty_duos as $duo )
        {
            if( ( $duo[1] - $duo[0] ) == 1 )
            {
                if( (floor($duo[0]/10))%2 == 0 )
                {
                    if( in_array( $duo[0]-9, $empty_spaces ) && !in_array( [ $duo[0]-9, $duo[0], $duo[1] ], $triangles )
                    &&( self::checkAdjacentPieces( $duo[0]-9, $duo[0] ) || self::checkAdjacentPieces( $duo[0]-9, $duo[1] ) ) )
                    {
                        $triangles[] = [ $duo[0]-9, $duo[0], $duo[1] ];
                    }
                    if( in_array( $duo[1]+10, $empty_spaces ) && !in_array( [ $duo[0], $duo[1], $duo[1]+10 ], $triangles )
                    &&( self::checkAdjacentPieces( $duo[1]+10, $duo[0] ) || self::checkAdjacentPieces( $duo[1]+10, $duo[1] ) ) )
                    {
                        $triangles[] = [ $duo[0], $duo[1], $duo[1]+10 ];
                    }
                }
                else
                {
                    if( in_array( $duo[0]-10, $empty_spaces ) && !in_array( [ $duo[0]-10, $duo[0], $duo[1] ], $triangles )
                    &&( self::checkAdjacentPieces( $duo[0]-10, $duo[0] ) || self::checkAdjacentPieces( $duo[0]-10, $duo[1] ) ) )
                    {
                        $triangles[] = [ $duo[0]-10, $duo[0], $duo[1] ];
                    }
                    if( in_array( $duo[1]+9, $empty_spaces ) && !in_array( [ $duo[0], $duo[1], $duo[1]+9 ], $triangles )
                    &&( self::checkAdjacentPieces( $duo[1]+9, $duo[0] ) || self::checkAdjacentPieces( $duo[1]+9, $duo[1] ) ) )
                    {
                        $triangles[] = [ $duo[0], $duo[1], $duo[1]+9 ];
                    }
                }
            }
            else if( ( $duo[1] - $duo[0] ) == 9 )
            {
                if( in_array( $duo[0]-1, $empty_spaces ) && !in_array( [ $duo[0]-1, $duo[0], $duo[1] ], $triangles )
                &&( self::checkAdjacentPieces( $duo[0]-1, $duo[0] ) || self::checkAdjacentPieces( $duo[0]-1, $duo[1] ) ) )
                {
                    $triangles[] = [ $duo[0]-1, $duo[0], $duo[1] ];
                }
                if( in_array( $duo[1]+1, $empty_spaces ) && !in_array( [ $duo[0], $duo[1], $duo[1]+1 ], $triangles )
                &&( self::checkAdjacentPieces( $duo[1]+1, $duo[0] ) || self::checkAdjacentPieces( $duo[1]+1, $duo[1] ) ) )
                {
                    $triangles[] = [ $duo[0], $duo[1], $duo[1]+1 ];
                }
            }
            else if( ( $duo[1] - $duo[0] ) == 10 )
            {
                if( (floor($duo[0]/10))%2 == 0 )
                {
                    if( in_array( $duo[0]-1, $empty_spaces ) && !in_array( [ $duo[0]-1, $duo[0], $duo[1] ], $triangles )
                    &&( self::checkAdjacentPieces( $duo[0]-1, $duo[0] ) || self::checkAdjacentPieces( $duo[0]-1, $duo[1] ) ) )
                    {
                        $triangles[] = [ $duo[0]-1, $duo[0], $duo[1] ];
                    }
                    if( in_array( $duo[1]+1, $empty_spaces ) && !in_array( [ $duo[0], $duo[1], $duo[1]+1 ], $triangles )
                    &&( self::checkAdjacentPieces( $duo[1]+1, $duo[0] ) || self::checkAdjacentPieces( $duo[1]+1, $duo[1] ) ) )
                    {
                        $triangles[] = [ $duo[0], $duo[1], $duo[1]+1 ];
                    }
                }
                else
                {
                    if( in_array( $duo[0]+1, $empty_spaces ) && !in_array( [ $duo[0], $duo[0]+1, $duo[1] ], $triangles )
                    &&( self::checkAdjacentPieces( $duo[0]+1, $duo[0] ) || self::checkAdjacentPieces( $duo[0]+1, $duo[1] ) ) )
                    {
                        $triangles[] = [ $duo[0], $duo[0]+1, $duo[1] ];
                    }
                    if( in_array( $duo[1]-1, $empty_spaces ) && !in_array( [ $duo[0], $duo[1]-1, $duo[1] ], $triangles )
                    &&( self::checkAdjacentPieces( $duo[1]-1, $duo[0] ) || self::checkAdjacentPieces( $duo[1]-1, $duo[1] ) ) )
                    {
                        $triangles[] = [ $duo[0], $duo[1]-1, $duo[1] ];
                    }
                }
            }
            else if( ( $duo[1] - $duo[0] ) == 11 )
            {
                if( in_array( $duo[0]+1, $empty_spaces ) && !in_array( [ $duo[0], $duo[0]+1, $duo[1] ], $triangles )
                &&( self::checkAdjacentPieces( $duo[0]+1, $duo[0] ) || self::checkAdjacentPieces( $duo[0]+1, $duo[1] ) ) )
                {
                    $triangles[] = [ $duo[0], $duo[0]+1, $duo[1] ];
                }
                if( in_array( $duo[1]-1, $empty_spaces ) && !in_array( [ $duo[0], $duo[1]-1, $duo[1] ], $triangles )
                &&( self::checkAdjacentPieces( $duo[1]-1, $duo[0] ) || self::checkAdjacentPieces( $duo[1]-1, $duo[1] ) ) )
                {
                    $triangles[] = [ $duo[0], $duo[1]-1, $duo[1] ];
                }
            }
        }
        return $triangles;
    }

    function getHats ( $empty_spaces, $empty_duos )
    {
        $hats = [];
        foreach( $empty_duos as $duo )
        {
            if( ( $duo[1] - $duo[0] ) == 1 )
            {
                if( (floor($duo[0]/10))%2 == 0 )
                {
                    if( in_array( $duo[0]-10, $empty_spaces ) && !in_array( [ $duo[0]-10, $duo[0], $duo[1] ], $hats )
                    &&( self::checkAdjacentPieces( $duo[0]-10, $duo[0] ) || self::checkAdjacentPieces( $duo[0]-10, $duo[1] ) ) )
                    {
                        $hats[] = [ $duo[0]-10, $duo[0], $duo[1] ];
                    }
                    if( in_array( $duo[0]-8, $empty_spaces ) && !in_array( [ $duo[0]-8, $duo[0], $duo[1] ], $hats )
                    &&( self::checkAdjacentPieces( $duo[0]-8, $duo[0] ) || self::checkAdjacentPieces( $duo[0]-8, $duo[1] ) ) )
                    {
                        $hats[] = [ $duo[0]-8, $duo[0], $duo[1] ];
                    }
                    if( in_array( $duo[1]+9, $empty_spaces ) && !in_array( [ $duo[0], $duo[1], $duo[1]+9 ], $hats )
                    &&( self::checkAdjacentPieces( $duo[1]+9, $duo[0] ) || self::checkAdjacentPieces( $duo[1]+9, $duo[1] ) ) )
                    {
                        $hats[] = [ $duo[0], $duo[1], $duo[1]+9 ];
                    }
                    if( in_array( $duo[1]+11, $empty_spaces ) && !in_array( [ $duo[0], $duo[1], $duo[1]+11 ], $hats )
                    &&( self::checkAdjacentPieces( $duo[1]+11, $duo[0] ) || self::checkAdjacentPieces( $duo[1]+11, $duo[1] ) ) )
                    {
                        $hats[] = [ $duo[0], $duo[1], $duo[1]+11 ];
                    }
                }
                else
                {
                    if( in_array( $duo[0]-11, $empty_spaces ) && !in_array( [ $duo[0]-11, $duo[0], $duo[1] ], $hats )
                    &&( self::checkAdjacentPieces( $duo[0]-11, $duo[0] ) || self::checkAdjacentPieces( $duo[0]-11, $duo[1] ) ) )
                    {
                        $hats[] = [ $duo[0]-11, $duo[0], $duo[1] ];
                    }
                    if( in_array( $duo[0]-9, $empty_spaces ) && !in_array( [ $duo[0]-9, $duo[0], $duo[1] ], $hats )
                    &&( self::checkAdjacentPieces( $duo[0]-9, $duo[0] ) || self::checkAdjacentPieces( $duo[0]-9, $duo[1] ) ) )
                    {
                        $hats[] = [ $duo[0]-9, $duo[0], $duo[1] ];
                    }
                    if( in_array( $duo[1]+8, $empty_spaces ) && !in_array( [ $duo[0], $duo[1], $duo[1]+8 ], $hats )
                    &&( self::checkAdjacentPieces( $duo[1]+8, $duo[0] ) || self::checkAdjacentPieces( $duo[1]+8, $duo[1] ) ) )
                    {
                        $hats[] = [ $duo[0], $duo[1], $duo[1]+8 ];
                    }
                    if( in_array( $duo[1]+10, $empty_spaces ) && !in_array( [ $duo[0], $duo[1], $duo[1]+10 ], $hats )
                    &&( self::checkAdjacentPieces( $duo[1]+10, $duo[0] ) || self::checkAdjacentPieces( $duo[1]+10, $duo[1] ) ) )
                    {
                        $hats[] = [ $duo[0], $duo[1], $duo[1]+10 ];
                    }
                }
            }
            if( ( $duo[1] - $duo[0] ) == 9 )
            {
                if( in_array( $duo[0]-11, $empty_spaces ) && !in_array( [ $duo[0]-11, $duo[0], $duo[1] ], $hats )
                &&( self::checkAdjacentPieces( $duo[0]-11, $duo[0] ) || self::checkAdjacentPieces( $duo[0]-11, $duo[1] ) ) )
                {
                    $hats[] = [ $duo[0]-11, $duo[0], $duo[1] ];
                }
                if( in_array( $duo[0]+1, $empty_spaces ) && !in_array( [ $duo[0], $duo[0]+1, $duo[1] ], $hats )
                &&( self::checkAdjacentPieces( $duo[0]+1, $duo[0] ) || self::checkAdjacentPieces( $duo[0]+1, $duo[1] ) ) )
                {
                    $hats[] = [ $duo[0], $duo[0]+1, $duo[1] ];
                }
                if( in_array( $duo[1]-1, $empty_spaces ) && !in_array( [ $duo[0], $duo[1]-1, $duo[1] ], $hats )
                &&( self::checkAdjacentPieces( $duo[1]-1, $duo[0] ) || self::checkAdjacentPieces( $duo[1]-1, $duo[1] ) ) )
                {
                    $hats[] = [ $duo[0], $duo[1]-1, $duo[1] ];
                }
                if( in_array( $duo[1]+11, $empty_spaces ) && !in_array( [ $duo[0], $duo[1], $duo[1]+11 ], $hats )
                &&( self::checkAdjacentPieces( $duo[1]+11, $duo[0] ) || self::checkAdjacentPieces( $duo[1]+11, $duo[1] ) ) )
                {
                    $hats[] = [ $duo[0], $duo[1], $duo[1]+11 ];
                }
            }
            if( ( $duo[1] - $duo[0] ) == 11 )
            {
                if( in_array( $duo[0]-9, $empty_spaces ) && !in_array( [ $duo[0]-9, $duo[0], $duo[1] ], $hats )
                &&( self::checkAdjacentPieces( $duo[0]-9, $duo[0] ) || self::checkAdjacentPieces( $duo[0]-9, $duo[1] ) ) )
                {
                    $hats[] = [ $duo[0]-9, $duo[0], $duo[1] ];
                }
                if( in_array( $duo[0]-1, $empty_spaces ) && !in_array( [ $duo[0]-1, $duo[0], $duo[1] ], $hats )
                &&( self::checkAdjacentPieces( $duo[0]-1, $duo[0] ) || self::checkAdjacentPieces( $duo[0]-1, $duo[1] ) ) )
                {
                    $hats[] = [ $duo[0]-1, $duo[0], $duo[1] ];
                }
                if( in_array( $duo[1]+1, $empty_spaces ) && !in_array( [ $duo[0], $duo[1], $duo[1]+1 ], $hats )
                &&( self::checkAdjacentPieces( $duo[1]+1, $duo[0] ) || self::checkAdjacentPieces( $duo[1]+1, $duo[1] ) ) )
                {
                    $hats[] = [ $duo[0], $duo[1], $duo[1]+1 ];
                }
                if( in_array( $duo[1]+9, $empty_spaces ) && !in_array( [ $duo[0], $duo[1], $duo[1]+9 ], $hats )
                &&( self::checkAdjacentPieces( $duo[1]+9, $duo[0] ) || self::checkAdjacentPieces( $duo[1]+9, $duo[1] ) ) )
                {
                    $hats[] = [ $duo[0], $duo[1], $duo[1]+9 ];
                }
            }
            if( ( $duo[1] - $duo[0] ) == 10 )
            {
                if( (floor($duo[0]/10))%2 == 0 )
                {
                    if( in_array( $duo[0]-10, $empty_spaces ) && !in_array( [ $duo[0]-10, $duo[0], $duo[1] ], $hats )
                    &&( self::checkAdjacentPieces( $duo[0]-10, $duo[0] ) || self::checkAdjacentPieces( $duo[0]-10, $duo[1] ) ) )
                    {
                        $hats[] = [ $duo[0]-10, $duo[0], $duo[1] ];
                    }
                    if( in_array( $duo[0]+1, $empty_spaces ) && !in_array( [ $duo[0], $duo[0]+1, $duo[1] ], $hats )
                    &&( self::checkAdjacentPieces( $duo[0]+1, $duo[0] ) || self::checkAdjacentPieces( $duo[0]+1, $duo[1] ) ) )
                    {
                        $hats[] = [ $duo[0], $duo[0]+1, $duo[1] ];
                    }
                    if( in_array( $duo[1]-1, $empty_spaces ) && !in_array( [ $duo[0], $duo[1]-1, $duo[1] ], $hats )
                    &&( self::checkAdjacentPieces( $duo[1]-1, $duo[0] ) || self::checkAdjacentPieces( $duo[1]-1, $duo[1] ) ) )
                    {
                        $hats[] = [ $duo[0], $duo[1]-1, $duo[1] ];
                    }
                    if( in_array( $duo[1]+10, $empty_spaces ) && !in_array( [ $duo[0], $duo[1], $duo[1]+10 ], $hats )
                    &&( self::checkAdjacentPieces( $duo[1]+10, $duo[0] ) || self::checkAdjacentPieces( $duo[1]+10, $duo[1] ) ) )
                    {
                        $hats[] = [ $duo[0], $duo[1], $duo[1]+10 ];
                    }
                }
                else
                {
                    if( in_array( $duo[0]-10, $empty_spaces ) && !in_array( [ $duo[0]-10, $duo[0], $duo[1] ], $hats )
                    &&( self::checkAdjacentPieces( $duo[0]-10, $duo[0] ) || self::checkAdjacentPieces( $duo[0]-10, $duo[1] ) ) )
                    {
                        $hats[] = [ $duo[0]-10, $duo[0], $duo[1] ];
                    }
                    if( in_array( $duo[0]-1, $empty_spaces ) && !in_array( [ $duo[0]-1, $duo[0], $duo[1] ], $hats )
                    &&( self::checkAdjacentPieces( $duo[0]-1, $duo[0] ) || self::checkAdjacentPieces( $duo[0]-1, $duo[1] ) ) )
                    {
                        $hats[] = [ $duo[0]-1, $duo[0], $duo[1] ];
                    }
                    if( in_array( $duo[1]+1, $empty_spaces ) && !in_array( [ $duo[0], $duo[1], $duo[1]+1 ], $hats )
                    &&( self::checkAdjacentPieces( $duo[1]+1, $duo[0] ) || self::checkAdjacentPieces( $duo[1]+1, $duo[1] ) ) )
                    {
                        $hats[] = [ $duo[0], $duo[1], $duo[1]+1 ];
                    }
                    if( in_array( $duo[1]+10, $empty_spaces ) && !in_array( [ $duo[0], $duo[1], $duo[1]+10 ], $hats )
                    &&( self::checkAdjacentPieces( $duo[1]+10, $duo[0] ) || self::checkAdjacentPieces( $duo[1]+10, $duo[1] ) ) )
                    {
                        $hats[] = [ $duo[0], $duo[1], $duo[1]+10 ];
                    }
                }
            }
        }
        return $hats;
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    function stNextPlayerGalaxy()
    {
        $player_id = self::activeNextPlayer();
        self::giveExtraTime( $player_id );
        $this->gamestate->nextState( 'playerChoice' );
    }

    function stNextPlayerBlackHole()
    {
        $player_id = self::activeNextPlayer();
        self::giveExtraTime( $player_id );
        $this->gamestate->nextState( 'firstPlayerTurn' );
    }

    function stNextPlayer()
    {

        $player_id = self::activeNextPlayer();
        if( self::checkPossiblePlace() )
        {
            self::giveExtraTime( $player_id );
            $this->gamestate->nextState( 'playerTurn' );
        }
        else
        {
            $opponent_id = self::getOpponentId( $player_id );
            self::DbQuery( "UPDATE player SET player_score=10 WHERE player_id='$opponent_id'" );
            self::notifyAllPlayers( 'info', clienttranslate('${player_name} cannot place a tile and loses the game!'), [
                'player_name' => self::getActivePlayerId() ] );
            $this->gamestate->nextState( 'endGame' );
        }
    }

    function stPlayerTurn()
    {
        self::DbQuery( "UPDATE board SET board_color_save=board_color WHERE 1" );
        self::DbQuery( "UPDATE tile SET tile_location_save=tile_location WHERE 1" );
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */


//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:

        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).

        Important: your zombie code will be called when the player leaves the game. This action is triggered
        from the main site and propagated to the gameserver from a server, not from a browser.
        As a consequence, there is no current player associated to this action. In your zombieTurn function,
        you must _never_ use getCurrentPlayerId() or getCurrentPlayerName(), otherwise it will fail with a "Not logged" error message.
    */

    function zombieTurn( $state, $active_player )
    {
        $statename = $state['name'];

        if ($state['type'] === "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState( "zombiePass" );
                    break;
            }
            return;
        }

        if ($state['type'] === "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $this->gamestate->setPlayerNonMultiactive( $active_player, '' );
            return;
        }

        throw new feException( "Zombie mode not supported at this game state: ".$statename );
    }

///////////////////////////////////////////////////////////////////////////////////:
////////// DB upgrade
//////////

    /*
        upgradeTableDb:

        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.

    */

    function upgradeTableDb( $from_version )
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345
    }
}
