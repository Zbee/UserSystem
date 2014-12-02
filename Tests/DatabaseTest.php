<?php
ob_start();
$tests = true;
require_once("UserSystem/config.php");
date_default_timezone_set('America/Denver');

class DatabaseTest extends PHPUnit_Framework_TestCase {
  public function testDB() {
    $a = new UserSystem("");
    $a->DATABASE->query("CREATE DATABASE test");
    $a = new UserSystem("test");
    $a->DATABASE->query("
    CREATE TABLE `".DB_PREFACE."test` (
    `id` INT(50) NOT NULL AUTO_INCREMENT,
    `test` VARCHAR(50) NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
    )
    COLLATE='latin1_swedish_ci'
    ENGINE=MyISAM
    AUTO_INCREMENT=0;
    ");
    $a->DATABASE->query("INSERT INTO `".DB_PREFACE."test` (test) VALUES ('cake')");
    $b = $a->dbSel(["test", ["id"=>1]]);
    $this->assertEquals(1, $b[0]);
    $this->assertEquals(1, $b[1]['id']);
    $this->assertEquals("cake", $b[1]['test']);

    $a->dbUpd(["test", ["test"=>"pie"], ["test"=>"cake"]]);
    $b = $a->dbSel(["test", ["id"=>1]]);
    $this->assertEquals(1, $b[0]);
    $this->assertEquals(1, $b[1]['id']);
    $this->assertEquals("pie", $b[1]['test']);

    $a->dbDel(["test", ["id"=>1]]);
    $b = $a->dbSel(["test1", ["id"=>1]]);
    $this->assertEquals(0, $b[0]);

    $b = $a->dbSel(["test", ["id"=>1]]);
    $this->assertEquals(0, $b[0]);

    $b = $a->numRows("test");
    $this->assertEquals(0, $b);
    $a->DATABASE->query("DROP DATABASE test");
  }
}
