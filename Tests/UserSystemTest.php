<?php
ob_start();
$tests = true;
require_once("UserSystem/config.php");
date_default_timezone_set('America/Denver');

class UserSystemTest extends PHPUnit_Framework_TestCase {
  public function testSession() {
    $a = new UserSystem("");
    $a->DATABASE->query("CREATE DATABASE test");
    $a = new UserSystem("test");
    $a->DATABASE->query("
      CREATE TABLE `".DB_PREFACE."users` (
        `id` INT(50) NOT NULL AUTO_INCREMENT,
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
        `id` INT(5) NOT NULL AUTO_INCREMENT,
        `user` VARCHAR(100) NOT NULL,
        `code` VARCHAR(256) NOT NULL,
        `ip` VARCHAR(256) NOT NULL,
        `action` VARCHAR(100) NOT NULL,
        `date` VARCHAR(50) NOT NULL,
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
    $a->DATABASE->query("DROP DATABASE test");
  }

  public function testInsertUserBlob() {
    $a = new UserSystem("");
    $a->DATABASE->query("CREATE DATABASE test");
    $a = new UserSystem("test");
    $a->DATABASE->query("
      CREATE TABLE `".DB_PREFACE."users` (
        `id` INT(50) NOT NULL AUTO_INCREMENT,
        `username` VARCHAR(50) NULL DEFAULT NULL,
        PRIMARY KEY (`id`)
      )
      COLLATE='latin1_swedish_ci'
      ENGINE=MyISAM
      AUTO_INCREMENT=0;
    ");
    $a->DATABASE->query("
      CREATE TABLE `".DB_PREFACE."userblobs` (
        `id` INT(5) NOT NULL AUTO_INCREMENT,
        `user` VARCHAR(100) NOT NULL,
        `code` VARCHAR(256) NOT NULL,
        `ip` VARCHAR(256) NOT NULL,
        `action` VARCHAR(100) NOT NULL,
        `date` VARCHAR(50) NOT NULL,
        PRIMARY KEY (`id`)
      )
      COLLATE='latin1_swedish_ci'
      ENGINE=MyISAM
      AUTO_INCREMENT=0;
    ");
    $_SERVER['REMOTE_ADDR'] = "127.0.0.1";
    $b = $a->insertUserBlob("cake");
    $c = $a->dbSel(["userblobs", ["user"=>"cake"]]);
    $this->assertEquals(1, $c[0]);
    $this->assertEquals(1, $c[1]["id"]);
    $this->assertEquals("cake", $c[1]["user"]);
    $this->assertEquals($b, $c[1]["code"]);
    $this->assertEquals(160, strlen($c[1]["code"]));
    $this->assertEquals("127.0.0.1", $c[1]["ip"]);
    $this->assertEquals("session", $c[1]["action"]);
    $a->DATABASE->query("DROP DATABASE test");
  }

  public function testCheckBan() {
    $a = new UserSystem("");
    $a->DATABASE->query("CREATE DATABASE test");
    $a = new UserSystem("test");
    $a->DATABASE->query("
      CREATE TABLE `".DB_PREFACE."ban` (
      	`id` INT(50) NOT NULL AUTO_INCREMENT,
      	`date` VARCHAR(50) NULL DEFAULT NULL,
      	`ip` VARCHAR(50) NULL DEFAULT NULL,
      	`username` VARCHAR(50) NULL DEFAULT NULL,
      	`issuer` VARCHAR(50) NOT NULL DEFAULT 'No issuer provided.',
      	`reason` VARCHAR(512) NOT NULL DEFAULT 'No reason provided.',
      	`appealed` VARCHAR(50) NOT NULL DEFAULT '0',
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
    $a->DATABASE->query("DROP DATABASE test");
  }

  public function testVerifySession() {
      $a = new UserSystem("");
      $a->DATABASE->query("CREATE DATABASE test");
      $a = new UserSystem("test");
      $a->DATABASE->query("
        CREATE TABLE `".DB_PREFACE."users` (
          `id` INT(50) NOT NULL AUTO_INCREMENT,
          `username` VARCHAR(50) NULL DEFAULT NULL,
          PRIMARY KEY (`id`)
        )
        COLLATE='latin1_swedish_ci'
        ENGINE=MyISAM
        AUTO_INCREMENT=0;");
      $a->DATABASE->query("
        CREATE TABLE `".DB_PREFACE."userblobs` (
          `id` INT(5) NOT NULL AUTO_INCREMENT,
          `user` VARCHAR(100) NOT NULL,
          `code` VARCHAR(256) NOT NULL,
          `ip` VARCHAR(256) NOT NULL,
          `action` VARCHAR(100) NOT NULL,
          `date` VARCHAR(50) NOT NULL,
          PRIMARY KEY (`id`)
        )
        COLLATE='latin1_swedish_ci'
        ENGINE=MyISAM
        AUTO_INCREMENT=0;
      ");
      $a->DATABASE->query("
        CREATE TABLE `".DB_PREFACE."ban` (
          `id` INT(50) NOT NULL AUTO_INCREMENT,
          `date` VARCHAR(50) NULL DEFAULT NULL,
          `ip` VARCHAR(50) NULL DEFAULT NULL,
          `username` VARCHAR(50) NULL DEFAULT NULL,
          `issuer` VARCHAR(50) NOT NULL DEFAULT 'No issuer provided.',
          `reason` VARCHAR(512) NOT NULL DEFAULT 'No reason provided.',
          `appealed` VARCHAR(50) NOT NULL DEFAULT '0',
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
      $a->DATABASE->query("DROP DATABASE test");
  }

  public function testActivateUser() {
      $a = new UserSystem("");
      $a->DATABASE->query("CREATE DATABASE test");
      $a = new UserSystem("test");
      $a->DATABASE->query("
        CREATE TABLE `".DB_PREFACE."userblobs` (
          `id` INT(5) NOT NULL AUTO_INCREMENT,
          `user` VARCHAR(100) NOT NULL,
          `code` VARCHAR(256) NOT NULL,
          `ip` VARCHAR(256) NOT NULL,
          `action` VARCHAR(100) NOT NULL,
          `date` VARCHAR(50) NOT NULL,
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
      $a->DATABASE->query("DROP DATABASE test");
  }

  public function testLogIn() {
      $a = new UserSystem("");
      $a->DATABASE->query("CREATE DATABASE test");
      $a = new UserSystem("test");
      $a->DATABASE->query("
        CREATE TABLE `".DB_PREFACE."userblobs` (
          `id` INT(5) NOT NULL AUTO_INCREMENT,
          `user` VARCHAR(100) NOT NULL,
          `code` VARCHAR(256) NOT NULL,
          `ip` VARCHAR(256) NOT NULL,
          `action` VARCHAR(100) NOT NULL,
          `date` VARCHAR(50) NOT NULL,
          PRIMARY KEY (`id`)
        )
        COLLATE='latin1_swedish_ci'
        ENGINE=MyISAM
        AUTO_INCREMENT=0;
      ");
      $a->DATABASE->query("
        CREATE TABLE `".DB_PREFACE."ban` (
          `date` VARCHAR(50) NULL DEFAULT NULL,
          `ip` VARCHAR(50) NULL DEFAULT NULL,
          `username` VARCHAR(50) NULL DEFAULT NULL,
          `appealed` VARCHAR(50) NOT NULL DEFAULT '0',
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
          `oldusername` VARCHAR(50) NOT NULL,
          `password` VARCHAR(100) NOT NULL,
          `oldpassword` VARCHAR(100) NOT NULL,
          `salt` VARCHAR(512) NOT NULL,
          `oldsalt` VARCHAR(512) NOT NULL,
          `activated` INT(1) NOT NULL DEFAULT '0',
          `2step` INT(1) NOT NULL DEFAULT '0',
          `last_logged_in` VARCHAR(50) NOT NULL DEFAULT '0000000000',
          `old_last_logged_in` VARCHAR(50) NOT NULL DEFAULT '0000000000',
          `ip` VARCHAR(64) NOT NULL DEFAULT '',
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
      $a->DATABASE->query("DROP DATABASE test");
  }

  public function testTwoStep() {
    $a = new UserSystem("");
    $a->DATABASE->query("CREATE DATABASE test");
    $a = new UserSystem("test");
    $a->DATABASE->query("
      CREATE TABLE `".DB_PREFACE."userblobs` (
      `id` INT(5) NOT NULL AUTO_INCREMENT,
      `user` VARCHAR(100) NOT NULL,
      `code` VARCHAR(256) NOT NULL,
      `ip` VARCHAR(256) NOT NULL,
      `action` VARCHAR(100) NOT NULL,
      `date` VARCHAR(50) NOT NULL,
      PRIMARY KEY (`id`)
      )
      COLLATE='latin1_swedish_ci'
      ENGINE=MyISAM
      AUTO_INCREMENT=0;
    ");
    $a->DATABASE->query("
      CREATE TABLE `".DB_PREFACE."ban` (
      `date` VARCHAR(50) NULL DEFAULT NULL,
      `ip` VARCHAR(50) NULL DEFAULT NULL,
      `username` VARCHAR(50) NULL DEFAULT NULL,
      `appealed` VARCHAR(50) NOT NULL DEFAULT '0',
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
      `oldusername` VARCHAR(50) NOT NULL,
      `password` VARCHAR(100) NOT NULL,
      `oldpassword` VARCHAR(100) NOT NULL,
      `salt` VARCHAR(512) NOT NULL,
      `oldsalt` VARCHAR(512) NOT NULL,
      `activated` INT(1) NOT NULL DEFAULT '0',
      `2step` INT(1) NOT NULL DEFAULT '0',
      `last_logged_in` VARCHAR(50) NOT NULL DEFAULT '0000000000',
      `old_last_logged_in` VARCHAR(50) NOT NULL DEFAULT '0000000000',
      `ip` VARCHAR(64) NOT NULL DEFAULT '',
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
    $b = $a->insertUserBlob("cake", "2step");
    $c = $a->twoStep($b);
    $this->assertEquals(0, $a->dbSel(["userblobs", ["code"=>$b]])[0]);
    $this->assertTrue($c);
    $a->DATABASE->query("DROP DATABASE test");
  }
}
