<?php
require_once 'classes/DB.php';
date_default_timezone_set('Asia/Tokyo');

class Attendance
{    
    /** @var object データベースを操作するためのインスタンス(DBクラスの静的プロパティの要約変数)*/
    protected $dbh;

    protected $userId;
    protected $timecardId;

    const TIME_FORMAT = 'Y-m-d H:i:00';
    const DAY_FORMAT = 'Y-m-d 00:00:00';
    protected $day;
    protected $nowTime;
    
    protected $now;

    protected $workStart;
    protected $workEnd;
    protected $restStart;
    protected $restEnd;

    // トータルの休憩時間
    protected $rest_h;
    protected $rest_i;

    protected $totalWork;
    protected $actualWork;

    public function __construct()
    {
        $this->now = new DateTimeImmutable();
        $this->day = $this->now->format(self::DAY_FORMAT);
        $this->nowTime = $this->now->format(self::TIME_FORMAT);
        try{
            $this->dbh = DB::getDbInstance()->getDbh();
            $dbh = $this->dbh;
        } catch(PDOException $e){
            echo '接続エラーが発生しました';
        }
    }

    public function createTimecard($userId){
        $dbh = $this->dbh;
        $sql = "INSERT INTO timecards 
            (id, user_id, `day`, `start`, `end`, work_time, actual_work_time, total_rest_time)
             VALUE (Null,:userid, '{$this->day}', '{$this->nowTime}', Null, Null, Null, Null);";
        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(":userid", $userId, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function getTimecard($timecardId){
        $dbh = $this->dbh;
        $sql = 'SELECT * FROM timecards WHERE id = :id';
        $stmt = $dbh->prepare($sql);
        $stmt->bindparam(":id", $timecardId, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function getTimecardByUserId($userId){
        $dbh = $this->dbh;
        $sql = 'SELECT * FROM timecards WHERE user_id = :userid';
        $stmt = $dbh->prepare($sql);
        $stmt->bindparam(":userid", $userId, PDO::PARAM_INT);
        $stmt->execute();
    }

    function createRest($timecardId){
        $dbh = $this->dbh;
        // 休憩開始を登録
        $sql = "INSERT INTO timecard_rests ('id','start','end','timecard_id')
                VALUES (Null, $this->now, Null, :timecardId);";
        $stmt = $dbh->prepare($sql);
        $stmt->bindparam(":timecardId", $timecardId,PDO::PARAM_INT);
        $stmt->execute();
    }

    function updateRest($timecardId){
        // 対象となるtimecard_restsレコードを取得する。
        $dbh = $this->dbh;
        $sql = "SELECT * FROM timecard_rests 
                WHERE timecard_id = :timecard_id
                AND `end` is Null;";

        $stmt = $dbh->prepare($sql);
        $stmt->bindparam(":timecard_id", $timecardId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // データが有れば更新
        if($row){
            $sql = "UPDATE timecard_rests SET `end` = {$this->now}
                    WHERE timecard_id = :timecard_id";
            $stmt = $dbh->prepare($sql);
            $stmt->bindparam(":timecard_id", $timecardId, PDO::PARAM_INT);
            $stmt->execute();
        }
    }

    function updateWork($timecardId){
        $this->workEnd = $this->now;
        $dbh = $this->dbh;

        // timecardの情報をセット
        $sql = "SELECT * FROM timecards WHERE id = :timecardId;";
        $stmt = $dbh->prepare($sql);
        $stmt->bindparam(":timecard_id", $timecardId, $timecardId,PDO::PARAM_INT);
        $stmt->execute();
        $i = 0;
        foreach($stmt->fetch() as $row){
            $this->timecardId = $row[$i++];
            $this->userId = $row[$i++];
            $this->day = $row[$i++];
            $this->workStart = $row[$i++];
            // $this->workEnd = $row[$i++];
            // $this->totalWork = $row[$i++];
            // $this->actualWork = $row[$i++];
        }
        
        // 休憩時間をセット
        $sql = "SELECT * FROM timecard_rests WHERE timecard_id = :timecardId;";
        $stmt = $dbh->prepare($sql);
        $stmt->bindparam(":timecard_id", $timecardId, $timecardId,PDO::PARAM_INT);
        $stmt->execute();

        foreach($stmt->fetch() as $row){
            $restStart = new DateTimeImmutable($row[1]);
            $restEnd = new DateTimeImmutable($row[2]);
            $restDiff = $restEnd->diff($restStart);
            $this->rest_h += $restDiff->h;
            $this->rest_i += $restDiff->i;
        }
    
        // 総労働時間の登録
        $workTimeDiff = $this->workEnd->diff($this->workStart);
        $this->totalWork = $this->day .$workTimeDiff->h .$workTimeDiff->i .":00";
        // 実労働時間の登録
        $this->actualWork = $this->day 
                            .$workTimeDiff->h - $this->rest_h
                            .$workTimeDiff->i - $this->rest_i
                             .":00"
                            ;
/*
        echo '<pre>';
        echo 'ここはendwork()<br>';
        echo 'workh<br>';
            var_dump($work_h);
        echo 'worki<br>';
    
            var_dump($work_i);
        echo 'resth<br>';
    
            var_dump($rest_h);
        echo 'resti<br>';
    
            var_dump($rest_i);
        echo 'starttime<br>';
    
            var_dump($startTime);
        echo 'endtime<br>';
    
            var_dump($endTime);
        echo 'work<br>';
            
            var_dump($work);
        echo 'actualwork<br>';
    
            var_dump($actualWork);
        echo '</pre>';
    */
      // 退勤を登録
        $sql = "UPDATE timecards SET 
            `end` = ?,
            work_time = ?,
            actual_work_time = ?,
            total_rest_time = ?
            WHERE timecard_id = ?;
            ";
        $stmt = $dbh->prepare($sql);
        $stmt->bindparam(1, $this->workEnd, PDO::PARAM_STR);
        $stmt->bindparam(2, $this->totalWork, PDO::PARAM_STR);
        $stmt->bindparam(3, $this->actualWork, PDO::PARAM_STR);
        $stmt->bindparam(4, $this->day . $this->rest_h . $this->rest_i .":00", PDO::PARAM_STR);
        $stmt->bindparam(5, $this->timecardId, PDO::PARAM_INT);
        $stmt->execute();
    }

}