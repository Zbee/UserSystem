<?php
ob_start();
$tests = true;
require_once("UserSystem/config.php");
date_default_timezone_set('America/Denver');

class UserSystemTest extends PHPUnit_Framework_TestCase {
  public function testCheckBan() {
    $a = new UserSystem("");
    $a->DATABASE->query("CREATE DATABASE ".DB_DATABASE);
    $a = new UserSystem();
    $a->DATABASE->query("
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
    $a->DATABASE->query("
      INSERT INTO `".DB_PREFACE."ban`
      (username, issuer, ip, date, reason, appealed) VALUES
      ('cake', 'pie', '127.0.0.1', '".(time() - 86400)."', 'Because', 0)
    ");
    $b = $a->checkBan("127.0.0.1");
    $this->assertTrue($b);
    $b = $a->checkBan("127.0.0.1", "cake");
    $this->assertTrue($b);
    $a->DATABASE->query("DROP DATABASE ".DB_DATABASE);
  }

  public function testVerifySession() {
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
        AUTO_INCREMENT=0;");
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
      $_SERVER['REMOTE_ADDR'] = "127.0.0.1";
      $_COOKIE['examplecom'] = $a->insertUserBlob("cake");
      $b = $a->verifySession();
      $this->assertTrue($b);
      $a->DATABASE->query("DROP DATABASE ".DB_DATABASE);
  }

  public function testActivateUser() {
      $a = new UserSystem("");
      $a->DATABASE->query("CREATE DATABASE ".DB_DATABASE);
      $a = new UserSystem();
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
        CREATE TABLE `".DB_PREFACE."users` (
        	`id` INT NOT NULL AUTO_INCREMENT,
        	`username` VARCHAR(50) NOT NULL,
        	`activated` INT(1) NOT NULL DEFAULT '0',
        	PRIMARY KEY (`id`)
        )
        COLLATE='latin1_swedish_ci'
        ENGINE=MyISAM
        AUTO_INCREMENT=0;
      ");
      $a->DATABASE->query("
        INSERT INTO `".DB_PREFACE."users` (user, activated) VALUES ('cake', 0)
      ");
      $_SERVER['REMOTE_ADDR'] = "127.0.0.1";
      $b = $a->insertUserBlob("cake", "activate");
      $c = $a->activateUser($b);
      $this->assertTrue($c);
      $a->DATABASE->query("DROP DATABASE ".DB_DATABASE);
  }

  public function testLogIn() {
      $a = new UserSystem("");
      $a->DATABASE->query("CREATE DATABASE ".DB_DATABASE);
      $a = new UserSystem();
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
      $a->DATABASE->query("
        CREATE TABLE `".DB_PREFACE."users` (
          `id` INT NOT NULL AUTO_INCREMENT,
          `username` VARCHAR(50) NOT NULL,
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
      $a->DATABASE->query("
        INSERT INTO `".DB_PREFACE."users`
        (username, password, activated) VALUES
        ('cake', '".hash("sha256", "pie")."', 1)
      ");
      $_SERVER['REMOTE_ADDR'] = "127.0.0.1";
      $b = $a->logIn("cake", "pie");
      $this->assertTrue($b);
      $a->DATABASE->query("DROP DATABASE ".DB_DATABASE);
  }

  public function testTwoStep() {
    $a = new UserSystem("");
    $a->DATABASE->query("CREATE DATABASE ".DB_DATABASE);
    $a = new UserSystem();
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
    $a->DATABASE->query("
      CREATE TABLE `".DB_PREFACE."users` (
        `id` INT(255) NOT NULL AUTO_INCREMENT,
        `username` VARCHAR(50) NOT NULL,
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
    $a->DATABASE->query("
      INSERT INTO `".DB_PREFACE."users`
      (username, password, activated) VALUES
      ('cake', '".hash("sha256", "pie")."', 1)
    ");
    $_SERVER['REMOTE_ADDR'] = "127.0.0.1";
    $b = $a->insertUserBlob("cake", "twoStep");
    $c = $a->twoStep($b);
    $this->assertEquals(0, $a->dbSel(["userblobs", ["code"=>$b]])[0]);
    $this->assertTrue($c);
    $a->DATABASE->query("DROP DATABASE ".DB_DATABASE);
  }

  public function testSendRecover() {
    $a = new UserSystem("");
    $a->DATABASE->query("CREATE DATABASE ".DB_DATABASE);
    $a = new UserSystem();
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
    $a->DATABASE->query("
      INSERT INTO `".DB_PREFACE."users`
      (username, password, email) VALUES
      ('cake', '".hash("sha256", "pie")."', 'example@pie.com')
    ");
    $_SERVER['REMOTE_ADDR'] = "127.0.0.1";
    $b = $a->sendRecover("example@pie.com");
    $this->assertEquals(1, $a->dbSel(["userblobs", ["action"=>"recover"]])[0]);
    $a->DATABASE->query("DROP DATABASE ".DB_DATABASE);
  }
}
