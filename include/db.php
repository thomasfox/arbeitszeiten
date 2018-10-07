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
  // $databaseName of column => array(optionId => optionDisplayName)
  $optionsForRows = array();
  foreach ($columnInfos as $columnInfo)
  {
	if (isset($columnInfo->foreignType))
	{
	  if ($columnInfo->foreignType == "dropdown" || $columnInfo->foreignType == "text")
	  {
	    $descriptionColumn = $columnInfo->foreignColumn;
        $optionsTable = $columnInfo->foreignTable;
	  }
	  else if ($columnInfo->foreignType == "multicolumn")
	  {
	    $descriptionColumn = $columnInfo->columnValuesDescriptionColumn;
        $optionsTable = $columnInfo->columnValuesTable;
	  }
	  $sql = "SELECT id," . $descriptionColumn . " FROM " . $optionsTable . " ORDER BY id ASC";
      $result = $conn->query($sql);
	  if ($conn->errno == 0)
	  {
		$optionsForRow = array();
		while ($row = $result->fetch_assoc()) 
		{
		  $optionsForRow[$row["id"]] = $row[$descriptionColumn];
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

function getValuesForMulticolumns($tableName, $columnInfos, $conn)
{
  // $databaseName of column => array(id of record in this table => (id of column => array(value)))
  $valuesForMulticolumns = array();
  foreach ($columnInfos as $columnInfo)
  {
	if ($columnInfo->foreignType == "multicolumn")
	{
	  $columnsToSelect = $tableName . ".id as id, " 
			. $columnInfo->foreignTable . "." . $columnInfo->foreignTableReferenceColumn . " as columnid," 
			. $columnInfo->foreignTable . '.id as foreignid,'
			. $columnInfo->foreignTable . '.' . $columnInfo->databaseName. " as foreignvalue";
	  $fromClause = $tableName . " JOIN " . $columnInfo->foreignTable . " ON " . $tableName . ".id=" . $columnInfo->foreignTable . "." . $columnInfo->foreignColumn;
	  $sql = "SELECT " . $columnsToSelect . " FROM " . $fromClause . " ORDER BY id,foreignid ASC";
      $result = $conn->query($sql);
	  if ($conn->errno == 0)
	  {
		$valuesForMulticolumn = array();
		while ($row = $result->fetch_assoc()) 
		{
		  if (!isset($valuesForMulticolumn[$row["id"]]))
		  {
			$valuesForMulticolumn[$row["id"]] = array(); 
		  }
		  $valuesForMulticolumn[$row["id"]][$row["columnid"]] = $row["foreignvalue"];
		}
		$valuesForMulticolumns[$columnInfo->databaseName] = $valuesForMulticolumn;
	  }
	  else
	  {
		echo "error for " . $sql . ":" . $conn->error . "<br>";
	  }
	}
  }
  return $valuesForMulticolumns;
}

function columnDataAsEditableTable($tableName, $columnInfos, $conn)
{
  $optionsForRows = getOptionsForRows($columnInfos, $conn);
  $valuesForMulticolumns = getValuesForMulticolumns($tableName, $columnInfos, $conn);
  $columnNames = ColumnInfo::getDatabaseNames($columnInfos);
  $concatenatedColumnNames = implode(",", $columnNames);
  $sql = "SELECT id," . $concatenatedColumnNames . " FROM " . $tableName . " ORDER BY id ASC";
  $result = $conn->query($sql);
  echo '<form method="POST"><table><tr><td>Nr</td>';
  foreach ($columnInfos as $columnInfo)
  {
	if ($columnInfo->foreignType != "multicolumn")
	{
      echo "<td>" . $columnInfo->getDisplayName() . "</td>";
	}
	else
	{
	  foreach ($optionsForRows[$columnInfo->databaseName] as $displayName)
	  {
		echo "<td>" . $displayName . "</td>";
	  }
	}
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
		if ($columnInfo->foreignType == "dropdown")
		{
          $value = $row[$columnInfo->databaseName];
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
		else if ($columnInfo->foreignType == "multicolumn")
		{
		  $optionsForRow = $optionsForRows[$columnInfo->databaseName];
		  $valuesForRow = $valuesForMulticolumns[$columnInfo->databaseName];
		  foreach ($optionsForRow as $optionId=>$optionDisplayName)
		  {
			$inputName = $columnInfo->databaseName . $id . '_' . $optionId;
			$inputValue = "";
			if (isset($valuesForRow[$id][$optionId]))
			{
			  $inputValue = $valuesForRow[$id][$optionId];
			}
		    echo '<td><input name="'. $inputName . '" value="' . $inputValue . '" /></td>';
		  }
		}
		else if ($columnInfo->foreignType == "text")
		{
          $value = $row[$columnInfo->databaseName];
		  $optionsForRow = $optionsForRows[$columnInfo->databaseName];
		  echo '<td><input name="'. $columnInfo->databaseName . $id . '" value="' . $optionsForRow[$value] . '" /></td>';
		}
		else
		{
          $value = $row[$columnInfo->databaseName];
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
  echo "<tr><td>neu:</td>";
  foreach ($columnInfos as $columnInfo)
  {
	if ($columnInfo->foreignType == "dropdown")
	{
	  echo '<td><select name="'. $columnInfo->databaseName . '">"';
	  echo '<option value=""></option>"';
	  $optionsForRow = $optionsForRows[$columnInfo->databaseName];
	  foreach ($optionsForRow as $optionId=>$optionDisplayName)
	  {
		echo '<option value="' . $optionId . '">' . $optionDisplayName . '</option>"';
	  }
	  echo '</select></td>';
	}
	else if ($columnInfo->foreignType == "multicolumn")
	{
	  $optionsForRow = $optionsForRows[$columnInfo->databaseName];
	  foreach ($optionsForRow as $optionId=>$optionDisplayName)
	  {
		$inputName = $columnInfo->databaseName . '_' . $optionId;
		echo '<td><input name="'. $inputName . '" /></td>';
	  }
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
  $valuesForMulticolumns = getValuesForMulticolumns($tableName, $columnInfos, $conn);

  $id = $row["id"];
  $updatedValues = array();
  foreach ($columnInfos as $columnInfo)
  {
	if ($columnInfo->foreignType != "multicolumn")
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
		  if (!empty($submittedValue))
		  {
	        $updatedValues[$columnInfo->databaseName] = $submittedValue;
		  }
		  else
		  {
			$updatedValues[$columnInfo->databaseName] = null;
		  }
	    }
	  }
	}
	else
	{
	  $optionsForRow = $optionsForRows[$columnInfo->databaseName];
	  $dbValuesForRow = $valuesForMulticolumns[$columnInfo->databaseName];
	  foreach ($optionsForRow as $optionId=>$optionDisplayName)
	  {
		$inputName = $columnInfo->databaseName . $id . '_' . $optionId;
		$submittedValue = trim($postData[$inputName]);
		$dbValue = "";
		if (isset($dbValuesForRow[$id][$optionId]))
		{
		  $dbValue = $dbValuesForRow[$id][$optionId];
		}
		if ($dbValue != $submittedValue)
		{
		  if (isset($dbValuesForRow[$id][$optionId]) && !empty($submittedValue))
		  {
		    $sql = "UPDATE " . $columnInfo->foreignTable . " SET " . $columnInfo->databaseName . "=? " 
				. "WHERE " . $columnInfo->foreignTableReferenceColumn . "=? "
				. "AND " . $columnInfo->foreignColumn . "=?";
	        $statement = $conn->prepare($sql);
		    $statement->bind_param("sii", $submittedValue, $optionId, $id); 
			if (!$statement->execute())
			{
			  echo "Execute of " . $sql . " with binding " . $submittedValue . ", ". $optionId . ", ". $id . "failed (" . $statement->error . ")";
			}
		  }
		  else if (!empty($submittedValue))
		  {
		    $sql = "INSERT INTO " . $columnInfo->foreignTable . " (" 
			    . $columnInfo->databaseName . ", " 
				. $columnInfo->foreignTableReferenceColumn . ","
				. $columnInfo->foreignColumn . ") VALUES (?,?,?)";
	        $statement = $conn->prepare($sql);
		    $statement->bind_param("sii", $submittedValue, $optionId, $id); 
			if (!$statement->execute())
			{
			  echo "Execute of " . $sql . " with binding " . $submittedValue . ", ". $optionId . ", ". $id . "failed (" . $statement->error . ")";
			}	
		  }
		  else
		  {
		    $sql = "DELETE FROM " . $columnInfo->foreignTable 
                . " WHERE " . $columnInfo->foreignTableReferenceColumn . "=" . $optionId
				. " AND " . $columnInfo->foreignColumn . "=" . $id;		 
			$conn->query($sql);
		  }
		}
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
	  var_dump($updatedValues);
	  echo "Execute of " . $sql . " with binding " . $types . ", ". implode(", ", array_values($updatedValues)) . "failed (" . $statement->error . ")";
	}
  }
}

function doInserts($tableName, $columnInfos, $postData, $conn)
{
  $optionsForRows = getOptionsForRows($columnInfos, $conn);
  $insertedValues = array();
  $insertedMulticolumnValues = array();
  foreach ($columnInfos as $columnInfo)
  {
	if ($columnInfo->foreignType != "multicolumn")
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
	else
	{
	  $optionsForRow = $optionsForRows[$columnInfo->databaseName];
	  foreach ($optionsForRow as $optionId=>$optionDisplayName)
	  {
	    $inputName = $columnInfo->databaseName . '_' . $optionId;
		$submittedValue = trim($postData[$inputName]);
        if (!empty($submittedValue))
		{
		  if (!isset($insertedMulticolumnValues[$columnInfo->databaseName]))
		  {
			$insertedMulticolumnValues[$columnInfo->databaseName] = array();
		  }
		  $insertedMulticolumnValues[$columnInfo->databaseName][$optionId] = $submittedValue;
		}
	  }
	}
  }
  if (count($insertedValues) > 0 || count($insertedMulticolumnValues) > 0)
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
	$id = $conn->insert_id;
  }
  foreach ($columnInfos as $columnInfo)
  {
    if (isset($insertedMulticolumnValues[$columnInfo->databaseName]))
    {
	  $insertValues = $insertedMulticolumnValues[$columnInfo->databaseName];
	  foreach ($insertValues as $optionId => $valueToInsert)
	  {
		$sql = "INSERT INTO " . $columnInfo->foreignTable . " (" 
			. $columnInfo->databaseName . ", " 
			. $columnInfo->foreignTableReferenceColumn . ","
			. $columnInfo->foreignColumn . ") VALUES (?,?,?)";
		$statement = $conn->prepare($sql);
		$statement->bind_param("sii", $valueToInsert, $optionId, $id); 
		if (!$statement->execute())
		{
		  echo "Execute of " . $sql . " with binding " . $valueToInsert . ", ". $optionId . ", ". $id . "failed (" . $statement->error . ")";
		}		
	  }
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
	  else if ($columnInfo->foreignType == "multicolumn")
	  {
	    $sql = "DELETE FROM ". $columnInfo->foreignTable . " WHERE " . $columnInfo->foreignColumn . "=" . $id;
        $conn->query($sql);
	    if ($conn->errno != 0)
	    {
	      echo "Execute of " . $sql . "failed (" . $conn->error . ")";
	    }
	  }
    }
    foreach ($foreignDeletes as $foreignTable => $foreignId)
	{
	  $sql = "DELETE FROM ". $foreignTable . " WHERE ID=" . $foreignId;
      $conn->query($sql);
	  if ($conn->errno != 0)
	  {
	    echo "Execute of " . $sql . "failed (" . $conn->error . ")";
	  }
	}
    $sql = "DELETE FROM ". $tableName . " WHERE ID=" . $id;
    $conn->query($sql);
    if ($conn->errno != 0)
	{
	  echo "Execute of " . $sql . "failed (" . $conn->error . ")";
	}
  }
}
?>