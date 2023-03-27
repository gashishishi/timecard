<?php
require_once 'classes/attendance.php';
if(!empty($_POST['timecardId'])){
  $timecardId = $_POST['timecardId'];
  $attendance = New Attendance;
  if(!empty($_POST['end'])){
    $items = $attendance->getAttendanceInfo($timecardId);
    $start = $items['workStart'];
    $totalWork = $items['totalWork'];
    $actualWork = $items['actualWork'];
    $end = $items['workEnd'];
    $rests = [$items['rest_h'], $items['rest_i']];
  } else {
    // 勤務終了時と勤務開始前以外はこちらの分岐
    $start = $attendance->getStartWork($timecardId);
    $rests = $attendance->getTotalRest($timecardId);
  }
}
var_dump($start);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <title>Timecard</title>
</head>
<body>
    <main class="container">
<section class="row">
    <div class="col-3">
        <p>現在時刻</p>
        <p id="RealtimeClockArea2"></p>
    </div>

    <div class="col-8">
        <form action="put-attendance.php" method="post">
            <p>タイムカード</p>
            <button type="submit" id="start" name="start" value="start">出勤</button>
            <p>出勤時間: <?= $start ?? '' ;?></p>
            <button type="submit" id="end" name="end" value="end" disabled >退勤</button>
            <p>退勤時間: <?= $end ?? '' ;?></p>
            <p>休憩</p>
            <button type="submit" id="start-rest" name="start-rest" value="start-rest" disabled >開始</button>
            <button type="submit" id="end-rest" name="end-rest" value="end-rest" disabled >終了</button>
            <p>現在の総休憩時間: <?= isset($rests) ? $rests[0] .':' .$rests[1] : '00:00' ;?></p>
            <p>勤務時間: <?= $totalWork ?? '00:00';?></p>
            <p>実労働時間: <?= $actualWork ?? '00:00';?></p>
            <input type="hidden" name="user" value='1'>
            <input type="hidden" name="timecardId" value='<?= $timecardId ?? '';?>'>
        </form>

    </div>

</section>
</main>
<script src="https://code.jquery.com/jquery-3.6.4.min.js" integrity="sha256-oP6HI9z1XaZNBrJURtCoUT5SUnxFr8s3BzRl+cbzUq8=" crossorigin="anonymous"></script>
<script>

function set2fig(num) {
   // 桁数が1桁だったら先頭に0を加えて2桁に調整する
   var ret;
   if( num < 10 ) { ret = "0" + num; }
   else { ret = num; }
   return ret;
}
function showClock2() {
   var nowTime = new Date();
   var nowHour = set2fig( nowTime.getHours() );
   var nowMin  = set2fig( nowTime.getMinutes() );
   var msg = nowHour + ":" + nowMin;
   document.getElementById("RealtimeClockArea2").innerHTML = msg;
}
setInterval('showClock2()',1000);


jQuery(function($){
    const start = "<?= $_POST['start'] ?? false; ?>";
    const end = "<?= $_POST['end'] ?? false; ?>";
    const restStart = "<?= $_POST['start-rest'] ?? false; ?>";
    const restEnd = "<?= $_POST['end-rest'] ?? false; ?>";

    if(restEnd){
        disabledEndRest();
    } else if(restStart){
        disabledStartRest();
    } else if(start){
        disabledStart();
    } else{
        buttonInit();
    }

    // 出勤後のボタン状態を作る
  function disabledStart(){
    $('#start').prop('disabled',true);
    $('#end').prop('disabled',false);
    $('#start-rest').prop('disabled',false);
    $('#end-rest').prop('disabled',true);
    console.log('startbutton');
  }

    // 休憩開始後のボタン状態を作る
  function disabledStartRest(){
    $('#start').prop('disabled',true);
    $('#end').prop('disabled',true);
    $('#start-rest').prop('disabled',true);
    $('#end-rest').prop('disabled',false);
    console.log('reststart');
  }

    // 休憩終了後のボタン状態を作る
  function disabledEndRest(){
    $('#start').prop('disabled',true);
    $('#end').prop('disabled',false);
    $('#start-rest').prop('disabled',false);
    $('#end-rest').prop('disabled',true);
    console.log('reststart');
  }

  // 退勤後・出勤前のボタン状態を作る。
  function buttonInit(){
    $('#start').prop('disabled',false);
    $('#end').prop('disabled',true);
    $('#start-rest').prop('disabled',true);
    $('#end-rest').prop('disabled',true);
  }

});
</script>
</body>
</html>