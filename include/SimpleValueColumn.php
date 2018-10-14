<?php
class SimpleValueColumn extends SingleColumn
{
  private $datatype; // one of "s" (String), "i" (Integer), "d" (Date) or "t" (Time)

  function __construct($databaseName, $displayName, $required, $dataType = "s")
  {
	parent::__construct($databaseName, $displayName, $required);
	$this->datatype = $dataType;
  }
  
  function isSingleEditableValue()
  {
    return true;
  }

  function getSelectOptions($conn)
  {
	return null;
  }
  
  function printColumnsForRow($row, $optionsToSelectFrom, $valuesForMulticolumns)
  {
	$id = $row["id"];
    $value = $row[$this->databaseName];
    if ($this->datatype == "d" and !empty($value))
    {
      $value = DateTime::createFromFormat("Y-m-d", $value)->format("d.m.Y");
    }
    echo '<td><input name="'. $this->databaseName . $id . '" value="' . $value . '" class="form-control"/></td>';
  }
  
  function printColumnsForNewRow($optionsToSelectFrom)
  {
    echo '<td><input name="'. $this->databaseName . '" class="form-control"/></td>';
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