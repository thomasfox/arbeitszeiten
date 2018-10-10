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
  
  public static function getColumnsOfMainTable($columnInfos)
  {
    $result = array();
    foreach ($columnInfos as $columnInfo)
    {
	  if ($columnInfo->addToMainTableColumns())
	  {
        array_push($result, $columnInfo->databaseName);
	  }
    }
    return $result;
  }
  
  /**
   * Returns true if this ColumnInfo is represented by a database column in the main table of the displayed data, false otherwise.
   */
  public abstract function addToMainTableColumns();

  /**
   * Returns the select options for a column, as array($key=>$DisplayName).
   * If the column does not have any select options, returns null.
   *
   * @param $conn the mysql connection.
   */
  public abstract function getSelectOptions($conn);
  
  /**
   * Returns the select options for a column, as array(id of record in this table => array(id of selectOption => value))
   * If the column does not have any select options, returns null.
   *
   * @param $tableName the name of the table which contains the displayed rows.
   * @param $conn the mysql connection.
   */
  public abstract function getMulticolumnValues($tableName, $conn);
  
  /**
   * Prints the column headers for the given options.
   *
   * @param $optionsToSelectFrom the options for this column, as array(column's database name => array(optionKey => optiondisplayName)).
   */   
  public abstract function printColumnHeaders($optionsToSelectFrom);
  
  /**
   * Prints the columns of one database row.
   *
   * @param $row the database row's content, as array(databaseColumnName => databaseValue).
   * @param $optionsToSelectFrom the options for this column, as array(column's database name => array(optionKey => optiondisplayName)).
   * @param $valuesForMulticolumns values for the multicolumn, as array(column's database name => array(id of record in this table => array(id of selectOption => value)).
   */   
  public abstract function printColumnsForRow($row, $optionsToSelectFrom, $valuesForMulticolumns);

  /**
   * Prints the columns for the row where a new database row can be created.
   *
   * @param $optionsToSelectFrom the options for this column, as array(column's database name => array(optionKey => optiondisplayName)).
   */   
  public abstract function printColumnsForNewRow($optionsToSelectFrom);
  
  /**
   * Performs any necessary modifications to a submitted value to be stored in the database, and returns the database value.
   *
   * @param submittedValue the value submitted by the user, already trimmed.   
   * @param &$validationFailed flag which is set to true if a validation error occurs.
   */
  public abstract function getDatabaseValue($submittedValue, &$validationFailed);

  /**
   * Determines the values to insert and stores them in &$insertedValues and &$insertedMulticolumnValues, respectively.
   * If validation errors occur, they are printed and &$validationFailed is set to true.
   *
   * @param $postData the data submitted by the user
   * @param &$insertedValues the values to be inserted into the column's table, in the form (databaseName of column => valueToSave)
   * @param &$multicolumnValuesToInsert the values to be inserted in the column's referenced table, in the form (databaseName of column => array(optionKey => valueToSave))
   * @param &$validationFailed flag which is set to true if a validation error occurs.
   * @param $conn the mysql database connection.
   */
  public abstract function getValuesToInsert($postData, &$insertedValues, &$multicolumnValuesToInsert, &$validationFailed, $conn);
  
  /**
   * Inserts values in the referenced foreign table.
   *
   * @param $multicolumnValuesToInsert the values to insert for the options, in the form array (databaseName of column => array(optionKey => valueToSave))
   * @param $tableNameForRow the name of the table in which the inserted row is located.
   * @param $idOfMainRow the id of the dataset in the table representing the inserted row. 
   * @param $conn the database connection.
   */   
  public abstract function insertMulticolumnValues($multicolumnValuesToInsert, $tableNameForRow, $idOfRow, $conn);
  
  /**
   * Returns the values which need to be updated for a single row in the table representing the row.
   *
   * @param &$valuesToUpdate the values to be updated in the table corresponding to the form ($database name of column => value)
   * @param &$foreignValuesToUpdate the values to be updated in the foreign table referenced by the column, in the form ($database name of column => custom format)
   * @param $postData the values submitted by the user.
   * @param $row the current values in the database of the considered row. 
   * @param $optionsToSelectFrom the options for this column, as array(column's database name => array(optionKey => optiondisplayName)).
   * @param $valuesForMulticolumns values for the multicolumn, as array(column's database name => array(id of record in this table => array(id of selectOption => value)).
   * @param &$validationFailed flag which is true in case of a validation error and false otherwise.
   *
   */
  public abstract function fillValuesToUpdate(&$valuesToUpdate, &$foreignValuesToUpdate, $postData, $row, $optionsToSelectFrom, $valuesForMulticolumns, &$validationFailed);
  
  public abstract function updateForeignValues($tableName, $foreignValuesToUpdate, $rowId, $conn);

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
}

abstract class SingleColumn extends ColumnInfo
{
  function addToMainTableColumns()
  {
	return true;
  }

  function getMulticolumnValues($tableName, $conn)
  {
	return null;
  }  

  function getValuesToInsert($postData, &$insertedValues, &$multicolumnValuesToInsert, &$validationFailed, $conn)
  {
	$valueToInsert = "";
	if (isset($postData[$this->databaseName]))
	{
	  $valueToInsert = trim($postData[$this->databaseName]);
	}
	if (!empty($valueToInsert))
	{
	  $insertedValues[$this->databaseName] = $this->getDatabaseValue($valueToInsert, $validationFailed);
	}
  }

  function insertMulticolumnValues($multicolumnValuesToInsert, $tableNameForRow, $idOfRow, $conn)
  {
  }

  function fillValuesToUpdate(&$valuesToUpdate, &$foreignValuesToUpdate, $postData, $row, $optionsToSelectFrom, $valuesForMulticolumns, &$validationFailed)
  {
	$id = $row["id"];
    $submittedValue = trim($postData[$this->databaseName . $id]);
    if ($this->required && empty($submittedValue))
    {
	  echo "Die Spalte " . $this->displayName . " in Datensatz Nr. " . $id . " ist ein Pflichtfeld und muss ausgefüllt werden. Der Datensatz wurde nicht gespeichert.<br/>";
	  $validationFailed = true;
	  return;
    }
    $dbValue = $row[$this->databaseName];	  
    {
	  if ($dbValue != $submittedValue)
	  {
	    if (!empty($submittedValue))
	    {
		  $valuesToUpdate[$this->databaseName] = $this->getDatabaseValue($submittedValue, $validationFailed);
	    }
	    else
	    {
		  $valuesToUpdate[$this->databaseName] = null;
	    }
	  }
    }
  }
  
  function updateForeignValues($tableName, $foreignValuesToUpdate, $rowId, $conn)
  {
  }
}

class SimpleValueColumn extends SingleColumn
{
  function __construct($databaseName, $displayName, $required, $dataType = "s")
  {
	parent::__construct($databaseName, $displayName, $required, $dataType);
  }
  
  function getSelectOptions($conn)
  {
	return null;
  }
  
  function printColumnHeaders($optionsToSelectFrom)
  {
    echo "<td>" . $this->getDisplayName() . "</td>";
  }
  
  function printColumnsForRow($row, $optionsToSelectFrom, $valuesForMulticolumns)
  {
	$id = $row["id"];
    $value = $row[$this->databaseName];
    if ($this->datatype == "d" and !empty($value))
    {
      $value = DateTime::createFromFormat("Y-m-d", $value)->format("d.m.Y");
    }
    echo '<td><input name="'. $this->databaseName . $id . '" value="' . $value . '" /></td>';
  }
  
  function printColumnsForNewRow($optionsToSelectFrom)
  {
    echo '<td><input name="'. $this->databaseName . '"/></td>';
  }

  function getDatabaseValue($submittedValue, &$validationFailed)
  {
	if ($this->datatype == "d" && !empty($submittedValue))
	{
	  $valueAsDate = DateTime::createFromFormat("d.m.Y", $submittedValue);
	  if ($valueAsDate == false)
	  {
		echo "Die Spalte " . $this->displayName . " in Datensatz Nr. " . $id . " hat ein ungültiges Datumsformat.  Bitte verwenden Die das Format TT.MM.JJJJ. Der Datensatz wurde nicht gespeichert.<br/>";
		$validationFailed = true;
		return null;
	  }
	  return $valueAsDate->format("Y-m-d");
	}
	else
	{
	  return $submittedValue;
	}
  }
}

class DropdownColumn extends SingleColumn
{
  function __construct($databaseName, $displayName, $required, $dataType, $foreignTable, $foreignColumn)
  {
	parent::__construct($databaseName, $displayName, $required, $dataType, $foreignTable, $foreignColumn, "dropdown");
  }
   
  function getSelectOptions($conn)
  {
	return $this->querySelectOptions($this->foreignColumn, $this->foreignTable, $conn);
  }
  
  function printColumnHeaders($optionsToSelectFrom)
  {
    echo "<td>" . $this->getDisplayName() . "</td>";
  }

  function printColumnsForRow($row, $optionsToSelectFrom, $valuesForMulticolumns)
  {
	$id = $row["id"];
    $value = $row[$this->databaseName];
    echo '<td><select name="'. $this->databaseName . $id . '">';
    echo '<option value=""></option>"';
    $optionsForColumn = $optionsToSelectFrom[$this->databaseName];
    foreach ($optionsForColumn as $optionId => $optionDisplayName)
    {
	  $selectedString = "";
	  if ($value == $optionId)
	  {
	    $selectedString = ' selected="selected"';
      }
	  echo '<option value="' . $optionId . '"' . $selectedString . '>' . $optionDisplayName . '</option>';
    }
    echo '</select></td>';
  }
  
  function printColumnsForNewRow($optionsToSelectFrom)
  {
    echo '<td><select name="'. $this->databaseName . '">"';
    echo '<option value=""></option>"';
    $optionsForColumn = $optionsToSelectFrom[$this->databaseName];
    foreach ($optionsForColumn as $optionId=>$optionDisplayName)
    {
	  echo '<option value="' . $optionId . '">' . $optionDisplayName . '</option>"';
    }
    echo '</select></td>';
  }

  function getDatabaseValue($submittedValue, &$validationFailed)
  {
    return $submittedValue;
  }
}

abstract class Multicolumn extends ColumnInfo
{
  protected function getMulticolumnValuesToInsert($postData, &$multicolumnValuesToInsert, &$validationFailed, $conn)
  {
    $optionsForRow = $this->getSelectOptions($conn);
	foreach ($optionsForRow as $optionId => $optionDisplayName)
	{
	  $inputName = $this->databaseName . '_' . $optionId;
	  $submittedValue = "";
	  if (isset($postData[$inputName]))
	  {
	    $submittedValue = trim($postData[$inputName]);
	  }
      if (!empty($submittedValue))
	  {
	    if (!isset($multicolumnValuesToInsert[$this->databaseName]))
	    {
	      $multicolumnValuesToInsert[$this->databaseName] = array();
		}
	    $multicolumnValuesToInsert[$this->databaseName][$optionId] = $submittedValue;
	  }
	}
  }
  
  function getDatabaseValue($submittedValue, &$validationFailed)
  {
    return $submittedValue;
  }
  
  function fillValuesToUpdate(&$valuesToUpdate, &$foreignValuesToUpdate, $postData, $row, $optionsToSelectFrom, $valuesForMulticolumns, &$validationFailed)
  {
    $optionsForColumn = $optionsToSelectFrom[$this->databaseName];
    $dbValuesForRow = $valuesForMulticolumns[$this->databaseName];
	$foreignValuesToUpdate[$this->databaseName]["remove"] = array();
	$foreignValuesToUpdate[$this->databaseName]["update"] = array();
	$foreignValuesToUpdate[$this->databaseName]["add"] = array();
	$toRemove = &$foreignValuesToUpdate[$this->databaseName]["remove"];
	$toUpdate = &$foreignValuesToUpdate[$this->databaseName]["update"];
	$toAdd = &$foreignValuesToUpdate[$this->databaseName]["add"];
	$id = $row["id"];
    foreach ($optionsForColumn as $optionId=>$optionDisplayName)
    {
	  $inputName = $this->databaseName . $id . '_' . $optionId;
	  $submittedValue = "";
	  if (isset($postData[$inputName]))
	  {
	    $submittedValue = trim($postData[$inputName]);
	  }
	  $dbValue = "";
	  if (isset($dbValuesForRow[$id][$optionId]))
	  {
	    $dbValue = $dbValuesForRow[$id][$optionId];
	  }
	  if ($dbValue != $submittedValue)
	  {
		if (isset($dbValuesForRow[$id][$optionId]) && !empty($submittedValue))
		{
		  $toUpdate[$optionId] = $submittedValue;
		}
        else if (!empty($submittedValue))
		{
          $toAdd[$optionId] = $submittedValue;
		}
		else
		{
		  $toRemove[$optionId] = 1;
		}	
	  }
	}
  }
  
  function updateForeignValues($tableName, $foreignValuesToUpdate, $rowId, $conn)
  {
	if (!empty($foreignValuesToUpdate[$this->databaseName]["remove"]))
	{
	  foreach (array_keys($foreignValuesToUpdate[$this->databaseName]["remove"]) as $optionId)
	  {
        $this->deleteForeignValuesOfColumn($tableName, $rowId, $optionId, $conn);
	  }
	}
	if (!empty($foreignValuesToUpdate[$this->databaseName]["add"]))
	{
	  foreach($foreignValuesToUpdate[$this->databaseName]["add"] as $optionId => $value)
	  {
	    $this->addForeignValuesOfColumn($tableName, $rowId, $optionId, $value, $conn);
	  }
	}
	if (!empty($foreignValuesToUpdate[$this->databaseName]["update"]))
	{
	  foreach($foreignValuesToUpdate[$this->databaseName]["update"] as $optionId => $value)
	  {
	    $this->updateForeignValuesOfColumn($tableName, $rowId, $optionId, $value, $conn);
	  }
	}
  }
  
  protected abstract function deleteForeignValuesOfColumn($tableName, $rowId, $optionId, $conn);
  
  protected abstract function addForeignValuesOfColumn($tableName, $rowId, $optionId, $value, $conn);
  
  protected abstract function updateForeignValuesOfColumn($tableName, $rowId, $optionId, $value, $conn);
}

class StringMulticolumn extends Multicolumn
{
  function __construct($databaseName, $displayName, $required, $dataType, $foreignTable, $foreignColumn, $columnValuesTable, $columnValuesDescriptionColumn, $foreignTableReferenceColumn)
  {
	parent::__construct($databaseName, $displayName, $required, $dataType, $foreignTable, $foreignColumn, "multicolumn", $columnValuesTable, $columnValuesDescriptionColumn, $foreignTableReferenceColumn);
  }
  
  function addToMainTableColumns()
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
  
  function printColumnHeaders($optionsToSelectFrom)
  {
    foreach ($optionsToSelectFrom[$this->databaseName] as $displayName)
	{
      echo "<td>" . $displayName . "</td>";
	}
  }
  
  function printColumnsForRow($row, $optionsToSelectFrom, $valuesForMulticolumns)
  {
	$id = $row["id"];
    $optionsForColumn = $optionsToSelectFrom[$this->databaseName];
    $valuesForColumn = $valuesForMulticolumns[$this->databaseName];
    foreach ($optionsForColumn as $optionId => $optionDisplayName)
    {
	  $inputName = $this->databaseName . $id . '_' . $optionId;
	  $inputValue = "";
	  if (isset($valuesForColumn[$id][$optionId]))
	  {
	    $inputValue = $valuesForColumn[$id][$optionId];
	  }
      echo '<td><input name="'. $inputName . '" value="' . $inputValue . '" /></td>';
    }
  }
  
  function printColumnsForNewRow($optionsToSelectFrom)
  {
    $optionsForColumn = $optionsToSelectFrom[$this->databaseName];
	foreach ($optionsForColumn as $optionId => $optionDisplayName)
	{
	  $inputName = $this->databaseName . '_' . $optionId;
	  echo '<td><input name="'. $inputName . '" /></td>';
	}
  }

  function getValuesToInsert($postData, &$insertedValues, &$multicolumnValuesToInsert, &$validationFailed, $conn)
  {
    $this->getMulticolumnValuesToInsert($postData, $multicolumnValuesToInsert, $validationFailed, $conn);
 }

  function insertMulticolumnValues($multicolumnValuesToInsert, $tableNameForRow, $idOfRow, $conn)
  {
    if (!isset($multicolumnValuesToInsert[$this->databaseName]))
    {
	  return;
	}
	$insertValues = $multicolumnValuesToInsert[$this->databaseName];
	foreach ($insertValues as $optionId => $valueToInsert)
	{
	  $sql = "INSERT INTO " . $this->foreignTable . " (" 
		  . $this->databaseName . ", " 
		  . $this->foreignTableReferenceColumn . ","
		  . $this->foreignColumn . ") VALUES (?,?,?)";
	  $statement = $conn->prepare($sql);
	  $statement->bind_param("sii", $valueToInsert, $optionId, $idOfRow); 
	  if (!$statement->execute())
	  {
		echo "Execute of " . $sql . " with binding " . $valueToInsert . ", ". $optionId . ", ". $idOfRow . "failed (" . $statement->error . ")";
	  }		
	}
  }
  
  protected function deleteForeignValuesOfColumn($tableName, $rowId, $optionId, $conn)
  {
	$sql = "DELETE FROM " . $this->foreignTable 
		. " WHERE " . $this->foreignTableReferenceColumn . "=" . $optionId
		. " AND " . $this->foreignColumn . "=" . $rowId;		 
	$conn->query($sql);
	if ($conn->errno != null)
	{
	  echo "error for " . $sql . ":" . $conn->error . "<br>";
	}
  }
  
  protected function addForeignValuesOfColumn($tableName, $rowId, $optionId, $value, $conn)
  {
    $sql = "INSERT INTO " . $this->foreignTable . " (" 
	    . $this->databaseName . ", " 
	    . $this->foreignTableReferenceColumn . ","
	    . $this->foreignColumn . ") VALUES (?,?,?)";
    $statement = $conn->prepare($sql);
    $statement->bind_param("sii", $value, $optionId, $rowId); 
    if (!$statement->execute())
    {
	  echo "Execute of " . $sql . " with binding " . $value . ", ". $optionId . ", ". $rowId . "failed (" . $statement->error . ")";
    }
  }
  
  protected function updateForeignValuesOfColumn($tableName, $rowId, $optionId, $value, $connn)
  {
    $sql = "INSERT INTO " . $this->columnValuesTable 
	    . " (" . $tableName . "_id, " . $this->foreignTable . "_id) "
	    . "VALUES (". $id . ',' . $optionId . ')';
    $conn->query($sql);
    if ($conn->errno != null)
    {
	  echo "error for " . $sql . ":" . $conn->error . "<br>";
    }
  }
}

class CheckboxMulticolumn extends Multicolumn
{
  function __construct($databaseName, $displayName, $required, $dataType, $foreignTable, $foreignColumn, $columnValuesTable)
  {
	parent::__construct($databaseName, $displayName, $required, $dataType, $foreignTable, $foreignColumn, "nToM", $columnValuesTable);
  }

  function addToMainTableColumns()
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

  function printColumnHeaders($optionsToSelectFrom)
  {
    foreach ($optionsToSelectFrom[$this->databaseName] as $displayName)
	{
      echo "<td>" . $displayName . "</td>";
	}
  }
  
  function printColumnsForRow($row, $optionsToSelectFrom, $valuesForMulticolumns)
  {
	$id = $row["id"];
    $optionsForColumn = $optionsToSelectFrom[$this->databaseName];
    $valuesForColumn = $valuesForMulticolumns[$this->databaseName];
    foreach ($optionsForColumn as $optionId=>$optionDisplayName)
    {
	  $inputName = $this->databaseName . $id . '_' . $optionId;
	  $checkedString = "";
	  if (isset($valuesForColumn[$id][$optionId]))
	  {
	    $checkedString = ' checked="checked"';
	  }
	  echo '<td><input type="checkbox" name="'. $inputName . '" value="1" ' . $checkedString . '/></td>';
    }
  }
  
  function printColumnsForNewRow($optionsToSelectFrom)
  {
	$optionsForColumn = $optionsToSelectFrom[$this->databaseName];
	foreach ($optionsForColumn as $optionId => $optionDisplayName)
	{
	  $inputName = $this->databaseName . '_' . $optionId;
	  echo '<td><input type="checkbox" name="'. $inputName . '" value="1" /></td>';
	}
  }

  function getValuesToInsert($postData, &$insertedValues, &$multicolumnValuesToInsert, &$validationFailed, $conn)
  {
    $this->getMulticolumnValuesToInsert($postData, $multicolumnValuesToInsert, $validationFailed, $conn);
  }

  function insertMulticolumnValues($multicolumnValuesToInsert, $tableNameForRow, $rowId, $conn)
  {
    if (!isset($multicolumnValuesToInsert[$this->databaseName]))
    {
	  return;
	}
	$insertValues = $multicolumnValuesToInsert[$this->databaseName];
	foreach ($insertValues as $optionId => $valueToInsert)
	{
	  $sql = "INSERT INTO " . $this->columnValuesTable 
		  . " (" . $tableNameForRow . "_id, " . $this->foreignTable . "_id) "
		  . "VALUES (". $rowId . ',' . $optionId . ')';
	  $conn->query($sql);
	  if ($conn->errno != null)
	  {
		echo "error for " . $sql . ":" . $conn->error . "<br>";
	  }
	}
  }
    
  protected function deleteForeignValuesOfColumn($tableName, $rowId, $optionId, $conn)
  {
	$sql = "DELETE FROM " . $this->columnValuesTable 
		. " WHERE " . $tableName . '_id=' . $rowId . ' AND ' . $this->foreignTable . "_id=" . $optionId;
	$conn->query($sql);
	if ($conn->errno != null)
	{
	  echo "error for " . $sql . ":" . $conn->error . "<br>";
	}
  }
  
  protected function addForeignValuesOfColumn($tableName, $rowId, $optionId, $value, $conn)
  {
    $sql = "INSERT INTO " . $this->columnValuesTable 
	    . " (" . $tableName . "_id, " . $this->foreignTable . "_id) "
	    . "VALUES (". $rowId . ',' . $optionId . ')';
    $conn->query($sql);
    if ($conn->errno != null)
    {
	  echo "error for " . $sql . ":" . $conn->error . "<br>";
    }
  }
  
  protected function updateForeignValuesOfColumn($tableName, $rowId, $optionId, $value, $conn)
  {
  }
}

?>