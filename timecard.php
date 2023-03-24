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
            <p></p>
            <button type="submit" id="end" name="end" value="end" disabled >退勤</button>
            <p></p>
            <p>休憩</p>
            <button type="submit" id="rest-start" name="rest-start" value="rest-start" disabled >開始</button>
            <button type="submit" id="rest-end" name="rest-end" value="rest-end" disabled >終了</button>
            <p></p>

            <p>勤務時間</p>
            <p></p>
            <p>実労働時間</p>
            <p></p>
            <input type="hidden" name="user" value='1'>
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
    const restStart = "<?= $_POST['rest-start'] ?? false; ?>";
    const restEnd = "<?= $_POST['rest-end'] ?? false; ?>";

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
    $('#rest-start').prop('disabled',false);
    $('#rest-end').prop('disabled',true);
    console.log('startbutton');
  }

    // 休憩開始後のボタン状態を作る
  function disabledStartRest(){
    $('#start').prop('disabled',true);
    $('#end').prop('disabled',true);
    $('#rest-start').prop('disabled',true);
    $('#rest-end').prop('disabled',false);
    console.log('reststart');
  }

    // 休憩終了後のボタン状態を作る
  function disabledEndRest(){
    $('#start').prop('disabled',true);
    $('#end').prop('disabled',false);
    $('#rest-start').prop('disabled',false);
    $('#rest-end').prop('disabled',true);
    console.log('reststart');
  }

  // 退勤後・出勤前のボタン状態を作る。
  function buttonInit(){
    $('#start').prop('disabled',false);
    $('#end').prop('disabled',true);
    $('#rest-start').prop('disabled',true);
    $('#rest-end').prop('disabled',true);
  }

});
</script>
</body>
</html>