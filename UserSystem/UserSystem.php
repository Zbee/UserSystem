<?php
class UserSystem {
  var $DATABASE = '';
  const OPTION = '';
  var $SERVER = [
    "REMOTE_ADDR" => filter_var(
                          $_SERVER['REMOTE_ADDR'],
                          FILTER_SANITIZE_FULL_SPECIAL_CHARS
                        ),
    "HTTP_HOST" => filter_var(
                          $_SERVER['HTTP_HOST'],
                          FILTER_SANITIZE_FULL_SPECIAL_CHARS
                        ),
    "REQUEST_URI" => filter_var(
                          $_SERVER['REQUEST_URI'],
                          FILTER_SANITIZE_FULL_SPECIAL_CHARS
                        )
  ];

  /**
  * Initializes the class and connects to the database and sets up options.
  * Example: $UserSystem = new UserSystem ($database, $opts)
  *
  * @access public
  * @param mixed $db
  * @param mixed $opts
  * @return void
  */
  public function __construct ($db, $opts) {
    if (!$db) {
      $db = ["location"=>"localhost","database"=>"","username"=>"root","password" =>""];
    }

    $this->DATABASE = new PDO(
                  "mysql:host=$db[location];dbname=$db[database];
                  charset=utf8", $db['username'], $db['password']
                );
    $this->OPTIONS = $opts;
  }
  var $COOKIE = filter_var(
                    $_COOKIE[$this->OPTIONS['sitename']],
                    FILTER_SANITIZE_FULL_SPECIAL_CHARS
                  );

  //////////////////////////////////////////////////////////////////////////////
  //Utility Functions
  //////////////////////////////////////////////////////////////////////////////

  /**
  * Gives the current url that the user is on.
  * Example: $UserSystem->currentURL()
  *
  * @access public
  * @return string
  */
  public function currentURL () {
    return "//$SERVER[HTTP_HOST]$SERVER[REQUEST_URI]";
  }

  /**
  * Provides the proper headers to redirect a user, including a page-has-moved flag.
  * Example: $UserSystem->redirect301("http://example.com")
  *
  * @access public
  * @param string $url
  * @return boolean
  */
  public function redirect301($url) {
    if (!headers_sent()) {
      header("HTTP/1.1 301 Moved Permanently");
      header("Location: $url");
      return true;
    } else {
      return false;
    }
  }

  /**
  * Encrypts any data and makes it only decryptable by the same user.
  * Example: $UserSystem->encrypt("myEmail", "bob")
  *
  * @access public
  * @param string $decrypted
  * @param string $username
  * @return string
  */
  public function encrypt ($decrypted, $username) {
    $key       = hash('SHA256', $username, true);
    srand();
    $initVector        = mcrypt_create_iv(
                  mcrypt_get_iv_size(
                    MCRYPT_RIJNDAEL_128,
                    MCRYPT_MODE_CBC
                  ),
                  MCRYPT_RAND
                );
    if (strlen($iv_base64 = rtrim(base64_encode($initVector), '=')) != 22) {
        return false;
    }
    $encrypted = base64_encode(
                  mcrypt_encrypt(
                    MCRYPT_RIJNDAEL_128,
                    $key,
                    $decrypted . md5($decrypted),
                    MCRYPT_MODE_CBC,
                    $initVector
                  )
                );
    return $iv_base64 . $encrypted;
   }

  /**
  * Decrypts any data that belongs to a set user
  * Example: $UserSystem->decrypt("4lj84mui4htwyi58g7gh5y8hvn8t", "bob")
  *
  * @access public
  * @param string $encrypted
  * @param string $username
  * @return string
  */
  public function decrypt ($encrypted, $username) {
    $key       = hash('SHA256', $username, true);
    $iv        = base64_decode(substr($encrypted, 0, 22) . '==');
    $encrypted = substr($encrypted, 22);
    $decrypted = rtrim(
                  mcrypt_decrypt(
                    MCRYPT_RIJNDAEL_128,
                    $key,
                    base64_decode($encrypted),
                    MCRYPT_MODE_CBC,
                    $iv
                   ),
                  "\0\4"
                );
    $hash      = substr($decrypted, -32);
    $decrypted = substr($decrypted, 0, -32);
    if (md5($decrypted) != $hash) {
      return false;
    }
    return $decrypted;
 }

  /**
  * Makes any string safe for HTML by converting it to an entity code.
  * Example: $UserSystem->handleUTF8("g'°")
  *
  * @access public
  * @param string $code
  * @return string
  */
  public function handleUTF8 ($code) {
      return preg_replace_callback('/[\x{80}-\x{10FFFF}]/u', function($match) {
          list($utf8) = $match;
          $entity = mb_convert_encoding($utf8, 'HTML-ENTITIES', 'UTF-8');
          return $entity;
      }, $code);
  }

  /**
  * $this->sanitizes any given string in a particular fashion of your choosing.
  * Example: $UserSystem->sanitize("dirt")
  *
  * @access public
  * @param string $data
  * @param string $type
  * @return mixed
  */
  public function $this->sanitize ($data, $type = 's') {
    $data = trim($data);

    if ($type == "n") { //if number type
      $data = filter_var($data, FILTER_SANITIZE_NUMBER_FLOAT);
      $data = preg_replace("/[^0-9]/", "", $data);
      return intval($data);
    } elseif ($type == "s") { //If string type
      $data = $this->handleUTF8($data);
      $data = filter_var($data, FILTER_SANITIZE_STRING);
      return filter_var($data, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    } elseif ($type == "d") { //If date type
      $data = preg_replace("/[^0-9\-\s\+a-zA-Z]/", "", $data);
      if (is_numeric($data) !== true) {
        $data = strtotime($data);
      }
      $month = date("n", $data);
      $day = date("j", $data);
      $year = date("Y", $data);

      if (checkdate($month, $day, $year) === true) {
       return $data;
      }
    } elseif ($type == "h") { //If html type
      return $this->handleUTF8($data);
    } elseif ($type == "q") { //If sql type
      $data = $this->handleUTF8($data);
      $bad = "drop table|show table|`|\*|--|1=1|1='1'|a=a|a='a'|not null|\\\\";
      $data = preg_replace(
                            "/($bad)/i",
                            "",
                            $data
                          );
      $data = filter_var($data, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

      if (is_string($data) === true) {
        return $data;
      }
    } elseif ($type == "b") { //If boolean type
      $data = (filter_var($data, FILTER_VALIDATE_BOOLEAN)) ? $data : "fail";

      if (is_bool($data)) {
        return $data;
      }
    } elseif ($type == "u") { //if url type
      if (filter_var($data, FILTER_VALIDATE_URL) === true) {
        return filter_var($data, FILTER_SANITIZE_URL);
      }
    }

    return "FAIL-Sanitization";
  }

  /**
  * A shortcut for eaily modifying the MySQL database, not necessarily easier, but hits up
  * all required functions in the process.
  * Example: $UserSystem->dbMod(["i","users",["username"=>"Bob","email"=>"bob@ex.com"]])
  *
  * @access public
  * @param array $data
  * @return boolean
  */
  public function dbMod ($data) {
    $dataArr = [];
    foreach ($data[2] as $item) {
      $col = array_search($item, $data[2]);
      array_push($dataArr, [$col, $item]);
    }
    $data[1] = $this->sanitize($data[1], "q");

    switch ($data[0]) {
      case "i":
        $cols = "";
        $entries = "";
        foreach ($dataArr as $item) {
          $cols .= $this->sanitize($item[0], "q").", ";
          $entries .= $this->sanitize($item[1], "q")."', '";
        }
        $cols = substr($cols, 0, -2);
        $entries = substr($entries, 0, -4);
        print "INSERT INTO $data[1] ($cols) VALUES ('$entries)";
        $this->DATABASE->query("INSERT INTO $data[1] ($cols) VALUES ('$entries)");
        return true;
      case "u":
        $update = "";
        foreach ($dataArr as $item) {
          $update .= "`".$this->sanitize($item[0], "q")."`='".$this->sanitize($item[1], "q")."', ";
        }
        $equalsArr = [];
        foreach ($data[3] as $item) {
          $col = array_search($item, $data[3]);
          array_push($equalsArr, [$this->sanitize($col, "q"), $this->sanitize($item, "q")]);
        }
        $equals = "";
        foreach ($equalsArr as $item) {
          $equals .= "`".$item[0]."`='".$item[1]."', ";
        }
        $equals = substr($equals, 0, -2);
        $update = substr($update, 0, -2);
        $this->DATABASE->query("UPDATE $data[1] SET $update WHERE $equals");
        return true;
      case "d":
        $equals = "";
        foreach ($dataArr as $item) {
          $equals .= "`".$this->sanitize($item[0], "q")."`='".$this->sanitize($item[1], "q")."', ";
        }
        $equals = substr($equals, 0, -2);
        $this->DATABASE->query("DELETE FROM $data[1] WHERE $equals");
        return true;
      default:
        return false;
    }
  }

  /**
  * Returns an array for the database search performed, again, just a shortcut for hitting
  * required functions
  * Example: $UserSystem->dbSel(["users", ["username"=>"Bob","id"=>0]])
  *
  * @access public
  * @param array $data
  * @return array
  */
  public function dbSel ($data) {
    $dataArr = [];
    foreach ($data[1] as $item) {
      $col = array_search($item, $data[1]);
      array_push($dataArr, [$this->sanitize($col, "q"), $this->sanitize($item, "q")]);
    }
    $equals = '';
    foreach ($dataArr as $item) {
      $equals .= " AND `".$item[0]."`='".$item[1]."'";
    }
    $equals = substr($equals, 5);
    $stmt = $this->DATABASE->query("SELECT * from $data[0] WHERE $equals");
    $arr = [$stmt->rowCount()];
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      array_push($arr, $row);
    }
    return $arr;
  }

   /**
   * Returns the number of rows for a given search.
   * Example: $UserSystem->numberOfRows("users", "username", $enteredUsername)
   * Should follow pattern of dbMod() so as to support more $thing/$answer combos.
   *
   * @access public
   * @param string $table
   * @param mixed $thing
   * @param mixed $answer
   * @return integer
   */
   public function numRows ($table, $thing = false, $answer = false) {
     $table = $this->sanitize($table, "q");
     if (!$thing && !$answer) {
       $stmt = $this->DATABASE->query("SELECT * FROM $table");
     } else {
       $thing = $this->sanitize($thing, "q");
       $answer = $this->sanitize($answer, "q");
       $stmt = $this->DATABASE->query("SELECT * FROM $table WHERE $thing='$answer'");
     }
     $rows =  (is_object($stmt)) ? $stmt->rowCount(): 0;
     return $rows;
   }

  //////////////////////////////////////////////////////////////////////////////
  //System Functions
  //////////////////////////////////////////////////////////////////////////////

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
      if (!isset($COOKIE)) { return false; }
      $session = $this->sanitize($COOKIE, "q");
      $time    = strtotime('+30 days');
      $stmt = $this->DATABASE->query(
                "SELECT * FROM userblobs
                WHERE code='$session' AND date<'$time' AND action='session'"
              );
      $rows = $stmt->rowCount();
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $username = $row['user'];
      }

      if ($rows === 1) {
        $stmt = $this->DATABASE->query(
                  "SELECT * FROM users WHERE username='$username'"
                );
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
          return $row;
        }
      } else {
        return false;
      }
    } else {
      $stmt = $this->DATABASE->query(
                "SELECT * FROM users WHERE username='$session'"
              );
      $rows = $stmt->rowCount();
      if ($rows === 1) {
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
          return $row;
        }
      } else {
        return false;
      }
    }
  }

  /**
   * Inserts a user blob into the database for you
   * Example: $UserSystem->insertUserBlob("bob", "rmt54h78tcy54hmgtx", "2step")
   *
   * @access public
   * @param string $username
   * @param string $hash
   * @param mixed $action
   * @return boolean
   */
  public function insertUserBlob ($username, $hash, $action="session") {
    $username = $this->sanitize($username, "q");
    $hash = $this->sanitize($hash, "q");
    $time = time();
    $ipAddress = $SERVER['REMOTE_ADDR'];
    $this->DATABASE->query(
      "INSERT INTO userblobs
      (user, code, action, date, ip) VALUES
      ('$username', '$hash', '$action', '$time', '$ipAddress')"
    );
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
  public function checkBan ($ip, $username = false) {
    $ipAddress = $this->sanitize($ipAddress, "q");
    $username = $this->sanitize($username, "q");

    $stmt = $this->DATABASE->query("SELECT * FROM ban WHERE ip='$ipAddress'");
    $rows = $stmt->rowCount();
    if ($rows > 0) {
      while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['appealed'] == 0) {
          $thing = true;
        } else {
          $thing = false;
        }
      }
    } else {
      $thing = false;
    }

    if ($username !== false) {
      $stmt = $this->DATABASE->query("SELECT * FROM ban WHERE username='$username'");
      $rows = $stmt->rowCount();
      if ($rows > 0) {
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
          if ($row['appealed'] == 0) {
            $thing = true;
          } else {
            $thing = false;
          }
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
    if (!isset($COOKIE)) { return false; }
    if (!$session) { $session = $COOKIE; }
    $time = strtotime("+30 days");
    $stmt = $this->DATABASE->query(
              "SELECT * FROM userblobs
              WHERE code='$session' AND date<'$time' AND action='session'"
            );
    $rows = $stmt->rowCount();
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $username = $row['user'];
    }
    $tamper  = substr($session, -32);

    if ($rows == 1) {
      if (md5($username.substr($session, 0, 64)) == $tamper) {
        if ($this->checkBan($SERVER['REMOTE_ADDR']) === false) {
          return true;
        } else {
          return "ban";
        }
      } else {
        $this->DATABASE->query(
                "DELETE FROM userblobs
                WHERE code='$session' AND action='session' LIMIT 1"
            );
        return "tamper";
      }
    } else {
      return "session";
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
    $code = $this->sanitize($code, "q");
  }

  /**
   * Logs in a user
   * Example: $UserSystem->LogIn("Bob", "Bob's Password")
   *
   * @access public
   * @param string $username
   * @param string $password
   * @return boolean
   */
  public function logIn ($username, $password) {
    $username = $this->sanitize($username, "q");
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

    if (!$all) {
      $this->DATABASE->query(
        "DELETE FROM userblobs
        WHERE code='$code' AND user='$user' AND action='session'
        LIMIT 1"
      );
    } else {
      $this->DATABASE->query(
        "DELETE FROM userblobs
        WHERE user='$user'"
      );
    }
    if ($cursess) {
      setcookie(
        $this->OPTIONS['sitename'],
        null,
        strtotime('-60 days'),
        "/",
        $this->OPTIONS['domain_simple']
      );
    }
  }
}
