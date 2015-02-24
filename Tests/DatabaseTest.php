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

class DatabaseTest extends PHPUnit_Framework_TestCase {
  public function testDB() {
    $a = new UserSystem("");
    $a->DATABASE->query("CREATE DATABASE ".DB_DATABASE);
    $a = new UserSystem();
    $a->DATABASE->query("
    CREATE TABLE `".DB_PREFACE."test` (
      `id` INT NOT NULL AUTO_INCREMENT,
      `test` VARCHAR(50) NULL DEFAULT NULL,
      PRIMARY KEY (`id`)
    )
    COLLATE='latin1_swedish_ci'
    ENGINE=MyISAM
    AUTO_INCREMENT=0;
    ");
    $a->DATABASE->query("
      INSERT INTO `".DB_PREFACE."test` (test) VALUES ('cake')
    ");
    $b = $a->dbSel(["test", ["id"=>1]]);
    $this->assertEquals(1, $b[0]);
    $this->assertEquals(1, $b[1]['id']);
    $this->assertEquals("cake", $b[1]['test']);

    $a->dbUpd(["test", ["test"=>"pie"], ["test"=>"cake"]]);
    $b = $a->dbSel(["test", ["id"=>1]]);
    $this->assertEquals(1, $b[0]);
    $this->assertEquals(1, $b[1]['id']);
    $this->assertEquals("pie", $b[1]['test']);

    $a->dbDel(["test", ["id"=>1]]);
    $b = $a->dbSel(["test1", ["id"=>1]]);
    $this->assertEquals(0, $b[0]);

    $b = $a->dbSel(["test", ["id"=>1]]);
    $this->assertEquals(0, $b[0]);

    $a->DATABASE->query("DROP DATABASE ".DB_DATABASE);
  }
}
