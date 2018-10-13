<?php
abstract class ColumnInfo
{
  public $databaseName; // name of the value column in the database. For "multicolumn": Column is in the foreign table. For others: column is in the current table.
  
  public $displayName; // A human readable label for the Value in the database column
  
  function __construct($databaseName, $displayName)
  {
	$this->databaseName = $databaseName;
    $this->displayName = $displayName;
  }
}

abstract class Multicolumn extends ColumnInfo
{
}

class StringMulticolumn extends Multicolumn
{
  protected $foreignTable; // foreign table containing the displayed value
  
  protected $foreignColumn; // foreign-key-column in the foreign table containing the id of this table's row
  
  protected $columnValuesTable;
  
  protected $columnValuesDescriptionColumn;
  
  protected $foreignTableReferenceColumn;

  function __construct($databaseName, $displayName, $foreignTable, $foreignColumn, $columnValuesTable, $columnValuesDescriptionColumn, $foreignTableReferenceColumn)
  {
	parent::__construct($databaseName, $displayName);
	var_dump($columnValuesTable);
	echo "<br/>";
	$this->foreignTable = $foreignTable;
	$this->foreignColumn = $foreignColumn;
	$this->columnValuesTable = $columnValuesTable;
	$this->columnValuesDescriptionColumn = $columnValuesDescriptionColumn;
	$this->foreignTableReferenceColumn = $foreignTableReferenceColumn;
	var_dump($this->$columnValuesTable);
	echo "<br/>";
	var_dump($this);
	echo "<br/>";
  }
   
  function getSelectOptions($conn)
  {
	var_dump($this->columnValuesTable);
  }
}

$c = new StringMulticolumn("1", "2", "3", "4", "5", "6", "7");
$c->getSelectOptions("1");

?>