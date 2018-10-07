<!DOCTYPE html>
<html>
<head>
<title>Unterseeschule - Arbeitszeit-Verwaltung</title>
</head>
<body>
<h1>Verwaltung der Arbeitsaufträge und Arbeitszeiten</h1>
<?php
include "include/config.php";
include "include/db.php";

$columnInfos = array(
  new ColumnInfo("beschreibung", "Beschreibung", "true"),
  new ColumnInfo("arbeitsgruppe_id", "Arbeitsgruppe", false, "i", "arbeitsgruppe", "name", "dropdown"),
  new ColumnInfo("workdate", "Datum(TT:MM:JJJJ)", true, "d"),
  new ColumnInfo("starttime", "Startzeit(SS:MM(:ss))", false, "t"),
  new ColumnInfo("minutes", "Arbeitszeit in Minuten", false, "i", "arbeitszeit", "arbeitsauftrag_id", "multicolumn", "familie", "name", "familie_id"));

checkAnyRowDeleted("arbeitsauftrag", $columnInfos, $_POST, $conn);
saveEditableTableData("arbeitsauftrag", $columnInfos, $_POST, $conn);
columnDataAsEditableTable("arbeitsauftrag", $columnInfos, $conn);
?>
</body>