<?php
/**
* System class for performing common user system operations.
*
* @package    UserSystem
* @author     Ethan Henderson <ethan@zbee.me>
* @copyright  Copyright 2014-2015 Ethan Henderson
* @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License
* @link       https://github.com/zbee/usersystem
* @since      Class available since Release 0.1
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
class UserSystem extends UserUtils {

  /**
  * Returns an array full of the data about a user
  * Example: $UserSystem->session("bob")
  *
  * @access public
  * @param string $session
  * @return mixed
  */
  public function session ($session = false) {
    if (!$session) {
      if (!isset($_COOKIE[SITENAME])) { return false; }
      $session = filter_var(
        $_COOKIE[SITENAME],
        FILTER_SANITIZE_FULL_SPECIAL_CHARS
      );
      $time = strtotime('+30 days');
      $query = $this->dbSel(
        [
          "userblobs",
          [
            "code" => $session,
            "date" => ["<", $time],
            "action" => "session"
          ]
        ]
      );
      if ($query[0] === 1) {
        $username = $query[1]['user'];
        $query = $this->dbSel(["users", ["username" => $username]]);
        if ($query[0] === 1) return $query[1];
      }
    } else {
      $query = $this->dbSel(["users", ["username" => $session]]);
      if ($query[0] === 1) return $query[1];
    }

    return false;
  }

  /**
   * Verifies a user's session
   * Example: $UserSystem->verifySession($_COOKIE[SITENAME])
   *
   * @access public
   * @param mixed $session
   * @return mixed
   */
  public function verifySession ($session = false) {
    if (!isset($_COOKIE[SITENAME])) { return false; }
    $COOKIE = filter_var(
      $_COOKIE[SITENAME],
      FILTER_SANITIZE_FULL_SPECIAL_CHARS
    );
    if (!$session) { $session = $COOKIE; }
    $tamper  = substr($session, -32);
    $ipAddress = $this->getIP();
    if (ENCRYPTION === true) $ipAddress = encrypt($ipAddress, $username);
    $time = strtotime("+30 days");
    $stmt = $this->dbSel(
      [
        "userblobs",
        [
          "code" => $session,
          "date" => ["<", $time],
          "action" => "session"
        ]
      ]
    );

    $rows = $stmt[0];
    if ($rows == 1) {
      $username = $stmt[1]['user'];
      if (md5($username.substr($session, 0, 128)) == $tamper) {
        if ($this->checkBan($username) === false) {
          return true;
        } else {
          return "ban";
        }
      } else {
        $this->dbDel(["userblobs", ["code"=>$session, "action"=>"session"]]);
        return "tamper";
      }
    } else {
      return "session";
    }
  }

  /**
   * Inserts a new user
   * Example: $UserSystem->addUser("Bob","jg85h58gh58","bob@example.com")
   *
   * @access public
   * @param string $username
   * @param string $password
   * @param string $email
   * @param mixed $more
   * @return mixed
   */
   public function addUser ($username, $password, $email, $more = false) {
     $usernameUse = $this->dbSel(["users", ["username" => $username]])[0];
     if ($usernameUse == 0) {
       $emailUse = $this->dbSel(["users", ["email" => $email]])[0];
       if ($emailUse == 0) {
         $salt = $this->createSalt($username);
         $data = [
           "username" => $username,
           "password" => hash("sha256", $password.$salt),
           "email" => $email,
           "salt" => $salt,
           "dateRegistered" => time()
         ];

         $morech = $this->sanitize($more, "b");

         if ($morech !== false && is_array($more)) {
           foreach ($more as $item) {
             $data[array_search($item, $more)] = $item;
           }
         }

         $this->dbIns(["users", $data]);
         $blob = $this->insertUserBlob($username, "activate");
         $link = $this->sanitize(
           URL_PREFACE."://".DOMAIN."/".ACTIVATE_PG."/?blob={$blob}",
           "u"
         );
         $this->sendMail(
           $email,
           "Activate your ".SITENAME." account",
           "           Hello {$username}

           To activate your account, click the link below.
           {$link}

           ======

           If this wasn't you, you can ignore this email.

           Thank you"
         );
         return true;
       } else {
         return "email";
       }
     } else {
       return "username";
     }
   }

  /**
   * Activates a new user's account
   * Example: $UserSystem->activateUser("mrogjsruicyu78chsr87thmrsu")
   *
   * @access public
   * @param string $code
   * @return boolean
   */
  public function activateUser ($code) {
    $rows = $this->dbSel(["userblobs", ["code"=>$code, "action"=>"activate"]]);
    if ($rows[0] == 1) {
      $user = $rows[1]["user"];
      $update = $this->dbUpd(["users", ["activated"=>1], ["username"=>$user]]);
      if ($update !== true) return "UpdateFailed";
      $noActiv = $this->dbSel(["users", ["username"=>$user,"activated"=>0]])[0];
      if ($noActiv !== 0) return "NotActivated";
      $del=$this->dbDel(["userblobs", ["code"=>$code, "action"=>"activate"]]);
      if ($del !== true) return "DeleteFailed";
      $blob=$this->dbSel(["userblobs",["code"=>$code,"action"=>"activate"]])[0];
      if ($blob !== 0) return "BlobNotRemoved";
      return true;
    } else {
      return "InvalidBlob";
    }
  }

  /**
   * Logs in a user
   * Example: $UserSystem->logIn("Bob", "Bob's Password")
   *
   * @access public
   * @param string $username
   * @param string $password
   * @return boolean
   */
  public function logIn ($username, $password, $ignoreTS = false) {
    $ignoreTS = $this->sanitize($ignoreTS, "b");
    $ipAddress = $this->getIP();
    $user = $this->session($username);
    if (is_array($user)) {
      $password = hash("sha256", $password.$user["salt"]);
      $oldPassword = hash("sha256", $password.$user["oldSalt"]);
      if ($password == $user["password"]) {
        if ($user["activated"] == 1) {
          if ($user["twoStep"] == 0 || $ignoreTS !== false) {
            if (ENCRYPTION === true) $ipAddress = encrypt($ipAddress, $username);
            $this->dbUpd(
              [
                "users",
                [
                  "ip" => $ipAddress,
                  "lastLoggedIn" => time(),
                  "oldLastLoggedIn" => $user["lastLoggedIn"]
                ],
                [
                  "username" => $username
                ]
              ]
            );
            $hash = $this->insertUserBlob($username);
            if (!headers_sent()) {
              setcookie(
                SITENAME,
                $hash,
                strtotime('+30 days'),
                "/",
                DOMAIN_SIMPLE
              );
              return true;
            } else {
              return true;
            }
          } else {
            return "twoStep";
          }
        } else {
          return "activate";
        }
      } else {
        if ($password == $oldPassword) {
          return "oldPassword";
        } else {
          return "password";
        }
      }
    } else {
      return "username";
    }
  }

  /**
   * Logs out a selected userblob or group of user blobs
   * Example: $UserSystem->logOut($_COOKIE[$sitename], "Bob", true)
   *
   * @access public
   * @param string $code
   * @param string $user
   * @param mixed $cursess
   * @param mixed $all
   * @return boolean
   */
  public function logOut ($code, $user, $cursess = false, $all = false) {
    $cursess = $this->sanitize($cursess, "b");
    $all = $this->sanitize($all, "b");

    if (!$all) {
      $this->dbDel(
        [
          "userblobs",
          [
            "code" => $code,
            "user" => $user,
            "action" => "session"
          ]
        ]
      );
    } else {
      $this->dbDel(["userblobs", ["user"=>$user]]);
    }
    if ($cursess === true) {
      setcookie(
        SITENAME,
        null,
        strtotime('-60 days'),
        "/",
        DOMAIN_SIMPLE
      );
    }
    return true;
  }

  /**
   * Finishes logging a user in if they have twoStep enabled.
   * Example: $UserSystem->twoStep($blob)
   *
   * @access public
   * @param string $code
   * @return mixed
   */
  public function twoStep ($code) {
    $ipAddress = $this->getIP();
    $return = "";
    $select = $this->dbSel(["userblobs", ["code"=>$code, "action"=>"twoStep"]]);
    if ($select[0] === 1) {
      if ($select[1]["date"] > time() - 3600) {
        if ($select[1]["ip"] == $ipAddress) {
          $this->logIn(
            $select[1]["user"],
            $this->session($select[1]["user"])["password"],
            true
          );
          $return = true;
        } else {
          $return = "ip";
        }
      } else {
        $return = "expired";
      }
    } else {
      $return = "code";
    }

    $this->dbDel(["userblobs", ["code"=>$code, "action"=>"twoStep"]]);
    return $return;
  }

  /**
  * Allows a user to reset their pass using the link received from sendRecover
  * Example: $UserSystem->recover("fmg49t4c8u5ym8598yv5")
  *
  * @access public
  * @param string $blob
  * @param string $pass
  * @param string $passconf
  * @return mixed
  */
  public function recover ($blob, $pass, $passconf) {
    if ($pass === $passconf) {
      $select = $this->dbSel(
        [
          "userblobs",
          [ "code"=>$blob,"action"=>"recovery" ]
        ]
      );
      if ($select[0] == 1) {
        $user = $select[1]["user"];
        $salt = $this->session($user)["salt"];
        $pass = hash("sha256", $pass.$salt);
      }
    } else {
      return "password";
    }
  }
}
