<?php
/**
* Class full of utility methods for the UserSystem class.
*
* @package    UserSystem
* @author     Ethan Henderson <ethan@zbee.me>
* @copyright  Copyright 2014-2015 Ethan Henderson
* @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License
* @link       https://github.com/zbee/usersystem
* @since      Class available since Release 0.48
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

class Utils {

  var $DATABASE = "";

  /**
  * Initializes the class and connects to the database.
  * Example: $UserSystem = new UserSystem ("")
  *
  * @access public
  * @param string $dbname
  * @return void
  */
  public function __construct ($dbname = DB_DATABASE) {
    try {
      $this->DATABASE = new PDO(
        "mysql:host=".DB_LOCATION.";dbname=".$dbname.";charset=utf8",
        DB_USERNAME,
        DB_PASSWORD
      );
    } catch(PDOException $ex) {
      $pdo = $ex;
    }

    if (!is_object($this->DATABASE) || isset($pdo)) {
      throw new Exception (
        "DB_* constants in config.php failed to connect to a database. " . $pdo
      );
    }
  }

  /**
  * Gets the IP of the current user
  * Example: $UserSystem->getIP()
  *
  * @access public
  * @return mixed
  */
  public function getIP () {
    $srcs = [
      'HTTP_CLIENT_IP',
      'HTTP_X_FORWARDED_FOR',
      'HTTP_X_FORWARDED',
      'HTTP_X_CLUSTER_CLIENT_IP',
      'HTTP_FORWARDED_FOR',
      'HTTP_FORWARDED',
      'REMOTE_ADDR'
    ];
    foreach ($srcs as $key)
      if (array_key_exists($key, $_SERVER) === true)
        foreach (explode(',', $_SERVER[$key]) as $ip)
          if (filter_var($ip, FILTER_VALIDATE_IP) !== false) return $ip;
    return false;
  }

  /**
  * Gives the current url that the user is on.
  * Example: $UserSystem->currentURL()
  *
  * @access public
  * @return string
  */
  public function currentURL () {
    $domain = htmlspecialchars(
      $_SERVER['HTTP_HOST'],
      ENT_QUOTES,
      "utf-8"
    );
    $page = htmlspecialchars(
      $_SERVER['REQUEST_URI'],
      ENT_QUOTES,
      "utf-8"
    );
    $http = isset($_SERVER['HTTPS']) ? "https" : "http";
    return "$http://$domain$page";
  }

  /**
  * Provides the proper headers to redirect a user, including a page-has-moved
  * flag.
  * Example: $UserSystem->redirect301("http://example.com")
  *
  * @access public
  * @param string $url
  * @param int $code
  * @return boolean
  */
  public function redirect301($url, $code = 301) {
    if (!headers_sent()) {
      $code = $this->sanitize($code, "n");
      $statCodes = [ #http://en.wikipedia.org/wiki/List_of_HTTP_status_codes
        100 => "100 Continue",
        101 => "101 Switching Protocols",
        102 => "102 Processing",
        200 => "200 OK",
        202 => "201 Created",
        203 => "203 Non-Authoritative Information",
        204 => "204 No Content",
        205 => "205 Reset Content",
        206 => "206 Partial Content",
        207 => "207 Multi-Status",
        208 => "208 Already Reported",
        226 => "226 IM Used",
        300 => "300 Multiple Choices",
        301 => "301 Moved Permanently",
        302 => "302 Found",
        303 => "303 See Other",
        304 => "304 Not Modified",
        305 => "305 Use Proxy",
        306 => "306 Switch Proxy",
        307 => "307 Temporary Redirect",
        308 => "308 Permanent Redirect",
        400 => "400 Bad Request",
        401 => "401 Unauthorized",
        402 => "402 Payment Required",
        403 => "403 Forbidden",
        404 => "404 Not Found",
        405 => "405 Method Not Allowed",
        406 => "406 Not Acceptable",
        407 => "407 Proxy Authentication Required",
        408 => "408 Request Timeout",
        409 => "409 Conflict",
        410 => "410 Gone",
        411 => "411 Length Required",
        412 => "412 Precondition Failed",
        413 => "413 Request Entity Too Large",
        414 => "414 Request-URI Too Long",
        415 => "415 Unsupported Media Type",
        416 => "416 Requested Range Not Satisfiable",
        417 => "417 Expectation Failed",
        418 => "418 I'm a teapot",
        419 => "419 Authentication Timeout",
        "420s" => "420 Method Failure",
        "420t" => "420 Enhance Your Calm",
        421 => "421 Misdirected Request",
        422 => "422 Unprocessable Entity",
        423 => "423 Locked",
        424 => "424 Failed Dependency",
        426 => "426 Upgrade Required",
        428 => "428 Precondition Required",
        429 => "429 Too Many Requests",
        431 => "431 Request Header Fields Too Large",
        440 => "440 Login Timeout",
        444 => "444 No Response",
        449 => "449 Retry With",
        450 => "450 Blocked by Windows Parental Controls",
        "451l" => "451 Unavailable For Legal Reasons",
        "451m" => "451 Redirect",
        494 => "494 Request Header Too Large",
        495 => "495 Cert Error",
        496 => "496 No Cert",
        497 => "497 HTTP to HTTPS",
        498 => "498 Token expired/invalid",
        "499n" => "499 Client Closed Request",
        "499a" => "499 Token required",
        500 => "500 Internal Server Error",
        501 => "501 Not Implemented",
        502 => "502 Bad Gateway",
        503 => "503 Service Unavailable",
        504 => "504 Gateway Timeout",
        505 => "505 HTTP Version Not Supported",
        506 => "506 Variant Also Negotiates",
        507 => "507 Insufficient Storage",
        508 => "508 Loop Detected",
        509 => "509 Bandwidth Limit Exceeded",
        510 => "510 Not Extended",
        511 => "511 Network Authentication Required",
        598 => "598 Network read timeout error",
        599 => "599 Network connect timeout error"
      ];
      if (!isset($statCodes[$code])) $code = 301;
      header("HTTP/1.1 " . $statCodes[$code]);
      header("Location: $url");
      return true;
    }
    return false;
  }

  /**
  * Generates a more secure random number
  * Example: $UserSystem->openssl_rand(0, 100)
  *
  * @access public
  * @param int min
  * @param int max
  * @return int
  */
  function opensslRand($min = 0, $max = 1000) {
    $range = $max - $min;
    if ($range < 1) return $min;
    $log = log($range, 2);
    $bytes = (int) ($log / 8) + 1;
    $bits = (int) $log + 1;
    $filter = (int) (1 << $bits) - 1;
    do {
      $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
      $rnd = $rnd & $filter;
    } while ($rnd >= $range);
    return $min + $rnd;
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
      $username
      . time()*sqrt(strlen($username.DOMAIN))
      . ($str = substr(
        str_shuffle(
          str_repeat(
            "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"
            . "`~0123456789!@$%^&*()-_+={}[]\\|:;'\"<,>."
            . bin2hex(openssl_random_pseudo_bytes(64)),
            $this->opensslRand(32, 64+strlen(SITENAME))
          )
        ),
        1,
        $this->opensslRand(2048, 8192)
      ))
      . ($strt = bin2hex(openssl_random_pseudo_bytes(strlen($str)/8)))
      . strlen($strt)*$this->opensslRand(4, 128)
      . $this->getIP()
    );
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
      return mb_convert_encoding($utf8, 'HTML-ENTITIES', 'UTF-8');
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
    if ($type == "n") {
      $data = filter_var(trim($data), FILTER_SANITIZE_NUMBER_FLOAT);
      $data = preg_replace("/[^0-9]/", "", $data);
      return intval($data);
    } elseif ($type == "s") {
      $data = $this->handleUTF8($data);
      $data = filter_var($data, FILTER_SANITIZE_STRING);
      return filter_var($data, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    } elseif ($type == "d") {
      $data = preg_replace("/[^0-9\-\s\+a-zA-Z]/", "", trim($data));
      if (is_numeric($data) !== true) $data = strtotime($data);
      $month = date("n", $data);
      $day = date("j", $data);
      $year = date("Y", $data);
      if (checkdate($month, $day, $year)) return $data;
    } elseif ($type == "h") {
      return $this->handleUTF8(trim($data));
    } elseif ($type == "q") {
      $data = htmlentities($this->handleUTF8($data));
      return $data;
    } elseif ($type == "b") {
      if ($data === true || $data === false) return $data;
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
        $data
      ) return $data;
    } elseif ($type == "i") {
      if (filter_var($data, FILTER_VALIDATE_IP) !== false) return $data;
    } elseif ($type == "e") {
      if (
        filter_var(
          filter_var(
            $data,
            FILTER_SANITIZE_EMAIL
          ),
          FILTER_VALIDATE_EMAIL
        )
        ===
        $data
      ) return $data;
    }

    return "FAILED";
  }


  /**
  * Sends properly formatted emails out from the system to many or just one user
  * Example: $UserSystem->sendMail(["bob@ex.com", "rob@ex.com"], "Hi", "Hello!")
  *
  * @access public
  * @param mixed recipient
  * @param string subject
  * @param string message
  * @return bool
  */
  public function sendMail ($recipient, $subject, $message) {
    $recipients = "";
    if (is_array($recipient)) {
      foreach ($recipient as $r) $recipients .= $this->sanitize($r, "e") . ", ";
    } else {
      $recipients = $this->sanitize($recipient, "e");
    }
    $recipient = $this->sanitize($recipients, "s");
    $subject = $this->sanitize($subject, "s");
    $headers = 'From: noreply@'.DOMAIN."\n" .
    'Reply-To: support@'.DOMAIN."\n" .
    'X-Mailer: PHP/'.phpversion();
    return mail($recipient, $subject, $message, $headers);
  }
}
