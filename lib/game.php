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

function generate_dice_roll($token){
    global $mysqli;
    if(validate_action($token,'roll')!=null){
        $d1 = rand(1,6);
        $d2 = rand(1,6);
        $sql='update game_status set first_dice=?, second_dice=?, last_change=NOW()';
        $st = $mysqli->prepare($sql);
        $st->bind_param('ii',$d1,$d2);
        $st->execute();
    }
    update_game_status();
}
?>