<?php
$conn = new mysqli($dbServer, $dbUser, $dbPassword, $dbName);
if ($conn->connect_error) 
{
  die("Connection failed: " . $conn->connect_error);
}

include('ColumnInfo.php');

function columnDataAsTable($tableName, $columnInfos, $conn)
{
  $columnNames = ColumnInfo::getDatabaseNames($columnInfos);
  $concatenatedColumnNames = implode(",", $columnNames);

  $sql = "SELECT id," . $concatenatedColumnNames . " FROM " . $tableName . " ORDER BY id ASC";
  $result = $conn->query($sql);
  echo "<tr>";
  foreach ($columnNames as $columnName)
  {
    echo "<td>" . $columnName . "</td>";
  }
  echo "</tr>";
  if ($conn->errno == 0 && $result->num_rows > 0)
  {
    while($row = $result->fetch_assoc()) 
    {
      echo "<tr>";
	  foreach ($columnNames as $columnName)
      {
        $value = $row[$columnName];
		echo "<td>" . $value . "</td>";
	  }
      echo "</tr>";
    }
  }
  else
  {
    echo "no result for " . $sql . "<br>";
  }
}

function getOptionsForRows($columnInfos, $conn)
{
  $optionsForRows = array();
  foreach ($columnInfos as $columnInfo)
  {
	if (isset($columnInfo->foreignType))
	{
	  $sql = "SELECT id," . $columnInfo->foreignColumn . " FROM " . $columnInfo->foreignTable . " ORDER BY id ASC";
      $result = $conn->query($sql);
	  if ($conn->errno == 0)
	  {
		$optionsForRow = array();
		while ($row = $result->fetch_assoc()) 
		{
		  $optionsForRow[$row["id"]] = $row[$columnInfo->foreignColumn];
		}
		$optionsForRows[$columnInfo->databaseName] = $optionsForRow;
	  }
	  else
	  {
		echo "error for " . $sql . ":" . $conn->error . "<br>";
	  }
	}
  }
  return $optionsForRows;
}

function columnDataAsEditableTable($tableName, $columnInfos, $conn)
{
  $optionsForRows = getOptionsForRows($columnInfos, $conn);
  $columnNames = ColumnInfo::getDatabaseNames($columnInfos);
  $concatenatedColumnNames = implode(",", $columnNames);
  $sql = "SELECT id," . $concatenatedColumnNames . " FROM " . $tableName . " ORDER BY id ASC";
  $result = $conn->query($sql);
  echo '<form method="POST"><table><tr><td>Nummer</td>';
  foreach ($columnInfos as $columnInfo)
  {
    echo "<td>" . $columnInfo->getDisplayName() . "</td>";
  }
  echo "</tr>";
  if ($conn->errno == 0)
  {
    while($row = $result->fetch_assoc()) 
    {
      echo "<tr>";
	  $id = $row["id"];
      echo '<td>' . $id . '</td>';
	  foreach ($columnInfos as $columnInfo)
      {
        $value = $row[$columnInfo->databaseName];
		if ($columnInfo->foreignType == "dropdown")
		{
		  echo '<td><select name="'. $columnInfo->databaseName . $id . '">';
		  echo '<option value=""></option>"';
		  $optionsForRow = $optionsForRows[$columnInfo->databaseName];
		  foreach($optionsForRow as $optionId=>$optionDisplayName)
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
		else if ($columnInfo->foreignType == "text")
		{
		  $optionsForRow = $optionsForRows[$columnInfo->databaseName];
		  echo '<td><input name="'. $columnInfo->databaseName . $id . '" value="' . $optionsForRow[$value] . '" /></td>';
		}
		else
		{
		  echo '<td><input name="'. $columnInfo->databaseName . $id . '" value="' . $value . '" /></td>';
		}
	  }
      echo '<td><button type="submit" name="delete" value="' . $id . '">Löschen</button></td>';
      echo "</tr>";  
    }
  }
  else
  {
    echo "error for " . $sql . ":" . $conn->error . "<br>";
  }
  echo "<tr><td>neuer Eintrag</td>";
  foreach ($columnInfos as $columnInfo)
  {
	if ($columnInfo->foreignType == "dropdown")
	{
	  echo '<td><select name="'. $columnInfo->databaseName . '">"';
	  echo '<option value=""></option>"';
	  $optionsForRow = $optionsForRows[$columnInfo->databaseName];
	  foreach($optionsForRow as $optionId=>$optionDisplayName)
	  {
		echo '<option value="' . $optionId . '">' . $optionDisplayName . '</option>"';
	  }
	  echo '</select></td>';
	}
	else
	{
      echo '<td><input name="'. $columnInfo->databaseName . '"/></td>';
	}
  }
  echo '</tr></table><br/><button type="submit" name="save" value="save">Speichern</button></form>';
}

function saveEditableTableData($tableName, $columnInfos, $postData, $conn)
{
  if (!isset($postData["save"]) && !isset($postData["delete"]))
  {
    return;
  }

  $columnNames = ColumnInfo::getDatabaseNames($columnInfos);
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
  $optionsForRows = getOptionsForRows($columnInfos, $conn);

  $id = $row["id"];
  $updatedValues = array();
  foreach ($columnInfos as $columnInfo)
  {
	$dbValue = $row[$columnInfo->databaseName];
	$submittedValue = trim($postData[$columnInfo->databaseName . $id]);
	if ($columnInfo->foreignType == "text")
	{
	  $optionsForRow = $optionsForRows[$columnInfo->databaseName];
      if ($optionsForRow[$dbValue] != $submittedValue)
	  {
	    $sql = "UPDATE " . $columnInfo->foreignTable . " SET " . $columnInfo->foreignColumn . "=? WHERE id=?";
	    $statement = $conn->prepare($sql);
		$statement->bind_param("si", $submittedValue, $dbValue);
		if (!$statement->execute())
		{
		  echo "Execute of " . $sql . " with binding " . $submittedValue . ", " . $dbValue . "failed (" . $statement->error . ")";
		}
	  }
	}
	else
	{
      if ($dbValue != $submittedValue)
	  {
	    $updatedValues[$columnInfo->databaseName] = $submittedValue;
	  }
	}
  }
  if (count($updatedValues) > 0)
  {
	$updateColumns = implode("=?,", array_keys($updatedValues));
	$sql = "UPDATE " . $tableName . " SET " . $updateColumns . "=? WHERE ID=" . $id;
	$statement = $conn->prepare($sql);
	$types = str_repeat("s", count($updatedValues));
	$statement->bind_param($types, ...array_values($updatedValues)); 
	if (!$statement->execute())
	{
	  echo "Execute of " . $sql . " with binding " . types . ", ". implode(", ", $array_values($updatedValues)) . "failed (" . $statement->error . ")";
	}
  }
}

function doInserts($tableName, $columnInfos, $postData, $conn)
{
  $insertedValues = array();
  foreach ($columnInfos as $columnInfo)
  {
	${'_'.$columnInfo->databaseName} = trim($postData[$columnInfo->databaseName]);
	if (!empty(${'_'.$columnInfo->databaseName}))
	{
	  if (!isset($columnInfo->foreignType) || $columnInfo->foreignType == "dropdown")
	  {
	    $insertedValues[$columnInfo->databaseName] = &${'_'.$columnInfo->databaseName};
	  }
	  else if ($columnInfo->foreignType == "text")
	  {
	    $sql = "INSERT INTO " . $columnInfo->foreignTable . "(" . $columnInfo->foreignColumn . ") VALUES (?)";
	    $statement = $conn->prepare($sql);
		$statement->bind_param("s", ${'_'.$columnInfo->databaseName});
		$statement->execute();
		${'_'.$columnInfo->databaseName} = $conn->insert_id;
	    $insertedValues[$columnInfo->databaseName] = &${'_'.$columnInfo->databaseName};		
	  }
	}
  }
  if (count($insertedValues) > 0)
  {
	$insertColumns = implode(",", array_keys($insertedValues));
	$insertPlaceholders = implode(',', array_fill(0, count($insertedValues), '?'));
	$sql = "INSERT INTO " . $tableName . "(" . $insertColumns . ") VALUES (" . $insertPlaceholders . ")";
	$statement = $conn->prepare($sql);
	$types = str_repeat("s", count($insertedValues));
	$statement->bind_param($types, ...array_values($insertedValues)); 
	if (!$statement->execute())
	{
	  echo "Execute of " . $sql . " with binding " . types . ", ". implode(", ", $array_values($insertedValues)) . "failed (" . $statement->error . ")";
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
	$foreignDeletes = array();
    foreach ($columnInfos as $columnInfo)
    {
	  if ($columnInfo->foreignType == "text")
	  {
	    $sql = "SELECT " . $columnInfo->databaseName . " FROM " . $tableName . " WHERE id= ". $id;
	    $result = $conn->query($sql);
		$foreignId = null;
	    if ($conn->errno == 0 && ($row = $result->fetch_assoc()))
	    {
		  $foreignId = $row[$columnInfo->databaseName];
	    }
	    else
	    {
		  echo "Execute of " . $sql . "failed (" . $statement->error . ")";
	    }
	    $sql = "SELECT id FROM " . $tableName . " WHERE " . $columnInfo->databaseName . "=" . $foreignId;
	    $result = $conn->query($sql);
		$affectedIds = array();
	    if ($conn->errno == 0)
	    {
		  while($row = $result->fetch_assoc()) 
		  {
		    array_push($affectedIds, $row['id']);
		  }
	    }
	    else
	    {
		  echo "Execute of " . $sql . "failed (" . $statement->error . ")";
	    }
		if (count($affectedIds) <= 1)
		{
		  $foreignDeletes[$columnInfo->foreignTable] = $foreignId;
		}
	  }
    }
    $sql = "DELETE FROM ". $tableName . " WHERE ID=" . $id;
    $conn->query($sql);
    foreach ($foreignDeletes as $foreignTable => $foreignId)
	{
	  $sql = "DELETE FROM ". $foreignTable . " WHERE ID=" . $foreignId;
      $conn->query($sql);
	}
  }
}
?>