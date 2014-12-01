<?php
/**
* Class full of methods for dealing with databases effectively adn securely.
*
* @package    UserSystem
* @author     Ethan Henderson <ethan@zbee.me>
* @copyright  2014 Ethan Henderson
* @license    http://aol.nexua.org  AOL v0.1
* @version    Release: 0.1
* @link       https://github.com/zbee/usersystem
* @since      Class available since Release 0.59
*/
class Database extends Utils {
  /*
  Include sanitization functions for database stuff (sanitization + escaping)
  Split dbMod into different sections (dbIns, dbDel, etc)
  Include dbSel
  */

  /**
  * A shortcut for eaily inserting a new item into a database.
  * Example: $UserSystem->dbIns(["users",["u"=>"Bob","e"=>"bob@ex.com"]])
  *
  * @access public
  * @param array $data
  * @return boolean
  */
  public function dbIns ($data) {
    $dataArr = [];
    foreach ($data[1] as $item) {
      $col = array_search($item, $data[1]);
      array_push($dataArr, [$col, $item]);
    }
    $data[0] = DB_PREFACE.$this->sanitize($data[0], "q");
    $cols = "";
    $entries = "";
    foreach ($dataArr as $item) {
      $cols .= $this->sanitize($item[0], "q").", ";
      $entries .= $this->sanitize($item[1], "q")."', '";
    }
    $cols = substr($cols, 0, -2);
    $entries = substr($entries, 0, -4);
    $this->DATABASE->query(
      "INSERT INTO `$data[0]` ($cols) VALUES ('$entries)"
    );
    return true;
  }


  /**
  * A shortcut for eaily updating an item into a database.
  * Example: $UserSystem->dbUpd(["users",[e"=>"bob@ex.com"],["u"=>"Bob"]])
  *
  * @access public
  * @param array $data
  * @return boolean
  */
  public function dbUpd ($data) {
    $dataArr = [];
    foreach ($data[1] as $item) {
      $col = array_search($item, $data[1]);
      array_push($dataArr, [$col, $item]);
    }
    $data[0] = DB_PREFACE.$this->sanitize($data[0], "q");
    $update = "";
    foreach ($dataArr as $item) {
      $update .= "`".$this->sanitize($item[0], "q").
      "`='".$this->sanitize($item[1], "q")."', ";
    }
    $equalsArr = [];
    foreach ($data[2] as $item) {
      $col = array_search($item, $data[2]);
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
    $this->DATABASE->query("UPDATE `$data[0]` SET $update WHERE $equals");
    return true;
  }


  /**
  * A shortcut for eaily deleting an item in a database.
  * Example: $UserSystem->dbDel(["users",["u"=>"Bob"]])
  *
  * @access public
  * @param array $data
  * @return boolean
  */
  public function dbDel ($data) {
    $dataArr = [];
    foreach ($data[1] as $item) {
      $col = array_search($item, $data[1]);
      array_push($dataArr, [$col, $item]);
    }
    $data[0] = DB_PREFACE.$this->sanitize($data[0], "q");
    $equals = "";
    foreach ($dataArr as $item) {
      $equals .= "`".$this->sanitize($item[0], "q").
      "`='".$this->sanitize($item[1], "q")."' AND ";
    }
    $equals = substr($equals, 0, -5);
    $this->DATABASE->query("DELETE FROM `$data[0]` WHERE $equals");
    return true;
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
          is_array($item) ? "@~#~@".$item[0]."~=exarg@@".
          $this->sanitize($item[1], "q") : $this->sanitize($item, "q")
        ]
      );
    }
    $equals = '';
    foreach ($dataArr as $item) {
      $diff = '=';
      if (substr($item[1], 0, 5) === "@~#~@") {
        $diff = explode("~=exarg@@", substr($item[1], 5))[0];
        $item[1] = explode("~=exarg@@", $item[1])[1];
      }
      $equals .= " AND `".$item[0]."`".$diff."'".$item[1]."'";
    }
    $equals = substr($equals, 5);
    $stmt = $this->DATABASE->query("
    SELECT * from `".DB_PREFACE."$data[0]` WHERE $equals
    ");
    $arr = [(is_object($stmt) ? $stmt->rowCount() : 0)];
    if ($arr[0] > 0) {
      while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        array_push($arr, $row);
      }
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
    $table = DB_PREFACE.$this->sanitize($table, "q");
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
