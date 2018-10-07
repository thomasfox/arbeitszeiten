<?php
class ColumnInfo
{
  public $databaseName;
  
  public $displayName;
  
  public $foreignTable;
  
  public $foreignColumn;
  
  public $foreignType; // possible values are "dropdown" for selection in a singleSelect, 
					   // "text" for creating new entries in the foreign table with the given text
					   // "multidropdown" for selection in a singleSelect but creating more than one entry in the displayed table at once

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
	  if ($args[4] != "dropdown" && $args[4] != "text" && $args[4] != "multidropdown")
	  {
		throw new Exception('foreignType must be one of "dropdown" or "text" or "multidropdown"');
	  }
      $this->foreignType = $args[4];
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
      array_push($result, $columnInfo->databaseName);
    }
    return $result;
  }
}

?>