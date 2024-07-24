/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * OrionDuel implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * orionduel.js
 *
 * OrionDuel user interface script
 *
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

const TOOLTIP_DELAY = 500;

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter"
],
function (dojo, declare) {
    return declare("bgagame.orionduel", ebg.core.gamegui, {
        constructor: function(){
            console.log('orionduel constructor');

            this.BLUE = "0eb0cc";
            this.ORANGE = "fba930";

            this.galaxies = [];
            this.black_holes = [];
            this.blue_tiles = [];
            this.orange_tiles = [];

            this.connections = [];
            this.tooltips = {};
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
            console.log(gamedatas);

            // Setting up player boards
            for( var player_id in gamedatas.players )
            {
                var player = gamedatas.players[player_id];
            }

            this.setupPlayersBoard();
            this.setupBoard();

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
            break;
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
                    this.addActionButton( 'placeTileFirst', _("Place Tile"), 'onPlaceTile' );
                    break;
                 case 'playerTurn':
                    this.addActionButton( 'placeTile', _("Place Tile"), 'onPlaceTile' );
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
            if( this.gamedatas.players[ this.player_id ] )
            {
                if( this.gamedatas.players[ this.player_id ].color == this.BLUE )
                {   return true;   }
            }
            return FALSE;
        },

        isCurrentPlayerOrange: function()
        {
            if( this.gamedatas.players[ this.player_id ] )
            {
                if( this.gamedatas.players[ this.player_id ].color == this.ORANGE )
                {   return true;   }
            }
            return FALSE;
        },


        deactivateConnections: function()
        {
            dojo.forEach(this.connections, dojo.disconnect);
            this.connections = [];
        },

        activateConnections: function()
        {
            this.gamedatas.no_tile_squares.forEach( square  =>
            {
                this.connections.push( dojo.connect( $('hex_'+square) , 'click', () => this.onClickOnHex(square) ) );
            });
            this.gamedatas.galaxies.forEach( square =>
            {
                this.connections.push( dojo.connect( $('galaxy_'+square) , 'click', () => this.onClickOnHex( square) ) );
            });
            this.gamedatas.black_holes.forEach( square =>
            {
                this.connections.push( dojo.connect( $('black_hole_'+square) , 'click', () => this.onClickOnHex( square) ) );
            });
        },

        removeHexes: function()
        {

            dojo.query('.h_blue').forEach(dojo.destroy);
            dojo.query('.h_orange').forEach(dojo.destroy);
            dojo.query('.h_green').forEach(dojo.destroy);
            dojo.query('.h_yellow').forEach(dojo.destroy);
        },

        setupPlayersBoard: function ()
        {
            console.info('setupPlayersBoard');
            for( let player_id in this.gamedatas.players )
            {
                const player = this.gamedatas.players[player_id];
                dojo.place( this.format_block('jstpl_player_board', player ), $('player_board_'+player_id) );

                if( player_id == this.player_id )
                {
                    dojo.place( this.format_block( 'jstpl_how_to_win', { } ),'ai_board_'+player_id );
                    this.addCustomTooltip( 'how_to_win_id', this.getTooltipHowToWinContent() );

                    dojo.place( this.format_block( 'jstpl_show_infos', {
                        id: player_id,
                        nb: 0
                        } ),'ai_board_'+player_id );

                    let chk = $("help-mode-chk");
                    dojo.connect(chk, "onchange", () => this.toggleHelpMode(chk.checked));
                    this.addTooltip("help-mode-switch", "", _("Toggle Tooltips on Mobile mode."));
                }
            }
        },

        setupBoard: function()
        {
            console.info('setupBoard');
            this.gamedatas.squares.forEach( square =>
            {
                this.addHex(square);
            });

            this.placeGalaxies( this.gamedatas.galaxies );
            this.placeForbiddenGalaxies( this.gamedatas.forbidden_galaxies );
            this.placeBlackHoles( this.gamedatas.black_holes );
            this.placeTilesInHands( this.gamedatas.tiles_in_hands );
            this.placeBoardTiles( this.gamedatas.blue_tiles, this.gamedatas.orange_tiles );

            dojo.query( '.constel' ).forEach( ( node ) =>
            {
                this.addCustomTooltip( node.id, this.getTooltipConstelContent( node.id.slice( 0, -3 ) ) );
            } );
        },

        addHex: function(square)
        {
            const x = square % 10;
            const y = Math.floor(square / 10);
            const delta = y%2-1;
            const top = 185 + (-delta/2 + x) * 93;
            const left = 770 + y * 0.75 * 107;

            dojo.place( this.format_block( 'jstpl_hex', {
                id:square,
                type:0,
                top:top,
                left:left,
                class:'',
                } ), player_board_id /*'hex'+galaxy*/) ;
        },

        placeGalaxies: function( galaxies, forbidden_galaxies )
        {
            galaxies.forEach( square =>
            {
                const x = square % 10;
                const y = Math.floor( square / 10);
                const delta = y%2-1;
                const top = 194 + (-delta/2 + x) * 93;
                const left = 782 + y * 0.75 * 107;

                dojo.place( this.format_block( 'jstpl_elt_galaxy', {
                    id: square,
                    top:top,
                    left:left,
                    } ), player_board_id );
            });
        },

        placeForbiddenGalaxies: function( forbidden_galaxies )
        {
            forbidden_galaxies.forEach( square =>
            {
                this.placeHex( square,'h_red' );
            });
        },

        placeBlackHoles: function( black_holes )
        {
            black_holes.forEach( square =>
            {
                const x = square % 10;
                const y = Math.floor( square / 10);
                const delta = y%2-1;
                const top = 194 + (-delta/2 + x) * 93;
                const left = 782 + y * 0.75 * 107;

                dojo.place( this.format_block( 'jstpl_elt_black_hole', {
                    id:square,
                    top:top,
                    left:left,
                    } ), player_board_id );
            });
        },

        removeOldHalos: function( )
        {
            var zelements = dojo.query( '.green' );
            zelements.forEach( zelement  =>
            {
                dojo.destroy( zelement.id);
            });
            var zelements = dojo.query( '.red' );
            zelements.forEach( zelement  =>
            {
                dojo.destroy( zelement.id);
            });
            var zelements = dojo.query( '.purple' );
            zelements.forEach( zelement  =>
            {
                dojo.destroy( zelement.id);
            });
            var zelements = dojo.query( '.pink' );
            zelements.forEach( zelement  =>
            {
                dojo.destroy( zelement.id);
            });
        },

        placeTile: function( tile, color )
        {
            const x = tile.square % 10;
            const y = Math.floor( tile.square / 10);
            const delta = y%2-1;
            const top = 183 + (-delta/2 + x) * 93;
            const left = 770 + y * 0.75 * 107;
            const appears = (tile.new == 1) ? 'appears':'';

            dojo.place( this.format_block( 'jstpl_tile', {
                id:tile.square,
                color:color,
                top:top,
                left:left,
                appears:appears,
                } ), player_board_id );
        },

        placeHex: function( square, color )
        {
            const x = square % 10;
            const y = Math.floor( square / 10);
            const delta = y%2-1;
            const top = 183 + (-delta/2 + x) * 93;
            const left = 770 + y * 0.75 * 107;

            dojo.place( this.format_block( 'jstpl_tile', {
                id:square,
                color:color,
                top:top,
                left:left,
                appears:'',
                } ), player_board_id );
            this.connections.push( dojo.connect( $(color+'_tile_'+square) , 'click', () => this.onClickOnHex( square) ) );
        },

        placeHalo: function( square, color )
        {
            const x = square % 10;
            const y = Math.floor( square / 10);
            const delta = y%2-1;
            const top = 183 + (-delta/2 + x) * 93;
            const left = 770 + y * 0.75 * 107;

            dojo.place( this.format_block( 'jstpl_tile', {
                id:square,
                color:color,
                top:top,
                left:left,
                appears:"",
                } ), player_board_id );
        },

        placeBoardTiles: function( blue_tiles, orange_tiles )
        {
            this.removeOldHalos();

            blue_tiles.forEach( tile =>
            {
                this.placeTile( tile, 'blue' );
                if( tile.new == 1)
                {
                    this.placeTile( tile, 'pink' );
                }
                else if( tile.new == 2)
                {
                    this.placeTile( tile, 'green' );
                }
                else if( tile.new == 3)
                {
                    this.placeTile( tile, 'red' );
                }
                else if( tile.new == 5)
                {
                    this.placeTile( tile, 'purple' );
                }
            });
            orange_tiles.forEach( tile =>
            {
                this.placeTile( tile, 'orange' );
                if( tile.new == 1)
                {
                    this.placeTile( tile, 'pink' );
                }
                else if( tile.new == 2)
                {
                    this.placeTile( tile, 'green' );
                }
                else if( tile.new == 3)
                {
                    this.placeTile( tile, 'red' );
                }
                else if( tile.new == 5)
                {
                    this.placeTile( tile, 'purple' );
                }
            });
        },

        addTile: function( id, color, top, left )
        {
            dojo.place( this.format_block( 'jstpl_tile', {
                id:'hnd_'+id,
                color:color,
                top:top,
                left:left,
                appears:'',
                } ), player_board_id );
        },

        addTileCount: function( type, value, inverted, top, left )
        {
            dojo.place( this.format_block( 'jstpl_tile_in_hand', {
                id:'nb_'+type,
                inverted:inverted,
                nb:value,
                top:top,
                left:left,
                } ), player_board_id );

            this.tiles_in_hand_counters[type] = new ebg.counter();
            this.tiles_in_hand_counters[type].create('nb_'+type);
            this.tiles_in_hand_counters[type].setValue(value);
        },

        placeTilesInHands: function( tiles_in_hands )
        {
            this.tiles_in_hand_counters = {};
            const inverted = this.isCurrentPlayerBlue() ? 'inverted' : '';
            Object.entries(tiles_in_hands).forEach(([key, value]) => {
                if( key == 1 & value > 0 )
                {
                    this.addTile( '1_1','orange',1241,260);
                    this.addTileCount(key,value, inverted, 1221,120);
                }
                else if( key == 2 & value > 0 )
                {
                    this.addTile( '2_1','orange',1077,260);
                    this.addTile( '2_2','orange',1122,337);
                    this.addTileCount(key,value, inverted, 1083,120);
                }
                else if( key == 3 & value > 0 )
                {
                    this.addTile( '3_2','orange',929,260);
                    this.addTile( '3_1','blue',974,337);
                    this.addTileCount(key,value, inverted, 945,120);
                }
                else if( key == 4 & value > 0 )
                {
                    this.addTile( '4_3','orange',769,259);
                    this.addTile( '4_2','orange',814,336);
                    this.addTile( '4_1','blue',858,413);
                    this.addTileCount(key,value, inverted, 807,120);
                }
                else if( key == 5 & value > 0 )
                {
                    this.addTile( '5_3','orange',604,259);
                    this.addTile( '5_2','orange',649,336);
                    this.addTile( '5_1','blue',603,412);
                    this.addTileCount(key,value, inverted, 669,120);
                }
                else if( key == 6 & value > 0 )
                {
                    this.addTile( '6_3','orange',469,259);
                    this.addTile( '6_2','orange',380,260);
                    this.addTile( '6_1','blue',424,338);
                    this.addTileCount(key,value, inverted, 394,120);
                }
                if( key == 7 & value > 0 )
                {
                    this.addTile( '7_1','blue',57,2075);
                    this.addTileCount(key,value, inverted, 77,2215);
                }
                else if( key == 8 & value > 0 )
                {
                    this.addTile( '8_2','blue',219,2075);
                    this.addTile( '8_1','blue',174,1997);
                    this.addTileCount(key,value, inverted, 215,2215);
                }
                else if( key == 9 & value > 0 )
                {
                    this.addTile( '9_2','blue',368,2075);
                    this.addTile( '9_1','orange',324,1998);
                    this.addTileCount(key,value, inverted, 353,2215);
                }
                else if( key == 10 & value > 0 )
                {
                    this.addTile( '10_3','blue',528,2076);
                    this.addTile( '10_2','blue',484,1999);
                    this.addTile( '10_1','orange',440,1922);
                    this.addTileCount(key,value, inverted, 491,2215);
                }
                else if( key == 11 & value > 0 )
                {
                    this.addTile( '11_3','blue',694,2076);
                    this.addTile( '11_2','blue',649,1999);
                    this.addTile( '11_1','orange',695,1922);
                    this.addTileCount(key,value, inverted, 629,2215);
                }
                else if( key == 12 & value > 0 )
                {
                    this.addTile( '12_3','blue',829,2075);
                    this.addTile( '12_2','blue',918,2074);
                    this.addTile( '12_1','orange',873,1998);
                    this.addTileCount(key,value, inverted, 894,2215);
                }
            });
        },

        updateTileInHand: function( nb_type, type )
        {
            const color1 = (type < 7 ) ? 'orange' : 'blue';
            const color2 = (type < 7 ) ? 'blue' : 'orange';
            if( nb_type == 0 )
            {
                if( (type-1)%6 < 2 )
                {
                    this.fadeOutAndDestroy( color1+'_tile_hnd_'+type+'_1' );
                }
                else
                {
                    this.fadeOutAndDestroy( color2+'_tile_hnd_'+type+'_1' );
                }
                if( (type-1)%6 > 0 )
                {
                    this.fadeOutAndDestroy( color1+'_tile_hnd_'+type+'_2' );
                }
                if( (type-1)%6 > 2 )
                {
                    this.fadeOutAndDestroy( color1+'_tile_hnd_'+type+'_3' );
                }
                this.fadeOutAndDestroy( 'text_container_nb_'+type );
            }
            else
            {
                this.tiles_in_hand_counters[type].incValue(-1);
            }
        },

   /**********************
     ****** HELP MODE ******
     **********************/
    /**
     * Toggle help mode
     */
        toggleHelpMode(b)
        {
            if (b)
                this.activateHelpMode();
            else
                this.desactivateHelpMode();
        },

        activateHelpMode()
        {
            this._helpMode = true;
            dojo.addClass('ebd-body', 'help-mode');
            this._displayedTooltip = null;
            document.body.addEventListener('click', this.closeCurrentTooltip.bind(this));
        },

        desactivateHelpMode()
        {
            this.closeCurrentTooltip();
            this._helpMode = FALSE;
            dojo.removeClass('ebd-body', 'help-mode');
            document.body.removeEventListener('click', this.closeCurrentTooltip.bind(this));
        },

        closeCurrentTooltip()
        {
            if (!this._helpMode)
                return;

            if (this._displayedTooltip == null)
                return;
            else
            {
                this._displayedTooltip.close();
                this._displayedTooltip = null;
            }
        },

    /*
     * Custom connect that keep track of all the connections
     *  and wrap clicks to make it work with help mode
     */
        connect(node, action, callback)
        {
            this._connections.push(dojo.connect($(node), action, callback));
        },

        onClick(node, callback, temporary = true)
        {
            let safeCallback = (evt) =>
            {
                evt.stopPropagation();
                if (this.isInterfaceLocked())
                    return FALSE;
                if (this._helpMode)
                    return FALSE;
                callback(evt);
            };

            if (temporary)
            {
                this.connect($(node), 'click', safeCallback);
                dojo.removeClass(node, 'unselectable');
                dojo.addClass(node, 'selectable');
                this._selectableNodes.push(node);
            }
            else
            {
                dojo.connect($(node), 'click', safeCallback);
            }
        },

    /**
     * Tooltip to work with help mode
     */
        registerCustomTooltip(html, id = null)
        {
            id = id || this.game_name + '-tooltipable-' + this._customTooltipIdCounter++;
            this._registeredCustomTooltips[id] = html;
            return id;
        },

        attachRegisteredTooltips()
        {
            Object.keys(this._registeredCustomTooltips).forEach((id) =>
            {
                if ($(id))
                {
                    this.addCustomTooltip(id, this._registeredCustomTooltips[id], { forceRecreate: true });
                }
            });
            this._registeredCustomTooltips = {};
        },

        addCustomTooltip(id, html, config = {})
        {
            config = Object.assign
            ({
                delay: 400,
                midSize: true,
                forceRecreate: FALSE,
                updateIfExisting: true
            }, config,);

            if(!config.updateIfExisting && this.tooltips[id])
                return;
             // Handle dynamic content out of the box

            let getContent = () =>
            {
                let content = typeof html === 'function' ? html() : html;
                if (config.midSize)
                {
                    content = '<div class="midSizeDialog">' + content + '</div>';
                }
                return content;
            };
            if (this.tooltips[id] && !config.forceRecreate)
            {
                this.tooltips[id].getContent = getContent;
                return;
            }
            let tooltip = new dijit.Tooltip(
            {
                getContent,
                position: this.defaultTooltipPosition,
                showDelay: config.delay,
            });
            this.tooltips[id] = tooltip;
            dojo.addClass(id, 'tooltipable');
            dojo.connect($(id), 'click', (evt) =>
            {
                if (!this._helpMode)
                {
                    tooltip.close();
                }
                else
                {
                    evt.stopPropagation();

                    if (tooltip.state == 'SHOWING')
                    {
                        this.closeCurrentTooltip();
                    }
                    else
                    {
                        if( tooltip.getContent() != '<div class="midSizeDialog"></div>' )
                        {
                            this.closeCurrentTooltip();
                            tooltip.open($(id));
                            this._displayedTooltip = tooltip;
                        }
                    }
                }
            });

            tooltip.showTimeout = null;
            dojo.connect($(id), 'mouseenter', (evt) =>
            {
                evt.stopPropagation();
                if (!this._helpMode && !this._dragndropMode)
                {
                    if (tooltip.showTimeout != null)
                        clearTimeout(tooltip.showTimeout);
                    if( tooltip.getContent() != '<div class="midSizeDialog"></div>' )
                    {
                        tooltip.showTimeout = setTimeout(() =>
                        {
                            if ($(id))
                                tooltip.open($(id));
                        }, config.delay);
                    }
                }
            });

            dojo.connect($(id), 'mouseleave', (evt) =>
            {
                evt.stopPropagation();
                if (!this._helpMode && !this._dragndropMode)
                {
                    tooltip.close();
                    if (tooltip.showTimeout != null)
                        clearTimeout(tooltip.showTimeout);
                }
            });
        },


        /******************************
         ****** TOOLTIPS ******
         *****************************/
        getTooltipConstelContent: function( constel_name )
        {
            let html = "<div class='tooltip_content'>";
            html += "<span class='constel_name'>"+_(this.gamedatas.constellations[constel_name])+"</span></div>";

            return html;
        },

        getTooltipHowToWinContent: function( )
        {
            let html = "<div class='tooltip_content'>";
            html += "<span>"+_("How to Win (for Blue)")+"</span></br>";
            html += '<hr/>';
            html += "<span class='constel_name'>"+_("LINK CONSTELLATIONS OF THE SAME COLOR")+"</span>";
            html += "<div class='win_link' id='win_link_id'></div>"
            html += '<hr/>';
            html += "<span class='constel_name'>"+_("CONNECT 4 GALAXIES")+"</span>";
            html += "<div class='win_galaxies' id='win_galaxies_id'></div>"
            html += '<hr/>';
            html += "<span class='constel_name'>"+_("FORCE YOUR OPPONENT TO CONNECT 3 BLACK HOLES")+"</span>";
            html += "<div class='win_black_holes' id='win_black_holes_id'></div>"
            html += '<hr/>';
            html += "</div>";

            return html;
        },


        /** Override this function to inject html into log items. This is a built-in BGA method.  */
        /* @Override */
        format_string_recursive: function (log, args)
        {
            try
            {
                if (log && args && !args.processed)
                {
                    // Representation of chosen character name
                    if( args.constel_name !== undefined )
                    {
                        args.constel_name = "<span style='font-weight:bold;color:#" + args.constel_color + "'>" + _(args.constel_name) + "</span>";
                    }
                    if( args.constel2_name !== undefined )
                    {
                        args.constel2_name = "<span style='font-weight:bold;color:#" + args.constel_color + "'>" + _(args.constel2_name) + "</span>";
                    }
                }
            }
            catch (e)
            {
                console.error(log, args, "Exception thrown", e.stack);
            }
            return this.inherited(arguments);
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

            var MAP_WIDTH = 2419;
            var MAP_HEIGHT = 1396;

            var gameWidth = MAP_WIDTH;
            var gameHeight = MAP_HEIGHT;

            var horizontalScale = document.getElementById('game_play_area').clientWidth / gameWidth;
            var verticalScale = (window.innerHeight - 0) / gameHeight;

            var scale = Math.min(1, horizontalScale, verticalScale);

            var resized_div = document.getElementById('resized_id');
            var play_area_height = dojo.marginBox("player_board_id").h;

            resized_div.style.transform = scale === 1 ? '' : "scale(".concat(scale, ")");
            if( this.isCurrentPlayerBlue() )
            {
                dojo.addClass('player_board_id', 'board-inverted');
            }

            dojo.style("resized_id",'height', (play_area_height*scale)+'px');
        },

        onClickOnHex: function(square)
        {
            console.info('onClickOnHex');
            console.log(square);
            if( this.checkAction( 'placeGalaxy', true ) )
            {
                if( $('h_green_tile_'+square ) )
                {
                    dojo.destroy( $('h_green_tile_'+square) );
                    const index = this.galaxies.indexOf(square);
                    this.galaxies.splice(index, 1);
                }
                else
                {
                    this.placeHex( square,'h_green' );
                    this.galaxies.push(square)
                }
            }
            if( this.checkAction( 'placeBlackHole', true ) )
            {
                if( !$('h_red_tile_'+square ) && !$('galaxy_'+square ) )
                {
                    if( $('h_yellow_tile_'+square ) )
                    {
                        dojo.destroy( $('h_yellow_tile_'+square) );
                        const index = this.black_holes.indexOf(square);
                        this.black_holes.splice(index, 1);
                    }
                    else
                    {
                        this.placeHex( square,'h_yellow' );
                        this.black_holes.push(square)
                    }
                }
            }
            if( this.checkAction( 'placeTile', true ) )
            {
                if( !$('blue_tile_'+square ) && !$('orange_tile_'+square ))
                {
                    if( this.isCurrentPlayerBlue() )
                    {
                        if( $('h_blue_tile_'+square ) )
                        {
                            dojo.destroy( $('h_blue_tile_'+square) );
                            const index = this.blue_tiles.indexOf(square);
                            this.blue_tiles.splice(index, 1);
                            this.placeHex( square,'h_orange' );
                            this.orange_tiles.push(square);
                        }
                        else if( $('h_orange_tile_'+square ) )
                        {
                            dojo.destroy( $('h_orange_tile_'+square) );
                            const index = this.orange_tiles.indexOf(square);
                            this.orange_tiles.splice(index, 1);
                        }
                        else
                        {
                            this.placeHex( square,'h_blue' );
                            this.blue_tiles.push(square);
                        }
                    }
                    else if( this.isCurrentPlayerOrange() )
                    {
                        if( $('h_orange_tile_'+square ) )
                        {
                            dojo.destroy( $('h_orange_tile_'+square) );
                            const index = this.orange_tiles.indexOf(square);
                            this.orange_tiles.splice(index, 1);
                            this.placeHex( square,'h_blue' );
                            this.blue_tiles.push(square);
                        }
                        else if( $('h_blue_tile_'+square ) )
                        {
                            dojo.destroy( $('h_blue_tile_'+square) );
                            const index = this.blue_tiles.indexOf(square);
                            this.blue_tiles.splice(index, 1);
                        }
                        else
                        {
                            this.placeHex( square,'h_orange' );
                            this.orange_tiles.push(square);
                        }
                    }
                }
            }
        },

        onEndGalaxies: function(evt)
        {
            if( this.checkAction( 'placeGalaxy', true ) )
            {
                let galaxies = '';
                Object.values(this.galaxies).forEach( square  => {
                    galaxies += ( square+'_' );
                } );

                this.ajaxcall( "/orionduel/orionduel/chooseGalaxies.html", {
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
                Object.values(this.black_holes).forEach( square  => {
                    black_holes += ( square+'_' );
                } );

                this.ajaxcall( "/orionduel/orionduel/chooseBlackHoles.html", {
                        lock: true,
                        black_holes: black_holes },
                    this, function( result ) {}, function( is_error) {} );
            }
        },

        onPlaceTile: function(evt)
        {
            if( this.checkAction( 'placeTile', true ) )
            {
                let blue_tiles = '';
                Object.values(this.blue_tiles).forEach( square  => {
                    blue_tiles += ( square+'_' );
                } );
                let orange_tiles = '';
                Object.values(this.orange_tiles).forEach( square  => {
                    orange_tiles += ( square+'_' );
                } );

                this.ajaxcall( "/orionduel/orionduel/placeTile.html", {
                        lock: true,
                        blue_tiles: blue_tiles,
                        orange_tiles: orange_tiles},
                    this, function( result ) {}, function( is_error) {} );
            }
        },

        onTestRandom: function(evt)
        {
            if( this.checkAction( 'placeTile', true ) )
            {
               this.ajaxcall( "/orionduel/orionduel/testRandom.html", {
                        lock: true },
                    this, function( result ) {}, function( is_error) {} );
            }
        },


        onPlayerPass: function(evt)
        {
            if( this.checkAction( 'pass', true ) )
            {
               this.ajaxcall( "/orionduel/orionduel/playerPass.html", {
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
                  your orionduel.game.php file.

        */
        setupNotifications: function()
        {
            console.log( 'notifications subscriptions setup' );

            // TODO: here, associate your game notifications with local methods

            var notifications=[
                ['galaxiesChoice'],
                ['blackHolesChoice'],
                ['placeTiles']
            ];
            var notifications_nodelay=[
                ['displayInfo'],
                ['constellation_road'],
                ['galaxy_win'],
                ['black_hole_win'],
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

        notif_displayInfo: function( notif )
        {
            console.log( 'notif_displayInfo' );
            console.log( notif );
        },


        notif_constellation_road: function( notif )
        {
            console.log( 'notif_constellation_road' );
            console.log( notif );

            this.scoreCtrl[ notif.args.player_id ].incValue( 1 );
            notif.args.road.forEach( hex =>
            {
                this.placeHalo( hex, 'green' );
            });
        },

        notif_galaxy_win: function( notif )
        {
            console.log( 'notif_galaxy_win' );
            console.log( notif );

            this.scoreCtrl[ notif.args.player_id ].incValue( 1 );
            notif.args.chain.forEach( hex =>
            {
                this.placeHalo( hex, 'purple' );
            });
        },

        notif_black_hole_win: function( notif )
        {
            console.log( 'notif_black_hole_win' );
            console.log( notif );

            this.scoreCtrl[ notif.args.player_id ].incValue( 1 );
            notif.args.chain.forEach( hex =>
            {
                this.placeHalo( hex, 'red' );
            });
        },

        notif_galaxiesChoice: function( notif )
        {
            console.log( 'galaxiesChoice' );
            console.log( notif );

            dojo.query('.h_green').forEach(dojo.destroy);
            this.placeGalaxies( notif.args.galaxies );
            this.gamedatas.galaxies = notif.args.galaxies;
            this.placeForbiddenGalaxies( notif.args.forbidden_galaxies );

        },

        notif_blackHolesChoice: function( notif )
        {
            console.log( 'blackHolesChoice' );
            console.log( notif );

            dojo.query('.h_red').forEach(dojo.destroy);
            dojo.query('.h_yellow').forEach(dojo.destroy);

            this.placeBlackHoles( notif.args.black_holes );
            this.gamedatas.black_holes = notif.args.black_holes;
        },

        notif_placeTiles: function( notif )
        {
            console.log( 'placeTiles' );
            console.log( notif );

            dojo.query('.h_blue').forEach(dojo.destroy);
            dojo.query('.h_orange').forEach(dojo.destroy);
            this.blue_tiles = [];
            this.orange_tiles = [];

            this.placeBoardTiles( notif.args.blue_pieces, notif.args.orange_pieces );
            this.updateTileInHand( notif.args.nb_type, notif.args.type );
        },
   });
});
