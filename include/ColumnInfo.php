<?php
class ColumnInfo
{
  public $databaseName; // name of the value column in the database. For "multicolumn": Column is in the foreign table. For others: column is in the current table.
  
  public $displayName;
  
  public $foreignTable; // foreign table containing the displayed value
  
  public $foreignColumn;// for multicolumn: foreign-key-column in the foreign table containing the id of this table's row
                        // for dropdown or text: foreign-key-column in this table containing the id of the foreign table's row
  
  public $foreignType; // possible values are "dropdown" for selection in a singleSelect, 
					   // "text" for creating new entries in the foreign table with the given text
					   // "multicolumn" for creating several entries at once associated with certain values in the foreign tables

  public $columnValuesTable; // for "multicolumn" only
  
  public $columnValuesDescriptionColumn; // for "multicolumn" only
  
  public $foreignTableReferenceColumn; // for "multicolumn" only, contains the join column in the foreign table

  function __construct()
  {
    $args = func_get_args();
	$this->databaseName = $args[0];
	if (count($args) > 1)
	{
	  $this->displayName = $args[1];
	}
	if (count($args) > 2)
    {
      $this->foreignTable = $args[2];
      $this->foreignColumn = $args[3];
	  if ($args[4] != "dropdown" && $args[4] != "text" && $args[4] != "multicolumn")
	  {
		throw new Exception('foreignType must be one of "dropdown" or "text" or "multicolumn"');
	  }
      $this->foreignType = $args[4];
	  if ($args[4] == "multicolumn")
	  {
		$this->columnValuesTable = $args[5];
		$this->columnValuesDescriptionColumn = $args[6];
		$this->foreignTableReferenceColumn = $args[7];
	  }
	}
  }
  
  function getDisplayName()
  {
	if (isset($this->displayName))
	return $this->displayName;
    return $this->databaseName;
  }

  static function getDatabaseNames($columnInfos)
  {
    $result = array();
    foreach ($columnInfos as $columnInfo)
    {
	  if ($columnInfo->foreignType != "multicolumn")
	  {
        array_push($result, $columnInfo->databaseName);
	  }
    }
    return $result;
  }
}

?>