<!DOCTYPE html>
<html>
<head>
<title>Unterseeschule - Arbeitsgruppen-Verwaltung</title>
</head>
<body>
<h1>Verwaltung der Arbeitsgruppen</h1>
<?php
include "include/config.php";
include "include/db.php";

$columnInfos = array(new SimpleValueColumn("name", "Name", true));

checkAnyRowDeleted("arbeitsgruppe", $columnInfos, $_POST, $conn);
saveEditableTableData("arbeitsgruppe", $columnInfos, $_POST, $conn);
columnDataAsEditableTable("arbeitsgruppe", $columnInfos, $conn);

?>
</body>