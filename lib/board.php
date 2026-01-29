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

function read_board(){
    global $mysqli;

    $sql = 'select * from board';
    $st = $mysqli->prepare($sql);
    $st->execute();
    $res = $st->get_result();
    return($res->fetch_all(MYSQLI_ASSOC));
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

function generate_dice_roll($token){
    global $mysqli;

    $my_color=validate_action($token,'roll');

    if($my_color==null){
        return;
    }

    $d1 = rand(1,6);
    $d2 = rand(1,6);
    $d3 = null;
    $d4 = null;

    if($d1==$d2){
        $d3=$d1;
        $d4=$d1;
    }

    $sql='update game_status set first_dice=?, second_dice=?, third_dice=?, fourth_dice=?';
    $st = $mysqli->prepare($sql);
    $st->bind_param('iiii',$d1,$d2,$d3,$d4);
    $st->execute();

}

function move_piece($point_id,$point_id_to,$token){
    global $mysqli;
    $my_color = validate_action($token,'move');

    if($my_color==null){
        return;
    }

    $distance = $point_id-$point_id_to;

    if($distance<0){
        $distance+=24;
        }

    $status = read_status();

    $dice_slots=[];

    if($status['first_dice']){
        $dice_slots['first_dice']=$status['first_dice'];
    }
    if($status['second_dice']){
        $dice_slots['second_dice']=$status['second_dice'];
    }
    if($status['third_dice']){
        $dice_slots['third_dice']=$status['third_dice'];
    }
    if($status['fourth_dice']){
        $dice_slots['fourth_dice']=$status['fourth_dice'];
    }

    $dice_used = [];
    $found_match = false;

    foreach($dice_slots as $col => $val){
        if($val==$distance){
            $dice_used[] = $col;
            $found_match=true;
            break;
        }
    }

    if($found_match==null&&count($dice_slots)>=2){
        $keys = array_keys($dice_slots);
        for($i = 0;$i<count($keys);$i++){
            for($j = $i+1;$j<count($keys);$j++){
                $sum = $dice_slots[$keys[$i]]+$dice_slots[$keys[$j]];
                if($distance==$sum){
                    $dice_used[]=$keys[$i];
                    $dice_used[]=$keys[$j];
                    $found_match=true;
                    break 2;
                }
            }
        }
    }

    if($found_match==null&&count($dice_slots)>=3){
        $val = reset($dice_slots);

        if($distance==3*$val){
            $keys = array_keys($dice_slots);
            $dice_used = [$keys[0],$keys[1],$keys[2]];
            $found_match=true;
        }
        elseif($distance==4*$val&&count($dice_slots)==4){
            $dice_used=array_keys($dice_slots);
            $found_match=true;
        }
    }

    if($found_match==null){
        header("HTTP/1.1 400 Bad Request");
        exit;
    }

    $sql = 'select point_count, piece_color from board where point_id=?';
    $st = $mysqli->prepare($sql);
    $st->bind_param('i',$point_id);
    $st->execute();
    $res=$st->get_result();
    $source = $res->fetch_assoc();

    if($source['piece_color']!=$my_color||$source['point_count']==0){
        header("HTTP/1.1 400 Bad Request");
        exit;
    }

    $st->bind_param('i',$point_id_to);
    $st->execute();
    $res=$st->get_result();
    $dest = $res->fetch_assoc();

    if($dest['piece_color']!=$my_color&&$dest['point_count']>0){
        header("HTTP/1.1 400 Bad Request");
        exit;
    }

    $sql='call move_piece(?,?)';
    $st2 = $mysqli->prepare($sql);
    $st2->bind_param('ii',$point_id,$point_id_to);
    $st2->execute();

    $allowed_values = ['first_dice','second_dice','third_dice','fourth_dice'];

    foreach($dice_used as $col){
        if(!in_array($col,$allowed_values)){
            continue;
        }

        $sql = "update game_status set $col = null";
        $st3 = $mysqli->prepare($sql);
        $st3->execute();

    }

    $status = read_status();
    if($status['first_dice']==null&&$status['second_dice']==null&&$status['third_dice']==null&&$status['fourth_dice']==null){
        $sql = "update game_status set current_turn = IF(current_turn='W','B','W')";
        $st4 = $mysqli->prepare($sql);
        $st4->execute();
    }


    header('Content-type: application/json');
    print json_encode(read_board(), JSON_PRETTY_PRINT);

}

function validate_action($token,$action_name){
    if($token==null||$token==''){
        header("HTTP/1.1 400 Bad Request");
        print json_encode(['errormesg'=>'token is not set']);
        return null;
        
    }

    $color = current_color($token);
    if($color==null){
        header("HTTP/1.1 400 Bad Request");
        print json_encode(['errormesg'=>'you are not a player in this game']);
        return null;
        
    }

    $status = read_status();
    if($status['current_status']!='started'){
        header("HTTP/1.1 400 Bad Request");
        print json_encode(['errormesg'=>'the game hasnt started']);
        return null;
        
    }

    if($status['current_turn']!=$color){
        header("HTTP/1.1 400 Bad Request");
        print json_encode(['errormesg'=>'its not your turn yet']);
        return null;
        
    }

    switch($action_name){
        case 'roll':
            if($status['first_dice']!=null||$status['second_dice']!=null||$status['third_dice']!=null||$status['fourth_dice']!=null){
                header("HTTP/1.1 400 Bad Request");
                print json_encode(['errormesg'=>'you have already rolled']);
                return null;
            }
            break;
        case 'move':
            if($status['first_dice']==null&&$status['second_dice']==null&&$status['third_dice']==null&&$status['fourth_dice']==null){
                header("HTTP/1.1 400 Bad Request");
                print json_encode(['errormesg'=>'dice is not set']);
                return null;
            }
            break;
        case 'general':
        default:
            break;
    }

    return $color;


}

function check_collection_phase($color){
    global $mysqli;

    if($color=='W'){
        $sql="select count(*) as c from board where piece_color='W' and point_id>6";
    }
    else{
        $sql="select count(*) as c from board where piece_color='B' and (point_id<13 or point_id>18)";
    }

    $st = $mysqli->prepare($sql);
    $st->execute();
    $res = $st->get_result();
    $collection = $res->fetch_assoc();

    return ($collection['c']==0);
}

?>