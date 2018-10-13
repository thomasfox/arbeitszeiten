<!DOCTYPE html>
<html>
<head>
<title>Unterseeschule - Arbeitszeit-Verwaltung</title>
<link rel="stylesheet" href="css/bootstrap.min.css" />
</head>
<body>
  <div class="container-fluid">
    <h1>Verwaltung der Arbeitsaufträge und Arbeitszeiten</h1>

<?php
include "include/config.php";
include "include/db.php";

printFilterForm("Arbeitsgruppe", "arbeitsgruppe", "name", $conn);
$filter = $_GET['filter'];

$columnInfos = array(
  new SimpleValueColumn("beschreibung", "Beschreibung", true),
  new DropdownColumn("arbeitsgruppe_id", "Arbeitsgruppe", false, "arbeitsgruppe", "name"),
  new SimpleValueColumn("workdate", "Datum(TT:MM:JJJJ)", true, "d"),
  new StringMulticolumn("minutes", "Arbeitszeit in Minuten", "arbeitszeit", "arbeitsauftrag_id", "familie", "name", "familie_id"));

checkAnyRowDeleted("arbeitsauftrag", $columnInfos, $_POST, $conn);
saveEditableTableData("arbeitsauftrag", $columnInfos, $_POST, $conn);
columnDataAsEditableTable("arbeitsauftrag", $columnInfos, $conn, empty($filter) ? null : ' WHERE arbeitsgruppe_id = ' . $filter);
?>
  </div>
</body>