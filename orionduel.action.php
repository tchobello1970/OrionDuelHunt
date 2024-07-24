<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * OrionDuel implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 *
 * orionduel.action.php
 *
 * OrionDuel main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/orionduel/orionduel/myAction.html", ...)
 *
 */


class action_orionduel extends APP_GameAction
{
    // Constructor: please do not modify
    public function __default()
    {
        if( self::isArg( 'notifwindow') )
        {
            $this->view = "common_notifwindow";
            $this->viewArgs['table'] = self::getArg( "table", AT_posint, true );
        }
        else
        {
            $this->view = "orionduel_orionduel";
            self::trace( "Complete reinitialization of board game" );
        }
    }

    public function chooseGalaxies()
    {
        self::setAjaxMode();

        $galaxies = self::getArg( "galaxies",  AT_alphanum_dash, true );
        $this->game->chooseGalaxies( $galaxies );
        self::ajaxResponse();
    }

    public function chooseBlackHoles()
    {
        self::setAjaxMode();

        $black_holes = self::getArg( "black_holes",  AT_alphanum_dash, true );
        $this->game->chooseBlackHoles( $black_holes );
        self::ajaxResponse();
    }

    public function placeTile()
    {
        self::setAjaxMode();

        $blue_tiles = self::getArg( "blue_tiles",  AT_alphanum_dash, true );
        $orange_tiles = self::getArg( "orange_tiles",  AT_alphanum_dash, true );
        $this->game->placeTileOnBoard( $blue_tiles, $orange_tiles );
        self::ajaxResponse();
    }

    public function playerPass()
    {
        self::setAjaxMode();

        $this->game->playerPass();
        self::ajaxResponse();
    }

    public function testRandom()
    {
        self::setAjaxMode();

        $this->game->testRandom();
        self::ajaxResponse();
    }
  }
