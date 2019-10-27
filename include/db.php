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

function columnDataAsEditableTable(string $tableName, array $columnInfos, $conn, string $orderByClause='id ASC', string $whereClause = '', $filterLabel=null, $filterValuesTable=null, $filterValuesColumn=null)
{
  $optionsToSelectFrom = getOptionsToSelectFrom($columnInfos, $conn);
  $valuesForMulticolumns = getValuesForMulticolumns($tableName, $columnInfos, $conn);
  $sql = getSql($tableName, $columnInfos, $orderByClause, $whereClause, $optionsToSelectFrom, $conn);
  $result = $conn->query($sql);
  echo '<form method="POST">';
  echo '<div class="form-inline my-3">';
  echo '<button type="submit" class="btn btn-primary px-5" name="save" value="save" onclick="beforeSubmit()">Speichern</button>';
  echo '<a href="#" class="btn btn-secondary mx-2" onclick="askForChangedValueSave(this, \'index.html\')" >Zurück</a>';
  
  if ($filterLabel != null)
  {
  	printFilter($filterLabel, $filterValuesTable, $filterValuesColumn, $conn);
  }
  echo '<button type="submit" class="btn btn-outline-secondary ml-5" name="export" value="export">Exportieren</button>';
  echo '</div><table class="table table-bordered"><thead class="thead-light"><tr><th scope="column">Nr</th>';
  
  foreach ($columnInfos as $columnInfo)
  {
    $columnInfo->printColumnHeaders($optionsToSelectFrom);
  }
  echo '<th scope="column"></th></tr></thead><tbody>';
  echo '<tr><th scope="row">neu:</td>';
  foreach ($columnInfos as $columnInfo)
  {
    $columnInfo->printColumnsForNewRow($optionsToSelectFrom);
  }
  echo '<td></td></tr>';
  if ($conn->errno == 0 && $result != false)
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
    alertError("columnDataAsEditableTable(): error for " . $sql . ":" . $conn->error);
  }
  echo '</tbody></table>';
  echo '</form>';
}


/**
 * Returns the SQL to query the database to display a table on screen.
 *
 * @param tableName the name of the table in the database to query
 * @param columnInfos the screen columns to display
 * @param orderByClause the order by clause, without the leading " ORDER BY "
 * @param whereClause the where clause, including the leading  " WHERE " if filled.
 * @param conn the database connection
 */

function getSql(string $tableName, array $columnInfos, string $orderByClause, string $whereClause, $optionsToSelectFrom, $conn) 
{
  $columnNames = ColumnInfo::getSelectColumnsOfMainTable($columnInfos);
  $concatenatedColumnNames = implode(",", $columnNames);
  $sql = "SELECT id," . $concatenatedColumnNames . " FROM " . $tableName . $whereClause . " ORDER BY " . $orderByClause;
 return $sql;
}

function checkCsvExport(string $tableName, array $columnInfos, $postData, $conn, string $orderByClause="id ASC", string $whereClause = '', $filterLabel=null, $filterValuesTable=null, $filterValuesColumn=null)
{
  if (!isset($postData["export"]) || $postData["export"] != "export")
  {
    return;
  }
  header('Content-Type: application/csv');
  header('Content-Disposition: attachment; filename="' . $tableName . '.csv";');
  echo "\xEF\xBB\xBF"; // UTF-8 Byte order mark for Excel
  $out = fopen('php://output', 'w');
  
  $optionsToSelectFrom = getOptionsToSelectFrom($columnInfos, $conn);
  $valuesForMulticolumns = getValuesForMulticolumns($tableName, $columnInfos, $conn);
  
  $columnHeadlines = array("ID");
  foreach ($columnInfos as $columnInfo)
  {
    $headlinesForColumn = $columnInfo->getColumnHeaders($optionsToSelectFrom);
    $columnHeadlines = array_merge($columnHeadlines, $headlinesForColumn);
  }
  fputcsv($out, $columnHeadlines, ';');
  
  $sql = getSql($tableName, $columnInfos, $orderByClause, $whereClause, $optionsToSelectFrom, $conn);
  $result = $conn->query($sql);
  if ($conn->errno == 0 && $result != false)
  {
    while ($row = $result->fetch_assoc())
    {
      $csvrow = array();
      $csvrow[] = $row["id"];
      foreach ($columnInfos as $columnInfo)
      {
        $csvrow = array_merge($csvrow, $columnInfo->getColumnValuesForRow($row, $optionsToSelectFrom, $valuesForMulticolumns));
      }
      fputcsv($out, $csvrow, ';');
    }
  }
  else
  {
    alertError("columnDataAsEditableTable(): error for " . $sql . ":" . $conn->error);
  }
  exit(0);
}



function saveEditableTableData($tableName, $columnInfos, $postData, $conn)
{
  echo '<!-- DEBUG: Postsize: ' . sizeof($postData) . " max_input_vars: " . ini_get('max_input_vars') . ' -->';
  if (sizeof($postData) >= ini_get('max_input_vars'))
  {
    alertError("Zu viele Daten übertragen. Bitte filtern, um Daten zu speichern.");
    return;
  }
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
    alertError("saveEditableTableData: error for " . $sql);
  }
  doInserts($tableName, $columnInfos, $postData, $conn);
}

function doUpdates($tableName, $row, $columnInfos, $postData, $conn)
{
  $id = $row["id"];
  if (!isset($postData[$id]))
  {
    // line was not shown
    echo '<!-- DEBUG: ignoring row '. $id . ' for updates -->';
    return;
  }
  echo '<!-- DEBUG: checking for updates on row '. $id . ' -->';
  $optionsForRows = getOptionsToSelectFrom($columnInfos, $conn);
  $valuesForMulticolumns = getValuesForMulticolumns($tableName, $columnInfos, $conn);

  $updatedValues = array();
  $foreignValuesToUpdate = array();
  $validationFailed = false;
  foreach ($columnInfos as $columnInfo)
  {
    $columnInfo->fillValuesToUpdate($updatedValues, $foreignValuesToUpdate, $postData, $row, $optionsForRows, $valuesForMulticolumns, $validationFailed);
    if ($validationFailed)
    {
      return;
    }
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
	  alertError("doUpdates: Execute of " . $sql . " with binding " . $types . ", ". implode(", ", array_values($updatedValues)) . "failed (" . $statement->error . ")");
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
  if ((count($insertedValues) > 0 || count($multicolumnValuesToInsert) > 0) && !$validationError)
  {
    echo '<!-- DEBUG: about to insert new row -->';
    foreach ($columnInfos as $columnInfo)
	{
	  $submittedValue = null;
	  if (isset($insertedValues[$columnInfo->databaseName]))
	  {
		$submittedValue = $insertedValues[$columnInfo->databaseName];
	  }
	  $validationError = !$columnInfo->validateSubmittedValue($submittedValue);
	  if ($validationError)
	  {
	    return;
	  }
	}
	$insertColumns = implode(",", array_keys($insertedValues));
	$insertPlaceholders = implode(',', array_fill(0, count($insertedValues), '?'));
	$sql = "INSERT INTO " . $tableName . "(" . $insertColumns . ") VALUES (" . $insertPlaceholders . ")";
	$statement = $conn->prepare($sql);
	$types = str_repeat("s", count($insertedValues));
	$statement->bind_param($types, ...array_values($insertedValues)); 
	if (!$statement->execute())
	{
	  alertError("doInserts: Execute of " . $sql . " with binding " . $types . ", ". implode(", ", array_values($insertedValues)) . "failed (" . $statement->error . ")");
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
    alertError("checkAnyRowDeleted: error for " . $sql);
  }
}

function checkDeleteRow($tableName, $id, $columnInfos, $postData, $conn)
{
  if ($postData["delete"] == $id)
  {
    echo '<!-- DEBUG: about to delete row '. $id . ' -->';
    $sql = "DELETE FROM ". $tableName . " WHERE ID=" . $id;
    $conn->query($sql);
    if ($conn->errno != 0)
    {
      alertError("checkDeleteRow: Execute of " . $sql . "failed (" . $conn->error . ")");
    }
  }
}

function printFilter($label, $table, $column, $conn)
{
  $optionsForColumn = ColumnInfo::querySelectOptions($column, $table, "", "", $conn);
  $oldFilterValue = null;
  if (isset($_GET["filter"]))
  {
    $oldFilterValue = $_GET["filter"];
  }
  echo '<label for="filter" class="col-auto col-form-label ml-5">' . $label . ' </label>';
  echo '<div class="col-auto"><select class="form-control" name="filter" id="filter" data-initial="' . $oldFilterValue . '" onchange="applyFilter(\'filter\',window.filter.value)"><option value="">alle</option>';
  foreach ($optionsForColumn as $key => $displayName)
  {
	$selectedString = '';
	if ($oldFilterValue == $key)
    {
	  $selectedString = ' selected="selected"';
	}
    echo '<option value="' . $key . '"' . $selectedString .'>' . $displayName . '</option>';
  }
  echo '</select></div>';
}

function checkIdValueExists($tableName, $value, $conn)
{
  $sql = 'SELECT id FROM ' . $tableName . ' WHERE id=?';
  $statement = $conn->prepare($sql);
  $statement->bind_param('s', $value); 
  if (!$statement->execute())
  {
    alertError("checkIdValueExists: Execute of " . $sql . " with binding " . $value . "failed (" . $statement->error . ")");
  }
  $statement->store_result();
  return ($statement->num_rows() == 1);
}

function alertError($message)
{
  echo '<div class="alert alert-danger" role="alert">' . $message . '</div>';
}
?>