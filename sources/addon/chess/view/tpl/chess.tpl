<script>
    var chess_board = null;
    var chess_init = function () {
        $("#chess-revert").hide();
        $("#chess-verify-move").hide();
        $("#expand-aside").on('click', chess_fit_board);
        var cfg = {
            draggable: true,
            sparePieces: true,
            position: 'start',
            orientation: '{{$color}}'
        };
        chess_board = ChessBoard('chessboard', cfg);
        $(window).resize(chess_fit_board);
        setTimeout(chess_fit_board,300);
    }
    
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
    
    $(document).ready(chess_init);

</script>

<div id="chessboard" style="width: 100px; position: fixed;"></div>