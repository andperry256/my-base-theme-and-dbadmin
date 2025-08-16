<?php
//================================================================================
/*
* Function readable_markup
*
* This function is used to display markup code (HTML/XML) visibly in the
* browser window when setting a debug point.
*/
//================================================================================

if (!function_exists('readable_markup')) {
    function readable_markup($str)
    {
        $str = str_replace("<","&lt;",$str);
        $str = str_replace(">","&gt;",$str);
        $str = str_replace("\n","<br />\n",$str);
        return $str;
}
}

//==============================================================================

if (!function_exists('output_back_button')) {
    function output_back_button($size)
    {
        print("<p><a href=# onclick=\"window.history.back()\"><button style=\"font-size:$size;\">&lt; Back</button></a></p>");
    }
}
//==============================================================================
