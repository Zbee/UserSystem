<?php
ob_start();
require_once("UserSystem/Utils.php");
require_once("UserSystem/UserSystem.php");
date_default_timezone_set('America/Denver');

class UserSystemTest extends PHPUnit_Framework_TestCase {
  public function testSession() {
    $a = new UserSystem(false,['sitename'=>"examplecom",'domain_simple'=>"example.com",'domain'=>"accounts.example.com",'system_loc'=>"/usersystem",'encryption'=>false]);
    $a->DATABASE->query("CREATE DATABASE test");
    $a = new UserSystem(["location"=>"localhost","database"=>"test","username"=>"root","password" =>""],['sitename'=>"examplecom",'domain_simple'=>"example.com",'domain'=>"accounts.example.com",'system_loc'=>"/usersystem",'encryption'=>false]);
    $a->DATABASE->query("CREATE TABLE `users` (`id` INT(50) NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NULL DEFAULT NULL,PRIMARY KEY (`id`))
    COLLATE='latin1_swedish_ci' ENGINE=MyISAM AUTO_INCREMENT=0;");
    $a->DATABASE->query("INSERT INTO users (username) VALUES ('cake')");
    $b = $a->session("cake")['username'];
    $this->assertEquals("cake", $b);
    $a->DATABASE->query("
      CREATE TABLE `userblobs` (
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
    $a->DATABASE->query("INSERT INTO userblobs (user, code, ip, action, date) VALUES ('cake', 'pie', '127.0.0.1', 'session', '1414169627')");
    $_COOKIE['examplecom'] = "pie";
    $b = $a->session()["username"];
    $this->assertEquals("cake", $b);
    $a->DATABASE->query("DROP DATABASE test");
  }

  public function testInsertUserBlob() {
    $a = new UserSystem(false,['sitename'=>"examplecom",'domain_simple'=>"example.com",'domain'=>"accounts.example.com",'system_loc'=>"/usersystem",'encryption'=>false]);
    $a->DATABASE->query("CREATE DATABASE test");
    $a = new UserSystem(["location"=>"localhost","database"=>"test","username"=>"root","password" =>""],['sitename'=>"examplecom",'domain_simple'=>"example.com",'domain'=>"accounts.example.com",'system_loc'=>"/usersystem",'encryption'=>false]);
    $a->DATABASE->query("CREATE TABLE `users` (`id` INT(50) NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NULL DEFAULT NULL,PRIMARY KEY (`id`))
    COLLATE='latin1_swedish_ci' ENGINE=MyISAM AUTO_INCREMENT=0;");
    $a->DATABASE->query("
      CREATE TABLE `userblobs` (
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
    $a->insertUserBlob("cake", "pie");
    $b = $a->dbSel(["userblobs", ["code"=>"pie"]]);
    $this->assertEquals(1, $b[0]);
    $this->assertEquals(1, $b[1]["id"]);
    $this->assertEquals("cake", $b[1]["user"]);
    $this->assertEquals("pie", $b[1]["code"]);
    $this->assertEquals("127.0.0.1", $b[1]["ip"]);
    $this->assertEquals("session", $b[1]["action"]);
    $a->DATABASE->query("DROP DATABASE test");
  }

  public function testCheckBan() {
    $a = new UserSystem(false,['sitename'=>"examplecom",'domain_simple'=>"example.com",'domain'=>"accounts.example.com",'system_loc'=>"/usersystem",'encryption'=>false]);
    $a->DATABASE->query("CREATE DATABASE test");
    $a = new UserSystem(["location"=>"localhost","database"=>"test","username"=>"root","password" =>""],['sitename'=>"examplecom",'domain_simple'=>"example.com",'domain'=>"accounts.example.com",'system_loc'=>"/usersystem",'encryption'=>false]);
    $a->DATABASE->query("
    CREATE TABLE `ban` (
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
    $a->DATABASE->query("INSERT INTO ban (username, issuer, ip, date, reason, appealed) VALUES ('cake', 'pie', '127.0.0.1', '1414169627', 'Because', 0)");
    $b = $a->checkBan($_SERVER['REMOTE_ADDR']);
    $this->assertTrue($b);
    $b = $a->checkBan($_SERVER['REMOTE_ADDR'], "cake");
    $this->assertTrue($b);
    $a->DATABASE->query("DROP DATABASE test");
  }

  public function testVerifySession() {
      $a = new UserSystem(false,['sitename'=>"examplecom",'domain_simple'=>"example.com",'domain'=>"accounts.example.com",'system_loc'=>"/usersystem",'encryption'=>false]);
      $a->DATABASE->query("CREATE DATABASE test");
      $a = new UserSystem(["location"=>"localhost","database"=>"test","username"=>"root","password" =>""],['sitename'=>"examplecom",'domain_simple'=>"example.com",'domain'=>"accounts.example.com",'system_loc'=>"/usersystem",'encryption'=>false]);
      $a->DATABASE->query("CREATE TABLE `users` (`id` INT(50) NOT NULL AUTO_INCREMENT,
      `username` VARCHAR(50) NULL DEFAULT NULL,PRIMARY KEY (`id`))
      COLLATE='latin1_swedish_ci' ENGINE=MyISAM AUTO_INCREMENT=0;");
      $a->DATABASE->query("
        CREATE TABLE `userblobs` (
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
      CREATE TABLE `ban` (
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
      $hash = hash("sha256", "cake".substr(str_shuffle(str_repeat("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@$%^&_+{}[]:<.>?", 17)), 1, 50));
      $hash = $hash.md5("cake".$hash);
      $_COOKIE['examplecom'] = $hash;
      $a->insertUserBlob("cake", $hash);
      $b = $a->verifySession();
      $this->assertTrue($b);
      $a->DATABASE->query("DROP DATABASE test");
  }

  public function testActivateUser() {
      $a = new UserSystem(false,['sitename'=>"examplecom",'domain_simple'=>"example.com",'domain'=>"accounts.example.com",'system_loc'=>"/usersystem",'encryption'=>false]);
      $a->DATABASE->query("CREATE DATABASE test");
      $a = new UserSystem(["location"=>"localhost","database"=>"test","username"=>"root","password" =>""],['sitename'=>"examplecom",'domain_simple'=>"example.com",'domain'=>"accounts.example.com",'system_loc'=>"/usersystem",'encryption'=>false]);
      $a->DATABASE->query("
        CREATE TABLE `userblobs` (
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
        CREATE TABLE `users` (
        	`id` INT(255) NOT NULL AUTO_INCREMENT,
        	`username` VARCHAR(50) NOT NULL,
        	`oldusername` VARCHAR(50) NOT NULL,
        	`permusername` VARCHAR(50) NOT NULL,
        	`name_first` VARCHAR(256) NOT NULL,
        	`name_last` VARCHAR(256) NOT NULL,
        	`password` VARCHAR(100) NOT NULL,
        	`oldpassword` VARCHAR(100) NOT NULL,
        	`passwordchanged` VARCHAR(50) NOT NULL DEFAULT '0000000000',
        	`salt` VARCHAR(512) NOT NULL,
        	`oldsalt` VARCHAR(512) NOT NULL,
        	`email` VARCHAR(255) NOT NULL,
        	`oldemail` VARCHAR(512) NOT NULL,
        	`emailchanged` VARCHAR(64) NOT NULL DEFAULT '0000000000',
        	`firstemail` VARCHAR(512) NOT NULL,
        	`date_registered` VARCHAR(255) NOT NULL,
        	`activated` INT(1) NOT NULL DEFAULT '0',
        	`bio` VARCHAR(510) NOT NULL DEFAULT '',
        	`sig` VARCHAR(100) NOT NULL DEFAULT '',
        	`title` VARCHAR(50) NOT NULL DEFAULT '',
        	`rep` INT(10) NOT NULL DEFAULT '0',
        	`2step` INT(1) NOT NULL DEFAULT '0',
        	`last_logged_in` VARCHAR(50) NOT NULL DEFAULT '0000000000',
        	`old_last_logged_in` VARCHAR(50) NOT NULL DEFAULT '0000000000',
        	`site` VARCHAR(255) NOT NULL,
        	`ip` VARCHAR(64) NOT NULL DEFAULT '',
        	PRIMARY KEY (`id`)
        )
        COLLATE='latin1_swedish_ci'
        ENGINE=MyISAM
        AUTO_INCREMENT=0;
      ");
      $a->DATABASE->query(
        "INSERT INTO users (user, activated) VALUES ('cake', 0)"
      );
      $_SERVER['REMOTE_ADDR'] = "127.0.0.1";
      $a->insertUserBlob("cake", "pie", "activate");
      $b = $a->activateUser("pie");
      $this->assertTrue($b);
      $a->DATABASE->query("DROP DATABASE test");
  }

  public function testLogIn() {
      $a = new UserSystem(false,['sitename'=>"examplecom",'domain_simple'=>"example.com",'domain'=>"accounts.example.com",'system_loc'=>"/usersystem",'encryption'=>false]);
      $a->DATABASE->query("CREATE DATABASE test");
      $a = new UserSystem(["location"=>"localhost","database"=>"test","username"=>"root","password" =>""],['sitename'=>"examplecom",'domain_simple'=>"example.com",'domain'=>"accounts.example.com",'system_loc'=>"/usersystem",'encryption'=>false]);
      $a->DATABASE->query("
        CREATE TABLE `userblobs` (
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
      CREATE TABLE `ban` (
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
        CREATE TABLE `users` (
          `id` INT(255) NOT NULL AUTO_INCREMENT,
          `username` VARCHAR(50) NOT NULL,
          `oldusername` VARCHAR(50) NOT NULL,
          `permusername` VARCHAR(50) NOT NULL,
          `name_first` VARCHAR(256) NOT NULL,
          `name_last` VARCHAR(256) NOT NULL,
          `password` VARCHAR(100) NOT NULL,
          `oldpassword` VARCHAR(100) NOT NULL,
          `passwordchanged` VARCHAR(50) NOT NULL DEFAULT '0000000000',
          `salt` VARCHAR(512) NOT NULL,
          `oldsalt` VARCHAR(512) NOT NULL,
          `email` VARCHAR(255) NOT NULL,
          `oldemail` VARCHAR(512) NOT NULL,
          `emailchanged` VARCHAR(64) NOT NULL DEFAULT '0000000000',
          `firstemail` VARCHAR(512) NOT NULL,
          `date_registered` VARCHAR(255) NOT NULL,
          `activated` INT(1) NOT NULL DEFAULT '0',
          `bio` VARCHAR(510) NOT NULL DEFAULT '',
          `sig` VARCHAR(100) NOT NULL DEFAULT '',
          `title` VARCHAR(50) NOT NULL DEFAULT '',
          `rep` INT(10) NOT NULL DEFAULT '0',
          `2step` INT(1) NOT NULL DEFAULT '0',
          `last_logged_in` VARCHAR(50) NOT NULL DEFAULT '0000000000',
          `old_last_logged_in` VARCHAR(50) NOT NULL DEFAULT '0000000000',
          `site` VARCHAR(255) NOT NULL,
          `ip` VARCHAR(64) NOT NULL DEFAULT '',
          PRIMARY KEY (`id`)
        )
        COLLATE='latin1_swedish_ci'
        ENGINE=MyISAM
        AUTO_INCREMENT=0;
      ");
      $a->DATABASE->query(
        "INSERT INTO users (username, password, activated) VALUES (
              'cake', '".hash("sha256", "pie")."', 1
            )"
      );
      $_SERVER['REMOTE_ADDR'] = "127.0.0.1";
      $b = $a->logIn("cake", "pie");
      $this->assertTrue($b);
      $a->DATABASE->query("DROP DATABASE test");
  }
}
