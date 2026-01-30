<?php
require_once "lib/dbconnect.php";
require_once "lib/board.php";
require_once "lib/game.php";
require_once "lib/users.php";


//Trims the api call
$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/',trim($_SERVER['PATH_INFO'],'/'));
$input = json_decode(file_get_contents('php://input'),true);

if($input==null){
    $input=[];
}
if(isset($_SERVER['HTTP_APP_TOKEN'])){
    $input['token']=$_SERVER['HTTP_APP_TOKEN'];
}
elseif(!isset($input['token'])){
    $input['token']='';
}

//The API handles, based on the trimed result
switch ($r = array_shift($request)) {
    case 'board':
        switch($b = array_shift($request)) {
            case '':
            case null:
                handle_board($method,$input);
                break;
            case 'piece':
                $point_id = array_shift($request);
                handle_piece($method,$point_id,$input);
                break;
            default:
                header("HTTP/1.1 404 Not Found");
                header('Content-Type:application/json');
                print json_encode(['errormesg'=>'No valid page']);
                exit;
                break;

            }
        break;
    case 'status':
        if(sizeof($request)==0){
            handle_status($method);
        }

        else{
            header("HTTP/1.1 404 Not Found");
            header('Content-Type:application/json');
            print json_encode(['errormesg'=>'No valid page']);
            exit;
        }
        break;
    case 'players':
        handle_player($method,$request,$input);
        break;
    case 'roll':
        handle_dice($method,$input);
        break;
    default:
        header("HTTP/1.1 404 Not Found");
        header('Content-Type:application/json');
        print json_encode(['errormesg'=>'No valid page']);
        exit;
        break;
}

//handles the simple Board API requests
function handle_board($method,$input){
    if($method=='GET'){
        show_board();
    }
    else if($method=='POST'){
        reset_board($input);
    }
    else{
        header('HTTP/1.1 405 Method Not Allowed');
        header('Content-Type:application/json');
        print json_encode(['errormesg'=>'Selected method is not valid']);
        exit;
    }
}

//handles the more advanced API for checkers
function handle_piece($method,$point_id,$input){
    if($method=='GET'){
        show_piece($point_id);
    }
    elseif($method=='PUT'){
        move_piece($point_id,$input['point_id'],$input['token']);
    }
    else{
        header('HTTP/1.1 405 Method Not Allowed');
        header('Content-Type:application/json');
        print json_encode(['errormesg'=>'Selected method is not valid']);
        exit;
    }
}

//Handles the status API calls
function handle_status($method){
    if($method=='GET'){
        show_status();
    }
    else{
        header('HTTP/1.1 405 Method Not Allowed');
        header('Content-Type:application/json');
        print json_encode(['errormesg'=>'Selected method is not valid']);
        exit;
    }
}

//Handles the users based API calls
function handle_player($method,$p,$input){
    switch($b  = array_shift($p)){
        case '':
        case null:
            if($method=='GET'){
                show_users($input);
            }
            else{
                header('HTTP/1.1 405 Method Not Allowed');
                header('Content-Type:application/json');
                print json_encode(['errormesg'=>'Selected method is not valid']);
                exit;
            }
            break;
        case 'W':
        case 'B':
            handle_user($method,$b,$input);
            break;
        default:
            header("HTTP/1.1 404 Not Found");
            header('Content-Type:application/json');
            print json_encode(['errormesg'=>'No valid page']);
            exit;
            break;
    }
}

//Handles the dice rolling API call
function handle_dice($method,$input){
    if($method=='POST'){
        generate_dice_roll($input['token']);
    }
    else{
        header('HTTP/1.1 405 Method Not Allowed');
        header('Content-Type:application/json');
        print json_encode(['errormesg'=>'Selected method is not valid']);
        exit;
    }

}

?>

