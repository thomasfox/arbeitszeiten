<?php
class DropdownColumn extends SingleColumn
{
  private $foreignTable;

  private $foreignColumn; // foreign-key-column in this table containing the id of the foreign table's row

  function __construct($databaseName, $displayName, $required, $foreignTable, $foreignColumn)
  {
	parent::__construct($databaseName, $displayName, $required);
	$this->foreignTable = $foreignTable; 
	$this->foreignColumn = $foreignColumn;
  }
   
  function isSingleEditableValue()
  {
    return true;
  }

  function getSelectOptions($conn)
  {
	return $this->querySelectOptions($this->foreignColumn, $this->foreignTable, "", "", $conn);
  }
  
  function getColumnValuesForRow($row, $optionsToSelectFrom, $valuesForMulticolumns)
  {
  	$value = $row[$this->databaseName];
    $optionsForColumn = $optionsToSelectFrom[$this->databaseName];
    $displayValue = "";
    foreach ($optionsForColumn as $optionId => $optionDisplayName)
    {
      if ($value == $optionId)
      {
        $displayValue = $optionDisplayName;
        break;
      }
    }
    return array($displayValue);
  }
  
  function printColumnsForRow($row, $optionsToSelectFrom, $valuesForMulticolumns)
  {
    $id = $row["id"];
    $value = $row[$this->databaseName];
    echo '<td><select name="'. $this->databaseName . $id . '" class="form-control" onchange="markChanged()">';
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
    echo '<td><select name="'. $this->databaseName . '" class="form-control" onchange="markChanged()">';
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
?>