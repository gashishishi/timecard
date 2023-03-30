<?php
require_once 'classes/attendance.php';
$attendance = new Attendance;
$timecards = [];

/** リダイレクトする */
function redirect() {
    header('Location: timecard.php',true,307);
}

if(!empty($_POST)){
    // 勤務開始でなければtimecardsを設定する。
    if(empty($_POST['start'])){
        $timecards = $attendance->getTimecardByUserId($_POST['userId']);
    } else {
        $attendance->startWork($_POST['userId']);
        redirect();
    }
}
$timecard = '';
// endがnullであるものが2つ以上あればエラー。
foreach($timecards as $row){
    if(is_null($row['end'])){
        if(!$timecard){
            $timecard = $row;
        } else {
            echo 'error';
        }
    }
}
// 勤務開始以外
if($timecard){
    if(is_null($timecard['end'])){

        if(!empty($_POST['end'])){
            $attendance->endWork($timecard['id']);
            redirect();
        } else if(!empty($_POST['start-rest'])){
            $attendance->startRest($timecard['id']);
            redirect();
        } else if(!empty($_POST['end-rest'])){
            $attendance->endRest($timecard['id']);
            redirect();
        }
    }
}
// リダイレクトする
// header('Location: timecard.php');
// exit();