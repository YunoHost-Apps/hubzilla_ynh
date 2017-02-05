<script>
    var chess_game_id = '{{$game_id}}';
    var chess_timer = null;
    var chess_myturn = {{$myturn}};
    var chess_board = null;
    var chess_viewing_history = false;
    var chess_original_pos = '{{$position}}';
    var chess_new_pos = [];
    var chess_viewing_position = '';
    var chess_viewing_mid = '';
    var chess_game_ended = {{$ended}};
    var chess_notify_enabled = {{$notifications}};
    var chess_notify_granted = false;
    var chess_notify_turn = true;
    var chess_notify_audio = {};
      
    var chess_notification_init = function () {
        if (chess_notify_enabled === 0) {
            return;
        }
        if (!("Notification" in window)) {
            window.console.log("This browser does not support system notifications");
        }
        // Let's check whether notification permissions have already been granted
        else if (Notification.permission === "granted") {
            // If it's okay let's create a notification
            chess_notify_granted = true; //var notification = new Notification("Hi there!");
        }

        // Otherwise, we need to ask the user for permission
        else if (Notification.permission !== 'denied') {
            Notification.requestPermission(function (permission) {
                // If the user accepts, let's create a notification
                if (permission === "granted") {
                    chess_notify_granted = true; //var notification = new Notification("Hi there!");
                }
            });
        }
        
    }
    var chess_init = function () {
        chess_notification_init();
        $("#chess-verify-move").hide();
        if($("#chess-game-" + chess_game_id).length) {
            $("#chess-game-" + chess_game_id).css("background-color","#F0D9B5");
        }
        $("#chess-revert").hide();
        $("#expand-aside").on('click', function () {
            setTimeout(chess_fit_board(),500)
        });
        if (chess_game_ended === 1) {
            $("#chess-turn-indicator").html('Game Over');
            $("#chess-resume-game").show();
        } else {            
            $("#chess-resume-game").hide();
        }
        $("#id_chess_notify_enable").change(function() {
            if($(this).is(":checked")) {
                chess_notify_enabled = 1; 
            } else {
                chess_notify_enabled = 0;
            }
            chess_update_settings();            
        });
        // Encode a wav audio file in base64 and create the audio object for game alerts
        var base64string = 'UklGRr4VAABXQVZFZm10IBAAAAABAAEAIlYAACJWAAABAAgAZGF0YZkVAACAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIBxcnJycoGNjY2NjYyMjIyMg3FxcXFxcXJycnJ0jY2NjY2NjIyMjIx1cXFxcXFxcnJycoKNjY2NjY2MjIyMgXFxcXFxcXJycnJ2jY2NjY2NjIyMjIxzcXFxcXFxcnJycoSNjY2NjY2MjIyMgHFxcXFxcXJycnJ4jY2NjY2NjIyMjIxycXFxcXFycnJycoWNjY2NjY2MjIyMf3FxcXFxcXJycnJ5jY2NjY2NjIyMjIxxcXFxcXFycnJycoeNjY2NjY2MjIyMfnFxcXFxcXJycnJ7jY2NjY2NjIyMjIpxcXFxcXFycnJycomNjY2NjYyMjIyMfHFxcXFxcXJycnJ8jY2NjY2NjIyMjIhycnJycoyLi4uLi4uLioqKfHFxcXFxcnJycnJyc4yMjIuLi4uLi4uKin5xcXFxcnJycnJyc3OMjIyMi4uLi4uLi4p/cXFxcnJycnJyc3Nzi4yMjIyLi4uLi4uLgHBwcXFxcXFxcnJycouNjY2NjYyMjIyMjIBwcHFxcXFxcXJycnKLjY2NjY2MjIyMjIyBcHFxcXFxcXJycnJyio2NjY2NjIyMjIyMgnBxcXFxcXFycnJycomNjY2NjY2MjIyMjINwcXFxcXFxcnJycnKIjY2NjY2NjIyMjIyEcHFxcXFxcXJycnJyh42NjY2NjYyMjIyMhXBxcXFxcXFycnJycoaNjY2NjY2MjIyMjIZwcXFxcXFxcnJycnKFjY2NjY2NjIyMjIyHcHFxcXFxcXJycnJyhI2NjY2NjYyMjIyMiHBxcXFxcXFycnJycoONjY2NjY2MjIyMjIlwcXFxcXFxcnJycnKCjY2NjY2NjIyMjIyKcHFxcXFxcXJycnJygY2NjYyMjIyLi4uLi4uLioqKioqKdnBxcXFxcXFycnKAi4uLi4uLioqKioJxcXFxcXFycnJydIyMi4uLi4uLi4qKdXFxcXFycnJycnKBjIyLi4uLi4uLioFxcXFycnJycXFydY2NjYyMjIyMjIyLc3BwcXFxcXFxcnKDjY2NjIyMjIyMjIBwcHFxcXFxcXJyd42NjY2MjIyMjIyMcnBxcXFxcXFycnKFjY2NjYyMjIyMjH9wcXFxcXFxcnJyeY2NjY2NjIyMjIyMcHFxcXFxcXJycnKHjY2NjY2MjIyMjH5xcXFxcXFycnJyeo2NjY2NjIyMjIyKcHFxcXFxcXJycnKIjY2NjY2MjIyMjHxxcXFxcXFycnJyfI2NjY2NjYyMjIyJcXFxcXFxcXJycnKKjY2NjY2MjIyMjHtxcXFxcXFycnJyfo2NjY2NjYyMjIyHcXFxcXFxcnJycnKMjY2NjY2MjIuLi3lycnJycnJzc3Nzf4yMjIyMjIuLi4uFcnJycnJycnNzc3OMjIyMjIyMi4uLi3hycnJycnJzc3NzgIyMjIyMjIuLi4uDcnJycnJycnNzc3SMjIyMjIyMi4uLi3dycnJycnJzc3NzgYyMjIyMjIuLi4uCcnJycnJyc3Nzc3aMjIyMjIyMi4uLi3VycnJycnJzc3Nzg4yMjIyMjIuLi4uAcnJycnJyc3Nzc3eMjIyMjIyMi4uLi3RycnJycnJzc3NzhIyMjIyMjIuLi4uAcnJycnJyc3Nzc3mMjIyMjIyMi4uLi3JycnJycnJzc3NzhoyMjIyMjIuLi4t+cnJycnJyc3Nzc3qMjIyMjIyLi4uLinJycnNzc3N0dHR0h4uLi4uLi4qKiop9c3Nzc3Nzc3R0dHyLi4uLi4uLioqKiHNzc3Nzc3N0dHR0iIuLi4uLi4qKiop8c3Nzc3Nzc3R0dH2Li4uLi4uLioqKhnNzc3Nzc3N0dHR0iYuLi4uLi4qKiop6c3Nzc3Nzc3R0dH+Li4uLi4uLioqKhXNzc3Nzc3N0dHR0i4uLi4uLi4qKiop5c3Nzc3Nzc3R0dICLi4uLi4uLioqKhHNzc3Nzc3N0dHR1i4uLi4uLi4qKiop4c3Nzc3NzdHR0dIGLi4uLi4uLioqKgnNzc3Nzc3N0dHR2i4uLi4uLi4qKiop2c3Nzc3NzdHR0dIKLi4uLi4uLioqKgXNzc3Nzc3N0dHR3i4uLi4uLi4qKiol2dHR0dHR0dHV1dYOKioqKioqKiYmJgHR0dHR0dHR0dXV5ioqKioqKiomJiYl0dHR0dHR0dHV1dYSKioqKioqKiYmJf3R0dHR0dHR0dXV7ioqKioqKiomJiYl0dHR0dHR0dHV1dYaKioqKioqKiYmJfnR0dHR0dHR1dXV8ioqKioqKioqJiYd0dHR0dHR0dHV1dYeKioqKioqKiYmJfHR0dHR0dHR1dXV9ioqKioqKiomJiYZ0dHR0dHR0dHV1dYiKioqKioqKiYmJe3R0dHR0dHR1dXV+ioqKioqKiomJiYV0dHR0dHR0dHV1dYmKioqKioqKiYmJenR0dHR0dHR1dXWAioqKiYmJiYmIiIN1dXV1dXV1dXZ2domJiYmJiYmJiYiIeXV1dXV1dXV1dnaAiYmJiYmJiYmIiIJ1dXV1dXV1dXZ2d4mJiYmJiYmJiYiIeHV1dXV1dXV1dnaBiYmJiYmJiYmIiIF1dXV1dXV1dXZ2eImJiYmJiYmJiYiId3V1dXV1dXV1dnaCiYmJiYmJiYmIiIB1dXV1dXV1dXZ2eYmJiYmJiYmJiIiIdnV1dXV1dXV1dnaDiYmJiYmJiYmIiH91dXV1dXV1dXZ2e4mJiYmJiYmJiIiIdXV1dXV1dXV1dnaFiYmJiYmJiYmIiH51dXV1dXV1dXZ2fImJiYmJiYmJiIiHdXV1dXV1dXV1dnaGiYmJiYmJiYmIiH11dXV1dXV1dXZ2fYiIiIiIiIiIiIiFdnZ2dnZ2dnZ2d3eGiIiIiIiIiIiIh3x2dnZ2dnZ2dnd3foiIiIiIiIiIiIeEdnZ2dnZ2dnZ2d3eHiIiIiIiIiIiIh3t2dnZ2dnZ2dnZ3f4iIiIiIiIiIiIeDdnZ2dnZ2dnZ2d3eIiIiIiIiIiIiIh3p2dnZ2dnZ2dnZ3gIiIiIiIiIiIiIeCdnZ2dnZ2dnZ2d3iIiIiIiIiIiIiIh3l2dnZ2dnZ2dnZ3gIiIiIiIiIiIiIeBdnZ2dnZ2dnZ2d3mIiIiIiIiIiIiHh3h2dnZ2dnZ2dnZ3goiIiIiIiIiIiIeAdnZ2dnZ2dnZ2d3qIiIiIiIiIiIiHh3d2dnZ2dnZ2dnZ3g4iIiIiIh4eHh4eAd3d3d3d3d3d3d3uHh4eHh4eHh4eHhnd3d3d3d3d3d3d3g4eHh4eHh4eHh4d/d3d3d3d3d3d3d3yHh4eHh4eHh4eHhnd3d3d3d3d3d3d4hIeHh4eHh4eHh4d+d3d3d3d3d3d3d32Hh4eHh4eHh4eHhXd3d3d3d3d3d3d4hYeHh4eHh4eHh4d9d3d3d3d3d3d3d36Hh4eHh4eHh4eHhHd3d3d3d3d3d3d4hoeHh4eHh4eHh4d8d3d3d3d3d3d3d3+Hh4eHh4eHh4eHg3d3d3d3d3d3d3d4h4eHh4eHh4eHh4d7d3d3d3d3d3d3d4CHh4eHh4eHh4eHgnd3d3d3d3d3d3d5h4aGhoaGhoaGhoZ7eHh4eHh4eHh4eICGhoaGhoaGhoaGgXh4eHh4eHh4eHh6h4aGhoaGhoaGhoZ6eHh4eHh4eHh4eIGGhoaGhoaGhoaGgHh4eHh4eHh4eHh7hoaGhoaGhoaGhoZ5eHh4eHh4eHh4eIKGhoaGhoaGhoaGgHh4eHh4eHh4eHh7hoaGhoaGhoaGhoZ4eHh4eHh4eHh4eIKGhoaGhoaGhoaGf3h4eHh4eHh4eHh8hoaGhoaGhoaGhoV4eHh4eHh4eHh4eIOGhoaGhoaGhoaGfnh4eHh4eHh4eHh9hoaGhoaGhoaGhoR4eHh4eHh4eHh4eISGhoaGhoaGhoaGfXh4eHh4eHh4eHh+hoaGhoaGhoaGhoR4eHh4eHh5eXl5eYSFhYWFhYWFhYWFfXl5eXl5eXl5eXl/hYWFhYWFhYWFhYJ5eXl5eXl5eXl5eYWFhYWFhYWFhYWFfHl5eXl5eXl5eXmAhYWFhYWFhYWFhYF5eXl5eXl5eXl5eYWFhYWFhYWFhYWFe3l5eXl5eXl5eXmAhYWFhYWFhYWFhYF5eXl5eXl5eXl5eoWFhYWFhYWFhYWFe3l5eXl5eXl5eXmAhYWFhYWFhYWFhYB5eXl5eXl5eXl5e4WFhYWFhYWFhYWFenl5eXl5eXl5eXmBhYWFhYWFhYWFhYB5eXl5eXl5eXl5fIWFhYWFhYWFhYWFeXl5eXl5eXl5eXmChYWFhYWFhYWFhX95enp6f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f39/f4CAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAA';
        chess_notify_audio = new Audio("data:audio/wav;base64," + base64string);
        
        var cfg = {
            sparePieces: true,
            position: '{{$position}}',
            orientation: '{{$color}}',
            dropOffBoard: 'snapback',
            onDragStart: chess_onDragStart,
            onDrop: chess_onDrop
        };
        chess_board = ChessBoard('chessboard', cfg);
        $(window).resize(chess_fit_board);
        setTimeout(chess_fit_board,300);
        setTimeout(chess_get_history,300);
        chess_timer = setTimeout(chess_update_game,300);
    };
    
    var chess_update_settings = function () {
        var updated_settings = { notify_enabled: chess_notify_enabled };
	$.post("chess/settings", {settings: JSON.stringify(updated_settings)}, 
            function(data) {
                if (data['status']) {
                } else {
                    window.console.log('Error updating settings ' + JSON.stringify(updated_settings) + ':' + data['errormsg']);
                }
                return false;
            },
        'json');
    };
    
    var chess_fit_board = function () {
        var viewportHeight = $(window).height() - $("#chessboard").offset().top;
        var leftOffset = 0;
        if($(window).width() > 767) {
            leftOffset = $('#region_1').width() + $('#region_1').offset().left;                
        } else if ($("main").hasClass('region_1-on') ) {
            //window.console.log('z-index: ' + $('#region_1').css('zIndex'));
            $('#chessboard').css('zIndex',-100);
            $('#region_1').css('background-color', 'rgba(255,255,255,0.8)');
            //leftOffset = $('#region_1').width();
        } else {
            $('#chessboard').css('zIndex','auto');
        }     
        $("#chessboard").offset({ left: leftOffset});
        var centerRegionWidth = $('#region_2').width();
        if (viewportHeight < centerRegionWidth * 1.25) {
            $("#chessboard").css('width', viewportHeight / 1.25);
        } else {
            $("#chessboard").css('width', centerRegionWidth * 1.0);
        }
        chess_board.resize();
    };
    // only allow pieces to be dragged when the board is oriented
    // in their direction
    var chess_onDragStart = function(source, piece, position, orientation) {
      if ((orientation === 'white' && piece.search(/^w/) === -1) ||
          (orientation === 'black' && piece.search(/^b/) === -1) ||
          (!chess_myturn) || chess_game_ended || chess_viewing_history) {
        return false;
      }
    };
    
    var chess_onDrop = function(source, target, piece, newPos, oldPos, orientation) {
        if(ChessBoard.objToFen(newPos) === ChessBoard.objToFen(oldPos)) {
            return false;
        }     
        chess_new_pos.push(ChessBoard.objToFen(newPos));
        chess_verify_move();
    };
    
    var chess_issue_notification = function (theBody,theTitle) {
        if (!chess_notify_enabled || !chess_notify_granted ) {
            return;
        }
        var nIcon = "/addon/chess/view/img/chesspieces/wikipedia/wN.png";
        if (chess_board.position() === 'black') {
            nIcon = "/addon/chess/view/img/chesspieces/wikipedia/bN.png";
        }
        var options = {
            body: theBody,
            icon: nIcon,
            silent: false
        }
        var n = new Notification(theTitle,options);
        n.onclick = function (event) {
            //alert("you clicked the notification!");
            setTimeout(n.close.bind(n), 300); 
        } 
        chess_notify_audio.play();
    }
    
    var chess_update_game = function () {
	$.post("chess/update", {game_id: chess_game_id} , 
            function(data) {
                if (data['status']) {
                    chess_board.position(data['position']);
                    if (data['position'] !== chess_original_pos) {
                        setTimeout(chess_get_history,1000);
                    }
                    chess_original_pos = data['position'];
                    chess_myturn = data['myturn'];
                    chess_game_ended = data['ended'];
                    if (chess_game_ended) {
                        $('#chess-turn-indicator').html("Game Over");
                        $("#chess-resume-game").show();
                        return false;
                    }
                    if (chess_myturn) {
                        $('#chess-turn-indicator').html("Your turn");
                        if (chess_notify_turn) {
                            chess_notify_turn = false;
                            chess_issue_notification("Your turn!", "Hubzilla Chess");
                        }
                    } else {
                        $('#chess-turn-indicator').html("Opponent's turn");  
                        chess_notify_turn = true;
                    }
                } else {
                    window.console.log('Error updating: ' + data['errormsg']);
                }
                return false;
            },
        'json');
	chess_timer = setTimeout(chess_update_game,5000);
    };
    
    var chess_verify_move = function () {
        clearTimeout(chess_timer);
        $("#chess-verify-move").show();
        if(!$("main").hasClass('region_1-on')) {
            $("main").addClass('region_1-on');
        }

        setTimeout(chess_fit_board,300);
    }
    
    var chess_accept_move = function () {
        if(chess_new_pos.length < 1 || chess_new_pos[chess_new_pos.length-1] === chess_original_pos) { 
            return false; 
        }        
        var newPos = chess_new_pos[chess_new_pos.length-1];
        chess_myturn = false;
        $.post("chess/move", {game_id: chess_game_id, newPosFEN: newPos} , function(data) {
            if (data['status']) {
                chess_new_pos = [];
                chess_original_pos = newPos;
                $("#chess-verify-move").hide();
                if($("main").hasClass('region_1-on')) {
                    $("main").removeClass('region_1-on');
                }
                chess_fit_board();
                chess_timer = setTimeout(chess_update_game,300);
            } else {
                window.console.log('Error: ' + data['errormsg']);
            }
            return false;
            },
        'json');
    }
    
    var chess_undo_move = function () {
        chess_new_pos.pop();
        if (chess_new_pos.length > 0) {
            chess_board.position(chess_new_pos[chess_new_pos.length-1]);
        } else {
            chess_board.position(chess_original_pos);
            chess_timer = setTimeout(chess_update_game,300);
            $("#chess-verify-move").hide();
        }
        if($("main").hasClass('region_1-on')) {
            $("main").removeClass('region_1-on');
        }   
        chess_myturn = true;
        chess_fit_board();
        
    }
    
    var chess_get_history = function () {
        $.post("chess/history", {game_id: chess_game_id} , function(data) {
            if (data['status']) {
                var move_history = data['history'];
                var moves = [];
                $("#chess-move-history").empty();
                for(var i=move_history.length-1; i>=0; i--) {
                    var move = JSON.parse(move_history[i]['obj']);
                    moves.push(move['position']);
                    var moveListElem = '';
                    if (i === move_history.length-1) {
                    moveListElem = '<li><a class="btn-success" href="#" onclick="clearTimeout(chess_timer); \n\
                                        chess_viewing_history = false; \n\
                                        chess_viewing_position = \'\'; \n\
                                        chess_viewing_mid = \'\'; \n\
                                        chess_board.position(\'' + move['position'] + '\'); \n\
                                        $(\'#chess-revert\').hide(); \n\
                                        chess_timer = setTimeout(chess_update_game,300); \n\
                                        return false;">';
                        moveListElem += '<b>Current Position</b>';
                    } else {
                    moveListElem = '<li><a href="#" onclick="clearTimeout(chess_timer); \n\
                                        chess_viewing_history = true; \n\
                                        chess_viewing_position = \'' + move['position'] + '\'; \n\
                                        chess_viewing_mid = \'' + move_history[i]['mid'] + '\'; \n\
                                        chess_board.position(\'' + move['position'] + '\'); \n\
                                        $(\'#chess-revert-info\').html(\'<h4>Revert game to position:</h4>' + move['position'] + '\'); \n\
                                        $(\'#chess-revert\').show(); \n\
                                        return false;">';
                        moveListElem += 'Position ' + (i+1).toString();
                    }
                    moveListElem += '</a></li>';
                    $("#chess-move-history").append(moveListElem);
                }
            } else {
                window.console.log('Error: ' + data['errormsg']);
            }
            return false;
            },
        'json');
    }
    
    var chess_revert_position = function () {
	$.post("chess/revert", {game_id: chess_game_id, mid: chess_viewing_mid}, 
            function(data) {
                if (data['status']) {
                    window.console.log('revert MID: ' + chess_viewing_mid);
                    window.console.log('revert FEN ' + chess_viewing_position);
                    chess_viewing_position = '';        
                    chess_viewing_mid = '';  
                    chess_update_game();
                } else {
                    window.console.log('Error reverting ' + data['errormsg']);
                }
                $('#chess-revert').hide();
                return false;
            },
        'json');
    }
    
    var chess_delete_game = function (game_id) {
        var answer = confirm("Delete game?");
        if (!answer) {
            return false;
        }        
	$.post("chess/delete", {game_id: game_id}, 
            function(data) {
                if (data['status']) {
                    $("#chess-game-"+game_id).remove();
                } else {
                    window.console.log('Error deleting: ' + game_id + ':' + data['errormsg']);
                }
                return false;
            },
        'json');
    }
    
    var chess_end_game = function () {
        var answer = confirm("End game?");
        if (!answer) {
            return false;
        }
        if(chess_new_pos.length < 1 || chess_new_pos[chess_new_pos.length-1] === chess_original_pos) { 
            return false; 
        }        
        var newPos = chess_new_pos[chess_new_pos.length-1];
        chess_myturn = false;
        $.post("chess/move", {game_id: chess_game_id, newPosFEN: newPos} , function(data) {
            if (data['status']) {
                chess_new_pos = [];
                chess_original_pos = newPos;
                $("#chess-verify-move").hide();
                if($("main").hasClass('region_1-on')) {
                    $("main").removeClass('region_1-on');
                }
                $.post("chess/end", {game_id: chess_game_id}, 
                    function(data) {
                        if (data['status']) {
                            chess_game_ended = 1;
                            $("#chess-turn-indicator").html('Game Over');
                            chess_fit_board();
                            chess_timer = setTimeout(chess_update_game,300);
                        } else {
                            window.console.log('Error ending ' + chess_game_id + ':' + data['errormsg']);
                        }
                        return false;
                    },
                'json');
            } else {
                window.console.log('Error: ' + data['errormsg']);
            }
            return false;
            },
        'json');
    }
    
    var chess_resume_game = function () {
        var answer = confirm("Resume game?");
        if (!answer) {
            return false;
        }        
	$.post("chess/resume", {game_id: chess_game_id}, 
            function(data) {
                if (data['status']) {
                    $("#chess-resume-game").hide();
                    chess_timer = setTimeout(chess_update_game,300);
                } else {
                    window.console.log('Error resuming ' + chess_game_id + ':' + data['errormsg']);
                }
                return false;
            },
        'json');
    }
    
    $(document).ready(chess_init);
</script>
<h2 id='chess-turn-indicator'>
{{if $active}}
Your turn
{{else}}
Opponent's turn
{{/if}}
{{if $ended}}
Game Over
{{/if}}
</h2>
<div id="chessboard" style="width: 400px; position: fixed;"></div>