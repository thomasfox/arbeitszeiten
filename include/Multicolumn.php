<?php
abstract class Multicolumn extends ColumnInfo
{
  function getSelectSnippet()
  {
	return null;
  }
  
  function isSingleEditableValue()
  {
    return false;
  }
  
  function getColumnHeaders($optionsToSelectFrom)
  {
    $result = array();
    foreach ($optionsToSelectFrom[$this->databaseName] as $displayName)
    {
      array_push($result, $displayName);
    }
    return $result;
  }
  
  protected function getMulticolumnValuesToInsert($postData, &$multicolumnValuesToInsert, &$validationFailed, $conn)
  {
    $optionsForRow = $this->getSelectOptions($conn);
	foreach ($optionsForRow as $optionId => $optionDisplayName)
	{
	  $inputName = $this->databaseName . '_' . $optionId;
	  $submittedValue = "";
	  if (isset($postData[$inputName]))
	  {
	    $submittedValue = $this->getDatabaseValue(trim($postData[$inputName]), $validationFailed);
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
	$validationFailed = false;
    foreach ($optionsForColumn as $optionId=>$optionDisplayName)
    {
	  $inputName = $this->databaseName . $id . '_' . $optionId;
	  $submittedValue = "";
	  if (isset($postData[$inputName]))
	  {
	    $submittedValue = trim($postData[$inputName]);
	    $submittedValue = $this->getDatabaseValue(trim($postData[$inputName]), $validationFailed);
	  }
	  $dbValue = "";
	  if (isset($dbValuesForRow[$id][$optionId]))
	  {
	    $dbValue = $dbValuesForRow[$id][$optionId];
	  }
	  if ($dbValue != $submittedValue)
	  {
	  	echo '<!-- DEBUG: value ' . $optionId . ' for row '. $id . ' in multicolumn ' . $this->databaseName . ' changed -->';
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
	if ($validationFailed)
	{
	  $foreignValuesToUpdate[$this->databaseName]["remove"] = array();
	  $foreignValuesToUpdate[$this->databaseName]["update"] = array();
	  $foreignValuesToUpdate[$this->databaseName]["add"] = array();
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
    
  function validateSubmittedValue($submittedValue)
  {
    return true;
  }
}
?>