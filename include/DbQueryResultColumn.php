<?php
class DbQueryResultColumn extends SingleColumn
{
  private $selectSnippet;

  function __construct($databaseName, $displayName, $selectSnippet)
  {
	parent::__construct($databaseName, $displayName, false);
	$this->selectSnippet = $selectSnippet; 
  }

  function isSingleEditableValue()
  {
    return false;
  }

  function getSelectOptions($conn)
  {
	return null;
  }

  function getSelectSnippet()
  {
	return $this->selectSnippet;
  }
  
  function printColumnsForRow($row, $optionsToSelectFrom, $valuesForMulticolumns)
  {
    $value = $row[$this->databaseName];
    echo '<td>'. $value . '</td>';
  }
  
  function printColumnsForNewRow($optionsToSelectFrom)
  {
    echo '<td></td>';
  }

  function getDatabaseValue($submittedValue, &$validationFailed)
  {
    return $submittedValue;
  }
  
  function fillValuesToUpdate(&$valuesToUpdate, &$foreignValuesToUpdate, $postData, $row, $optionsToSelectFrom, $valuesForMulticolumns, &$validationFailed)
  {
  }

}
?>