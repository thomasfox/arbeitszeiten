<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8"> 
<title>Unterseeschule - Arbeitszeit-Verwaltung</title>
<link rel="stylesheet" href="css/bootstrap.min.css" />
<link rel="stylesheet" href="css/arbeitsgruppen.css" />
</head>
<body>
  <div class="container-fluid">
    <h1>Verwaltung der Arbeitsaufträge und Arbeitszeiten</h1>

<?php
include "include/config.php";
include "include/db.php";

printFilterForm("Arbeitsgruppe", "arbeitsgruppe", "name", $conn);
$filter = null;
$filterWhereClause = "";
if (isset($_GET['filter']))
{
  $filter = $_GET['filter'];
  if (!empty($filter))
  {
	if (!checkIdValueExists("arbeitsgruppe", $filter, $conn))
	{
      alertError("ungültiger Filterwert " . $filter . " wird ignoriert");
	  $filter = null;
	  $filterWhereClause = "";
	}
	else
	{
      $filterWhereClause = ' WHERE exists (SELECT * from arbeitsgruppe_familie WHERE familie_id=familie.id AND arbeitsgruppe_id=' . $filter . ") ";
	}
  }
}

$columnInfos = array(
  new SimpleValueColumn("beschreibung", "Beschreibung", true),
  new DropdownColumn("arbeitsgruppe_id", "Arbeitsgruppe", false, "arbeitsgruppe", "name"),
  new SimpleValueColumn("workdate", "Datum(TT.MM.JJJJ)", true, "d"),
  new StringMulticolumn("stunden", "Arbeitsstunden", "f", "arbeitszeit", "arbeitsauftrag_id", "familie", "name", "familie_id", $filterWhereClause));

checkAnyRowDeleted("arbeitsauftrag", $columnInfos, $_POST, $conn);
saveEditableTableData("arbeitsauftrag", $columnInfos, $_POST, $conn);
columnDataAsEditableTable("arbeitsauftrag", $columnInfos, $conn, empty($filter) ? null : ' WHERE arbeitsgruppe_id = ' . $filter);
?>
  </div>
</body>