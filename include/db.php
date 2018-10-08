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

function columnDataAsEditableTable($tableName, $columnInfos, $conn)
{
  $optionsForRows = getOptionsToSelectFrom($columnInfos, $conn);
  $valuesForMulticolumns = getValuesForMulticolumns($tableName, $columnInfos, $conn);
  $columnNames = ColumnInfo::getDatabaseNames($columnInfos);
  $concatenatedColumnNames = implode(",", $columnNames);
  $sql = "SELECT id," . $concatenatedColumnNames . " FROM " . $tableName . " ORDER BY id ASC";
  $result = $conn->query($sql);
  echo '<form method="POST"><table><tr><td>Nr</td>';
  foreach ($columnInfos as $columnInfo)
  {
	if ($columnInfo->foreignType == "multicolumn" || $columnInfo->foreignType == "nToM")
	{
	  foreach ($optionsForRows[$columnInfo->databaseName] as $displayName)
	  {
		echo "<td>" . $displayName . "</td>";
	  }
	}
	else
	{
      echo "<td>" . $columnInfo->getDisplayName() . "</td>";
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
		else if ($columnInfo->foreignType == "nToM")
		{
		  $optionsForRow = $optionsForRows[$columnInfo->databaseName];
		  $valuesForRow = $valuesForMulticolumns[$columnInfo->databaseName];
		  foreach ($optionsForRow as $optionId=>$optionDisplayName)
		  {
			$inputName = $columnInfo->databaseName . $id . '_' . $optionId;
		    $checkedString = "";
		    if (isset($valuesForRow[$id][$optionId]))
		    {
		      $checkedString = ' checked="checked"';
		    }
		    echo '<td><input type="checkbox" name="'. $inputName . '" value="1" ' . $checkedString . '/></td>';
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
		  if ($columnInfo->datatype == "d" and !empty($value))
		  {
			$value = DateTime::createFromFormat("Y-m-d", $value)->format("d.m.Y");
		  }
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
	else if ($columnInfo->foreignType == "nToM")
	{
	  $optionsForRow = $optionsForRows[$columnInfo->databaseName];
	  foreach ($optionsForRow as $optionId=>$optionDisplayName)
	  {
		$inputName = $columnInfo->databaseName . '_' . $optionId;
		echo '<td><input type="checkbox" name="'. $inputName . '" value="1" /></td>';
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
  $optionsForRows = getOptionsToSelectFrom($columnInfos, $conn);
  $valuesForMulticolumns = getValuesForMulticolumns($tableName, $columnInfos, $conn);

  $id = $row["id"];
  $updatedValues = array();
  $validationFailed = false;
  foreach ($columnInfos as $columnInfo)
  {
	if ($columnInfo->foreignType != "multicolumn" && $columnInfo->foreignType != "nToM")
	{
	  $submittedValue = trim($postData[$columnInfo->databaseName . $id]);
	  if ($columnInfo->required && empty($submittedValue))
	  {
		echo "Die Spalte " . $columnInfo->displayName . " in Datensatz Nr. " . $id . " ist ein Pflichtfeld und muss ausgefüllt werden. Der Datensatz wurde nicht gespeichert.<br/>";
		$validationFailed = true;
		continue;
	  }
      $dbValue = $row[$columnInfo->databaseName];	  
	  if ($columnInfo->foreignType == "text" && !$validationFailed) // TODO does not work in all cases, we should collect all data before writing into db
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
			if ($columnInfo->datatype == "d")
			{
			  $valueAsDate = DateTime::createFromFormat("d.m.Y", $submittedValue);
			  if ($valueAsDate == false)
			  {
		        echo "Die Spalte " . $columnInfo->displayName . " in Datensatz Nr. " . $id . " hat ein ungültiges Datumsformat.  Bitte verwenden Die das Format TT.MM.JJJJ. Der Datensatz wurde nicht gespeichert.<br/>";
		        $validationFailed = true;
		        continue;
			  }
			  $updatedValues[$columnInfo->databaseName] = $valueAsDate->format("Y-m-d");
			}
			else
			{
	          $updatedValues[$columnInfo->databaseName] = $submittedValue;
			}
		  }
		  else
		  {
			$updatedValues[$columnInfo->databaseName] = null;
		  }
	    }
	  }
	}
	else if (!$validationFailed) // TODO validation check does not work in all cases, we should collect all data before writing into db
	{
	  $optionsForRow = $optionsForRows[$columnInfo->databaseName];
	  $dbValuesForRow = $valuesForMulticolumns[$columnInfo->databaseName];
	  foreach ($optionsForRow as $optionId=>$optionDisplayName)
	  {
		$inputName = $columnInfo->databaseName . $id . '_' . $optionId;
		$submittedValue = "";
		if (isset($postData[$inputName]))
		{
		  $submittedValue = trim($postData[$inputName]);
		}
		$dbValue = "";
		if (isset($dbValuesForRow[$id][$optionId]))
		{
		  $dbValue = $dbValuesForRow[$id][$optionId];
		}
		if ($dbValue != $submittedValue)
		{
		  if (isset($dbValuesForRow[$id][$optionId]) && !empty($submittedValue) && $columnInfo->foreignType != "multicolumn")
		  {
			if ($columnInfo->foreignType != "multicolumn")
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
		  }
		  else if (!empty($submittedValue))
		  {
			if ($columnInfo->foreignType == "multicolumn")
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
		      $sql = "INSERT INTO " . $columnInfo->columnValuesTable 
			      . " (" . $tableName . "_id, " . $columnInfo->foreignTable . "_id) "
				  . "VALUES (". $id . ',' . $optionId . ')';
			  $conn->query($sql);
			  if ($conn->errno != null)
	          {
		        echo "error for " . $sql . ":" . $conn->error . "<br>";
	          }
			}
		  }
		  else if ($columnInfo->foreignType == "multicolumn")
		  {
		    $sql = "DELETE FROM " . $columnInfo->foreignTable 
                . " WHERE " . $columnInfo->foreignTableReferenceColumn . "=" . $optionId
				. " AND " . $columnInfo->foreignColumn . "=" . $id;		 
			$conn->query($sql);
			if ($conn->errno != null)
	        {
		      echo "error for " . $sql . ":" . $conn->error . "<br>";
	        }
		  }
		  else
		  {
		    $sql = "DELETE FROM " . $columnInfo->columnValuesTable 
			    . " WHERE " . $tableName . '_id=' . $id . ' AND ' . $columnInfo->foreignTable . "_id=" . $optionId;
			$conn->query($sql);
			if ($conn->errno != null)
	        {
		      echo "error for " . $sql . ":" . $conn->error . "<br>";
	        }
		  }
		}
	  }
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
	  var_dump($updatedValues);
	  echo "Execute of " . $sql . " with binding " . $types . ", ". implode(", ", array_values($updatedValues)) . "failed (" . $statement->error . ")";
	}
  }
}

function doInserts($tableName, $columnInfos, $postData, $conn)
{
  $optionsForRows = getOptionsToSelectFrom($columnInfos, $conn);
  $insertedValues = array();
  $insertedMulticolumnValues = array();
  $validationError = false;
  foreach ($columnInfos as $columnInfo)
  {
	if ($columnInfo->foreignType != "multicolumn" && $columnInfo->foreignType != "nToM")
	{
	  ${'_'.$columnInfo->databaseName} = "";
	  if (isset($postData[$columnInfo->databaseName]))
	  {
	    ${'_'.$columnInfo->databaseName} = trim($postData[$columnInfo->databaseName]);
	  }
	  if (!empty(${'_'.$columnInfo->databaseName}))
	  {
	    if (!isset($columnInfo->foreignType) || $columnInfo->foreignType == "dropdown")
	    {
	      $insertedValues[$columnInfo->databaseName] = &${'_'.$columnInfo->databaseName};
		  if ($columnInfo->datatype == "d" and !empty($insertedValues[$columnInfo->databaseName]))
		  {
		    $valueAsDate = DateTime::createFromFormat("d.m.Y", $insertedValues[$columnInfo->databaseName]);
			if ($valueAsDate == false)
			{
		      echo "Die Spalte " . $columnInfo->displayName . " im neuen Datensatz hat ein ungültiges Datumsformat. Bitte verwenden Die das Format TT.MM.JJJJ. Der Datensatz wurde nicht gespeichert.<br/>";
		      $validationError = true;
		      continue;
			}
			$insertedValues[$columnInfo->databaseName] = $valueAsDate->format("Y-m-d");
		  }
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
		$submittedValue = "";
		if (isset($postData[$inputName]))
		{
		  $submittedValue = trim($postData[$inputName]);
		}
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
  if (count($insertedValues) > 0 || count($insertedMulticolumnValues) > 0 && !$validationError)
  {
    foreach ($columnInfos as $columnInfo)
	{
	  if ($columnInfo->foreignType != "multicolumn")
	  {
	    if ($columnInfo->required && !isset($insertedValues[$columnInfo->databaseName]))
		{
		  echo "Die Spalte " . $columnInfo->displayName . " im neuen Datensatz ist ein Pflichtfeld und muss ausgefüllt werden. Der Datensatz wurde nicht gespeichert.<br/>";
		  $validationError = true;
		}
	  }
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
  }
  foreach ($columnInfos as $columnInfo)
  {
    if (isset($insertedMulticolumnValues[$columnInfo->databaseName]))
    {
	  if ($columnInfo->foreignType == "multicolumn")
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
	  else
	  {
	    $insertValues = $insertedMulticolumnValues[$columnInfo->databaseName];
	    foreach ($insertValues as $optionId => $valueToInsert)
	    {
	      $sql = "INSERT INTO " . $columnInfo->columnValuesTable 
		      . " (" . $tableName . "_id, " . $columnInfo->foreignTable . "_id) "
		      . "VALUES (". $id . ',' . $optionId . ')';
	      $conn->query($sql);
	      if ($conn->errno != null)
	      {
		    echo "error for " . $sql . ":" . $conn->error . "<br>";
	      }
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