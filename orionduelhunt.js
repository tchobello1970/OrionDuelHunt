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
            
            /* Example:
            
            case 'myGameState':
            
                // Show some HTML block at this game state
                dojo.style( 'my_html_block_id', 'display', 'block' );
                
                break;
           */
           
           
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
            
            /* Example:
            
            case 'myGameState':
            
                // Hide the HTML block we are displaying only during this game state
                dojo.style( 'my_html_block_id', 'display', 'none' );
                
                break;
           */
           
           
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
/*               
                 Example:
 
                 case 'myGameState':
                    
                    // Add 3 action buttons in the action status bar:
                    
                    this.addActionButton( 'button_1_id', _('Button 1 label'), 'onMyMethodToCall1' ); 
                    this.addActionButton( 'button_2_id', _('Button 2 label'), 'onMyMethodToCall2' ); 
                    this.addActionButton( 'button_3_id', _('Button 3 label'), 'onMyMethodToCall3' ); 
                    break;
*/
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
/*            for (let i = 0; i < 10; i++) {
                for (let j = 0; j < 10; j++) {
                    this.addHex(i,j);
                }
            }*/
			
			this.gamedatas.squares.forEach( square =>
			{
				this.addHex(square);
			});
			
            console.log('sqwarzzz');
            console.log( this.gamedatas.squares );
            console.log( this.gamedatas.galaxies );
            console.log( this.gamedatas.black_holes );
            this.gamedatas.galaxies.forEach( galaxy =>
            { // 474,850 13 1 et 3
                const x = galaxy.square % 10;
                const y = Math.floor(galaxy.square / 10);
			    const delta = y%2-1;
                const top = 185 + (-delta/2 + x) * 93;
                const left = 770 + y * 0.75 * 107;

                dojo.place( this.format_block( 'jstpl_piece', {
                    id:'galaxy_'+galaxy.square,
                    type:0,
                    top:top,
                    left:left,
                    class:'galaxy',
                    } ), player_board_id/*'hex'+galaxy*/);

            });
            this.gamedatas.black_holes.forEach( black_hole =>
            {
                const x = black_hole.square % 10;
                const y = Math.floor(black_hole.square / 10);
			    const delta = y%2-1;
                const top = 185 + (-delta/2 + x) * 93;
                const left = 770 + y * 0.75 * 107;
                dojo.place( this.format_block( 'jstpl_piece', {
                    id:'black_hole_'+black_hole.square,
                    type:0,
                    top:top,
                    left:left,
                    class:'black_hole',
                    } ), player_board_id/*'hex'+b*/);

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
            console.log( 'horizontal_scale '+horizontalScale);
            console.log( 'vertical_scale '+verticalScale);
                
            this.scale = Math.min(1, horizontalScale, verticalScale);
/*            TODO : check with MEDIA QUUERY
            if( screen.orientation.type == 'landscape-primary' )
            {
                this.resizedDiv.style.transform = this.scale === 1 ? '' : "scale(".concat(this.scale, ")");    
                if( this.isCurrentPlayerBlue() )
                {
                    dojo.addClass('player_board_id','board-inverted');
                }
            }
            else
            {
                //this.scale = Math.min(1, Math.max( horizontalScale, verticalScale));
                this.resizedDiv.style.transform = this.scale === 1 ? '' : "scale(".concat(this.scale, ")");
                dojo.addClass('player_board_id', this.isCurrentPlayerBlue() ? 'board-blue-inverted' :'board-orange-inverted');
            }
*/
            this.resizedDiv.style.transform = this.scale === 1 ? '' : "scale(".concat(this.scale, ")");    
            if( this.isCurrentPlayerBlue() )
            {
                dojo.addClass('player_board_id', 'board-inverted');
            }


            console.log( 'orientation ')
            console.log(screen.orientation.type);
            
            dojo.style("resized_id",'height', (this.height*this.scale)+'px');
            
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
            
            // Example 1: standard notification handling
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            
            // Example 2: standard notification handling + tell the user interface to wait
            //            during 3 seconds after calling the method in order to let the players
            //            see what is happening in the game.
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            // this.notifqueue.setSynchronous( 'cardPlayed', 3000 );
            // 
        },  
        
        // TODO: from this point and below, you can write your game notifications handling methods
        
        /*
        Example:
        
        notif_cardPlayed: function( notif )
        {
            console.log( 'notif_cardPlayed' );
            console.log( notif );
            
            // Note: notif.args contains the arguments specified during you "notifyAllPlayers" / "notifyPlayer" PHP call
            
            // TODO: play the card in the user interface.
        },    
        
        */
   });             
});
