<?php
require_once "lib/board.php";



$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/',trim($_SERVER['PATH_INFO'],'/'));

switch ($r = array_shift($request)) {
    case 'board':
        echo 'its the board! <br>';
        switch($b = array_shift($request)) {
            case '':
            case null:
                handle_board($method);
                break;
            case 'piece':
                echo 'its a number!';
                break;
            default:
                echo 'idk';
                break;
            }
        break;
    default:
        echo 'idk';
        break;
}
function handle_board($method){
    if($method=='GET'){
        show_board();
    }
    else if($method=='POST'){
        reset_board();
    }
    else{
        echo'Something else';
    }
}

?>

