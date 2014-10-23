<?php
ob_start(); #For testing redirection
require_once 'UserSystem/UserSystem.php';
date_default_timezone_set('America/Denver');

class UserSystemTest extends PHPUnit_Framework_TestCase {
    public function testDefaultConstruct() {
        $a = new UserSystem(
          ["location" => "localhost","database" => "","username" => "root","password" => ""],
          ['sitename' => "examplecom", 'domain_simple' => "example.com", 'domain' => "accounts.example.com", 'system_loc'=> "/usersystem", 'encryption' => false]
        );
        $this->assertObjectHasAttribute("db", $a);
        $this->assertObjectHasAttribute("OPTIONS", $a);
    }

    public function testCurrentURL() {
        $a = new UserSystem(
          ["location" => "localhost","database" => "","username" => "root","password" => ""],
          ['sitename' => "examplecom", 'domain_simple' => "example.com", 'domain' => "accounts.example.com", 'system_loc'=> "/usersystem", 'encryption' => false]
        );
        $_SERVER['HTTP_HOST'] = "test";
        $_SERVER['REQUEST_URI'] = "php";
        $b = $a->currentURL();
        $this->assertEquals("//testphp", $b);
    }

    public function testDefaultRedirect301() {
        $a = new UserSystem(
          ["location" => "localhost","database" => "","username" => "root","password" => ""],
          ['sitename' => "examplecom", 'domain_simple' => "example.com", 'domain' => "accounts.example.com", 'system_loc'=> "/usersystem", 'encryption' => false]
        );
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
        $a = new UserSystem(
          ["location" => "localhost","database" => "","username" => "root","password" => ""],
          ['sitename' => "examplecom", 'domain_simple' => "example.com", 'domain' => "accounts.example.com", 'system_loc'=> "/usersystem", 'encryption' => false]
        );
        $b = $a->encrypt("cake", "dessert");
        $this->assertNotEquals("cake", $b);
    }

    public function testDecryption() {
        $a = new UserSystem(
          ["location" => "localhost","database" => "","username" => "root","password" => ""],
          ['sitename' => "examplecom", 'domain_simple' => "example.com", 'domain' => "accounts.example.com", 'system_loc'=> "/usersystem", 'encryption' => false]
        );
        $b = $a->encrypt("cake", "dessert");
        $c = $a->decrypt($b, "dessert");
        $this->assertEquals("cake", $c);
    }

    public function testSanitizeNumber() {
        $a = new UserSystem(
          ["location" => "localhost","database" => "","username" => "root","password" => ""],
          ['sitename' => "examplecom", 'domain_simple' => "example.com", 'domain' => "accounts.example.com", 'system_loc'=> "/usersystem", 'encryption' => false]
        );
        $b = $a->sanitize(123, ["t" => "n", "d" => false]);
        $this->assertEquals(123, $b);

        $c = $a->sanitize("123", ["t" => "n", "d" => false]);
        $this->assertEquals(123, $c);

        $d = $a->sanitize("123g", ["t" => "n", "d" => false]);
        $this->assertEquals(123, $d);

        $e = $a->sanitize("g", ["t" => "n", "d" => false]);
        $this->assertEquals(0, $e);
    }

    public function testSanitizeString() {
        $a = new UserSystem(
          ["location" => "localhost","database" => "","username" => "root","password" => ""],
          ['sitename' => "examplecom", 'domain_simple' => "example.com", 'domain' => "accounts.example.com", 'system_loc'=> "/usersystem", 'encryption' => false]
        );
        $b = $a->sanitize("g", ["t" => "s", "d" => false]);
        $this->assertEquals("g", $b);

        $c = $a->sanitize("g'°", ["t" => "s", "d" => false]);
        $this->assertEquals("g&#39;&deg;", $c);

        $d = $a->sanitize(123, ["t" => "s", "d" => false]);
        $this->assertEquals("123", $d);
    }

    public function testSanitizeDate() {
        $a = new UserSystem(
          ["location" => "localhost","database" => "","username" => "root","password" => ""],
          ['sitename' => "examplecom", 'domain_simple' => "example.com", 'domain' => "accounts.example.com", 'system_loc'=> "/usersystem", 'encryption' => false]
        );
        $b = $a->sanitize(1414035554, ["t" => "d", "d" => false]);
        $this->assertEquals(1414035554, $b);

        $c = $a->sanitize("1414035554", ["t" => "d", "d" => false]);
        $this->assertEquals(1414035554, $c);

        $d = $a->sanitize("1414;035554", ["t" => "d", "d" => false]);
        $this->assertEquals(1414035554, $d);

        $e = $a->sanitize("2014-10-21", ["t" => "d", "d" => false]);
        $this->assertEquals(1413871200, $e);

        $f = $a->sanitize("+1 week 2 days 4 hours 2 seconds", ["t" => "d", "d" => false]);
        $this->assertEquals(strtotime("+1 week 2 days 4 hours 2 seconds"), $f);

        $g = $a->sanitize("next Thursday", ["t" => "d", "d" => false]);
        $this->assertEquals(strtotime("next Thursday"), $g);
    }

    public function testSanitizeHTML() {
        $a = new UserSystem(
          ["location" => "localhost","database" => "","username" => "root","password" => ""],
          ['sitename' => "examplecom", 'domain_simple' => "example.com", 'domain' => "accounts.example.com", 'system_loc'=> "/usersystem", 'encryption' => false]
        );
        $b = $a->sanitize("<span>cake</span>", ["t" => "h", "d" => false]);
        $this->assertEquals("<span>cake</span>", $b);

        $c = $a->sanitize("g'°", ["t" => "h", "d" => false]);
        $this->assertEquals("g'&deg;", $c);
    }
}
