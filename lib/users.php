<?php

require_once "lib/game.php";

//Handles the players request
function handle_user($method,$b,$input){
    if($method=='GET'){
        show_user($b);
    }
    elseif($method=='PUT'){
        set_user($b,$input);
    }
    else{
        header('HTTP/1.1 405 Method Not Allowed');
        header('Content-Type:application/json');
        print json_encode(['errormesg'=>'Selected method is not valid']);
        exit;
    }
}

//Returns all users through the API
function show_users($input){
    global $mysqli;

    if(current_color($input['token'])!=null){
    $sql='select username,piece_color from players';
    $st = $mysqli->prepare($sql);
    $st->execute();
    $res = $st->get_result();
    header('Content-type: application/json');
    print json_encode($res->fetch_all(MYSQLI_ASSOC), JSON_PRETTY_PRINT);
    }
    else{
        header("HTTP/1.1 400 Bad Request");
        print json_encode(['errormesg'=>'You do not have permission to use this']);
        exit;
    }
}

//returns single user through the API
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

//Sets up a new user
function set_user($b,$input){
    if(!isset($input['username'])||$input['username']==''){
        header("HTTP/1.1 400 Bad Request");
        print json_encode(['errormesg'=>'No username was given']);
        exit;
    }
    $username=$input['username'];
    global $mysqli;

    $sql = 'select count(*) as c
            from players 
            where piece_color=?
            and username is not null
            and last_action > (NOW() - INTERVAL 5 MINUTE)';
    $st = $mysqli->prepare($sql);
    $st->bind_param('s',$b);
    $st->execute();
    $res=$st->get_result();
    $r = $res->fetch_all(MYSQLI_ASSOC);
    $st->close();
    if($r[0]['c']>0){
        header("HTTP/1.1 400 Bad Request");
        print json_encode(['errormesg'=>'This color is already set, please select another one']);
        exit;
    }

    $sql = 'update players
            set username = ?,
            token = md5(CONCAT(?, NOW()))
            where piece_color = ?';
    $st = $mysqli->prepare($sql);
    $st->bind_param('sss',$username,$username,$b);
    $st->execute();
    $st->close();

    update_game_status();
    $sql = 'select * from players where piece_color=?';
    $st = $mysqli->prepare($sql);
    $st->bind_param('s',$b);
    $st->execute();
    $res=$st->get_result();
    $st->close();
    header('Content-type: application/json');
    print json_encode($res->fetch_all(MYSQLI_ASSOC), JSON_PRETTY_PRINT);
}

//Returns user checker color based on the token sent, used mostly to authenticate tokens
function current_color($token){
    global $mysqli;
    if($token==null){
        return(null);
    }
    $sql = 'select * from players where token = ?';
    $st = $mysqli->prepare($sql);
    $st->bind_param('s',$token);
    $st->execute();
    $res=$st->get_result();
    if($row=$res->fetch_assoc()){
        return($row['piece_color']);
    }
    return(null);
}

//updates players last action
//function update_last_action($token){
    //global $mysqli;

    //$sql='update players set last_action= NOW() where token =?';
    //$st = $mysqli->prepare($sql);
    //$st->bind_param('s',$token);
    //$st->execute();

//}
?>