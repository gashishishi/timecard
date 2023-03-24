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
    static function getDbInstance() {
        return self::$dbInstance ?? self::$dbInstance = New DB;
    }
}