<?php
$conn = new mysqli($dbServer, $dbUser, $dbPassword, $dbName);
if ($conn->connect_error) 
{
  die("Connection failed: " . $conn->connect_error);
}

include('ColumnInfo.php');

/**
 * Returns the possible values for the columns in the form
 * array($databaseName of column => array(optionId => optionDisplayName))
 *
 * $columns array of ColumnInfo objects
 * $conn mysql connection
 */
function getOptionsToSelectFrom($columns, $conn)
{
  $result = array();
  foreach ($columns as $column)
  {
	$optionsForColumn = $column->getSelectOptions($conn);
	if (!is_null($optionsForColumn))
	{
	  $result[$column->databaseName] = $optionsForColumn;
	}
  }
  return $result;
}

/**
 * Returns the select options for a column, in the form
 * array($databaseName of column => array(id of record in this table => array(id of selectOption => value)))
 *
 * @param $tableName the name of the table which contains the displayed rows.
 * $columns array of ColumnInfo objects
 * $conn mysql connection
 */
function getValuesForMulticolumns($tableName, $columns, $conn)
{
  $result = array();
  foreach ($columns as $column)
  {
	$columnValues = $column->getMulticolumnValues($tableName, $conn);
	if (!is_null($columnValues))
	{
	  $result[$column->databaseName] = $columnValues;
	}
  }
  return $result;
}

function columnDataAsEditableTable($tableName, $columnInfos, $conn, $whereClause = '')
{
  $optionsToSelectFrom = getOptionsToSelectFrom($columnInfos, $conn);
  $valuesForMulticolumns = getValuesForMulticolumns($tableName, $columnInfos, $conn);
  $columnNames = ColumnInfo::getSelectColumnsOfMainTable($columnInfos);
  $concatenatedColumnNames = implode(",", $columnNames);
  $sql = "SELECT id," . $concatenatedColumnNames . " FROM " . $tableName . $whereClause . " ORDER BY id ASC";
  $result = $conn->query($sql);
  echo '<form method="POST"><table class="table table-bordered"><thead class="thead-light"><tr><th scope="column">Nr</th>';
  foreach ($columnInfos as $columnInfo)
  {
	$columnInfo->printColumnHeaders($optionsToSelectFrom);
  }
  echo '<th scope="column"></th></tr></thead><tbody>';
  if ($conn->errno == 0)
  {
    while($row = $result->fetch_assoc()) 
    {
      echo "<tr>";
	  $id = $row["id"];
      echo '<th scope="row">' . $id . '<input type="hidden" name="' . $id . '" value="1"/></th>';
	  foreach ($columnInfos as $columnInfo)
      {
		$columnInfo->printColumnsForRow($row, $optionsToSelectFrom, $valuesForMulticolumns);
	  }
      echo '<td><button type="submit" class="btn btn-secondary" name="delete" value="' . $id . '">Löschen</button></td>';
      echo "</tr>";  
    }
  }
  else
  {
    echo "error for " . $sql . ":" . $conn->error . "<br>";
  }
  echo '<tr><th scope="row">neu:</td>';
  foreach ($columnInfos as $columnInfo)
  {
	$columnInfo->printColumnsForNewRow($optionsToSelectFrom);
  }
  echo '<td></td></tr></tbody></table><br/><button type="submit" class="btn btn-primary" name="save" value="save">Speichern</button></form>';
}

function saveEditableTableData($tableName, $columnInfos, $postData, $conn)
{
  if (!isset($postData["save"]) && !isset($postData["delete"]))
  {
    return;
  }

  $columnNames = ColumnInfo::getSubmittableColumnsOfMainTable($columnInfos);
  $concatenatedColumnNames = implode(",", $columnNames);
  $sql = "SELECT id," . $concatenatedColumnNames . " FROM " . $tableName . " ORDER BY id ASC";
  $result = $conn->query($sql);
  if ($conn->errno == 0)
  {
    while($row = $result->fetch_assoc()) 
    {
	  doUpdates($tableName, $row, $columnInfos, $postData, $conn);
    }
  }
  else
  {
    echo "error for " . $sql . "<br>";
  }
  doInserts($tableName, $columnInfos, $postData, $conn);
}

function doUpdates($tableName, $row, $columnInfos, $postData, $conn)
{
  $id = $row["id"];
  if (!isset($postData[$id]))
  {
    // line was not shown
	return;
  }
  $optionsForRows = getOptionsToSelectFrom($columnInfos, $conn);
  $valuesForMulticolumns = getValuesForMulticolumns($tableName, $columnInfos, $conn);

  $updatedValues = array();
  $foreignValuesToUpdate = array();
  $validationFailed = false;
  foreach ($columnInfos as $columnInfo)
  {
	$columnInfo->fillValuesToUpdate($updatedValues, $foreignValuesToUpdate, $postData, $row, $optionsForRows, $valuesForMulticolumns, $validationFailed);
  }
  if ($validationFailed)
  {
	return;
  }
  if (count($updatedValues) > 0 && !$validationFailed)
  {
	$updateColumns = implode("=?,", array_keys($updatedValues));
	$sql = "UPDATE " . $tableName . " SET " . $updateColumns . "=? WHERE ID=" . $id;
	$statement = $conn->prepare($sql);
	$types = str_repeat("s", count($updatedValues));
	$statement->bind_param($types, ...array_values($updatedValues)); 
	if (!$statement->execute())
	{
	  echo "Execute of " . $sql . " with binding " . $types . ", ". implode(", ", array_values($updatedValues)) . "failed (" . $statement->error . ")";
	}
  }
  foreach ($columnInfos as $columnInfo)
  {
	$columnInfo->updateForeignValues($tableName, $foreignValuesToUpdate, $id, $conn);
  }
}

function doInserts($tableName, $columnInfos, $postData, $conn)
{
  $optionsForRows = getOptionsToSelectFrom($columnInfos, $conn);
  $insertedValues = array();
  $multicolumnValuesToInsert = array();
  $validationError = false;
  foreach ($columnInfos as $columnInfo)
  {
    $columnInfo->getValuesToInsert($postData, $insertedValues, $multicolumnValuesToInsert, $validationError, $conn);
  }
  if (count($insertedValues) > 0 || count($multicolumnValuesToInsert) > 0 && !$validationError)
  {
    foreach ($columnInfos as $columnInfo)
	{
	  $submittedValue = null;
	  if (isset($insertedValues[$columnInfo->databaseName]))
	  {
		$submittedValue = $insertedValues[$columnInfo->databaseName];
	  }
	  $validationError = !$columnInfo->validateSubmittedValue($submittedValue);
	}
	if ($validationError)
	{
	  return;
	}
	$insertColumns = implode(",", array_keys($insertedValues));
	$insertPlaceholders = implode(',', array_fill(0, count($insertedValues), '?'));
	$sql = "INSERT INTO " . $tableName . "(" . $insertColumns . ") VALUES (" . $insertPlaceholders . ")";
	$statement = $conn->prepare($sql);
	$types = str_repeat("s", count($insertedValues));
	$statement->bind_param($types, ...array_values($insertedValues)); 
	if (!$statement->execute())
	{
	  echo "Execute of " . $sql . " with binding " . $types . ", ". implode(", ", array_values($insertedValues)) . "failed (" . $statement->error . ")";
	}
	$id = $conn->insert_id;
    foreach ($columnInfos as $columnInfo)
    {
      $columnInfo->insertMulticolumnValues($multicolumnValuesToInsert, $tableName, $id, $conn);
    }
  }
}

function checkAnyRowDeleted($tableName, $columnInfos, $postData, $conn)
{
  if (!isset($postData["delete"]))
  {
    return;
  }
  $sql = "SELECT id FROM " . $tableName . " ORDER BY id ASC";
  $result = $conn->query($sql);
  if ($conn->errno == 0)
  {
    $first = true;
    while($row = $result->fetch_assoc()) 
    {
	  checkDeleteRow($tableName, $row["id"], $columnInfos, $postData, $conn);
    }
  }
  else
  {
    echo "error for " . $sql . "<br>";
  }
}

function checkDeleteRow($tableName, $id, $columnInfos, $postData, $conn)
{
  if ($postData["delete"] == $id)
  {
    $sql = "DELETE FROM ". $tableName . " WHERE ID=" . $id;
    $conn->query($sql);
    if ($conn->errno != 0)
	{
	  echo "Execute of " . $sql . "failed (" . $conn->error . ")";
	}
  }
}

function printFilterForm($label, $table, $column, $conn)
{
  $optionsForColumn = ColumnInfo::querySelectOptions($column, $table, $conn);
  $oldFilterValue = null;
  if (isset($_GET["filter"]))
  {
    $oldFilterValue = $_GET["filter"];
  }
  echo '<form class="my-4" method="GET"><div class="form-row">';
  echo '<label for="filter" class="col-auto col-form-label">' . $label . ' </label><div class="col-auto">';
  echo '<select class="form-control" name="filter"><option value="">alle</option>';
  foreach ($optionsForColumn as $key => $displayName)
  {
	$selectedString = '';
	if ($oldFilterValue == $key)
    {
	  $selectedString = ' selected="selected"';
	}
    echo '<option value="' . $key . '"' . $selectedString .'>' . $displayName . '</option>';
  }
  echo '</select></div><div class="col-auto"><button type="submit" class="btn btn-primary mb-2">Filter</button></div></div></form>';
}

?>