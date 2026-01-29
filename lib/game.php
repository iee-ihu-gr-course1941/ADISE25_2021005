<?php
function show_status(){
    global $mysqli;

    $sql = 'select * from game_status';
    $st = $mysqli->prepare($sql);
    $st->execute();
    $res = $st->get_result();
    header('Content-type: application/json');
    print json_encode($res->fetch_all(MYSQLI_ASSOC), JSON_PRETTY_PRINT);
}

function update_game_status(){
    $status = read_status();
    $new_status = null;
    $new_turn = null;

    global $mysqli;

    $sql = 'select count(*) as aborted from players where last_action < (NOW() - INTERVAL 10 MINUTE)';
    $st = $mysqli->prepare($sql);
    $st->execute();
    $res = $st->get_result();
    $aborted = $res->fetch_assoc();
    if($aborted['aborted']>0){
        $sql = 'update players set username = null, token = null where last_action < (NOW() - INTERVAL 10 MINUTE)';
        $st1 = $mysqli->prepare($sql);
        $st1->execute();
        $res1 = $st1->get_result();
        if($status['current_status']=='started'){
            $new_status='aborted';
        }
    }

    $sql = 'select count(*) as c from players where username is not null';
    $st2 = $mysqli->prepare($sql);
    $st2->execute();
    $res2 = $st2->get_result();
    $active_players = $res2->fetch_assoc();

    switch($active_players['c']){
        case 0: 
            $new_status = 'not active';
            break;
        case 1:
            $new_status = 'initialized';
            break;
        case 2:
            $new_status = 'started';
            if($status['current_turn']==null){
                $new_turn = 'W';
            }
            break;
    }
    


    $sql='update game_status set current_status=?,current_turn=?';
    $st3 = $mysqli->prepare($sql);
    $st3->bind_param('ss',$new_status,$new_turn);
    $st3->execute();


}

function read_status(){
    global $mysqli;
    $sql='select * from game_status';
    $st = $mysqli->prepare($sql);
    $st->execute();
    $res = $st->get_result();
    $status = $res->fetch_assoc();
    return($status);
}
?>