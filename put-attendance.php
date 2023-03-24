<?php
require_once 'classes/attendance.php';
$attendance = new Attendance;
$timecards = '';
if($_POST && empty($_POST['start'])){
    $timecards = $attendance->getTimecardByUserId($_POST['user']);
}
print_r($_POST);
print_r($timecards);

if(!$timecards){
    if(!empty($_POST['start'])){
        $attendance->createTimecard($_POST['user']);
        var_dump('start');
    }
} else {
    if(is_null($timecard->end)){

        if(!empty($_POST['end'])){
            $attendance->updateWork($timecard->id);
            var_dump('end');
        } else if(!empty($_POST['rest-start'])){
            $attendance->createRest($timecard->id);
            var_dump('reststart');
        } else if(!empty($_POST['rest-end'])){
            $attendance->updateRest($timecard->id);
            var_dump('restend');
        }
    }
}

function redirect(){
    header('Location: timecard.php');
}