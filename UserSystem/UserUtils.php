<?php
/**
* Class full of utility methods for working with users.
*
* @package    UserSystem
* @author     Ethan Henderson <ethan@zbee.me>
* @copyright  Copyright 2014-2015 Ethan Henderson
* @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License
* @link       https://github.com/zbee/usersystem
* @since      Class available since Release 0.96
*/
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
class UserUtils extends Database {

  /**
  * Inserts a user blob into the database for you
  * Example: $UserSystem->insertUserBlob("bob", "twoStep")
  *
  * @access public
  * @param string $username
  * @param mixed $action
  * @return boolean
  */
  public function insertUserBlob ($username, $action = "session") {
    $hash = $this->createSalt($username);
    $hash = $hash.md5($username.$hash);
    $ipAddress = $this->getIP();
    if (ENCRYPTION === true) $ipAddress = encrypt($ipAddress, $username);
    $this->dbIns(
      [
        "userblobs",
        [
          "user" => $username,
          "code" => $hash,
          "action" => $action,
          "ip" => $ipAddress,
          "date" => time()
        ]
      ]
    );
    return $hash;
  }

  /**
   * Checks if a user is banned
   * Example: $UserSystem->checkBan("bob)
   *
   * @access public
   * @param string $ip
   * @param mixed $username
   * @return boolean
   */
  public function checkBan ($username) {
    $ipAddress = $this->getIP();
    if (ENCRYPTION === true) $ipAddress = encrypt($ipAddress, $username);

    $stmt = $this->dbSel(["ban", ["ip" => $ipAddress]]);
    $rows = $stmt[0];
    if ($rows > 0) {
      if ($stmt[1]['appealed'] == 0) {
        $thing = true;
      } else {
        $thing = false;
      }
    } else {
      $thing = false;
    }

    if ($username !== false) {
      $stmt = $this->dbSel(["ban", ["username" => $username]]);
      $rows = $stmt[0];
      if ($rows > 0) {
        if ($stmt[1]['appealed'] == 0) {
          $thing = true;
        } else {
          $thing = false;
        }
      }
    }

    return $thing;
  }

  /**
  * Allows a user to send a link to reset their passsword if they forgot it.
  * Example: $UserSystem->sendRecover("example@pie.com")
  *
  * @access public
  * @param string $email
  * @return mixed
  */
  public function sendRecover ($email) {
    $select = $this->dbSel(["users", ["email"=>$email]]);
    if ($select[0] == 1) {
      $blob = $this->insertUserBlob($select[1]["username"], "recover");
      $link = $this->sanitize(
        URL_PREFACE."://".DOMAIN."/".RECOVERY_PG."?blob={$blob}",
        "u"
      );
      $this->sendMail(
        $email,
        "Recover your ".SITENAME." account",
        "        Hello ".$select[1]["username"]."

        To reset your password click the link below.
        {$link}

        ======

        If this wasn't you,you should update your password on ".SITENAME.".

        Thank you"
      );
      return true;
    } else {
      return "email";
    }
  }
}
