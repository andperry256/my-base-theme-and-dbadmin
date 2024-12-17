// Function to check/un-check all check boxes in a form in response to
// an action on the designated 'check all' check box.
function checkAll(source)
{
  var checkboxes = new Array();
  checkboxes = document.getElementsByTagName('input');
  for (var i=0; i<checkboxes.length; i++)  {
    if (checkboxes[i].type == 'checkbox')   {
      checkboxes[i].checked = source.checked;
    }
  }
}

// Function to submit the form with no specific option
function submitForm(form)
{
  element = document.getElementById("submitted");
  element.value = '#';
  form.submit();
}

// Function to apply a search to the table
function applySearch(form)
{
  element = document.getElementById("submitted");
  element.value = 'apply_search';
  form.submit();
}

// Function to confirm submission for a delete action
function confirmDelete(form)
{
  if (confirm("Delete the selected records?")) {
    element = document.getElementById("submitted");
    element.value = 'delete';
    form.submit();
  }
}

// Function to confirm submission for a renumber action
function confirmRenumber(form)
{
    if (confirm("Renumber the records?")) {
        element = document.getElementById("submitted");
        element.value = 'renumber_records';
        form.submit();
      }
    }

// Functions to perform submission for an update action
function selectUpdate(form)
{
  element = document.getElementById("submitted");
  element.value = 'select_update';
  form.submit();
}
function selectUpdateAll(form)
{
  element = document.getElementById("submitted");
  element.value = 'select_update_all';
  form.submit();
}
function runUpdate(form)
{
  element = document.getElementById("submitted");
  element.value = 'run_update';
  form.submit();
}
function runUpdateAll(form)
{
  element = document.getElementById("submitted");
  element.value = 'run_update_all';
  form.submit();
}

// Functions to perform submission for a copy action
function selectCopy(form)
{
  element = document.getElementById("submitted");
  element.value = 'select_copy';
  form.submit();
}
function runCopy(form)
{
  element = document.getElementById("submitted");
  element.value = 'run_copy';
  form.submit();
}
