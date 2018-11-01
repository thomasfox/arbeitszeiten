var entryChanged = false;

function applyFilter(paramKey, paramValue)
{
  if (entryChanged)
  {
    if (!confirm("Alle Änderungen werden verworfen. Wollen Sie die Filterung ändern, ohne zu speichern?"))
    {
      var filter = document.getElementById("filter");
      filter.value = filter.getAttribute('data-initial');
      return;
    }
  }
  var currentUrl = window.location.href;
  if (currentUrl.indexOf("?") > 0) {
    currentUrl = currentUrl.substring(0, currentUrl.indexOf("?"));
  }

  currentUrl += "?" + paramKey + "=" + paramValue;
  window.location.replace(currentUrl);
}

function askForChangedValueSave(event, href)
{
  if (entryChanged)
  {
    if (!confirm("Alle Änderungen werden verworfen. Wollen Sie die Seite verlassen, ohne zu speichern?"))
    {
      return false;
    }
  }
  window.location.replace(href);
}

function markChanged()
{
  entryChanged = true;
}
