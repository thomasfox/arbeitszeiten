<?php
include "include/config.php";
include "include/db.php";

$columnInfos = array(new SimpleValueColumn("name", "Name", true));

checkCsvExport("arbeitsgruppe", $columnInfos, $_POST, $conn, "name,id ASC");
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8"> 
<title>Unterseeschule - Arbeitsgruppen-Verwaltung</title>
<link rel="stylesheet" href="css/bootstrap.min.css" />
<link rel="stylesheet" href="css/arbeitsgruppen.css" />
</head>
<body>
  <script src="js/arbeitszeiten.js"></script>
  <div class="container-fluid">
    <h1>Verwaltung der Arbeitsgruppen</h1>
<?php
checkAnyRowDeleted("arbeitsgruppe", $columnInfos, $_POST, $conn);
saveEditableTableData("arbeitsgruppe", $columnInfos, $_POST, $conn);
columnDataAsEditableTable("arbeitsgruppe", $columnInfos, $conn, "name,id ASC");
?>
  </div>
</body>