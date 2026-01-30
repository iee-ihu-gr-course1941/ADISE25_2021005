<?php

//Returns the board for the API
function show_board(){
    global $mysqli;

    $sql = 'select * from board';
    $st = $mysqli->prepare($sql);
    $st->execute();
    $res = $st->get_result();
    header('Content-type: application/json');
    print json_encode($res->fetch_all(MYSQLI_ASSOC), JSON_PRETTY_PRINT);
}

//Returns the board for other functions to use
function read_board(){
    global $mysqli;

    $sql = 'select * from board';
    $st = $mysqli->prepare($sql);
    $st->execute();
    $res = $st->get_result();
    return($res->fetch_all(MYSQLI_ASSOC));
}

//Resets the board for the API
function reset_board($input){
    global $mysqli;

    if(current_color($input['token'])!=null){
    $sql = 'call clean_board()';
    $mysqli->query($sql);
    show_board();
    }
    else{
        header("HTTP/1.1 400 Bad Request");
        print json_encode(['errormesg'=>'You dont have access to do that']);
        exit;
    }
}

//Returns the information of a single board segment (mainly for CLI)
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

//Generates the dice and updates the DB
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

//handles moving the checkers
function move_piece($point_id,$point_id_to,$token){
    global $mysqli;
    $my_color = validate_action($token,'move');

    if($my_color==null){
        return;
    }

    $allowed_values = ['first_dice','second_dice','third_dice','fourth_dice'];

    //in the case of collection (final part of the game) changes moving logic
    if($point_id_to==0){
        if(!check_collection_phase($my_color)){
            header("HTTP/1.1 400 Bad Request");
            print json_encode(['errormesg'=>'You cannot collect unless all pieces are in your home board']);
            exit;
        }

        $needed_die = ($my_color == 'W')? $point_id : ($point_id-12);
        $status = read_status();
        $dice = [];
        if($status['first_dice']!=null){
            $dice['first_dice'] = $status['first_dice'];
        }
        if($status['second_dice']!=null){
            $dice['second_dice'] = $status['second_dice'];
        }
        if($status['third_dice']!=null){
            $dice['third_dice'] = $status['third_dice'];
        }
        if($status['fourth_dice']!=null){
            $dice['fourth_dice'] = $status['fourth_dice'];
        }

        $dice_used= null;

        foreach($dice as $die=>$val){
            if($val==$needed_die){
                $dice_used=$die;
                break;
            }
        }
        if($dice_used==null){
           foreach($dice as $die=>$val){
                if($val>$needed_die){
                    $is_furtest=true;
                    if($my_color=='W'){
                        $sql="select count(*) as c from board where piece_color='W' and point_id>?";
                    }
                    else{
                        $sql="select count(*) as c from board where piece_color='B' and point_id>? and point_id<=18";
                    }
                    $st = $mysqli->prepare($sql);
                    $st->bind_param('i',$point_id);
                    $st->execute();
                    $res=$st->get_result();
                    $check = $res->fetch_assoc();
                    $st->close();

                    if($check['c']==0){
                        $dice_used=$die;
                        break;
                    }
                }
            } 
        }

        if($dice_used==null){
            header("HTTP/1.1 400 Bad Request");
            print json_encode(['errormesg'=>'Invalid collection move']);
            exit;
        }
        if(in_array($dice_used,$allowed_values)){

            $sql = "update board set point_count = point_count-1 where point_id=?";
            $st = $mysqli->prepare($sql);
            $st->bind_param('i',$point_id);
            $st->execute();
            $st->close();

            $sql = "update board set piece_color = null where point_id=? and point_count=0";
            $st = $mysqli->prepare($sql);
            $st->bind_param('i',$point_id);
            $st->execute();
            $st->close();

            $sql = "update game_status set $dice_used = null";
            $st= $mysqli->prepare($sql);
            $st->execute();
            $st->close();

            $col_score = ($my_color=='W')?'white_collected':'black_collected';
            $sql = "update game_status set $col_score = $col_score + 1";
            $st= $mysqli->prepare($sql);
            $st->execute();
            $st->close();

            $sql="select sum(point_count) as total from board where piece_color=?";
            $st = $mysqli->prepare($sql);
            $st->bind_param('s',$my_color);
            $st->execute();
            $res=$st->get_result();
            $rem = $res->fetch_assoc();
            $st->close();

            //handles winning
            if($rem['total']==0){
                $sql = "update game_status set current_status='ended', result_of_match = ?, current_turn=null";
                $st = $mysqli->prepare($sql);
                $st->bind_param('s',$my_color);
                $st->execute();
                $st->close();
            }
            else{
                //checks if stalemate and turn change
                $status = read_status();

                $dice_left = ($status['first_dice']!=null||$status['second_dice']!=null||$status['third_dice']!=null||$status['fourth_dice']!=null);
                
                if($dice_left){
                    if(!any_moves_available($my_color)){
                        $sql="update game_status set first_dice=null, second_dice=null, third_dice=null, fourth_dice=null";
                        $st = $mysqli->prepare($sql);
                        $st->execute();
                        $st->close();
                        $dice_left=false;
                    }

                }
                
                if(!$dice_left){
                    $sql = "update game_status set current_turn = IF(current_turn='W','B','W')";
                    $st = $mysqli->prepare($sql);
                    $st->execute();
                    $st->close();
                }

            }
            header('Content-type: application/json');
            print json_encode(read_board(), JSON_PRETTY_PRINT);
            exit;
        }
    }

    //Checks bountries
    if($point_id_to>24||$point_id_to<1){
        header("HTTP/1.1 400 Bad Request");
        print json_encode(['errormesg'=>'This move is not allowed']);
        exit;
    }

    //Handles normal moving logic
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

    //handles different dice combinations
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

    //Returns error if the move cant work with the dice at hand
    if($found_match==null){
        header("HTTP/1.1 400 Bad Request");
        print json_encode(['errormesg'=>'This move isnt allowed']);
        exit;
    }

    $sql = 'select point_count, piece_color from board where point_id=?';
    $st = $mysqli->prepare($sql);
    $st->bind_param('i',$point_id);
    $st->execute();
    $res=$st->get_result();
    $source = $res->fetch_assoc();

    //Returns error if player tries to move from a place they dont have a piece on
    if($source['piece_color']!=$my_color||$source['point_count']==0){
        header("HTTP/1.1 400 Bad Request");
        print json_encode(['errormesg'=>'You dont have a piece here']);
        exit;
    }

    $st->bind_param('i',$point_id_to);
    $st->execute();
    $res=$st->get_result();
    $dest = $res->fetch_assoc();
    $st->close();

    //Returns error if player tries to move a piece on top of an opponents piece
    if($dest['piece_color']!=$my_color&&$dest['point_count']>0){
        header("HTTP/1.1 400 Bad Request");
        print json_encode(['errormesg'=>'You cant move onto opponents pieces']);
        exit;
    }


    //calls the procedure to move pieces within the DB
    $sql='call move_piece(?,?)';
    $st = $mysqli->prepare($sql);
    $st->bind_param('ii',$point_id,$point_id_to);
    $st->execute();
    $st->close();

    foreach($dice_used as $col){
        if(!in_array($col,$allowed_values)){
            continue;
        }

        $sql = "update game_status set $col = null";
        $st = $mysqli->prepare($sql);
        $st->execute();
        $st->close();

    }

    //Handles stalemate and turn change
    $status = read_status();

    $dice_left = ($status['first_dice']!=null||$status['second_dice']!=null||$status['third_dice']!=null||$status['fourth_dice']!=null);
    
    if($dice_left){
        if(!any_moves_available($my_color)){
            $sql="update game_status set first_dice=null, second_dice=null, third_dice=null, fourth_dice=null";
            $st = $mysqli->prepare($sql);
            $st->execute();
            $st->close();
            $dice_left=false;
        }

    }
    
    if(!$dice_left){
        $sql = "update game_status set current_turn = IF(current_turn='W','B','W')";
        $st = $mysqli->prepare($sql);
        $st->execute();
        $st->close();
    }

    header('Content-type: application/json');
    print json_encode(read_board(), JSON_PRETTY_PRINT);
}

//Checks if the player is allowed to act based on different situations
function validate_action($token,$action_name){
    global $mysqli;

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

    //Makes sure players dont abort
    $sql='update players set last_action = NOW() where piece_color=?';
    $st = $mysqli->prepare($sql);
    $st->bind_param('s',$color);
    $st->execute();
    $st->close();

    return $color;
}

//Checks if the collection phase has started
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

//Checks if the player has any available moves
function any_moves_available($color){
    global $mysqli;

    $status=read_status();
    $dice=[];
    if($status['first_dice']!=null){
        $dice[] = $status['first_dice'];
    }
    if($status['second_dice']!=null){
        $dice[] = $status['second_dice'];
    }
    if($status['third_dice']!=null){
        $dice[] = $status['third_dice'];
    }
    if($status['fourth_dice']!=null){
        $dice[] = $status['fourth_dice'];
    }

    if(empty($dice)){
        return false;
    }

    $sql='select point_id from board where piece_color = ?';
    $st = $mysqli->prepare($sql);
    $st->bind_param('s',$color);
    $st->execute();
    $res=$st->get_result();
    $pieces = $res->fetch_all(MYSQLI_ASSOC);
    $st->close();

    $can_collect = check_collection_phase($color);

    foreach($pieces as $p){
        $from = $p['point_id'];
        foreach($dice as $die){
            $target = $from - $die;
            if($color=='W'){
                if($target<1){
                    if($can_collect){
                        return true;
                    }
                    continue;
                }
            }
            if($color=='B'){
                if($from<=12 && $target<1){
                    $target+=24;
                }
                elseif($from>=13 && $target<13){
                    if($can_collect){
                        return true;
                    }
                    continue;
                }
            }

            $sql = 'select piece_color from board where point_id=?';
            $st = $mysqli->prepare($sql);
            $st->bind_param('i',$target);
            $st->execute();
            $res=$st->get_result();
            $spot = $res->fetch_assoc();
            $st->close();

            if($spot['piece_color']==null||$spot['piece_color']==$color){
                return true;
            }
        }
    }
    return false;

}

?>