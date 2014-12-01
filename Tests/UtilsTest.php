<?php
ob_start();
$tests = true;
require_once("UserSystem/config.php");
date_default_timezone_set('America/Denver');

class UtilsTest extends PHPUnit_Framework_TestCase {
  public function testDefaultConstruct() {
    $a = new UserSystem("");
    $this->assertObjectHasAttribute("DATABASE", $a);
  }

  public function testCurrentURL() {
    $a = new UserSystem();
    $_SERVER['HTTP_HOST'] = "test";
    $_SERVER['REQUEST_URI'] = "php";
    $b = $a->currentURL();
    $this->assertEquals("http://testphp", $b);
  }

  public function testDefaultRedirect301() {
    $a = new UserSystem();
    $b = $a->redirect301("localhost");
    if ($b) {
      $b = 1;
    }
    if (!$b) {
      $b = 0;
    }
    $this->assertLessThan(2, $b);
  }

  public function testEncryption() {
    $a = new UserSystem();
    $b = $a->encrypt("cake", "dessert");
    $this->assertNotEquals("cake", $b);

    $a = new UserSystem();
    $b = $a->encrypt("cake", "dessert");
    $c = $a->decrypt($b, "dessert");
    $this->assertEquals("cake", $c);
  }

  public function testSanitize() {
    $a = new UserSystem();

    $t = $a->sanitize("123g", "n");
    $this->assertEquals(123, $t);

    $t = $a->sanitize("g", "n");
    $this->assertEquals(0, $t);

    $t = $a->sanitize("g", "s");
    $this->assertEquals("g", $t);

    $t = $a->sanitize("g'°", "s");
    $this->assertEquals("g&#39;&deg;", $t);

    $t = $a->sanitize(1414035554, "d");
    $this->assertEquals(1414035554, $t);

    $t = $a->sanitize("1414;035554", "d");
    $this->assertEquals(1414035554, $t);

    $t = $a->sanitize("2014-10-21", "d");
    $this->assertEquals(1413871200, $t);

    $t = $a->sanitize("+1 week 2 days 4 hours 2 seconds", "d");
    $this->assertEquals(strtotime("+1 week 2 days 4 hours 2 seconds"), $t);

    $t = $a->sanitize("next Thursday", "d");
    $this->assertEquals(strtotime("next Thursday"), $t);

    $t = $a->sanitize("<span>cake</span>", "h");
    $this->assertEquals("<span>cake</span>", $t);

    $t = $a->sanitize("g'°", "h");
    $this->assertEquals("g'&deg;", $t);
  }

  public function testDB() {
    $a = new UserSystem("");
    $a->DATABASE->query("CREATE DATABASE test");
    $a = new UserSystem("test");
    $a->DATABASE->query("
      CREATE TABLE `".DB_PREFACE."test1` (
        `id` INT(50) NOT NULL AUTO_INCREMENT,
        `test` VARCHAR(50) NULL DEFAULT NULL,
        PRIMARY KEY (`id`)
      )
      COLLATE='latin1_swedish_ci'
      ENGINE=MyISAM
      AUTO_INCREMENT=0;
    ");
    $a->DATABASE->query("INSERT INTO `".DB_PREFACE."test1` (test) VALUES ('cake')");
    $b = $a->dbSel(["test1", ["test"=>"cake"]]);
    $this->assertEquals(1, $b[0]);
    $this->assertEquals(1, $b[1]['id']);
    $this->assertEquals("cake", $b[1]['test']);

    $a->dbMod(["u", "test1", ["id"=>1], ["test"=>"pie"]]);
    $b = $a->dbSel(["test1", ["id"=>1]]);
    $this->assertEquals(1, $b[0]);
    $this->assertEquals(1, $b[1]['id']);
    $this->assertEquals("cake", $b[1]['test']);

    $a->dbMod(["d", "test1", ["id"=>1]]);
    $b = $a->dbSel(["test1", ["id"=>1]]);
    $this->assertEquals(0, $b[0]);

    $b = $a->dbSel(["test1", ["id"=>1]]);
    $this->assertEquals(0, $b[0]);

    $b = $a->numRows("test1");
    $this->assertEquals(0, $b);
  }
}
