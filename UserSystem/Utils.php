<?php
/**
* Class full of utility methods for the UserSystem class.
*
* @package    UserSystem
* @author     Ethan Henderson <ethan@zbee.me>
* @copyright  2015 Ethan Henderson
* @license    http://aol.nexua.org  AOL v0.6
* @link       https://github.com/zbee/usersystem
* @since      Class available since Release 0.48
*/
class Utils {
  var $DATABASE = '';

  /**
  * Initializes the class and connects to the database.
  * Example: $UserSystem = new UserSystem ()
  *
  * @access public
  * @param string $database
  * @return void
  */
  public function __construct ($dbname = DB_DATABASE) {
    $this->DATABASE = new PDO(
      "mysql:host=".DB_LOCATION.";dbname=".$dbname.";charset=utf8",
      DB_USERNAME,
      DB_PASSWORD
    );

    if (!is_object($this->DATABASE)) {
      throw new Exception ("DB_* constants failed to connect to a database.");
    }
  }

  /**
  * Gives the current url that the user is on.
  * Example: $UserSystem->currentURL()
  *
  * @access public
  * @return string
  */
  public function currentURL () {
    $hh = htmlspecialchars(
      $_SERVER['HTTP_HOST'],
      ENT_QUOTES,
      "utf-8"
    );
    $ri = htmlspecialchars(
      $_SERVER['REQUEST_URI'],
      ENT_QUOTES,
      "utf-8"
    );
    return (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$hh$ri";
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
    $key = hash('SHA256', $username, true);
    srand();
    $initVector = mcrypt_create_iv(
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
    $hash = substr($decrypted, -32);
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
    },
    $code);
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
  public function sanitize ($data, $type = "s") {
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
      $data = htmlentities($this->handleUTF8($data));
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

    return "FAILED SANITIZATION";
  }

  public function sendMail ($recipient, $subject, $message) {
    $recipient = $this->sanitize($recipient, "s");
    $subject = $this->sanitize($subject, "s");
    $headers = 'From: noreply@'.DOMAIN_SIMPLE."\r\n" .
    'Reply-To: support@'.DOMAIN_SIMPLE."\r\n" .
    'X-Mailer: PHP/'.phpversion();
    mail($recipient, $subject, $message, $headers);
  }
}
