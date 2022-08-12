<?php
  if (!function_exists('output_back_button'))
  {
    function output_back_button($size)
    {
      print("<p><a href=# onclick=\"window.history.back()\"><button style=\"font-size:$size;\">&lt; Back</button></a></p>");
    }
  }
?>
