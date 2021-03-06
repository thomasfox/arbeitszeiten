<?php
include "include/config.php";
include "include/db.php";

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
$rowFilter = empty($filter) ? '' : ' WHERE arbeitsgruppe_id = ' . $filter;

$columnInfos = array(
  new SimpleValueColumn("beschreibung", "Beschreibung", true, "s", "usag-minwidth-beschreibung"),
  new DropdownColumn("arbeitsgruppe_id", "Arbeitsgruppe", false, "arbeitsgruppe", "name"),
  new SimpleValueColumn("workdate", "Datum(TT.MM.JJJJ)", true, "d"),
  new StringMulticolumn("stunden", "Arbeitsstunden", "f", "arbeitszeit", "arbeitsauftrag_id", "familie", "name", "familie_id", $filterWhereClause, " name,id ASC "));

checkCsvExport("arbeitsauftrag", $columnInfos, $_POST, $conn, "workdate DESC,id DESC", $rowFilter, "Arbeitsgruppe", "arbeitsgruppe", "name");
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8"> 
<title>Unterseeschule - Arbeitszeit-Verwaltung nach Arbeitsgruppen</title>
<link rel="stylesheet" href="css/bootstrap.min.css" />
<link rel="stylesheet" href="css/arbeitsgruppen.css" />
</head>
<body>
  <script src="js/arbeitszeiten.js"></script>
  <div class="container-fluid">
    <h1>Arbeitsaufträge und Arbeitszeiten nach Arbeitsgruppen</h1>
<?php
checkAnyRowDeleted("arbeitsauftrag", $columnInfos, $_POST, $conn);
saveEditableTableData("arbeitsauftrag", $columnInfos, $_POST, $conn);
columnDataAsEditableTable("arbeitsauftrag", $columnInfos, $conn, "workdate DESC,id DESC", $rowFilter, "Arbeitsgruppe", "arbeitsgruppe", "name");
?>
  </div>
</body>