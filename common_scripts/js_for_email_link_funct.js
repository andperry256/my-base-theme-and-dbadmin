function copyAddresses(id)
{
  var copyText = document.getElementById(id);
  copyText.value = copyText.value.replace(/#/g, "@");

  var textarea = document.createElement('textarea');
  textarea.textContent = copyText.value;
  document.body.appendChild(textarea);

  var selection = document.getSelection();
  var range = document.createRange();
  range.selectNode(textarea);
  selection.removeAllRanges();
  selection.addRange(range);
  console.log('copy success', document.execCommand('copy'));
  selection.removeAllRanges();
  document.body.removeChild(textarea);

  var isList = copyText.value.search(',');
  if (isList >= 0) {
    alert("Addresses [ " + copyText.value + " ] copied");
  } else {
    alert("Address [ " + copyText.value + " ] copied");
  }
}
function openInMailClient(id)
{
  var copyText = document.getElementById(id);
  copyText.value = copyText.value.replace(/#/g, "@");
  window.open('mailto:' + copyText.value,'MailApp')
}
