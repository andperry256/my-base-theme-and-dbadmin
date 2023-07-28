function selectFile(dropdown,site,mode)
{
  var option_value = dropdown.options[dropdown.selectedIndex].value;
  location.href = './display_access_logs.php?site=' + site + '&file=' + encodeURIComponent(option_value) + '&mode=' + mode;
}
