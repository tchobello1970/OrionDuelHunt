/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * OrionDuelHunt implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * orionduelhunt.js
 *
 * OrionDuelHunt user interface script
 *
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter"
],
function (dojo, declare) {
    return declare("bgagame.orionduelhunt", ebg.core.gamegui, {
        constructor: function(){
            console.log('orionduelhunt constructor');

            this.resizedDiv = document.getElementById('resized_id');
            this.height = dojo.marginBox("play_area_id").h;

            this.BLUE = "0eb0cc";
            this.ORANGE = "fba930";

            this.galaxies = [];
            this.black_holes = [];

            this.connections = [];
        },

        /*
            setup:

            This method must set up the game user interface according to current game situation specified
            in parameters.

            The method is called each time the game interface is displayed to a player, ie:
            _ when the game starts
            _ when a player refreshes the game page (F5)

            "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
        */

        setup: function( gamedatas )
        {
            console.log( "Starting game setup" );

            // Setting up player boards
            for( var player_id in gamedatas.players )
            {
                var player = gamedatas.players[player_id];

                // TODO: Setting up players boards if needed
            }

            // TODO: Set up your game interface here, according to "gamedatas"

            this.setupBoard();
            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            console.log( "Ending game setup" );
        },


        ///////////////////////////////////////////////////
        //// Game & client states

        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function( stateName, args )
        {
            console.log( 'Entering state: '+stateName );

            switch( stateName )
            {
            case 'firstPlayerTurn':
            case 'playerTurn':
            case 'galaxiesChoice':
            case 'blackHolesChoice':
                this.activateConnections();
            case 'dummmy':
                break;
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function( stateName )
        {
            console.log( 'Leaving state: '+stateName );

            switch( stateName )
            {
            case 'firstPlayerTurn':
            case 'playerTurn':
            case 'galaxiesChoice':
            case 'blackHolesChoice':
                this.deactivateConnections();
				this.removeHexes();
            break;


            case 'dummmy':
                break;
            }
        },

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //
        onUpdateActionButtons: function( stateName, args )
        {
            console.log( 'onUpdateActionButtons: '+stateName );

            if( this.isCurrentPlayerActive() )
            {
                switch( stateName )
                {

                 case 'galaxiesChoice':
                    this.addActionButton( 'endGalaxies', _("End Turn"), 'onEndGalaxies' );
                    break;
                 case 'blackHolesChoice':
                    this.addActionButton( 'endBlackHoles', _("End Turn"), 'onEndBlackHoles' );
                    break;
                 case 'firstPlayerTurn':
                    this.addActionButton( 'playerPass', _("Pass"), 'onPlayerPass' );
                    break;
                }
            }
        },

        ///////////////////////////////////////////////////
        //// Utility methods

        /*

            Here, you can defines some utility methods that you can use everywhere in your javascript
            script.

        */
        isCurrentPlayerBlue: function()
        {
            console.log(this.gamedatas.players[ this.player_id ].color);
            if( this.gamedatas.players[ this.player_id ] )
            {
                if( this.gamedatas.players[ this.player_id ].color == this.BLUE )
                {   return true;   }
            }
            return false;
        },

        deactivateConnections: function()
        {
            dojo.forEach(this.connections, dojo.disconnect);
            this.connections = [];
        },

        activateConnections: function()
        {
            this.gamedatas.no_tile_squares.forEach( id  =>
            {
//                console.info(id.square);
                this.connections.push( dojo.connect( $('hex_'+id.square) , 'click', () => this.onClickOnHex(id.square) ) );
            });
            console.log( 'connections'  );
            console.log(this.connections);
        },

        removeHexes: function()
		{
            dojo.query( '.hex_black_hole' ).removeClass( 'hex_black_hole' );
			dojo.query( '.hex_galaxy' ).removeClass( 'hex_galaxy' );
			dojo.query( '.hex_blue_tile' ).removeClass( 'hex_blue_tile' );
			dojo.query( '.hex_orange_tile' ).removeClass( 'hex_orange_tile' );
		},


        addHex: function(square)
        {
            const x = square % 10;
            const y = Math.floor(square / 10);
            const delta = y%2-1;

            var top = 185 + (-delta/2 + x) * 93;
            var left = 770 + y * 0.75 * 107;
            dojo.place( this.format_block( 'jstpl_hex', {
                id:square,
                type:0,
                top:top,
                left:left,
                class:'',
                } ), player_board_id/*'hex'+galaxy*/);
        },

        setupBoard: function()
        {
            console.log( 'squares'  );
            console.log(this.gamedatas.squares);

            console.log( 'no_tile_squares'  );
            console.log(this.gamedatas.no_tile_squares);

            this.gamedatas.squares.forEach( square =>
            {
                this.addHex(square);
            });

            console.log('sqwarzzz');
        //    console.log( this.gamedatas.squares );
            console.log( this.gamedatas.galaxies );
            console.log( this.gamedatas.black_holes );
            console.log( 'tiles_in_hands' );
            console.log( this.gamedatas.tiles_in_hands );			
			
            this.placeGalaxies( this.gamedatas.galaxies );
            this.placeBlackHoles( this.gamedatas.black_holes );
        },

        placeGalaxies: function( galaxies )
        {
            galaxies.forEach( galaxy =>
            { // 474,850 13 1 et 3
                const x = galaxy.square % 10;
                const y = Math.floor(galaxy.square / 10);
                const delta = y%2-1;
                const top = 187 + (-delta/2 + x) * 93;
                const left = 778 + y * 0.75 * 107;
                dojo.place( this.format_block( 'jstpl_elt_galaxy', {
                    id:galaxy.square,
                    top:top,
                    left:left,
                    } ), player_board_id );
            });
        },

        placeBlackHoles: function( black_holes )
        {
            black_holes.forEach( black_hole =>
            {
                const x = black_hole.square % 10;
                const y = Math.floor(black_hole.square / 10);
                const delta = y%2-1;
                const top = 187 + (-delta/2 + x) * 93;
                const left = 778 + y * 0.75 * 107;
                dojo.place( this.format_block( 'jstpl_elt_black_hole', {
                    id:black_hole.square,
                    top:top,
                    left:left,
                    } ), player_board_id );
            });
        },

        ///////////////////////////////////////////////////
        //// Player's action

        /*

            Here, you are defining methods to handle player's action (ex: results of mouse click on
            game objects).

            Most of the time, these methods:
            _ check the action is possible at this game state.
            _ make a call to the game server

        */

        onScreenWidthChange: function()
        {
            /* Tisaac Boiler Plate
            * Remove non standard zoom property
            */
            this.gameinterface_zoomFactor = 1;
            dojo.style('page-content', 'zoom', '');
            dojo.style('page-title', 'zoom', '');
            dojo.style('right-side-first-part', 'zoom', '');

            this.default_viewport = "width=" + this.interface_min_width;

// doesn't work            screen.orientation.lock('landscape');

            var MAP_WIDTH = 2419;
            var MAP_HEIGHT = 1396;
//            var RATIO = MAP_WIDTH / MAP_HEIGHT;

            var gameWidth = MAP_WIDTH;
            var gameHeight = MAP_HEIGHT;

            var horizontalScale = document.getElementById('game_play_area').clientWidth / gameWidth;
            var verticalScale = (window.innerHeight - 0) / gameHeight;

            this.scale = Math.min(1, horizontalScale, verticalScale);

            this.resizedDiv.style.transform = this.scale === 1 ? '' : "scale(".concat(this.scale, ")");
            if( this.isCurrentPlayerBlue() )
            {
                dojo.addClass('player_board_id', 'board-inverted');
            }

            dojo.style("resized_id",'height', (this.height*this.scale)+'px');
        },

        onClickOnHex: function(square)
        {
            console.info('onClickOnHex');

            if( this.checkAction( 'placeGalaxy', true ) )
            {
                if( dojo.hasClass( 'hex_'+square,'hex_galaxy' ) )
                {
                    dojo.removeClass( 'hex_'+square,'hex_galaxy' );
                    const index = this.galaxies.indexOf(square);
                    this.galaxies.splice(index, 1);
                }
                else
                {
                    dojo.addClass( 'hex_'+square,'hex_galaxy' );
                    this.galaxies.push(square)
                }
            }
            if( this.checkAction( 'placeBlackHole', true ) )
            {
                if( dojo.hasClass( 'hex_'+square,'hex_black_hole' ) )
                {
                    dojo.removeClass( 'hex_'+square,'hex_black_hole' );
                    const index = this.black_holes.indexOf(square);
                    this.black_holes.splice(index, 1);
                }
                else
                {
                    dojo.addClass( 'hex_'+square,'hex_black_hole' );
                    this.black_holes.push(square)
                }
            }
        },

        onEndGalaxies: function(evt)
        {
            if( this.checkAction( 'placeGalaxy', true ) )
            {
                let galaxies = '';
                Object.values(this.galaxies).forEach( square  =>
                {
                    galaxies += ( square+'_' );
                } );

                this.ajaxcall( "/orionduelhunt/orionduelhunt/chooseGalaxies.html", {
                        lock: true,
                        galaxies: galaxies },
                    this, function( result ) {}, function( is_error) {} );
            }
        },

        onEndBlackHoles: function(evt)
        {
            if( this.checkAction( 'placeBlackHole', true ) )
            {
                let black_holes = '';
                Object.values(this.black_holes).forEach( square  =>
                {
                    black_holes += ( square+'_' );
                } );

                this.ajaxcall( "/orionduelhunt/orionduelhunt/chooseBlackHoles.html", {
                        lock: true,
                        black_holes: black_holes },
                    this, function( result ) {}, function( is_error) {} );
            }
        },

        onPlayerPass: function(evt)
        {
            if( this.checkAction( 'pass', true ) )
            {
               this.ajaxcall( "/orionduelhunt/orionduelhunt/playerPass.html", {
                        lock: true },
                    this, function( result ) {}, function( is_error) {} );
            }
        },

        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:

            In this method, you associate each of your game notifications with your local method to handle it.

            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your orionduelhunt.game.php file.

        */
        setupNotifications: function()
        {
            console.log( 'notifications subscriptions setup' );

            // TODO: here, associate your game notifications with local methods

            var notifications=[
                ['galaxiesChoice'],
                ['blackHolesChoice'],


            ];
            var notifications_nodelay=[
            ];

            for(i=0;i<notifications.length;i++)
            {
                dojo.subscribe( notifications[i], this, "notif_"+ notifications[i]);
                this.notifqueue.setSynchronous( notifications[i], 500 );
            }
            for(i=0;i<notifications_nodelay.length;i++)
            {
                dojo.subscribe( notifications_nodelay[i], this, "notif_"+ notifications_nodelay[i]);
            }
        },

        // TODO: from this point and below, you can write your game notifications handling methods
        notif_galaxiesChoice: function( notif )
        {
            console.log( 'galaxiesChoice' );
            console.log( notif );
			dojo.query( '.hex_galaxy' ).removeClass( 'hex_galaxy' );
            this.placeGalaxies( notif.args.galaxies );
        },

        notif_blackHolesChoice: function( notif )
        {
            console.log( 'blackHolesChoice' );
            console.log( notif );
			dojo.query( '.hex_black_hole' ).removeClass( 'hex_black_hole' );
            this.placeBlackHoles( notif.args.black_holes );
        },

   });
});
