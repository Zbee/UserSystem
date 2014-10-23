<?php
class UserSystem {
  var $db = '';
  const OPTIONS = '';

  #$UserSystem = new UserSystem ($database, $opts)
  #Would initialize the UserSystem.PHP class with the database connection info
  #and config.php options
  public function __construct ($db, $opts) {
    if (!$db) {
      $db = ["location"=>"localhost","database"=>"","username"=>"root","password" =>""];
    }
    
    $this->db = new PDO(
                  "mysql:host=$db[location];dbname=$db[database];
                  charset=utf8", $db['username'], $db['password']
                );
    $this->OPTIONS = $opts;
  }

  //////////////////////////////////////////////////////////////////////////////
  //Utility Functions
  //////////////////////////////////////////////////////////////////////////////

  #$UserSystem->currentURL()
  #Returns URL of current page
  public function currentURL () {
    return "//$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
  }

  #$UserSystem->redirect301("http://example.com")
  #Would redirect the user or bot to "http://example.com" and set the correct
  #HTTP error so the bot will follow the page
  public function redirect301($url) {
    if (!headers_sent()) {
      header("HTTP/1.1 301 Moved Permanently");
      header("Location: $url");
      return true;
    } else {
      return false;
    }
  }

  #$UserSystem->encrypt("myEmail", "bob")
  #Would encrypt "bob"'s "myemail" text
  public function encrypt ($decrypted, $username) {
    $key       = hash('SHA256', $username, true);
    srand();
    $iv        = mcrypt_create_iv(
                  mcrypt_get_iv_size(
                    MCRYPT_RIJNDAEL_128,
                    MCRYPT_MODE_CBC
                  ),
                  MCRYPT_RAND
                );
    if (strlen($iv_base64 = rtrim(base64_encode($iv), '=')) != 22) {
        return false;
    }
    $encrypted = base64_encode(
                  mcrypt_encrypt(
                    MCRYPT_RIJNDAEL_128,
                    $key,
                    $decrypted . md5($decrypted),
                    MCRYPT_MODE_CBC,
                    $iv
                  )
                );
    return $iv_base64 . $encrypted;
   }

  #$UserSystem->decrypt("fnmeuixf4hm98g45hgx849gx4hg98h598g", "bob")
  #Would decrypt the stated string of "bob"'s
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

  #$UserSystem->numberOfRows("users", "username", $enteredUsername)
  #Would return the number of users with the entered username
  public function numRows ($table, $thing = false, $answer = false) {
    if (!$thing && !$answer) {
      $stmt = $this->db->query("SELECT * FROM $table");
    } else {
      $stmt = $this->db->query("SELECT * FROM $table WHERE $thing='$answer'");
    }
    $rows = $stmt->rowCount();
    return $rows;
  }

  #UserSystem->handleUTF8("g'°")
  #Would make that string safe for HTML by turning them into HTML entities
  public function handleUTF8 ($code) {
      return preg_replace_callback('/[\x{80}-\x{10FFFF}]/u', function($match) {
          list($utf8) = $match;
          $entity = mb_convert_encoding($utf8, 'HTML-ENTITIES', 'UTF-8');
          return $entity;
      }, $code);
  }

  #$UserSystem->sanitize("dirt")
  #Would sanitize the string dirt with the set options
  public function sanitize ($data, $type = 's') {
    $data = trim($data);

    if ($type === false) {
      $tc = false;
    } else {
      $tc = false;
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
        $m = date("n", $data);
        $d = date("j", $data);
        $y = date("Y", $data);

        if (checkdate($m, $d, $y) === true) {
         return $data;
        }
      } elseif ($type == "h") { //If html type
        return $this->handleUTF8($data);
      } elseif ($type == "q") { //If sql type
        $data = $this->handleUTF8($data);
        $b = "drop table|show table|`|\*|--|1=1|1='1'|a=a|a='a'|not null|\\\\";
        $data = preg_replace(
                              "/($b)/i",
                              "",
                              $data
                            );
        $data = filter_var($data, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $data = mysql_real_escape_string($data);

        if (is_string($data) === true) {
          return $data;
        }
      } elseif ($type == "b") { //If boolean type
        $data = (filter_var($data, FILTER_VALIDATE_BOOLEAN)) ? true : "fail";

        if (is_bool($data)) {
          return $data;
        }
      } elseif ($type == "u") { //if url type
        if (filter_var($data, FILTER_VALIDATE_URL) === true) {
          return filter_var($data, FILTER_SANITIZE_URL);
        }
      }
    }

    return "FAIL-Sanitization";
  }

  #$UserSystem->dbMod(["i","users",["username"=>"Bob","email"=>"bob@ex.com"]])
  #Will insert the user bob into the table users with the email of bob@ex.com
  public function dbMod ($data) {
    $d = [];
    foreach ($data[2] as $item) {
      $col = array_search($item, $data[2]);
      array_push($d, [$col, $item]);
    }

    switch ($data[0]) {
      case "i":
        $cols = "";
        $entries = "";
        foreach ($d as $item) {
          $cols .= $item[0].", ";
          $entries .= $item[1]."', '";
        }
        $cols = substr($cols, 0, -2);
        $entries = substr($entries, 0, -3);
        $this->db->query("INSERT INTO $data[1] ($cols) VALUES ('$entries)");
        return true;
        break;
      case "u":
        $update = "";
        foreach ($d as $item) {
          $update .= "`".$item[0]."`='".$item[1]."', ";
        }
        $q = [];
        foreach ($data[3] as $item) {
          $col = array_search($item, $data[3]);
          array_push($q, [$col, $item]);
        }
        $equals = "";
        foreach ($q as $item) {
          $equals .= "`".$item[0]."`='".$item[1]."', ";
        }
        $equals = substr($equals, 0, -2);
        $update = substr($update, 0, -2);
        $this->db->query("UPDATE $data[1] SET $update WHERE $equals");
        return true;
      case "d":
        $equals = "";
        foreach ($d as $item) {
          $equals .= "`".$item[0]."`='".$item[1]."', ";
        }
        $equals = substr($equals, 0, -2);
        $this->db->query("DELETE FROM $data[1] WHERE $equals");
        return true;
        break;
      default:
        return false;
        break;
    }
  }

  #$userSystem->dbSel(["users", ["username"=>"Bob","id"=>0]])
  #Returns an array of all of the users with the username bob AND the id of 0
  public function dbSel ($data) {
    $d = [];
    foreach ($data[1] as $item) {
      $col = array_search($item, $data[1]);
      array_push($d, [$col, $item]);
    }
    $equals = '';
    foreach ($d as $item) {
      $equals .= " AND `".strtolower($item[0])."`='".$item[1]."'";
    }
    $equals = substr($equals, 5);
    $stmt = $this->db->query("SELECT * from $data[0] WHERE $equals");
    $arr = [$stmt->rowCount()];
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      array_push($arr, $row);
    }
    return $arr;
  }

  //////////////////////////////////////////////////////////////////////////////
  //System Functions
  //////////////////////////////////////////////////////////////////////////////

  #$UserSystem->session("bob")
  #Will get the whole user array for the user "bob"
  public function session ($session = false) {
    if (!$session) {
      if (!isset($_COOKIE[$this->OPTIONS['sitename']])) { return false; }
      $session = $this->sanitize($_COOKIE[$this->OPTIONS['sitename']], ["t"=>"q"]);
      $time    = strtotime('+30 days');
      $stmt = $this->db->query(
                "SELECT * FROM userblobs
                WHERE code='$session' AND date<'$time' AND action='session'"
              );
      $rows = $stmt->rowCount();
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $username = $row['user'];
      }

      if ($rows === 1) {
        $stmt = $this->db->query(
                  "SELECT * FROM users WHERE username='$username'"
                );
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
          return $row;
        }
      } else {
        return false;
      }
    } else {
      $stmt = $this->db->query(
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

  #$UserSystem->insertUserBlob("bob", "rmt9c84htnqy54h78tcy54hmgtx", "2step")
  #Would insert a 2-step user blob for "bob" with the code stated above
  public function insertUserBlob ($username, $hash, $action="session") {
    $time = time();
    $ip   = $_SERVER['REMOTE_ADDR'];
    $this->db->query(
      "INSERT INTO userblobs
      (user, code, action, date, ip) VALUES
      ('$username', '$hash', '$action', '$time', '$ip')"
    );
  }

  #$UserSystem->banCheck("127.0.0.1", "bob)
  #Would check if "bob" at "127.0.0.1" is banned
  public function checkBan ($ip, $username = false) {
    $stmt = $this->db->query("SELECT * FROM ban WHERE ip='$ip'");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      if ($row['appealed'] == 0) {
        $thing = true;
      } else {
        $thing = false;
      }
    }

    if ($username !== false) {
      $stmt = $this->db->query("SELECT * FROM ban WHERE username='$username'");
      while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['appealed'] == 0) {
          if ($thing === false) { $thing = true; } else {$thing = false; }
        } else {
          $thing = false;
        }
      }
    }

    return $thing;
  }

  #$UserSystem->verifySession($_COOKIE[$sitename])
  #Verifies the session with the stated code
  public function verifySession ($session = false) {
    if (!isset($_COOKIE[$this->OPTIONS['sitename']])) { return false; }
    if (!$session) { $session = $_COOKIE[$this->OPTIONS['sitename']]; }
    $time    = strtotime( '+30 days' );
    $stmt = $this->db->query(
              "SELECT * FROM userblobs
              WHERE code='$session' AND date<'$time' AND action='session'"
            );
    $rows = $stmt->rowCount();
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $username = $row['user'];
    }
    $tamper  = substr($session, -32);

    if ($rows === 1) {
      if (md5($username.substr($session, 0, 64)) === $tamper) {
        if (banCheck($_SERVER['REMOTE_ADDR']) == false) {
          return true;
        } else {
          return "ban";
        }
      } else {
        return "tamper";
        $this->db->query(
                  "DELETE FROM userblobs
                  WHERE code='$session' AND action='session' LIMIT 1"
              );
      }
    } else {
      return "session";
    }
  }

  #$UserSystem->activateUser("mrogjsruicyu78chsr87thmrsu")
  #Would activate the user to which this activation code belongs
  public function activateUser ($code) {

  }

  #$UserSystem->LogIn("Bob", "Bob's Password")
  #Would login Bob if "Bob's Password" was his actual password
  public function logIn ($username, $password) {

  }

  #$UserSystem->logOut($_COOKIE[$sitename], "Bob", true)
  #Would logout Bob's session by removing the user blob as well as the cookie
  public function logOut ($code, $user, $cursess = false, $all = false) {
    if (!$all) {
      $this->db->query(
        "DELETE FROM userblobs
        WHERE code='$code' AND user='$user' AND action='session'
        LIMIT 1"
      );
    } else {
      $this->db->query(
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
