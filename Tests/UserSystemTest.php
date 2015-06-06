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

class UserSystemTest extends PHPUnit_Framework_TestCase {

  public function testVerifySession() {
      $user = new UserSystem("");
      $user->DATABASE->query("CREATE DATABASE ".DB_DATABASE);
      $user = new UserSystem();
      $user->DATABASE->query("
        CREATE TABLE `".DB_PREFACE."users` (
          `id` INT NOT NULL AUTO_INCREMENT,
          `username` VARCHAR(50) NULL DEFAULT NULL,
          `salt` VARCHAR(50) NULL DEFAULT NULL,
          PRIMARY KEY (`id`)
        )
        COLLATE='latin1_swedish_ci'
        ENGINE=MyISAM
        AUTO_INCREMENT=0;");
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
      $user->dbIns(["users", ["username" => "cake", "salt" => "1234"]]);
      $_COOKIE['examplecom'] = $user->insertUserBlob(1);
      $test = $user->verifySession();
      $this->assertTrue($test);
      $user->DATABASE->query("DROP DATABASE ".DB_DATABASE);
  }

  public function testActivateUser() {
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
        	`salt` VARCHAR(50) NOT NULL,
        	`activated` INT(1) NOT NULL DEFAULT '0',
        	PRIMARY KEY (`id`)
        )
        COLLATE='latin1_swedish_ci'
        ENGINE=MyISAM
        AUTO_INCREMENT=0;
      ");
      $user->dbIns(["users", ["username"=>"cake","salt"=>"c","activated"=>0]]);
      $test = $user->insertUserBlob(1, "activate");
      $testdos = $user->activateUser($test);
      $this->assertTrue($testdos);
      $user->DATABASE->query("DROP DATABASE ".DB_DATABASE);
  }

  public function testLogIn() {
      $user = new UserSystem("");
      $user->DATABASE->query("CREATE DATABASE ".DB_DATABASE);
      $user = new UserSystem();
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
      $user->DATABASE->query("
        CREATE TABLE `".DB_PREFACE."ban` (
          `date` INT NULL DEFAULT NULL,
          `ip` VARCHAR(256) NULL DEFAULT NULL,
          `username` VARCHAR(50) NULL DEFAULT NULL,
          `appealed` INT(1) NOT NULL DEFAULT '0',
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
          `password` VARCHAR(100) NOT NULL,
          `oldPassword` VARCHAR(100) NOT NULL,
          `salt` VARCHAR(512) NOT NULL,
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
      $test = $user->dbIns(
        [
          "users",
          [
            "username" => "cake",
            "password" => hash("sha256", "pie"),
            "activated" => 1,
            "salt" => ""
          ]
        ]
      );
      $test = $user->logIn("cake", "pie");
      $this->assertTrue($test);
      $user->DATABASE->query("DROP DATABASE ".DB_DATABASE);
  }

  public function testTwoStep() {
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
      CREATE TABLE `".DB_PREFACE."ban` (
      `date` INT NULL DEFAULT NULL,
      `ip` VARCHAR(256) NULL DEFAULT NULL,
      `username` VARCHAR(50) NULL DEFAULT NULL,
      `appealed` INT(1) NOT NULL DEFAULT '0',
      PRIMARY KEY (`id`)
      )
      COLLATE='latin1_swedish_ci'
      ENGINE=MyISAM
      AUTO_INCREMENT=0;
    ");
    $user->DATABASE->query("
      CREATE TABLE `".DB_PREFACE."users` (
        `id` INT(255) NOT NULL AUTO_INCREMENT,
        `username` VARCHAR(50) NOT NULL,
        `password` VARCHAR(100) NOT NULL,
        `oldPassword` VARCHAR(100) NOT NULL,
        `salt` VARCHAR(512) NOT NULL,
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
    $user->dbIns(
      [
        "users",
        [
          "username" => "cake",
          "password" => hash("sha256", "pie"),
          "activated" => 1
        ]
      ]
    );
    $test = $user->insertUserBlob(1, "twoStep");
    $testdos = $user->twoStep($test);
    $this->assertEquals(0, $user->dbSel(["userblobs", ["code"=>$test]])[0]);
    $this->assertTrue($testdos);
    $user->DATABASE->query("DROP DATABASE ".DB_DATABASE);
  }
}
