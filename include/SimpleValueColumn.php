<?php
class SimpleValueColumn extends SingleColumn
{
  private $datatype; // one of "s" (String), "i" (Integer), "d" (Date) or "t" (Time)

  private $tdClassName; // additional css classes for the columns
  
  function __construct($databaseName, $displayName, $required, $dataType = "s", $tdClassName = null)
  {
    parent::__construct($databaseName, $displayName, $required);
    $this->datatype = $dataType;
    $this->tdClassName = $tdClassName;
  }
  
  function isSingleEditableValue()
  {
    return true;
  }

  function getSelectOptions($conn)
  {
	return null;
  }
  
  function getColumnValuesForRow($row, $optionsToSelectFrom, $valuesForMulticolumns)
  {
    $value = $row[$this->databaseName];
    if ($this->datatype == "d" and !empty($value))
    {
      $value = DateTime::createFromFormat("Y-m-d", $value)->format("d.m.Y");
    }
    return array($value);
  }
  
  function printColumnsForRow($row, $optionsToSelectFrom, $valuesForMulticolumns)
  {
    $id = $row["id"];
    $value = $row[$this->databaseName];
    if ($this->datatype == "d" and !empty($value))
    {
      $value = DateTime::createFromFormat("Y-m-d", $value)->format("d.m.Y");
    }
    $classSnippet = "";
    if (isset($this->tdClassName))
    {
      $classSnippet = ' class="' . $this->tdClassName .'"';
    }
    echo '<td' . $classSnippet . '><input name="'. $this->databaseName . $id . '" value="' . $value . '" class="form-control" onchange="markChanged()" /></td>';
  }
  
  function printColumnsForNewRow($optionsToSelectFrom)
  {
    echo '<td><input name="'. $this->databaseName . '" class="form-control" onchange="markChanged()"/></td>';
  }

  function getDatabaseValue($submittedValue, &$validationFailed)
  {
	if ($this->datatype == "d" && !empty($submittedValue))
	{
	  $valueAsDate = DateTime::createFromFormat("d.m.Y", $submittedValue);
	  if ($valueAsDate == false)
	  {
		alertError("Die Spalte " . $this->displayName . " hat ein ungültiges Datumsformat.  Bitte verwenden Sie das Format TT.MM.JJJJ. Der Datensatz wurde nicht gespeichert.");
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
?>