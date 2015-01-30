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

  public function testCreateSalt() {
    $a = new UserSystem("");
    $b = $a->createSalt("bob");
    $this->assertEquals(128, strlen($b));
  }

  public function testSession() {
    $a = new UserSystem("");
    $a->DATABASE->query("CREATE DATABASE ".DB_DATABASE);
    $a = new UserSystem();
    $a->DATABASE->query("
      CREATE TABLE `".DB_PREFACE."users` (
      `id` INT NOT NULL AUTO_INCREMENT,
      `username` VARCHAR(50) NULL DEFAULT NULL,
      PRIMARY KEY (`id`)
      )
      COLLATE='latin1_swedish_ci'
      ENGINE=MyISAM
      AUTO_INCREMENT=0;
      ");
      $a->DATABASE->query("
      INSERT INTO `".DB_PREFACE."users` (username) VALUES ('cake')
    ");
    $b = $a->session("cake")['username'];
    $this->assertEquals("cake", $b);

    $a->DATABASE->query("
      CREATE TABLE `".DB_PREFACE."userblobs` (
      `id` INT NOT NULL AUTO_INCREMENT,
      `user` VARCHAR(50) NOT NULL,
      `code` VARCHAR(512) NOT NULL,
      `ip` VARCHAR(256) NOT NULL,
      `action` VARCHAR(100) NOT NULL,
      `date` INT NOT NULL,
      PRIMARY KEY (`id`)
      )
      COLLATE='latin1_swedish_ci'
      ENGINE=MyISAM
      AUTO_INCREMENT=0;
    ");
    $a->DATABASE->query("
      INSERT INTO `".DB_PREFACE."userblobs`
      (user, code, ip, action, date) VALUES
      ('cake', 'pie', '127.0.0.1', 'session', '1414169627')
    ");
    $_COOKIE['examplecom'] = "pie";
    $b = $a->session()["username"];
    $this->assertEquals("cake", $b);
    $a->DATABASE->query("DROP DATABASE ".DB_DATABASE);
  }

  public function testCurrentURL() {
    $a = new UserSystem("");
    $_SERVER['HTTP_HOST'] = "test";
    $_SERVER['REQUEST_URI'] = "php";
    $b = $a->currentURL();
    $this->assertEquals("http://testphp", $b);
  }

  public function testDefaultRedirect301() {
    $a = new UserSystem("");
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
    $a = new UserSystem("");
    $b = $a->encrypt("cake", "dessert");
    $this->assertNotEquals("cake", $b);

    $a = new UserSystem("");
    $b = $a->encrypt("cake", "dessert");
    $c = $a->decrypt($b, "dessert");
    $this->assertEquals("cake", $c);
  }

  public function testSanitize() {
    $a = new UserSystem("");

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
}
