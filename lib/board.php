<?php
function show_board(){
    global $mysqli;

    $sql = 'select * from board';
    $st = $mysqli->prepare($sql);
    $st->execute();
    $res = $st->get_result();
    header('Content-type: application/json');
    print json_encode($res->fetch_all(MYSQLI_ASSOC), JSON_PRETTY_PRINT);
}
function reset_board(){
    global $mysqli;

    $sql = 'call clean_board()';
    $mysqli->query($sql);
    show_board();
}

function show_piece($point_id){
    global $mysqli;

    $sql= 'select * from board where point_id=?';
    $st = $mysqli->prepare($sql);
    $st->bind_param('i',$point_id);
    $st->execute();
    $res=$st->get_result();
    header('Content-type: application/json');
    print json_encode($res->fetch_all(MYSQLI_ASSOC), JSON_PRETTY_PRINT);
}

function move_piece($point_id,$point_id_to,$token){
    global $mysqli;
    $my_color = validate_action($token,'move');

    $distance = $point_id-$point_id_to;

    if($distance<0){
        $distance+=24;
        }

    $status = read_status();
    $d1=$status['first_dice'];
    $d2=$status['second_dice'];

    if($distance!=$d1||$distance!=$d2||$distance!=$d1+$d2){
        header("HTTP/1.1 400 Bad Request");
        exit;
    }

    $sql = 'select point_count, piece_color from board where point_id=?';
    $st = $mysqli->prepare($sql);
    $st->bind_param('i',$point_id);
    $st->execute();
    $res=$st->get_result();
    $source = $res->fetch_assoc();

    if($source['piece_color']!=$my_color||$source['piece_count']==0){
        header("HTTP/1.1 400 Bad Request");
        exit;
    }

    $st->bind_param('i',$point_id_to);
    $st->execute();
    $res=$st->get_result();
    $dest = $res->fetch_assoc();

    if($dest['piece_color']!=$my_color&&$dest['piece_count']>0){
        header("HTTP/1.1 400 Bad Request");
        exit;
    }

    $sql='call move_piece(?,?)';
    $st2 = $mysqli->prepare($sql);
    $st2->bind_param('ii',$point_id,$point_id_to);
    $st2->execute();

    //header('Content-type: application/json');
    //print json_encode(read_board(), JSON_PRETTY_PRINT);

}

function validate_action($token,$action_name){
    if($token==null||$token==''){
        header("HTTP/1.1 400 Bad Request");
        return null;
        
    }

    $color = current_color($token);
    if($color==null){
        header("HTTP/1.1 400 Bad Request");
        return null;
        
    }

    $status = read_status();
    if($status['status']!='started'){
        header("HTTP/1.1 400 Bad Request");
        return null;
        
    }

    if($status['current_turn']!=$color){
        header("HTTP/1.1 400 Bad Request");
        return null;
        
    }

    switch($action_name){
        case 'roll':
            if($status['first_dice']!=null){
                header("HTTP/1.1 400 Bad Request");
                return null;
            }
            break;
        case 'move':
            if($status['first_dice']!=null){
                header("HTTP/1.1 400 Bad Request");
                return null;
            }
            break;
        case 'general':
        default:
            break;
    }

    return $color;


}

function do_move($point_id,$point_id_to){
    global $mysqli;
    $sql = 'call move_piece(?,?)';
    $st = $mysqli->prepare($sql);
    $st->bind_param('ii',$point_id,$point_id_to);
    $st->execute();
    show_board();
    //header('Content-type: application/json');
    //print json_encode(read_board(), JSON_PRETTY_PRINT);

}
?>