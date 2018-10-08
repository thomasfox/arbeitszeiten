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

$columnInfos = array(new SimpleValueColumn("name", "Name", true), 
  new SimpleValueColumn("sollstunden", "Sollstunden", false),
  new CheckboxMulticolumn("arbeitsgruppe_id", "Mitglied in Arbeitsgruppe", false, "i", "arbeitsgruppe", "name", "arbeitsgruppe_familie"));

checkAnyRowDeleted("familie", $columnInfos, $_POST, $conn);
saveEditableTableData("familie", $columnInfos, $_POST, $conn);
columnDataAsEditableTable("familie", $columnInfos, $conn);

?>
</body>