<?php
/**
* Class full of utility methods for the UserSystem class.
*
* @package    UserSystem
* @author     Ethan Henderson <ethan@zbee.me>
* @copyright  2014 Ethan Henderson
* @license    http://aol.nexua.org  AOL v0.6
* @version    Release: 0.1
* @link       https://github.com/zbee/usersystem
* @since      Class available since Release 0.48
*/
class Utils {
  var $DATABASE = '';
  const OPTIONS = '';

  /**
  * Initializes the class and connects to the database and sets up options.
  * Example: $UserSystem = new UserSystem ($database, $opts)
  *
  * @access public
  * @param mixed $dbConn
  * @param mixed $opts
  * @return void
  */
  public function __construct ($dbConn, $opts) {
    if (!$dbConn) {
      $dbConn = [
        "location"=>"localhost",
        "database"=>"",
        "username"=>"root",
        "password" =>""
      ];
    }

    $this->DATABASE = new PDO(
      "mysql:host=$dbConn[location];dbname=$dbConn[database];
      charset=utf8", $dbConn['username'], $dbConn['password']
    );
    $this->OPTIONS = $opts;
  }

  /**
  * Gives the current url that the user is on.
  * Example: $UserSystem->currentURL()
  *
  * @access public
  * @return string
  */
  public function currentURL () {
    return "//$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
  }

  /**
  * Provides the proper headers to redirect a user, including a
  * page-has-moved flag.
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
    $key = hash('SHA256', $username, true);
    $initVector  = base64_decode(substr($encrypted, 0, 22) . '==');
    $encrypted = substr($encrypted, 22);
    $decrypted = rtrim(
    mcrypt_decrypt(
    MCRYPT_RIJNDAEL_128,
    $key,
    base64_decode($encrypted),
    MCRYPT_MODE_CBC,
    $initVector
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
  public function sanitize ($data, $type = 's') {
    $data = trim($data);

    if ($type == "n") {
      $data = filter_var($data, FILTER_SANITIZE_NUMBER_FLOAT);
      $data = preg_replace("/[^0-9]/", "", $data);
      return intval($data);
    } elseif ($type == "s") {
      $data = $this->handleUTF8($data);
      $data = filter_var($data, FILTER_SANITIZE_STRING);
      return filter_var($data, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    } elseif ($type == "d") {
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
    } elseif ($type == "h") {
      return $this->handleUTF8($data);
    } elseif ($type == "q") {
      $data = $this->handleUTF8($data);
      $bad = "drop table|show table|`|\*|--|1=1|1='1'|a=a|a='a'|not null|\\\\";
      $data = preg_replace(
      "/($bad)/i",
      "",
      $data
      );
      $data = filter_var($data, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
      return $data;
    } elseif ($type == "b") {
      $data = (filter_var($data, FILTER_VALIDATE_BOOLEAN)) ? $data : "fail";
      return $data;
    } elseif ($type == "u") {
      if (
      filter_var(
      filter_var(
      $data,
      FILTER_SANITIZE_URL
      ),
      FILTER_VALIDATE_URL
      )
      ===
      true
      ) {
        return $data;
      }
    }

    return "FAIL-Sanitization";
  }

  /**
  * A shortcut for eaily modifying the MySQL database, not necessarily easier,
  * but hits up
  * all required functions in the process.
  * Example: $UserSystem->dbMod(["i","users",["u"=>"Bob","e"=>"bob@ex.com"]])
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
      $this->DATABASE->query(
      "INSERT INTO $data[1] ($cols) VALUES ('$entries)"
      );
      return true;
      case "u":
      $update = "";
      foreach ($dataArr as $item) {
        $update .= "`".$this->sanitize($item[0], "q").
        "`='".$this->sanitize($item[1], "q")."', ";
      }
      $equalsArr = [];
      foreach ($data[3] as $item) {
        $col = array_search($item, $data[3]);
        array_push(
        $equalsArr,
        [
        $this->sanitize($col, "q"),
        $this->sanitize($item, "q")
        ]
        );
      }
      $equals = "";
      foreach ($equalsArr as $item) {
        $equals .= "`".$item[0]."`='".$item[1]."' AND ";
      }
      $equals = substr($equals, 0, -5);
      $update = substr($update, 0, -2);
      $this->DATABASE->query("UPDATE $data[1] SET $update WHERE $equals");
      return true;
      case "d":
      $equals = "";
      foreach ($dataArr as $item) {
        $equals .= "`".$this->sanitize($item[0], "q").
        "`='".$this->sanitize($item[1], "q")."' AND ";
      }
      $equals = substr($equals, 0, -5);
      $this->DATABASE->query("DELETE FROM $data[1] WHERE $equals");
      return true;
      default:
      return false;
    }
  }

  /**
  * Returns an array for the database search performed, again, just a shortcut
  * for hitting required functions
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
      array_push(
        $dataArr,
        [
          $this->sanitize($col, "q"),
          is_array($item) ? "@~#~@".$item[0]."~=exarg@@".$this->sanitize($item[1], "q") : $this->sanitize($item, "q")
        ]
      );
    }
    $equals = '';
    foreach ($dataArr as $item) {
      $ex = '=';
      if (substr($item[1], 0, 5) === "@~#~@") {
        $ex = explode("~=exarg@@", substr($item[1], 5))[0];
        $item[1] = explode("~=exarg@@", $item[1])[1];
      }
      $equals .= " AND `".$item[0]."`".$ex."'".$item[1]."'";
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
  * Should follow pattern of dbMod() so as to support more $thing/$answer
  * combos.
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
      $stmt = $this->DATABASE->query(
      "SELECT * FROM $table WHERE $thing='$answer'"
      );
    }
    $rows =  (is_object($stmt)) ? $stmt->rowCount(): 0;
    return $rows;
  }
}
