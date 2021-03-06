<?php
class CheckboxMulticolumn extends Multicolumn
{
  private $foreignTable; // foreign table containing the displayed value
  
  private $foreignColumn; // foreign-key-column in the foreign table containing the id of this table's row
  
  private $columnValuesTable;

  function __construct($databaseName, $displayName, $foreignTable, $foreignColumn, $columnValuesTable)
  {
	parent::__construct($databaseName, $displayName);
	$this->foreignTable = $foreignTable;
	$this->foreignColumn = $foreignColumn;
	$this->columnValuesTable = $columnValuesTable;
  }

  function getSelectOptions($conn)
  {
	return $this->querySelectOptions($this->foreignColumn, $this->foreignTable, "", "", $conn);
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
      alertError("getMulticolumnValues(): error for " . $sql . ":" . $conn->error);
    }
  }

  function getColumnValuesForRow($row, $optionsToSelectFrom, $valuesForMulticolumns)
  {
    $result = array();
    $id = $row["id"];
    $optionsForColumn = $optionsToSelectFrom[$this->databaseName];
    $valuesForColumn = $valuesForMulticolumns[$this->databaseName];
    foreach ($optionsForColumn as $optionId=>$optionDisplayName)
    {
      $value = "";
      if (isset($valuesForColumn[$id][$optionId]))
      {
        $value = '1';
      }
      $result[] = $value;
    }
    return $result;
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
      echo '<td><input type="checkbox" name="'. $inputName . '" value="1" ' . $checkedString . ' onchange="markChanged()"/></td>';
    }
  }
  
  function printColumnsForNewRow($optionsToSelectFrom)
  {
	$optionsForColumn = $optionsToSelectFrom[$this->databaseName];
	foreach ($optionsForColumn as $optionId => $optionDisplayName)
	{
	  $inputName = $this->databaseName . '_' . $optionId;
	  echo '<td><input type="checkbox" name="'. $inputName . '" value="1" onchange="markChanged()" /></td>';
	}
  }

  function getDatabaseValue($submittedValue, &$validationFailed)
  {
    return $submittedValue;
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
		alertError("insertMulticolumnValues(): error for " . $sql . ":" . $conn->error);
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
	  alertError("deleteForeignValuesOfColumn(): error for " . $sql . ":" . $conn->error);
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
	  alertError("addForeignValuesOfColumn() : error for " . $sql . ":" . $conn->error);
    }
  }
  
  protected function updateForeignValuesOfColumn($tableName, $rowId, $optionId, $value, $conn)
  {
  }
}
?>