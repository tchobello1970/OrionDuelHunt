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
  * orionduelhunt.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */


require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );

define('BLUE_COLOR','0eb0cc');
define('ORANGE_COLOR','fba930');

class OrionDuelHunt extends Table
{
    function __construct( )
    {
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();

        self::initGameStateLabels( array(
            //    "my_first_global_variable" => 10,
            //    "my_second_global_variable" => 11,
            //      ...

            "board_display"        => 100
        ) );
    }

    protected function getGameName( )
    {
        // Used for translations and stuff. Please do not modify.
        return "orionduelhunt";
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
        $values = array();
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
        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        self::initStat( 'table', 'turns_nb', 0 );

        self::initStat( 'player', 'turns_nb', 0);
/*        self::initStat( 'player', 'tiles_up_placed_nb', 0 );
        self::initStat( 'player', 'tiles_down_placed_nb', 0 );
        self::initStat( 'player', 'pass_turns_nb', 0 );

        $sql = "INSERT INTO gamestats ( stat_type, stat_save, stat_inc ) VALUES ";
        $sql_values = [];
        $sql_values[] = "( 'tiles_up_placed_nb',0,0 )";
        $sql_values[] = "( 'tiles_down_placed_nb',0,0 )";
        $sql_values[] = "( 'pass_turns_nb',0,0 )";
        $sql .= implode( ',', $sql_values );
        self::DbQuery( $sql );*/
    }

    function updateStatistics()
    {
/*
    update official game stats with table gamestats.
    reset gamestats table

*/
/*        $player_id = self::getActivePlayerId();
        $stats_inc = self::getCollectionFromDb( "SELECT stat_type, stat_inc FROM gamestats WHERE stat_inc!=0" , true );
        foreach( $stats_inc as $type => $inc )
        {
            self::incStat( $inc, $type, $player_id );
        }
        self::DbQuery("UPDATE gamestats SET stat_inc=0 WHERE stat_inc!=0" );*/
    }

    function resetStatistics()
    {
/*
    reset gamestats table (when cancelling action
*/
//        self::DbQuery("UPDATE gamestats SET stat_inc=0 WHERE stat_inc!=0" );
    }

    function initializeGame()
    {
        self::createSquares();
        self::createTiles();
        self::notifyAllPlayers('info', clienttranslate( 'Welcome to ORION DUEL !' ), [] );
    }


    function stGamePreparation()
    {
        if( self::getGameStateValue('board_display' ) != 1 )
        {
            self::createGalaxiesAndBlackHoles();
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
        $sql = "INSERT INTO board ( board_square,board_color,board_black_hole, board_galaxy, board_color_save ) VALUES ";
        $sql_values = [];
        foreach( $this->board_squares as $square )
        {
            $sql_values[] = "( '$square','0','0','0','0' )";
        }
        $sql .= implode( ',', $sql_values );
        self::DbQuery( $sql );
    }

    function createGalaxiesAndBlackHoles()
    {
 /* predefined modes 1, 2 and radom */
        if( self::getGameStateValue('board_display' ) == 2)
        {
            foreach( $this->galaxies_1 as $square )
            {
                self::DbQuery( "UPDATE board SET board_galaxy=1 WHERE board_square='$square'" );
            }
            foreach( $this->black_holes_1 as $square )
            {
                self::DbQuery( "UPDATE board SET board_black_hole=1 WHERE board_square='$square'" );
            }
        }
        else if( self::getGameStateValue('board_display' ) == 3)
        {
            foreach( $this->galaxies_2 as $square )
            {
                self::DbQuery( "UPDATE board SET board_galaxy=1 WHERE board_square='$square'" );
            }
            foreach( $this->black_holes_2 as $square )
            {
                self::DbQuery( "UPDATE board SET board_black_hole=1 WHERE board_square='$square'" );
            }
        }
        else if( self::getGameStateValue('board_display' ) == 0)
        {
            self::createRandomBoardLayout();
        }
    }

    function createRandomBoardLayout()
    {
        $available_galaxy_hexes = $this->board_squares;
        $available_black_hole_hexes = $this->board_squares;
        shuffle( $available_galaxy_hexes );
        shuffle( $available_black_hole_hexes );

        for( $i=1 ; $i<9; $i++)
        {
            // picks first value from shuffled array
            $galaxy = array_shift( $available_galaxy_hexes );
            self::DbQuery( "UPDATE board SET board_galaxy=1 WHERE board_square='$galaxy'" );

            // value is removed from black holes array
            if( in_array( $galaxy, $available_black_hole_hexes ) )
            {
                array_splice($available_black_hole_hexes, array_search($galaxy, $available_black_hole_hexes ), 1);
            }
            $one_hex = self::getAdjacentToHex( $galaxy );
            foreach( $one_hex as $hex )
            {
                // remove adjacents for galaxies and black holes
                if( in_array( $hex, $available_galaxy_hexes ) )
                {
                    array_splice($available_galaxy_hexes, array_search($hex, $available_galaxy_hexes ), 1);
                }
                if( in_array( $hex, $available_black_hole_hexes ) )
                {
                    array_splice($available_black_hole_hexes, array_search($hex, $available_black_hole_hexes ), 1);
                }
            }
            foreach( $one_hex as $hex )
            {
                $two_hex = self::getAdjacentToHex( $hex );
                foreach( $two_hex as $hex2 )
                {
                    // remove 2-space for other galaxies
                    if( in_array( $hex2, $available_galaxy_hexes ) )
                    {
                        array_splice($available_galaxy_hexes, array_search($hex2, $available_galaxy_hexes ), 1);
                    }
                }
            }
        }
        for( $i=1 ; $i<8; $i++)
        {
            // picks first value from shuffled array
            $black_hole = array_shift( $available_black_hole_hexes );
            self::DbQuery( "UPDATE board SET board_black_hole=1 WHERE board_square='$black_hole'" );

            $one_hex = self::getAdjacentToHex( $black_hole );
            foreach( $one_hex as $hex )
            { // remove adjacents for black holes
                if( in_array( $hex, $available_black_hole_hexes ) )
                {
                    array_splice($available_black_hole_hexes, array_search($hex, $available_black_hole_hexes ), 1);
                }
            }
            foreach( $one_hex as $hex )
            {
                $two_hex = self::getAdjacentToHex( $hex );
                foreach( $two_hex as $hex )
                {
                    // remove 2-space for other black holes
                    if( in_array( $hex, $available_black_hole_hexes ) )
                    {
                        array_splice($available_black_hole_hexes, array_search($hex, $available_black_hole_hexes ), 1);
                    }
                }
            }
        }
     }

    function getAdjacentToHex( $hex )
    {
        $adjacents = [];
        if( (floor($hex/10)) % 2 == 0 )
        {
            $delta_array = in_array( $hex, [40,60] ) ? $this->adjacent_hexes_40_60 : $this->adjacent_hexes_even;
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


    /*
        getAllDatas:

        Gather all informations about current game situation (visible by the current player).

        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = array();

        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!

        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score FROM player ";
        $result['players'] = self::getCollectionFromDb( $sql );

        $result['squares'] = $this->board_squares;
        $result['no_tile_squares'] = self::getNoTileSquares();
        $result['galaxies'] = self::getGalaxies();
        $result['black_holes'] = self::getBlackHoles();
        return $result;
    }

    /*
        getGameProgression:

        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).

        This method is called each time we are in a game state with the "updateGameProgression" property set to true
        (see states.inc.php)
    */
    function getGameProgression()
    {
        // TODO: compute and return the game progression

        return 0;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////

    /*
        In this space, you can put any utility methods useful for your game logic
    */
    function checkConstellationRoad( )
    {

    }

    function checkGalaxyBonus()
    {

    }

    function checkBlackHolesMalus()
    {

    }

    function getGalaxies()
    {
        return self::getObjectListFromDb( "SELECT board_square square FROM board WHERE board_galaxy=1" );
    }

    function getBlackHoles()
    {
        return self::getObjectListFromDb( "SELECT board_square square FROM board WHERE board_black_hole=1" );
    }

    function getNoTileSquares()
    {
        return self::getObjectListFromDb( "SELECT board_square square FROM board WHERE board_color=0" );
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in orionduelhunt.action.php)
    */



    function chooseGalaxies( $galaxies )
    {
/* checks and DB update */
        self::checkAction( 'placeGalaxy' );

        $pieces = explode("_", $galaxies);
        $last_piece = array_pop( $pieces ); //remove useless _

        if( count( $pieces ) != 8 )
            throw new BgaUserException( "You must place 8 Galaxies" );

        if( self::checkValidGalaxies( $pieces ) == false )
            throw new BgaUserException( "Galaxies spacing is not valid" );
        foreach( $pieces as $piece )
        {
            self::DbQuery( "UPDATE board SET board_galaxy=1 WHERE board_square='$piece'" );
        }

        self::notifyAllPlayers( 'galaxiesChoice', clienttranslate('${player_name} places 8 Galaxies'), [
            'player_name' => self::getActivePlayerName(),
            'player_id' => self::getActivePlayerId(),
            'galaxies' => self::getGalaxies() ] );
        $this->gamestate->nextState( 'nextPlayer' );
    }

    function checkValidGalaxies( $galaxies )
    {
/* checks if spacing between galaxies is valid */
        $available_galaxy_hexes = $this->board_squares;

        forEach( $galaxies as $galaxy )
        {
            if( !in_array( $galaxy, $available_galaxy_hexes ) )
            {
                self::notifyAllPlayers('info', clienttranslate( 'false galax ${bearer}' ), ['bearer' => $galaxy ] );

                return false;
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
        return true;
    }


    function chooseBlackHoles( $black_holes )
    {
/* checks and DB update */
        self::checkAction( 'placeBlackHole' );

        $pieces = explode("_", $black_holes);
        $last_piece = array_pop( $pieces ); //remove useless _

        if( count( $pieces ) != 7 )
            throw new BgaUserException( "You must place 7 Black Holes" );
        if( self::checkValidBlackHoles( $pieces ) == false )
            throw new BgaUserException( "Black Holes spacing is not valid" );
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
/* checks of spacing between galaxies and black holes is valid */
        $available_black_holes_hexes = $this->board_squares;
        $galaxies = self::getObjectListFromDb( "SELECT board_square square FROM board WHERE board_galaxy=1" );

        forEach( $galaxies as $galaxy )
        {
            array_splice($available_black_holes_hexes, array_search($galaxy['square'], $available_black_holes_hexes ), 1);
            $one_hex = self::getAdjacentToHex( $galaxy['square'] );
            foreach( $one_hex as $hex )
            { // remove adjacents to galaxies
                if( in_array( $hex, $available_black_holes_hexes ) )
                {
                    array_splice($available_black_holes_hexes, array_search($hex, $available_black_holes_hexes ), 1);
                }
            }
        }
        forEach( $black_holes as $black_hole )
        {
            if( !in_array( $black_hole, $available_black_holes_hexes ) )
            {
                return false;
            }
            $one_hex = self::getAdjacentToHex( $black_hole );
            foreach( $one_hex as $hex )
            { // remove adjacents to black holes
                if( in_array( $hex, $available_black_holes_hexes ) )
                {
                    array_splice($available_black_holes_hexes, array_search($hex, $available_black_holes_hexes ), 1);
                }
            }
            foreach( $one_hex as $hex )
            {
                // remove 2-space for other black holes
                $two_hex = self::getAdjacentToHex( $hex );
                foreach( $two_hex as $hex2 )
                {
                    if( in_array( $hex2, $available_black_holes_hexes ) )
                    {
                        array_splice($available_black_holes_hexes, array_search($hex2, $available_black_holes_hexes ), 1);
                    }
                }
            }
        }
        return true;
    }

    function playerPass()
    {
        self::checkAction( 'pass' );
            self::notifyAllPlayers( 'playerPass', clienttranslate('${player_name} passes'), [
                'player_name' => self::getActivePlayerName() ]);
            $this->gamestate->nextState( 'pass' );
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
/* TODO check if player can play : immediate loss if impossible */

        $player_id = self::activeNextPlayer();
        self::giveExtraTime( $player_id );
        $this->gamestate->nextState( 'playerTurn' );
    }



    /*

    Example for game state "MyGameState":

    function argMyGameState()
    {
        // Get some values from the current game situation in database...

        // return values:
        return array(
            'variable1' => $value1,
            'variable2' => $value2,
            ...
        );
    }
    */

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */

    /*

    Example for game state "MyGameState":

    function stMyGameState()
    {
        // Do some stuff ...

        // (very often) go to another gamestate
        $this->gamestate->nextState( 'some_gamestate_transition' );
    }
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

        // Example:
//        if( $from_version <= 1404301345 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        if( $from_version <= 1405061421 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        // Please add your future database scheme changes here
//
//


    }
}
