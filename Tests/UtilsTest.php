<?php
/*
This file is part of Zbee/UserSystem.

Zbee/UserSystem is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Zbee/UserSystem is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Zbee/UserSystem.  If not, see <http://www.gnu.org/licenses/>.
*/
ob_start();
$tests = true;
require_once("UserSystem/config.php");
date_default_timezone_set('America/Denver');

class UtilsTest extends PHPUnit_Framework_TestCase {
  public function testDefaultConstruct() {
    $user = new UserSystem("");
    $this->assertObjectHasAttribute("DATABASE", $user);
  }

  public function testCreateSalt() {
    $user = new UserSystem("");
    $test = $user->createSalt("bob");
    $this->assertEquals(128, strlen($test));
  }

  public function testSession() {
    $user = new UserSystem("");
    $user->DATABASE->query("CREATE DATABASE ".DB_DATABASE);
    $user = new UserSystem();
    $user->DATABASE->query("
      CREATE TABLE `".DB_PREFACE."users` (
      `id` INT NOT NULL AUTO_INCREMENT,
      `username` VARCHAR(50) NULL DEFAULT NULL,
      PRIMARY KEY (`id`)
      )
      COLLATE='latin1_swedish_ci'
      ENGINE=MyISAM
      AUTO_INCREMENT=0;
      ");
      $user->DATABASE->query("
      INSERT INTO `".DB_PREFACE."users` (username) VALUES ('cake')
    ");
    $test = $user->session("cake")['username'];
    $this->assertEquals("cake", $test);

    $user->DATABASE->query("
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
    $user->DATABASE->query("
      INSERT INTO `".DB_PREFACE."userblobs`
      (user, code, ip, action, date) VALUES
      ('cake', 'pie', '127.0.0.1', 'session', '1414169627')
    ");
    $_COOKIE['examplecom'] = "pie";
    $test = $user->session()["username"];
    $this->assertEquals("cake", $test);
    $user->DATABASE->query("DROP DATABASE ".DB_DATABASE);
  }

  public function testCurrentURL() {
    $user = new UserSystem("");
    $_SERVER['HTTP_HOST'] = "test";
    $_SERVER['REQUEST_URI'] = "php";
    $test = $user->currentURL();
    $this->assertEquals("http://testphp", $test);
  }

  public function testDefaultRedirect301() {
    $user = new UserSystem("");
    $test = $user->redirect301("localhost");
    if ($test) {
      $test = 1;
    }
    if (!$test) {
      $test = 0;
    }
    $this->assertLessThan(2, $test);
  }

  public function testEncryption() {
    $user = new UserSystem("");
    $test = $user->encrypt("cake", "dessert");
    $this->assertNotEquals("cake", $test);

    $user = new UserSystem("");
    $test = $user->encrypt("cake", "dessert");
    $testdos = $user->decrypt($test, "dessert");
    $this->assertEquals("cake", $testdos);
  }

  public function testSanitize() {
    $user = new UserSystem("");

    $test = $user->sanitize("123g", "n");
    $this->assertEquals(123, $test);

    $test = $user->sanitize("g", "n");
    $this->assertEquals(0, $test);

    $test = $user->sanitize("g", "s");
    $this->assertEquals("g", $test);

    $test = $user->sanitize("g'°", "s");
    $this->assertEquals("g&#39;&deg;", $test);

    $test = $user->sanitize(1414035554, "d");
    $this->assertEquals(1414035554, $test);

    $test = $user->sanitize("1414;035554", "d");
    $this->assertEquals(1414035554, $test);

    $test = $user->sanitize("2014-10-21", "d");
    $this->assertEquals(1413871200, $test);

    $test = $user->sanitize("+1 week 2 days 4 hours 2 seconds", "d");
    $this->assertEquals(strtotime("+1 week 2 days 4 hours 2 seconds"), $test);

    $test = $user->sanitize("next Thursday", "d");
    $this->assertEquals(strtotime("next Thursday"), $test);

    $test = $user->sanitize("<span>cake</span>", "h");
    $this->assertEquals("<span>cake</span>", $test);

    $test = $user->sanitize("g'°", "h");
    $this->assertEquals("g'&deg;", $test);
  }
}
