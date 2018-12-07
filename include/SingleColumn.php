<?php
abstract class SingleColumn extends ColumnInfo
{
  protected $required;

  function __construct($databaseName, $displayName, $required)
  {
	parent::__construct($databaseName, $displayName);
	$this->required = $required;
  }

  function getSelectSnippet()
  {
	return $this->databaseName;
  }

  function getColumnHeaders($optionsToSelectFrom)
  {
    $result = array($this->getDisplayName());
    return $result;
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
    $submittedValue = null;
    if (isset($postData[$this->databaseName . $id]))
    {
      $submittedValue = trim($postData[$this->databaseName . $id]);
    }
    if ($this->required && empty($submittedValue))
    {
      alertError("Die Spalte " . $this->displayName . " in Datensatz Nr. " . $id . " ist ein Pflichtfeld und muss ausgefüllt werden. Der Datensatz wurde nicht gespeichert.");
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
  
  function validateSubmittedValue($submittedValue)
  {
    if ($this->required && empty($submittedValue))
	{
	  alertError("Die Spalte " . $this->displayName . " im neuen Datensatz ist ein Pflichtfeld und muss ausgefüllt werden. Der Datensatz wurde nicht gespeichert.");
	  return false;
	}
	return true;
  }
}
?>