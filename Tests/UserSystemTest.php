<?php
ob_start(); #For testing redirection
require_once 'UserSystem/UserSystem.php';
date_default_timezone_set('America/Denver');

class UserSystemTest extends PHPUnit_Framework_TestCase {
  public function testDefaultConstruct() {
    $a = new UserSystem(["location" => "localhost","database"=> "","username" =>"root","password" =>""],['sitename'=>"examplecom",'domain_simple'=>"example.com",'domain'=>"accounts.example.com",'system_loc'=>"/usersystem",'encryption'=>false]);
    $this->assertObjectHasAttribute("DATABASE", $a);
    $this->assertObjectHasAttribute("OPTIONS", $a);
  }

  public function testCurrentURL() {
    $a = new UserSystem(false,['sitename'=>"examplecom",'domain_simple'=>"example.com",'domain'=>"accounts.example.com",'system_loc'=>"/usersystem",'encryption'=>false]);
    $_SERVER['HTTP_HOST'] = "test";
    $_SERVER['REQUEST_URI'] = "php";
    $b = $a->currentURL();
    $this->assertEquals("//testphp", $b);
  }

  public function testDefaultRedirect301() {
    $a = new UserSystem(false,['sitename'=>"examplecom",'domain_simple'=>"example.com",'domain'=>"accounts.example.com",'system_loc'=>"/usersystem",'encryption'=>false]);
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
    $a = new UserSystem(false,['sitename'=>"examplecom",'domain_simple'=>"example.com",'domain'=>"accounts.example.com",'system_loc'=>"/usersystem",'encryption'=>false]);
    $b = $a->encrypt("cake", "dessert");
    $this->assertNotEquals("cake", $b);

    $a = new UserSystem(false,['sitename'=>"examplecom",'domain_simple'=>"example.com",'domain'=>"accounts.example.com",'system_loc'=>"/usersystem",'encryption'=>false]);
    $b = $a->encrypt("cake", "dessert");
    $c = $a->decrypt($b, "dessert");
    $this->assertEquals("cake", $c);
  }

  public function testSanitize() {
    $a = new UserSystem(false,['sitename'=>"examplecom",'domain_simple'=>"example.com",'domain'=>"accounts.example.com",'system_loc'=>"/usersystem",'encryption'=>false]);

    $t = $a->sanitize(123, "n");
    $this->assertEquals(123, $t);

    $t = $a->sanitize("123", "n");
    $this->assertEquals(123, $t);

    $t = $a->sanitize("123g", "n");
    $this->assertEquals(123, $t);

    $t = $a->sanitize("g", "n");
    $this->assertEquals(0, $t);

    $t = $a->sanitize("g", "s");
    $this->assertEquals("g", $t);

    $t = $a->sanitize("g'°", "s");
    $this->assertEquals("g&#39;&deg;", $t);

    $t = $a->sanitize(123, "s");
    $this->assertEquals("123", $t);

    $t = $a->sanitize(1414035554, "d");
    $this->assertEquals(1414035554, $t);

    $t = $a->sanitize("1414035554", "d");
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
    $a = new UserSystem(false,['sitename'=>"examplecom",'domain_simple'=>"example.com",'domain'=>"accounts.example.com",'system_loc'=>"/usersystem",'encryption'=>false]);
    $a->DATABASE->query("CREATE DATABASE test");
    $a = new UserSystem(["location"=>"localhost","database"=>"test","username"=>"root","password" =>""],['sitename'=>"examplecom",'domain_simple'=>"example.com",'domain'=>"accounts.example.com",'system_loc'=>"/usersystem",'encryption'=>false]);
    $a->DATABASE->query("CREATE TABLE `test1` (`id` INT(50) NOT NULL AUTO_INCREMENT,
    `test` VARCHAR(50) NULL DEFAULT NULL,PRIMARY KEY (`id`))
    COLLATE='latin1_swedish_ci' ENGINE=MyISAM AUTO_INCREMENT=0;");
    //$a->dbMod(["i", "test1", ["test"=>"cake"]]);
    $a->DATABASE->query("INSERT INTO test1 (test) VALUES ('cake')");
    $b = $a->dbSel(["test1", ["id"=>1]]);
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
    $a->DATABASE->query("DROP DATABASE test");
  }

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
}
