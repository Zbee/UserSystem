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

class UserUtilsTest extends PHPUnit_Framework_TestCase {

  public function testInsertUserBlob() {
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
      CREATE TABLE `".DB_PREFACE."userblobs` (
      `id` INT NOT NULL AUTO_INCREMENT,
      `user` VARCHAR(50) NOT NULL,
      `code` VARCHAR(512) NOT NULL,
      `action` VARCHAR(100) NOT NULL,
      `date` INT NOT NULL,
      PRIMARY KEY (`id`)
      )
      COLLATE='latin1_swedish_ci'
      ENGINE=MyISAM
      AUTO_INCREMENT=0;
    ");
    $test = $user->insertUserBlob("cake");
    $testdos = $user->dbSel(["userblobs", ["user"=>"cake"]]);
    $this->assertEquals(1, $testdos[0]);
    $this->assertEquals(1, $testdos[1]["id"]);
    $this->assertEquals("cake", $testdos[1]["user"]);
    $this->assertEquals($test, $testdos[1]["code"]);
    $this->assertEquals(160, strlen($testdos[1]["code"]));
    $this->assertEquals("session", $testdos[1]["action"]);
    $user->DATABASE->query("DROP DATABASE ".DB_DATABASE);
  }

  public function testCheckBan() {
    $user = new UserSystem("");
    $user->DATABASE->query("CREATE DATABASE ".DB_DATABASE);
    $user = new UserSystem();
    $user->DATABASE->query("
      CREATE TABLE `".DB_PREFACE."ban` (
      	`id` INT NOT NULL AUTO_INCREMENT,
      	`date` INT NULL DEFAULT NULL,
      	`ip` VARCHAR(50) NULL DEFAULT NULL,
      	`username` VARCHAR(50) NULL DEFAULT NULL,
      	`issuer` VARCHAR(50) NOT NULL DEFAULT 'No issuer provided.',
      	`reason` VARCHAR(512) NOT NULL DEFAULT 'No reason provided.',
      	`appealed` INT(1) NOT NULL DEFAULT '0',
      	PRIMARY KEY (`id`)
      )
      COLLATE='latin1_swedish_ci'
      ENGINE=MyISAM
      AUTO_INCREMENT=0;
    ");
    $user->DATABASE->query("
      INSERT INTO `".DB_PREFACE."ban`
      (username, issuer, ip, date, reason, appealed) VALUES
      ('cake', 'pie', '".$user->getIP()."', '".(time() - 86400)."', 'Cuz', 0)
    ");
    $test = $user->checkBan("cake");
    $this->assertTrue($test);
    $user->DATABASE->query("DROP DATABASE ".DB_DATABASE);
  }

  public function testSendRecover() {
    $user = new UserSystem("");
    $user->DATABASE->query("CREATE DATABASE ".DB_DATABASE);
    $user = new UserSystem();
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
      CREATE TABLE `".DB_PREFACE."users` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `username` VARCHAR(50) NOT NULL,
        `email` VARCHAR(512) NOT NULL,
        `password` VARCHAR(100) NOT NULL,
        `oldPassword` VARCHAR(100) NOT NULL,
        `salt` VARCHAR(512) NOT NULL,
        `oldSalt` VARCHAR(512) NOT NULL,
        `activated` INT(1) NOT NULL DEFAULT '0',
        `twoStep` INT(1) NOT NULL DEFAULT '0',
        `lastLoggedIn` VARCHAR(50) NOT NULL DEFAULT '0000000000',
        `oldLastLoggedIn` VARCHAR(50) NOT NULL DEFAULT '0000000000',
        `ip` VARCHAR(256) NOT NULL DEFAULT '',
        PRIMARY KEY (`id`)
      )
      COLLATE='latin1_swedish_ci'
      ENGINE=MyISAM
      AUTO_INCREMENT=0;
    ");
    $user->DATABASE->query("
      INSERT INTO `".DB_PREFACE."users`
      (username, password, email) VALUES
      ('cake', '".hash("sha256", "pie")."', 'example@pie.com')
    ");
    $_SERVER['REMOTE_ADDR'] = "127.0.0.1";
    $user->sendRecover("example@pie.com");
    $this->assertEquals(1, $user->dbSel(["userblobs", ["action"=>"recover"]])[0]);
    $user->DATABASE->query("DROP DATABASE ".DB_DATABASE);
  }

}