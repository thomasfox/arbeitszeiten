<!DOCTYPE html>
<html>
<head>
<title>Unterseeschule - Arbeitszeit-Verwaltung</title>
</head>
<body>
<h1>Verwaltung der Arbeitszeit</h1>
<?php
include "include/config.php";
include "include/db.php";

$columnInfos = array(
  new ColumnInfo("arbeitsauftrag_id", "Arbeitsauftrag", "arbeitsauftrag", "beschreibung", "text"),
  new ColumnInfo("arbeitsgruppe_id", "Arbeitsgruppe", "arbeitsgruppe", "name", "dropdown"),
  new ColumnInfo("minutes", "Arbeitszeit in Minuten"), 
  new ColumnInfo("starttime", "Startdatum"),
  new ColumnInfo("familie_id", "Familie", "familie", "name", "dropdown"));

checkAnyRowDeleted("arbeitszeit", $columnInfos, $_POST, $conn);
saveEditableTableData("arbeitszeit", $columnInfos, $_POST, $conn);
columnDataAsEditableTable("arbeitszeit", $columnInfos, $conn);
?>
</body>