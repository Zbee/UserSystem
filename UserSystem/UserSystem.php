<?php
/**
* System class for performing common user system operations.
*
* @package    UserSystem
* @author     Ethan Henderson <ethan@zbee.me>
* @copyright  2014 Ethan Henderson
* @license    http://aol.nexua.org  AOL v0.6
* @link       https://github.com/zbee/usersystem
* @since      Class available since Release 0.1
*/
class UserSystem extends Database {

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
      if (!isset($_COOKIE)) { return false; }
      $session = $this->sanitize(
        filter_var(
            $_COOKIE[SITENAME],
            FILTER_SANITIZE_FULL_SPECIAL_CHARS
        ),
        "q"
      );
      $time = strtotime('+30 days');
      $query = $this->dbSel(
        [
          "userblobs",
          [
            "code"=>$session,
            "date"=>["<", $time],
            "action"=>"session"
          ]
        ]
      );
      if ($query[0] === 1) {
        $username = $query[1]['user'];
        $query = $this->dbSel(["users", ["username"=>$username]]);
        if ($query[0] === 1) {
          return $query[1];
        } else {
          return false;
        }
      } else {
        return false;
      }
    } else {
      $query = $this->dbSel(["users", ["username"=>$session]]);
      if ($query[0] === 1) {
        return $query[1];
      } else {
        return false;
      }
    }
  }


  /**
  * Generates a new salt based off of a username
  * Example: $UserSystem->createSalt("Bob")
  *
  * @access public
  * @param string $username
  * @return string
  */
  public function createSalt ($username) {
    return hash(
      "sha512",
      $username.substr(
        str_shuffle(
          str_repeat(
            "abcdefghijklmnopqrstuvwxyz
            ABCDEFGHIJKLMNOPQRSTUVWXYZ
            0123456789!@$%^&_+{}[]:<.>?",
            (
              rand(15,20)*
              strlen($username)-
              preg_match_all('/[aeiou]/i',$username,$matches)
            )
          )
        ),
        1,
        rand(256,1024)
      )
    );
  }

  /**
   * Inserts a user blob into the database for you
   * Example: $UserSystem->insertUserBlob("bob", "2step")
   *
   * @access public
   * @param string $username
   * @param mixed $action
   * @return boolean
   */
  public function insertUserBlob ($username, $action = "session") {
    $username = $this->sanitize($username, "q");
    $action = $this->sanitize($action, "q");
    $hash = $this->createSalt($username);
    $hash = $hash.md5($username.$hash);
    $time = time();
    $ipAddress = filter_var(
                    $_SERVER["REMOTE_ADDR"],
                    FILTER_SANITIZE_FULL_SPECIAL_CHARS
                  );
    if (ENCRYPTION === true) {
      $ipAddress = encrypt($ipAddress, $username);
    }
    $this->dbIns(
      [
        "userblobs",
        [
          "user"=>$username,
          "code"=>$hash,
          "action"=>$action,
          "ip"=>$ipAddress
        ]
      ]
    );
    return $hash;
  }

  /**
   * Checks if a user is banned
   * Example: $UserSystem->checkBan("127.0.0.1", "bob)
   *
   * @access public
   * @param string $ip
   * @param mixed $username
   * @return boolean
   */
  public function checkBan ($ipAddress, $username = false) {
    $username = $this->sanitize($username, "q");
    $ipAddress = filter_var(
      $ipAddress,
      FILTER_SANITIZE_FULL_SPECIAL_CHARS
    );
    if (ENCRYPTION === true) {
      $ipAddress = encrypt($ipAddress, $username);
    }
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
   * Verifies a user's session
   * Example: $UserSystem->verifySession($_COOKIE[$sitename])
   *
   * @access public
   * @param mixed $session
   * @return mixed
   */
  public function verifySession ($session = false) {
    if (!isset($_COOKIE)) { return false; }
    $COOKIE = $this->sanitize(
      filter_var(
          $_COOKIE[SITENAME],
          FILTER_SANITIZE_FULL_SPECIAL_CHARS
      ),
      "q"
    );
    if (!$session) { $session = $COOKIE; }
    $tamper  = substr($session, -32);
    $ipAddress = filter_var(
      $_SERVER["REMOTE_ADDR"],
      FILTER_SANITIZE_FULL_SPECIAL_CHARS
    );
    if (ENCRYPTION === true) {
      $ipAddress = encrypt($ipAddress, $username);
    }
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
        if ($this->checkBan($ipAddress) === false) {
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
     $data = [
       "username" => $username,
       "password" => $password,
       "email" => $email,
       "salt" => $this->createSalt($username)
     ];
     $more = $this->sanitize($more, "b");

     if ($more !== false && is_array($more)) {
       foreach ($more as $item) {
         $data[array_search($item, $more)] = $item;
       }
     }

     $this->dbIns(["users", $data]);
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
    $code = $this->sanitize($code, "q");
    $rows = $this->dbSel(["userblobs", ["code"=>$code, "action"=>"activate"]]);
    $rows = $rows[0];
    if ($rows >= 1) {
      $user = $rows[1]["user"];
      $this->dbUpd(["users", ["activated"=>1], ["username"=>$user]]);
      $noActiv = $this->dbSel(["users", ["username"=>$user,"activated"=>0]])[0];
      $this->dbDel(["userblobs", ["code"=>$code, "action"=>"activate"]]);
      $blob=$this->dbSel(["userblobs",["code"=>$code,"action"=>"activate"]])[0];
      if ($noActiv === 0 && $blob === 0) {
        return true;
      } else {
        return false;
      }
    } else {
      return false;
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
  public function logIn ($username, $password) {
    $username = $this->sanitize($username, "q");
    $ipAddress = filter_var(
      $_SERVER["REMOTE_ADDR"],
      FILTER_SANITIZE_FULL_SPECIAL_CHARS
    );
    $user = $this->session($username);
    if (is_array($user)) {
      $password = hash("sha256", $password.$user["salt"]);
      $oldPassword = hash("sha256", $password.$user["oldsalt"]);
      if ($password == $user["password"]) {
        if ($user["activated"] == 1) {
          if ($this->checkBan($ipAddress, $username) === false) {
            if (ENCRYPTION === true) {
              $ipAddress = encrypt($ipAddress, $username);
            }
            $this->dbUpd(
              [
                "users",
                [
                  "ip"=>$ipAddress,
                  "last_logged_in"=>time(),
                  "old_last_logged_in"=>$user["old_last_logged_in"]
                ],
                ["username"=>$username]
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
            return "ban";
          }
        } else {
          return "activate";
        }
      } else {
        if ($password == $oldPassword) {
          return "oldpassword";
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
    $code = $this->sanitize($code, "q");
    $user = $this->sanitize($user, "q");
    $cursess = $this->sanitize($cursess, "b");
    $all = $this->sanitize($all, "b");

    if (!$all) {
      $this->dbDel(
        [
          "userblobs",
          [
            "code"=>$code,
            "user"=>$user,
            "action"=>"session"
          ]
        ]
      );
    } else {
      $this->dbDel(["userblobs", ["user"=>$user]]);
    }
    if ($cursess) {
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
}
