<?php
include("SingleColumn.php");
include("SimpleValueColumn.php");
include("DropdownColumn.php");
include("DbQueryResultColumn.php");
include("Multicolumn.php");
include("StringMulticolumn.php");
include("CheckboxMulticolumn.php");

abstract class ColumnInfo
{
  public $databaseName; // name of the value column in the database. For "multicolumn": Column is in the foreign table. For others: column is in the current table.
  
  public $displayName; // A human readable label for the Value in the database column
  
  function __construct($databaseName, $displayName)
  {
	$this->databaseName = $databaseName;
    $this->displayName = $displayName;
  }
  
  function getDisplayName()
  {
	if (isset($this->displayName))
	return $this->displayName;
    return $this->databaseName;
  }
  
  public static function getSelectColumnsOfMainTable($columnInfos)
  {
    $result = array();
    foreach ($columnInfos as $columnInfo)
    {
	  $selectSnippet = $columnInfo->getSelectSnippet();
	  if (isset($selectSnippet))
	  {
        array_push($result, $selectSnippet);
	  }
    }
    return $result;
  }

  public static function getSubmittableColumnsOfMainTable($columnInfos)
  {
    $result = array();
    foreach ($columnInfos as $columnInfo)
    {
	  if ($columnInfo->isSingleEditableValue())
	  {
        array_push($result, $columnInfo->databaseName);
	  }
    }
    return $result;
  }
  
  /**
   * Returns the select Snippet if this ColumnInfo is represented by a database expression in the main table of the displayed data, or null otherwise.
   */
  public abstract function getSelectSnippet();
  
  /**
   * Returns true if this ColumnInfo is represented by a single editable value, false otherwise.
   */
  public abstract function isSingleEditableValue();

  /**
   * Returns the select options for a column, as array($key=>$DisplayName).
   * If the column does not have any select options, returns null.
   *
   * @param $conn the mysql connection.
   *
   * @return the select options in the form array($databaseValue => $displayName)
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
  
  public abstract function validateSubmittedValue($submittedValue);

  public static function querySelectOptions($descriptionColumn, $table, $whereClause, $orderByClause, $conn)
  {
    if (empty($orderByClause))
    {
      $orderByClause = "id ASC";
    }
    $sql = "SELECT id," . $descriptionColumn . " FROM " . $table . $whereClause . " ORDER BY " . $orderByClause;
    $result = $conn->query($sql);
	$selectOptions = null;
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
      alertError("querySelectOptions() : error for " . $sql . ":" . $conn->error);
	}
	return $selectOptions;
  }
}
?>