<!DOCTYPE html>
<html>
<head>
<title>Unterseeschule - Familien-Verwaltung</title>
</head>
<body>
<h1>Verwaltung der Familien</h1>
<?php
include "include/config.php";
include "include/db.php";

$columnInfos = array(new ColumnInfo("name", "Name", true), 
  new ColumnInfo("sollstunden", "Sollstunden", false),
  new ColumnInfo("arbeitsgruppe_id", "Mitglied in Arbeitsgruppe", false, "i", "arbeitsgruppe", "name", "nToM", "arbeitsgruppe_familie"));

checkAnyRowDeleted("familie", $columnInfos, $_POST, $conn);
saveEditableTableData("familie", $columnInfos, $_POST, $conn);
columnDataAsEditableTable("familie", $columnInfos, $conn);

?>
</body>