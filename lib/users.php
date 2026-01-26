<?php

require_once "lib/game.php";

function handle_user($method,$b,$input){
    if($method=='GET'){
        show_user($b);
    }
    elseif($method=='PUT'){
        set_user($b,$input);
    }
    else{
        header('HTTP/1.1 405 Method Not Allowed');
    }
}

function show_users(){
    global $mysqli;

    $sql='select username,piece_color from players';
    $st = $mysqli->prepare($sql);
    $st->execute();
    $res = $st->get_result();
    header('Content-type: application/json');
    print json_encode($res->fetch_all(MYSQLI_ASSOC), JSON_PRETTY_PRINT);


}

function show_user($b){
    global $mysqli;

    $sql = 'select username,piece_color from players where piece_color=?';
    $st = $mysqli->prepare($sql);
    $st->bind_param('s',$b);
    $st->execute();
    $res=$st->get_result();
    header('Content-type: application/json');
    print json_encode($res->fetch_all(MYSQLI_ASSOC), JSON_PRETTY_PRINT);
}

function set_user($b,$input){

}
?>