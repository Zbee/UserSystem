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
$tests = "This stops config.php from initiating class";
require_once("config.php");
$UserSystem = new UserSystem ("");

$pre = DB_USERNAME."@".DB_DATABASE.":";
$err = "<font style='color: red'>";
$made = $pre."%s table made or existed (`".DB_PREFACE."%s`).<br>";
$failed = $err.$pre.'%s table NOT made successfully.</font><br>';

$database =
#Database: Contains all tables
$UserSystem->DATABASE->query("
CREATE DATABASE IF NOT EXISTS ".DB_DATABASE.";
");

if (is_object($database)) { #If the database was made successfully
	echo DB_USERNAME.'@'.DB_DATABASE.': Database made successfully or already
	existed (`'.DB_DATABASE.'`).<br>';
	$UserSystem = new UserSystem ();
} else {
	echo '<font style="color: red">'.DB_USERNAME.'@'.DB_DATABASE.': Database
	NOT made successfully.</font><br>';
}

$ban =
#Ban table: Stores basic user information about each user that is banned
$UserSystem->DATABASE->query("
CREATE TABLE IF NOT EXISTS `".DB_PREFACE."ban` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`date` VARCHAR(50) NULL DEFAULT NULL,
	`ip` VARCHAR(256) NULL DEFAULT NULL,
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

if (is_object($ban)) { #If the ban table was made successfully
	echo sprintf($made, "Bans", "bans");
} else {
	echo sprintf($failed, "Bans");
}

$userblobs =
#Userblobs table: Active session codes
$UserSystem->DATABASE->query("
CREATE TABLE IF NOT EXISTS `".DB_PREFACE."userblobs` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`user` VARCHAR(100) NOT NULL,
	`code` VARCHAR(512) NOT NULL,
	`action` VARCHAR(100) NOT NULL,
	`date` INT NOT NULL,
	PRIMARY KEY (`id`)
)
COLLATE='latin1_swedish_ci'
ENGINE=MyISAM
AUTO_INCREMENT=0;
");

if (is_object($userblobs)) { #If the ban table was made successfully
	echo sprintf($made, "Userblobs", "userblobs");
} else {
	echo sprintf($failed, "Userblobs");
}

$users =
#Users table: Stores all information about users
$UserSystem->DATABASE->query("
CREATE TABLE IF NOT EXISTS `".DB_PREFACE."users` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`username` VARCHAR(50) NOT NULL,
	`firstName` VARCHAR(256) NOT NULL,
	`lastName` VARCHAR(256) NOT NULL,
	`password` VARCHAR(100) NOT NULL,
	`oldPassword` VARCHAR(100) NOT NULL,
	`passwordChanged` VARCHAR(50) NOT NULL DEFAULT '0000000000',
	`salt` VARCHAR(512) NOT NULL,
	`oldSalt` VARCHAR(512) NOT NULL,
	`email` VARCHAR(256) NOT NULL,
	`oldEmail` VARCHAR(512) NOT NULL,
	`emailchanged` INT NOT NULL DEFAULT '0000000000',
	`phone` INT(11) NOT NULL DEFAULT '0',
	`dateRegistered` INT NOT NULL,
	`activated` INT(1) NOT NULL DEFAULT '0',
	`title` VARCHAR(50) NOT NULL DEFAULT '',
	`twoStep` INT(1) NOT NULL DEFAULT '0',
	`lastLoggedIn` INT NOT NULL DEFAULT '0000000000',
	`oldLastLoggedIn` INT NOT NULL DEFAULT '0000000000',
	`ip` VARCHAR(256) NOT NULL DEFAULT '',
	PRIMARY KEY (`id`)
)
COLLATE='latin1_swedish_ci'
ENGINE=MyISAM
AUTO_INCREMENT=0;
");

if (is_object($users)) { #If the ban table was made successfully
	echo sprintf($made, "Users", "users");
} else {
	echo sprintf($failed, "Users");
}
