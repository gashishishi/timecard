<?php

/**
 * データベースに接続するクラス
 */
class DB{
    /** @var DBクラスのインスタンス */
    private static $dbInstance;
    private $dbh;

    /**データベースの情報。本来は別ファイルに分けたりしたい。*/

    // // アップロード
    // private const HOST ='mysql1.php.xdomain.ne.jp';
    // private const DB_NAME = 'akisyokuren_sampledb';
    // private const DB = "mysql:host=" .self::HOST .";dbname=" .self::DB_NAME;
    // private const USER = 'akisyokuren_toga';
    // private const PASSWORD = 'yesterday';
    // ローカル
    private const HOST ='localhost';
    private const DB_NAME = 'timecard';
    private const DB = "mysql:host=" .self::HOST .";dbname=" .self::DB_NAME;
    private const USER = 'root';
    private const PASSWORD = 'yesterday';

    //ローカル・アップロード共通部分
    private const OPT = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        // MySQLからのエラーを取得する
        PDO::ATTR_EMULATE_PREPARES => false,
        // マルチクエリを不可に。セキュリティ的な目的。
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,
    ];
    
    private function __construct(){
        // データベースに接続する。
        $this->dbh = new PDO(self::DB, self::USER, self::PASSWORD, self::OPT);  
    }

    /** $dbhのゲッター */ 
    public function getDbh(){
        return $this->dbh;
    }

    /** DBクラスのインスタンスを取得する */
    public static function getDBInstance() {
        return self::$dbInstance ?? self::$dbInstance = New DB;
    }

    /** select文を実行する */
    public function select($sql, array $param){
        $stmt = $this->dbh->prepare($sql);
        $stmt->execute($param);
        return $stmt;
    }

    /** timecardsにinsertする */
    public function insertTimecard($sql,array $param){
        $i = 1;
        $j = 0;
        $stmt = $this->dbh->prepare($sql);
        $stmt->bindParam($i++, $param[$j++], PDO::PARAM_NULL);
        $stmt->bindParam($i++, $param[$j++], PDO::PARAM_INT);
        $stmt->bindParam($i++, $param[$j++], PDO::PARAM_STR);
        $stmt->bindParam($i++, $param[$j++], PDO::PARAM_STR);
        $stmt->bindParam($i++, $param[$j++], PDO::PARAM_NULL);
        $stmt->bindParam($i++, $param[$j++], PDO::PARAM_NULL);
        $stmt->bindParam($i++, $param[$j++], PDO::PARAM_NULL);
        $stmt->bindParam($i++, $param[$j++], PDO::PARAM_NULL);
        $stmt->execute();
    }

    /** timecard_restsにinsertする */
    public function insertRests($sql,array $param){
        $i = 1;
        $j = 0;
        $stmt = $this->dbh->prepare($sql);

        $stmt->bindParam($i++, $param[$j++], PDO::PARAM_NULL);
        $stmt->bindParam($i++, $param[$j++], PDO::PARAM_STR);
        $stmt->bindParam($i++, $param[$j++], PDO::PARAM_NULL);
        $stmt->bindParam($i++, $param[$j++], PDO::PARAM_INT);

        $stmt->execute();
    }

    /** timecardsをupdateする */
    public function updateTimecards($sql, array $param){
        $i = 1;
        $j = 0;
        $stmt = $this->dbh->prepare($sql);
        $stmt->bindParam($i++, $param[$j++], PDO::PARAM_STR);
        $stmt->bindParam($i++, $param[$j++], PDO::PARAM_STR);
        $stmt->bindParam($i++, $param[$j++], PDO::PARAM_STR);
        $stmt->bindParam($i++, $param[$j++], PDO::PARAM_STR);
        $stmt->bindParam($i++, $param[$j++], PDO::PARAM_INT);
        $stmt->execute();
    }

    /** timecard_restsをupdateする */
    public function updateRests($sql, array $param){
        $i = 1;
        $j = 0;
        $stmt = $this->dbh->prepare($sql);
        $stmt->bindParam($i++, $param[$j++], PDO::PARAM_STR);
        $stmt->bindParam($i++, $param[$j++], PDO::PARAM_INT);
        $stmt->execute($param);
    }
}