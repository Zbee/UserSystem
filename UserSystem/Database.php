<?php
/**
* Class full of methods for dealing with databases effectively adn securely.
*
* @package    UserSystem
* @author     Ethan Henderson <ethan@zbee.me>
* @copyright  2015 Ethan Henderson
* @license    http://aol.nexua.org  AOL v0.1
* @link       https://github.com/zbee/usersystem
* @since      Class available since Release 0.59
*/
class Database extends Utils {
  /**
  * A shortcut for easily escaping a table/column name for PDO
  * Example: $UserSystem->dbIns(["users",["u"=>"Bob","e"=>"bob@ex.com"]])
  *
  * @access public
  * @param string $field
  * @return string
  */
  function quoteIdent ($field) {
    return "`".str_replace("`","``",$field)."`";
  }

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
    $data[0] = $this->quoteIdent(DB_PREFACE.$data[0]);
    $cols = "";
    $entries = "";
    $enArr = [];
    foreach ($dataArr as $item) {
      $cols .= $this->quoteIdent($item[0]).", ";
      $entries .= "?, ";
      array_push($enArr, $item[1]);
    }
    $cols = substr($cols, 0, -2);
    $entries = substr($entries, 0, -2);
    $stmt = $this->DATABASE->prepare("
      INSERT INTO $data[0] ($cols) VALUES ($entries)
    ");
    $stmt->execute($enArr);
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
    $data[0] = "`".DB_PREFACE.$data[0]."`";
    $update = "";
    $qArr = [];
    foreach ($dataArr as $item) {
      $update .= $this->quoteIdent($item[0])."=?, ";
      array_push($qArr, $item[1]);
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
      $equals .= $item[0]."=? AND ";
      array_push($qArr, $item[1]);
    }
    $equals = substr($equals, 0, -5);
    $update = substr($update, 0, -2);
    $stmt = $this->DATABASE->prepare("
      UPDATE $data[0] SET $update WHERE $equals
    ");
    $stmt->execute($qArr);
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
    $equals = "";
    $eqArr = [];
    foreach ($dataArr as $item) {
      $equals .= $this->quoteIdent($item[0])."=? AND ";
      array_push($eqArr, $item[1]);
    }
    $equals = substr($equals, 0, -5);
    $stmt = $this->DATABASE->prepare("
      DELETE FROM ".$this->quoteIdent(DB_PREFACE.$data[0])." WHERE $equals
    ");
    $stmt->execute($eqArr);
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
          $col,
          is_array($item) ? "@~#~@".$item[0]."~=exarg@@".$item[1] : $item
        ]
      );
    }
    $equals = '';
    $qmark = [];
    foreach ($dataArr as $item) {
      $diff = '=';
      if (substr($item[1], 0, 5) === "@~#~@") {
        $diff = explode("~=exarg@@", substr($item[1], 5))[0];
        $item[1] = explode("~=exarg@@", $item[1])[1];
      }
      $equals .= " AND ".$this->quoteIdent($item[0]).$diff."?";
      array_push($qmark, $item[1]);
    }
    $equals = substr($equals, 5);
    $stmt = $this->DATABASE->prepare("
      select * from ".$this->quoteIdent(DB_PREFACE.$data[0])." where $equals
    ");
    $stmt->execute($qmark);
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
    $table = "`".DB_PREFACE.$this->quoteIdent($table)."`";
    if (!$thing && !$answer) {
      $stmt = $this->DATABASE->query("SELECT * FROM $table");
    } else {
      $thing = $this->sanitize($thing, "q");
      $answer = $this->sanitize($answer, "q");
      $stmt = $this->DATABASE->prepare("SELECT * FROM $table WHERE $thing=?");
      $stmt->exectue([$answer]);
    }
    $rows =  (is_object($stmt)) ? $stmt->rowCount(): 0;
    return $rows;
  }
}
