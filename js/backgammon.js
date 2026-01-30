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

//global variables
var me = {};

var game_status = {};

var game_loop_timer = null;

game_ended_flag=false;

$( function() {
    $('#queue-button').click(login_to_game);
    $('#refresh-backgammon-game').click(fill_board);
    $('#quit-backgammon-game').click(quit_game_action);
    $('#move-button').click(do_move);
});

//Handles when players quit the game
function quit_game_action(){
    if(!confirm("Are you sure you want to quit?")){
        return;
    }
    if(game_loop_timer){
        clearTimeout(game_loop_timer);
    }
    $.ajax(
        {
            method: "post",
            url: "backgammon.php/board/",
            headers: {"App-Token":me.token},
            success: function(){
                alert('You have quit the game.');
                return_to_screen();
            }
        }
    );
}

//Calls the API for the status changes
function game_status_update(){
    $.ajax(
        {
            url: "backgammon.php/status/",
            headers: {"App-Token":me.token},
            success: update_status
        }
    );
}

//Function that helps the gameplay loop
function update_status(data){

    if(!me.token){
        return;
    }
    var new_game_status = data[0];

    //handle victory
    if(new_game_status.current_status=='ended'){

        if(game_ended_flag){

        return;}

        game_ended_flag = true;
        
        if(game_loop_timer){
            clearTimeout(game_loop_timer);
            game_loop_timer=null;
        }
        game_status = new_game_status;
        update_info();
        $('#debug-controls').hide();

        var winner= (game_status.result_of_match=='W')?"White":"Black";

        setTimeout(function(){
            alert("Game Over!\n\nThe winner is: "+winner+" \n\nClick ok to return to login screen.");
            return_to_screen();
        },100);

        return;
    }

    //handle start
    if(game_status.current_turn==null||
        data[0].current_turn!=game_status.current_turn||
        data[0].current_status!=game_status.current_status){
        update_players_lists();
        fill_board();
    }

    //handle opponent quiting
    if(new_game_status.current_status=='not active'&&game_status.current_status=='started'){
        if(game_loop_timer){
            clearTimeout(game_loop_timer);
        }
        setTimeout(function(){
            alert("Your opponent has quit the match, returning to login screen");
            return_to_screen();
        },100);
    }


    //handle normal loop
    game_status=new_game_status;
    update_info();
    if(game_status.current_turn==me.piece_color && me.piece_color!=null){
        if(game_status.first_dice!=null||game_status.second_dice!=null||game_status.third_dice!=null||game_status.fourth_dice!=null){
            $('#debug-controls').show();
        }
        else{
            check_dice();
        }
        game_loop_timer=setTimeout(function(){game_status_update();},2000);
        }
    else{
        $('#debug-controls').hide();
        check_dice();
        game_loop_timer=setTimeout(function(){game_status_update();},2000);
    }
}
//Resets game state
function return_to_screen(){
    game_ended_flag=false;

    if(me.token){
        reset_board();
    }
    $('#game-screen').hide();
    $('#login-screen').show();

    $('#username-input').val('');

    $('#status-display').html('');
    $('#playerW_info').text('Waiting...');
    $('#playerB_info').text('Waiting...');
    $('#debug-controls').hide();

    me={};
    game_status={};
}

//Updates small status like the dice and the side text
function update_info(){

    if(game_status.current_status=='ended'){
        var winner= (game_status.result_of_match=='W')?"White":"Black";
        $('#status-display').html('Game Over!<br>The winner is: '+winner+' <br>Click ok to return to login screen.')
        $('#dice_1').html('');
        $('#dice_2').html('');
        return;
    }

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

//Calls the API to get the players
function update_players_lists(){
    $.ajax({
        method: "get",
            url: "backgammon.php/players/",
            headers: {"App-Token":me.token},
            dataType: "json",
            success: add_player_info
    });
}

//Dynamically adds the username, checker color and score on each players field
function add_player_info(data){

    var w_score = (game_status&&game_status.white_collected)?game_status.white_collected:0;
    var b_score = (game_status&&game_status.black_collected)?game_status.black_collected:0;

    for(var i=0;i<data.length;i++){
        var val = data[i];
    if(val.username==null){
        continue;
    }

    if(val.piece_color=='W'){
        $('#playerW_info').text(val.username+' W '+w_score);
    }
    else{
        $('#playerB_info').text(val.username+' B '+b_score);
    }
}
}

//Calls the API to reset the board
function reset_board(){
    $.ajax(
        {
            method: "post",
            url: "backgammon.php/board/",
            headers: {"App-Token":me.token},
            success: fill_board_by_data
        }
    );
}

//Calls the API to return the board data
function fill_board(){
    $.ajax(
        {
            method: "get",
            url: "backgammon.php/board/",
            headers: {"App-Token":me.token},
            success: fill_board_by_data
        }
    );
}

//Draws the board based on the players checker color
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

//Fills the boards data after the API call
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

//Sets up the player via the API
function login_to_game(){
    if($('#username-input').val()==''){
        alert('You must enter a Username.');
        return;
    }
    var p_color = $('#pcolor').val();

    // Sets up the player
    $.ajax({
        url: "backgammon.php/players/"+p_color,
        headers: {"App-Token":me.token},
        method: "PUT",
        dataType: "json",
        contentType: "application/json",
        data: JSON.stringify( {username:$('#username-input').val(),piece_color:p_color}),
        success: login_result,
        error: login_error
    });
}

//The result of the call, sets the game screen and starts the gameplay loop
function login_result(data){
    var p_color = $('#pcolor').val();
    $('#login-screen').hide();
    $('#game-screen').show();
    drawEmptyBoard(p_color);
    fill_board();
    me = data[0];
    update_info();
    game_status_update();
}

//Handles possible login errors
function login_error(data,y,z,c){
    var x = data.responseJSON;
    alert(x.errormesg);

}

//Sets up dice css
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

//Small helping dice function
function click_dice_action(){
    if($(this).hasClass('glow-my-turn')){
        $('.dice').removeClass('glow-my-turn');
    }

    call_dice_roll();

}

//Calls the API to generate dice rolls
function call_dice_roll(){
    $.ajax({
        url:"backgammon.php/roll/",
        method: "POST",
        dataType: "json",
        contentType: "application/json",
        headers: {"App-Token":me.token},
        //data: JSON.stringify({token: me.token }),
        success: update_status
    });
}

//Calls the API to move checkers
function do_move(){

    if($('#debug-from').val()==''||$('#debug-to').val()==''){
        alert('You must fill both fields');
        return;
    }

    var from_val = parseInt($('#debug-from').val());
    var to_val = parseInt($('#debug-to').val());

    if(isNaN(from_val)||isNaN(to_val)){
        alert('You must enter valid numbers');
        return;
    }

    var from_ID;
    var to_ID;

    if(to_val===0||to_val>=25){
        to_ID=0;
    }
    else{
        if(me.piece_color=='W'){
            to_ID = 25-to_val;
        }

        else{
            if(to_val<=12){
                to_ID = 13-to_val;
            }
            else{
                to_ID = 37-to_val;
            }
        }
    }
    if(me.piece_color=='W'){
        from_ID = 25-from_val;
    }
    else{
        if(from_val<=12){
            from_ID = 13-from_val;
        }
        else{
            from_ID = 37-from_val;
        }
    }

    $.ajax({
        url:"backgammon.php/board/piece/"+from_ID,
        headers: {"App-Token":me.token},
        method: "PUT",
        dataType: "json",
        contentType: "application/json",
        data: JSON.stringify({point_id: to_ID}),
        //data: JSON.stringify({point_id: to_ID,token: me.token }),
        success: move_result,
        error:handle_move_error
    });
}

//In the success of the API call
function move_result(data){
    $('#debug-from').val('');
    $('#debug-to').val('');
    fill_board_by_data(data);
    game_status_update();
}

//Shows the error that happened during the action
function handle_move_error(data,y,z,c){
    var x = data.responseJSON;
    alert(x.errormesg);
}