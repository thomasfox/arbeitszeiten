<?php
abstract class ColumnInfo
{
  public $databaseName; // name of the value column in the database. For "multicolumn": Column is in the foreign table. For others: column is in the current table.
  
  public $displayName; // A human readable label for the Value in the database column
  
  public $required = false;
  
  public $datatype = "s"; // one of "s" (String), "i" (Integer), "d" (Date) or "t" (Time)

  public $foreignTable; // foreign table containing the displayed value
  
  public $foreignColumn;// for multicolumn: foreign-key-column in the foreign table containing the id of this table's row
                        // for dropdown or text: foreign-key-column in this table containing the id of the foreign table's row
  
  public $foreignType; // possible values are "dropdown" for selection in a singleSelect, 
					   // "multicolumn" for creating several entries at once associated with certain values in the foreign tables
					   // "nToM" for a n-to-m-relationship between two tables

  public $columnValuesTable; // for "multicolumn" and "nToM" only
  
  public $columnValuesDescriptionColumn; // for "multicolumn" only
  
  public $foreignTableReferenceColumn; // for "multicolumn" only, contains the join column in the foreign table

  function __construct()
  {
    $args = func_get_args();
	$this->databaseName = $args[0];
	if (count($args) > 1)
	{
	  $this->displayName = $args[1];
	}
	if (count($args) > 2)
	{
	  $this->required = $args[2];
	}
	if (count($args) > 3)
	{
	  $this->datatype = $args[3];
	}
    if (count($args) > 4)
    {
      $this->foreignTable = $args[4];
      $this->foreignColumn = $args[5];
	  if ($args[6] != "dropdown" && $args[6] != "text" && $args[6] != "multicolumn" && $args[6] != "nToM")
	  {
		throw new Exception('foreignType must be one of "dropdown" or "text" or "multicolumn"');
	  }
      $this->foreignType = $args[6];
	  if ($args[6] == "multicolumn" || $args[6] == "nToM")
	  {
		$this->columnValuesTable = $args[7];
	  }
	  if ($args[6] == "multicolumn")
	  {
		$this->columnValuesDescriptionColumn = $args[8];
		$this->foreignTableReferenceColumn = $args[9];
	  }
	}
  }
  
  function getDisplayName()
  {
	if (isset($this->displayName))
	return $this->displayName;
    return $this->databaseName;
  }
  
  abstract function addToDatabaseNames();

  static function getDatabaseNames($columnInfos)
  {
    $result = array();
    foreach ($columnInfos as $columnInfo)
    {
	  if ($columnInfo->addToDatabaseNames())
	  {
        array_push($result, $columnInfo->databaseName);
	  }
    }
    return $result;
  }
  
  /**
   * Returns the select options for a column, as array($key=>$DisplayName).
   * If the column does not have any select options, returns null.
   *
   * @param $conn the mysql connection.
   */
  public abstract function getSelectOptions($conn);
  
  protected function querySelectOptions($descriptionColumn, $table, $conn)
  {
    $sql = "SELECT id," . $descriptionColumn . " FROM " . $table . " ORDER BY id ASC";
    $result = $conn->query($sql);
    if ($conn->errno == 0)
    {
   	  $selectOptions = array();
      while ($row = $result->fetch_assoc()) 
      {
        $selectOptions[$row["id"]] = $row[$descriptionColumn];
      }
    }
    else
    {
      echo "error for " . $sql . ":" . $conn->error . "<br>";
	}
	return $selectOptions;
  }
  
  /**
   * Returns the select options for a column, as array(id of record in this table => array(id of selectOption => value))
   * If the column does not have any select options, returns null.
   *
   * @param $tableName the name of the table which contains the displayed rows.
   * @param $conn the mysql connection.
   */
  public abstract function getMulticolumnValues($tableName, $conn);
}

class SimpleValueColumn extends ColumnInfo
{
  function __construct($databaseName, $displayName, $required, $dataType = "s")
  {
	parent::__construct($databaseName, $displayName, $required, $dataType);
  }
  
  function addToDatabaseNames()
  {
	return true;
  }
  
  function getSelectOptions($conn)
  {
	return null;
  }
  
  function getMulticolumnValues($tableName, $conn)
  {
	return null;
  }
}

class DropdownColumn extends ColumnInfo
{
  function __construct($databaseName, $displayName, $required, $dataType, $foreignTable, $foreignColumn)
  {
	parent::__construct($databaseName, $displayName, $required, $dataType, $foreignTable, $foreignColumn, "dropdown");
  }
  
  function addToDatabaseNames()
  {
	return true;
  }
  
  function getSelectOptions($conn)
  {
	return $this->querySelectOptions($this->foreignColumn, $this->foreignTable, $conn);
  }
  
  function getMulticolumnValues($tableName, $conn)
  {
	return null;
  }
}

class StringMulticolumn extends ColumnInfo
{
  function __construct($databaseName, $displayName, $required, $dataType, $foreignTable, $foreignColumn, $columnValuesTable, $columnValuesDescriptionColumn, $foreignTableReferenceColumn)
  {
	parent::__construct($databaseName, $displayName, $required, $dataType, $foreignTable, $foreignColumn, "multicolumn", $columnValuesTable, $columnValuesDescriptionColumn, $foreignTableReferenceColumn);
  }
  
  function addToDatabaseNames()
  {
	return false;
  }
   
  function getSelectOptions($conn)
  {
	return $this->querySelectOptions($this->columnValuesDescriptionColumn, $this->columnValuesTable, $conn);
  }
  
  function getMulticolumnValues($tableName, $conn)
  {
    $columnsToSelect = $tableName . ".id as id, " 
	  . $this->foreignTable . "." . $this->foreignTableReferenceColumn . " as columnid," 
      . $this->foreignTable . '.id as foreignid,'
      . $this->foreignTable . '.' . $this->databaseName. " as foreignvalue";
    $fromClause = $tableName . " JOIN " . $this->foreignTable . " ON " . $tableName . ".id=" . $this->foreignTable . "." . $this->foreignColumn;
    $sql = "SELECT " . $columnsToSelect . " FROM " . $fromClause . " ORDER BY id,foreignid ASC";
    $result = $conn->query($sql);
    if ($conn->errno == 0)
    {
      $valuesForColumn = array();
      while ($row = $result->fetch_assoc()) 
      {
        if (!isset($valuesForColumn[$row["id"]]))
        {
          $valuesForColumn[$row["id"]] = array(); 
        }
        $valuesForColumn[$row["id"]][$row["columnid"]] = $row["foreignvalue"];
	  }
	  return $valuesForColumn;
    }
    else
    {
      echo "error for " . $sql . ":" . $conn->error . "<br>";
    }
  }
}

class CheckboxMulticolumn extends ColumnInfo
{
  function __construct($databaseName, $displayName, $required, $dataType, $foreignTable, $foreignColumn, $columnValuesTable)
  {
	parent::__construct($databaseName, $displayName, $required, $dataType, $foreignTable, $foreignColumn, "nToM", $columnValuesTable);
  }

  function addToDatabaseNames()
  {
	return false;
  }
  
  function getSelectOptions($conn)
  {
	return $this->querySelectOptions($this->foreignColumn, $this->foreignTable, $conn);
  }

  function getMulticolumnValues($tableName, $conn)
  {
    $columnsToSelect = $tableName . "_id, " . $this->foreignTable . "_id";
    $sql = "SELECT " . $columnsToSelect . " FROM " . $this->columnValuesTable;
    $result = $conn->query($sql);
    if ($conn->errno == 0)
    {
      $valuesForColumn = array();
      while ($row = $result->fetch_assoc()) 
      {
        if (!isset($valuesForColumn[$row[$tableName . "_id"]]))
        {
          $valuesForColumn[$row[$tableName . "_id"]] = array(); 
        }
        $valuesForColumn[$row[$tableName . "_id"]][$row[$this->foreignTable . "_id"]] = "1";
      }
      return $valuesForColumn;
    }
    else
    {
      echo "error for " . $sql . ":" . $conn->error . "<br>";
    }
  }
}

?>