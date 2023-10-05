<?php
//==============================================================================
/*
  The following array defines the valid widget types. At present there is only
  one attribute definable against each type, namely a boolean flag to indicate
  whether fields of the given type are to be included in a search. Should
  other attributes be required in the future, then the structure will be
  changed so that each array element becomes a sub-array of elements.
*/
//==============================================================================

$WidgetTypes = array (
  'auto-increment' => false,
  'checkbox' => false,
  'checklist' => false,
  'date' => false,
  'enum' => true,
  'file' => false,
  'hidden' => false,
  'input-num' => false,
  'input-text' => true,
  'input-text-small' => false,
  'password' => false,
  'select' => true,
  'static' => true,
  'static-date' => false,
  'textarea' => true,
  'time' => false,
);

//==============================================================================
?>
