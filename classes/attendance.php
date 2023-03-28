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

    /** トータルの休憩時間 hh:ii:00 */
    protected $totalRest = 0;

    /** 総労働時間 hh:ii:00 */
    protected $totalWork = 0;

    /** 実労働時間 hh:ii:00 */
    protected $actualWork = 0;

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
        $stmt = $this->db->select($sql, $param);
        return $stmt;
    }

    /** userIdをもとにtimecardsレコードを取得する */
    public function getTimecardByUserId($userId){
        $sql = 'SELECT * FROM timecards WHERE user_id = ? AND `day` = ?;';
        $param = [$userId, $this->dayStr];
        $stmt = $this->db->select($sql, $param);
        return $stmt;
    }

    /** userIdをもとにtimecards_idを取得する */
    public function getTimecardIdByUserId($userId){
        $sql = 'SELECT id FROM timecards WHERE user_id = ? AND `day` = ? AND `end` is NULL;';
        $param = [$userId, $this->dayStr];
        $stmt = $this->db->select($sql, $param);
        $timecardId = $stmt->fetch()[0];
        return $timecardId;
    }

    /**
     * 画面表示用に始業時間を取得する。
     *
     * @param [type] $timecardId
     * @return void
     */
    public function getStartWork($timecardId){
        $sql = "SELECT `start` FROM timecards WHERE id = ?;";
        $param = [$timecardId];
        $stmt = $this->db->select($sql,$param);
        $start = $stmt->fetch()[0];
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
            Null,
        ];

        $this->db->insertTimecard($sql,$param);
    }

    /**
     * 休憩開始を登録。timecard_restsにinsertする。
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
        $sql = "SELECT id, `start` FROM timecard_rests 
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
            $this->db->updateRests($sql,$param);
        } else {
            echo 'エラーが発生しました。';
        }

        // $endRest = strtotime($this->nowStr);
        // $add = $endRest - $startRest;
        // var_dump($add);
        // // timecardsに休憩時間を登録
        // $sql = 'UPDATE timecards SET (total_rest_time = total_rest_time + ?)
        //         WHERE id = ?     
        //         ;';
        // $param = [$add, $restId];
        // $this->db->updateTotalRest($sql,$param);
    
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

        // タイムカードの情報をプロパティに設定
        $this->setAttendanceInfo($timecardId);

        // 休憩時間を$totalRestプロパティに設定
        $this->setRestTime($timecardId);
        // 総労働時間と実労働時間をプロパティに設定
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
            $this->totalRest,
            $this->timecardId
        ];

        $this->db->updateTimecards($sql,$param);
    }
    
    /**
     * 退勤時表示用および退勤情報設定用に、Attendanceクラスのプロパティにtimecardsレコードの各フィールドを設定する。
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
            $this->workEnd = new DateTimeImmutable($row[$i++]);
            $this->workEndStr = $this->workEnd->format(self::DATETIME_FORMAT);
            $this->totalWork = $row[$i++];
            $this->actualWork = $row[$i++];
            $this->totalRest = $row[$i++];
        }
    }

    /**
     * 画面表示用にこのクラスのプロパティを取得する。
     *
     * @param [type] $timecardId
     * @return void
     */
    public function getAttendanceInfo($timecardId){
        $this->setAttendanceInfo($timecardId);
        $items = [
            'workStart' => $this->workStartStr,
            'workEnd' => $this->workEndStr,
            // hh:iiにする
            'totalWork' => $this->substrTime( $this->totalWork ),
            'actualWork' => $this->substrTime( $this->actualWork),
            'totalRest' => $this->substrTime( $this->totalRest)
        ];
        return $items;
    }

    /**
     * Attendanceクラスの$totalWorkプロパティと$actualWorkプロパティを設定する。
     * $totalRestプロパティの設定後に行うこと。
     *
     * @return void
     */
    public function setWorkTimes(){
        var_dump($this->totalRest);

        /////////// x時間とy分、の計算でなく、 x時y分という時刻で計算してるっぽい?///////////////////////
        // 時間設定の準備
        $totalWork = strtotime($this->workEndStr) - strtotime($this->workStartStr);
        $totalRest = strtotime($this->totalRest);
        // 実労働時間を設定
        $actualWork = $totalWork - $totalRest;
        var_dump($totalWork);
        echo '<br>';
        var_dump($totalRest);
        // タイムスタンプから時間・分にする。
        $actualWork = $this->changeHI($actualWork);
        // 時間の文字列を作り設定する。
        $this->actualWork = $this->createTimeStr($actualWork);

        // 総労働時間の設定
        // タイムスタンプから時間・分にする。
        $totalWork = $this->changeHI($totalWork);
        // 時間の文字列を作り設定する。
        $this->totalWork = $this->createTimeStr($totalWork);
    }

    /**
     * timecard_restsから休憩時間を取得して,総休憩時間文字列をプロパティに設定する
     *
     * @param [type] $timecardId
     * @return void
     */
    public function setRestTime($timecardId){
        $sql = "SELECT `start`,`end` FROM timecard_rests WHERE timecard_id = ? AND `end` IS NOT NULL;";
        $param = [$timecardId];
        $stmt = $this->db->select($sql,$param);
        $totalRest = 0;
        foreach($stmt as $row){
            $totalRest += strtotime($row[1]) - strtotime($row[0]);
        }
        $totalRest = $this->changeHI($totalRest);
        $this->totalRest = $this->createTimeStr($totalRest);

    }

    /**
     * 画面表示用に休憩時間を取得。
     *
     * @param [type] $timecardId
     * @return void
     */
    public function getRestTime($timecardId):string {
        // 休憩時間を取得する。
        if(empty($this->totalRest)){
            $this->setRestTime($timecardId);
        }
        return $this->substrTime($this->totalRest);
    }

    /**
     * timestampを時と分に変換する
     *
     * @param [type] $timestamp
     * @return array [$hour,$minute]
     */
    public function changeHI($timestamp):array {
        // 全体を分に変換
        $i = $timestamp % 60;
        // 時(小数点切り捨て)
        $hour = floor($i / 60);
        // 分
        $minute = $i % 60;
        return [$hour, $minute];
    }

    /**
     * 時間文字列を作成する(hh:ii:00)。数字が一桁のときは0をつける。
     *
     * @param array $time
     * @return string
     */
    public function createTimeStr(array $time):string {
        $h = ($time[0] < 10) ? "0"."$time[0]" : "$time[0]";
        $i = ($time[1] < 10) ? "0"."$time[1]" : "$time[1]";
        return "{$h}:{$i}:00";
    }

    /**
     * 時間文字列hh:ii:00をhh:iiにする。
     *
     * @param [type] $timeStr
     * @return string hh:ii
     */
    public function substrTime($timeStr):string {
        return substr($timeStr,0,5);
    }
}