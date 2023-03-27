<?php
require_once 'classes/DB.php';
date_default_timezone_set('Asia/Tokyo');

class Attendance
{    
    /** @var object データベースを操作するためのインスタンス */
    protected $db;

    protected $userId;
    protected $timecardId;

    const DATETIME_FORMAT = 'Y-m-d H:i:00';
    const DAY_FORMAT = 'Y-m-d';

    /** 'Y-m-d' */
    protected $dayStr;
    /**'Y-m-d H:i:00' */
    protected $nowStr;
    /**'Y-m-d H:i:00' */
    protected $workStartStr;
    /**'Y-m-d H:i:00' */
    protected $workEndStr;
    
    /** 現在時間 dateTimeImmutableオブジェクト */
    protected $now;
    /** 出勤時間 dateTimeImmutableオブジェクト */
    protected $workStart;
    /** 退勤時間 dateTimeImmutableオブジェクト */
    protected $workEnd;
    /** 休憩開始時間 dateTimeImmutableオブジェクト */
    protected $restStart;
    /** 休憩終了時間 dateTimeImmutableオブジェクト */
    protected $restEnd;

    /** トータルの休憩時間 $rest_h:$rest_i:00 */
    protected $rest_h;

    /** トータルの休憩時間 $rest_h:$rest_i:00 */
    protected $rest_i;

    /** 総労働時間 YYYY-MM-DD hh:ii:00 */
    protected $totalWork;

    /** 実労働時間 YYYY-MM-DD hh:ii:00 */
    protected $actualWork;

    const DB_ERROR = '接続エラーが発生しました';

    /** インスタンス化時に現在時刻と現在日時を設定する */
    public function __construct()
    {
        $this->now = new DateTimeImmutable();
        $this->dayStr = $this->now->format(self::DAY_FORMAT);
        $this->nowStr = $this->now->format(self::DATETIME_FORMAT);
        $this->db = DB::getDBInstance();
    }

    /** timecardIdをもとにtimecardsレコードを取得する */
    public function getTimecard($timecardId){
        $sql = 'SELECT * FROM timecards WHERE id = ? AND `day` = ?;';
        $param = [$timecardId, $this->dayStr];
        return $this->db->select($sql, $param);
    }

    /** userIdをもとにtimecardsレコードを取得する */
    public function getTimecardByUserId($userId){
        $sql = 'SELECT * FROM timecards WHERE user_id = ? AND `day` = ?;';
        $param = [$userId, $this->dayStr];
        $stmt = $this->db->select($sql, $param);
        return $stmt;
    }

    public function getStartWork($timecardId){
        $sql = "SELECT `start` FROM timecards WHERE id = ?;";
        $param = [$timecardId];
        $stmt = $this->db->select($sql,$param);
        $start = $stmt->fetch();
        return $start;
    }

    /**
     * 出勤時の処理。timecardsテーブルに行を挿入する。
     *
     * @param [type] $userId
     * @return void
     */
    public function startWork($userId){
        $sql = "INSERT INTO timecards 
            (
             id,
             user_id,
             `day`,
             `start`,
             `end`,
              work_time,
              actual_work_time,
              total_rest_time
              )
             VALUE (?,?,?,?,?,?,?,?);";
        $param = [
            Null,
            $userId,
            $this->dayStr,
            $this->nowStr,
            Null,
            Null,
            Null,
            Null
        ];

        $this->db->insertTimecard($sql,$param);
    }

    /**
     * 休憩開始を登録。timecard_restにinsertする。
     *
     * @param [type] $timecardId
     * @return void
     */
    function startRest($timecardId){
        $sql = "INSERT INTO timecard_rests (id,`start`,`end`,timecard_id)
                VALUES (?,?,?,?);";
        $param = [
            Null,
            $this->nowStr,
            Null,
            $timecardId
        ];
        $this->db->insertRests($sql, $param);

    }

    /**
     * 休憩終了を登録。timecard_restsのidを取得し、updateする。
     *
     * @param [type] $timecardId
     * @return void
     */
    function endRest($timecardId){
        // 対象となるtimecard_restsのidを取得する。
        $sql = "SELECT * FROM timecard_rests 
                WHERE timecard_id = ?
                AND `end` is Null;";
        $param = [$timecardId];
        $stmt = $this->db->select($sql, $param);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $restId = $row['id'];

        // データがあれば更新
        if($row){
            $sql = "UPDATE timecard_rests SET `end` = ?
                    WHERE id = ?";
            $param = [
                $this->nowStr,
                $restId
            ];
            $stmt = $this->db->updateRests($sql,$param);
        } else {
            echo 'エラーが発生しました。';
        }

    }

    /**
     * 勤務終了を登録。指定idのtimecardsをupdate
     *
     * @param [type] $timecardId
     * @return void
     */
    function endWork($timecardId){
        // 各プロパティの設定
        // 勤務終了時間
        $this->workEnd = $this->now;
        $this->workEndStr = $this->nowStr;

        // タイムカードの情報
        $this->setAttendanceInfo($timecardId);

        // 休憩時間
        $this->setRestTime($timecardId);
    
        // 総労働時間と実労働時間
        $this->setWorkTimes();

        // 退勤を登録
        $sql = "UPDATE timecards SET 
            `end` = ?,
            work_time = ?,
            actual_work_time = ?,
            total_rest_time = ?
            WHERE id = ?;
            ";
        $param = [
            $this->workEndStr,
            $this->totalWork,
            $this->actualWork,
            $this->dayStr .' ' .$this->rest_h .':' .$this->rest_i .":00",
            $this->timecardId
        ];

        $this->db->updateTimecards($sql,$param);
    }
    
    /**
     * Attendanceクラスのプロパティにtimecardsレコードの各フィールドを設定する。
     *
     * @param [type] $timecardId
     * @return void
     */
    public function setAttendanceInfo($timecardId){
        // timecardの情報をセットする
        $sql = "SELECT * FROM timecards WHERE id = ?;";
        $param = [$timecardId];
        $stmt = $this->db->select($sql,$param);
        $i = 0;
        foreach($stmt as $row){
            $this->timecardId = $row[$i++];
            $this->userId = $row[$i++];
            $this->dayStr = $row[$i++];
            $this->workStart = new DateTimeImmutable($row[$i++]);
            $this->workStartStr = $this->workStart->format(self::DATETIME_FORMAT);
            $this->workEnd = $row[$i++];
            $this->workEndStr = $this->workEnd->format(self::DATETIME_FORMAT);
            $this->totalWork = $row[$i++];
            $this->actualWork = $row[$i++];
        }
        $this->setRestTime($timecardId);
    }

    public function getAttendanceInfo($timecardId){
        $this->setAttendanceInfo($timecardId);
        $items = [
            'workStart' => $this->workStartStr,
            'workEnd' => $this->workEndStr,
            'totalWork' => substr($this->totalWork,11),
            'actualWork' => substr($this->actualWork,11),
            0 => $this->rest_h,
            1 => $this->rest_i,
        ];
        return $items;
    }

    /**
     * Attendanceクラスの$totalWorkプロパティと$actualWorkプロパティを設定する。
     *
     * @return void
     */
    public function setWorkTimes(){
        // 総労働時間の登録
        $workTimeDiff = $this->workEnd->diff($this->workStart);
        $this->totalWork = $this->dayStr .' '
                            ."{$workTimeDiff->h}:{$workTimeDiff->i}:00";
        // 実労働時間の登録
        $this->actualWork = $this->dayStr .' '
                            .$workTimeDiff->h - $this->rest_h
                            .":"
                            .$workTimeDiff->i - $this->rest_i
                             .":00"
                            ;
    }

    /**
     * Attendanceクラスの$rest_h, $rest_iプロパティを設定する。
     *
     * @param [type] $timecardId
     * @return void
     */
    public function setRestTime($timecardId){
        // 休憩時間をセットする。
        $sql = "SELECT * FROM timecard_rests WHERE timecard_id = ?;";
        $param = [$timecardId];
        $stmt = $this->db->select($sql,$param);

        while($row = $stmt->fetch()){
            // $row = [id,start,end,timecardId]
            $restStart = new DateTimeImmutable($row[1]);
            $restEnd = new DateTimeImmutable($row[2]);
            $restDiff = $restEnd->diff($restStart);
            $this->rest_h += $restDiff->h;
            $this->rest_i += $restDiff->i;
        }
    }


    public function getTotalRest($timecardId):array {
        $sql = "SELECT `start`,`end` FROM timecard_rests
                WHERE id = ?;";
        $param = [$timecardId];
        $stmt = $this->db->select($sql,$param);

        $rest_h = 0;
        $rest_i = 0;

        foreach($stmt as $row){
            $start = New DateTimeImmutable($row[0]);
            $end = New DateTimeImmutable($row[1]) ?? $this->now;
            $diff = $end->diff($start);
            $rest_h = $diff->h;
            $rest_i = $diff->i;
        }
        if($rest_i / 60 > 0){
            $rest_h = $rest_i / 60;
            $rest_i = $rest_i % 60;
        }
        return [$rest_h, $rest_i];
    }
}