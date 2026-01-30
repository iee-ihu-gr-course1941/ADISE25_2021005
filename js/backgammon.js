/*
document.addEventListener("DOMContentLoaded", () =>{
   drawEmptyBoard('#board');
});
*/
/*
function drawEmptyBoard(selector){
    var container = document.querySelector(selector);
    var pawn_color = 'W'
    if(!container) return;

    var t = '<table id="Backgammon_table">';

    function makeCell(id,type){
        var color = (id%2==0) ? 'even':'odd';
        return '<td class="board_section" id="section_'+id+'">'+
        '<div class="triangle '+type+' '+ color+'">'+
        '<span class="debug_number">'+id+'</span>'+
        '</div></td>';

    }
    
    if(pawn_color=='W'){
        t+='<tr>';
        for(var i = 1;i<=6;i++){
            t+= makeCell(i,'down');
        }
        t+= '<td class="bar_center"><div class="dice" id="dice_1">1</div></td>';
        for(var i = 7;i<=12;i++){
            t+= makeCell(i,'down');
        }
        t+= '</tr>';
        t+= '<tr>';
        for(var j = 24;j>=19;j--){
            t+= makeCell(j,'up');
        }
        t+='<td class="bar_center"><div class="dice" id="dice_2">2</div></td>';
        for(var j = 18;j>=13;j--){
            t+= makeCell(j,'up');
        }
        t+='</tr>'
    }
    else{
         t+='<tr>';
        for(var i = 13;i<=18;i++){
            t+= makeCell(i,'down');
        }
        t+= '<td class="bar_center"><div class="dice" id="dice_1">1</div></td>';
        for(var i = 19;i<=24;i++){
            t+= makeCell(i,'down');
        }
        t+= '</tr>';
        t+= '<tr>';
        for(var j = 12;j>=7;j--){
            t+= makeCell(j,'up');
        }
        t+='<td class="bar_center"><div class="dice" id="dice_2">2</div></td>';
        for(var j = 6;j>=1;j--){
            t+= makeCell(j,'up');
        }
        t+='</tr>'
    }
    t+= '</table>';
    container.innerHTML = t;
}
*/
var me = {};

var game_status = {};

$( function() {
    $('#queue-button').click(login_to_game);
    $('#refresh-backgammon-game').click(fill_board);
    $('#quit-backgammon-game').click(reset_board);
    $('#move-button').click(do_move);
});


function game_status_update(){
    $.ajax(
        {
            url: "backgammon.php/status/",
            success: update_status
        }
    );
}

function update_status(data){
    if(game_status.current_turn==null||
        data[0].current_turn!=game_status.current_turn||
        data[0].current_status!=game_status.current_status){
        update_players_lists();
        fill_board();
    }

    game_status=data[0];
    update_info();
    if(game_status.current_turn==me.piece_color && me.piece_color!=null){
        if(game_status.first_dice!=null||game_status.second_dice!=null||game_status.third_dice!=null||game_status.fourth_dice!=null){
            $('#debug-controls').show();
        }
        else{
            check_dice();
        }
        setTimeout(function(){game_status_update();},2000);
        }
    else{
        $('#debug-controls').hide();
        check_dice();
        setTimeout(function(){game_status_update();},2000);
    }
}

function update_info(){
    $('#status-display').html('I am the '+me.piece_color+' player<br>'+
        ((game_status.current_turn)?('It is '+game_status.current_turn+'s turn'):'The game hasnt started yet')+
        ((game_status.first_dice!=null||game_status.second_dice!=null||game_status.third_dice!=null||game_status.fourth_dice!=null)?'<br>Available advances: ':'')+
        ((game_status.first_dice!=null)?game_status.first_dice:'')+' '+
        ((game_status.second_dice!=null)?game_status.second_dice:'')+' '+
        ((game_status.third_dice!=null)?game_status.third_dice:'')+' '+
        ((game_status.fourth_dice!=null)?game_status.fourth_dice:''));
    if(game_status.first_dice!=null){
        $('#dice_1').html(game_status.first_dice);
    }
    if(game_status.second_dice!=null){
        $('#dice_2').html(game_status.second_dice);
    }

}

function update_players_lists(){
    $.ajax({
        method: "get",
            url: "backgammon.php/players/",
            dataType: "json",
            success: add_player_info
    });
}

function add_player_info(data){

    var w_score = (game_status&&game_status.white_collected)?game_status.white_collected:0;
    var b_score = (game_status&&game_status.black_collected)?game_status.black_collected:0;

    for(var i=0;i<data.length;i++){
        var val = data[i];
    
    if(val.piece_color=='W'){
        $('#playerW_info').text(val.username+' W '+w_score);
    }
    else{
        $('#playerB_info').text(val.username+' B '+b_score);
    }
}
}

function reset_board(){
    $.ajax(
        {
            method: "post",
            url: "backgammon.php/board/",
            success: fill_board_by_data
        }
    );
}

function fill_board(){
    $.ajax(
        {
            method: "get",
            url: "backgammon.php/board/",
            success: fill_board_by_data
        }
    );
}

function drawEmptyBoard(pawn_color){
    var t = '<table id="Backgammon_table">';

    function makeCell(id,type){
        var color = (id%2==0) ? 'even':'odd';
        return '<td class="board_section" id="section_'+id+'">'+
        '<div class="triangle '+type+' '+ color+'"></div></td>';

    }
    
    if(pawn_color=='W'){
        t+='<tr>';
        for(var i = 1;i<=6;i++){
            t+= makeCell(i,'down');
        }
        t+= '<td class="bar_center"><div class="dice" id="dice_1"></div></td>';
        for(var i = 7;i<=12;i++){
            t+= makeCell(i,'down');
        }
        t+= '</tr>';
        t+= '<tr>';
        for(var j = 24;j>=19;j--){
            t+= makeCell(j,'up');
        }
        t+='<td class="bar_center"><div class="dice" id="dice_2"></div></td>';
        for(var j = 18;j>=13;j--){
            t+= makeCell(j,'up');
        }
        t+='</tr>'
    }
    else{
         t+='<tr>';
        for(var i = 13;i<=18;i++){
            t+= makeCell(i,'down');
        }
        t+= '<td class="bar_center"><div class="dice" id="dice_1"></div></td>';
        for(var i = 19;i<=24;i++){
            t+= makeCell(i,'down');
        }
        t+= '</tr>';
        t+= '<tr>';
        for(var j = 12;j>=7;j--){
            t+= makeCell(j,'up');
        }
        t+='<td class="bar_center"><div class="dice" id="dice_2"></div></td>';
        for(var j = 6;j>=1;j--){
            t+= makeCell(j,'up');
        }
        t+='</tr>'
    }
    t+= '</table>';
    $('#board').html(t);
    $('#dice_1').click(click_dice_action);
    $('#dice_2').click(click_dice_action);
}

function fill_board_by_data(data){
    for(var i=0;i<data.length;i++){
        var o = data[i];
        var section_id = '#section_'+o.point_id;
        var triangle = $(section_id+' .triangle');
        triangle.empty();
        var piece_class = (o.piece_color=='W') ? 'piece W':'piece B';
        var pieces_per_section = (o.point_count>3) ? 3:o.point_count;
        for(var j=0;j<pieces_per_section;j++){
            triangle.append('<div class="'+piece_class+'"></div>');
        }

        triangle.append('<span class="debug_number">'+o.point_count+'</span>');
    }

}

function login_to_game(){
    if($('#username-input').val()==''){
        alert('You must enter a Username.');
        return;
    }
    var p_color = $('#pcolor').val();

    // Makes the login box disappear.
    $('#login-screen').hide();

    // Makes the game screen appear
    $('#game-screen').show();

    // Sets up the board
    drawEmptyBoard(p_color);
    fill_board();

    // Sets up the player
    $.ajax({
        url: "backgammon.php/players/"+p_color,
        method: "PUT",
        dataType: "json",
        contentType: "application/json",
        data: JSON.stringify( {username:$('#username-input').val(),piece_color:p_color}),
        success: login_result,
        error: login_error
    });
}

function login_result(data){
    me = data[0];
    update_info();
    game_status_update();
}

function login_error(data,y,z,c){
    var x = data.responseJSON;
    alert(x.errormesg);

}

function check_dice(){
    $('.dice').removeClass('glow-my-turn glow-opponent');

    if(game_status.current_turn==null) {
        return;
    }

    if(game_status.first_dice!=null){
        return;
    }

    if(game_status.current_turn==me.piece_color){
        $('.dice').addClass('glow-my-turn');
    }
    else{
        $('.dice').addClass('glow-opponent');
    }
}


function click_dice_action(){
    if($(this).hasClass('glow-my-turn')){
        $('.dice').removeClass('glow-my-turn');
    }

    call_dice_roll();

}

function call_dice_roll(){
    $.ajax({
        url:"backgammon.php/roll/",
        method: "POST",
        dataType: "json",
        contentType: "application/json",
        data: JSON.stringify({token: me.token }),
        success: update_status
    });
}

function do_move(){

    if($('#debug-from').val()==''||$('#debug-to').val()==''){
        alert('You must fill both fields');
        return;
    }
        var from_val = $('#debug-from').val();
        var to_val = $('#debug-to').val();
    if(me.piece_color=='W'){
        var from_ID = 25-from_val;
        var to_ID = 25-to_val;
    }

    else{
        if(from_val<=12){
            var from_ID = 13-from_val;
        }
        else{
            var from_ID = 37-from_val;
        }
        if(to_val<=12){
            var to_ID = 13-to_val;
        }
        else{
            var to_ID = 37-to_val;
        }
    }

    $.ajax({
        url:"backgammon.php/board/piece/"+from_ID,
        method: "PUT",
        dataType: "json",
        contentType: "application/json",
        data: JSON.stringify({point_id: to_ID,token: me.token }),
        success: move_result,
        error:handle_move_error
    });
}

function move_result(data){
    $('#debug-from').val('');
    $('#debug-to').val('');
    fill_board_by_data(data);
    game_status_update();
}

function handle_move_error(data){
alert('an error happened');
}