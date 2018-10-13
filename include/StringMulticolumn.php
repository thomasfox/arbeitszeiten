<?php
class StringMulticolumn extends Multicolumn
{
  protected $foreignTable; // foreign table containing the displayed value
  
  protected $foreignColumn; // foreign-key-column in the foreign table containing the id of this table's row
  
  protected $columnValuesTable;
  
  protected $columnValuesDescriptionColumn;
  
  protected $foreignTableReferenceColumn;
  
  protected $optionsWhereClause;

  function __construct($databaseName, $displayName, $foreignTable, $foreignColumn, $columnValuesTable, $columnValuesDescriptionColumn, $foreignTableReferenceColumn, $optionsWhereClause = "")
  {
	parent::__construct($databaseName, $displayName);
	$this->foreignTable = $foreignTable;
	$this->foreignColumn = $foreignColumn;
	$this->columnValuesTable = $columnValuesTable;
	$this->columnValuesDescriptionColumn = $columnValuesDescriptionColumn;
	$this->foreignTableReferenceColumn = $foreignTableReferenceColumn;
	$this->optionsWhereClause = $optionsWhereClause;
  }
  
  function getSelectOptions($conn)
  {
	return $this->querySelectOptions($this->columnValuesDescriptionColumn, $this->columnValuesTable, $this->optionsWhereClause, $conn);
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
      alertError("getMulticolumnValues: error for " . $sql . ":" . $conn->error);
    }
  }
  
  function printColumnHeaders($optionsToSelectFrom)
  {
    foreach ($optionsToSelectFrom[$this->databaseName] as $displayName)
	{
      echo '<th scope="column">' . $displayName . '</th>';
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
      echo '<td><input name="'. $inputName . '" value="' . $inputValue . '" class="form-control" /></td>';
    }
  }
  
  function printColumnsForNewRow($optionsToSelectFrom)
  {
    $optionsForColumn = $optionsToSelectFrom[$this->databaseName];
	foreach ($optionsForColumn as $optionId => $optionDisplayName)
	{
	  $inputName = $this->databaseName . '_' . $optionId;
	  echo '<td><input name="'. $inputName . '" class="form-control"/></td>';
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
		alertError("insertMulticolumnValues(): Execute of " . $sql . " with binding " . $valueToInsert . ", ". $optionId . ", ". $idOfRow . "failed (" . $statement->error . ")");
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
	  alertError("deleteForeignValuesOfColumn: error for " . $sql . ":" . $conn->error);
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
	  alertError("addForeignValuesOfColumn(): Execute of " . $sql . " with binding " . $value . ", ". $optionId . ", ". $rowId . "failed (" . $statement->error . ")");
    }
  }
  
  protected function updateForeignValuesOfColumn($tableName, $rowId, $optionId, $value, $conn)
  {
    $sql = "UPDATE " . $this->foreignTable . " SET " 
	    . $this->databaseName . "=? WHERE " 
	    . $this->foreignTableReferenceColumn . "=? AND "
	    . $this->foreignColumn . "=?";
    $statement = $conn->prepare($sql);
    $statement->bind_param("sii", $value, $optionId, $rowId); 
    $conn->query($sql);
    if (!$statement->execute())
    {
	  alertError("updateForeignValuesOfColumn(): error for " . $sql . " with binding " . $value . ", ". $optionId . ", ". $rowId . ":" . $statement->error);
    }
  }
}
?>