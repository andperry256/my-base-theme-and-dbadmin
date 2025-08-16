<?php
//==============================================================================

$db = admin_db_connect();

print("<h1>Repair Database</h1>\n");

print("<p><a href=\"index.php?-action=repair_database_2&dry-run\"><button>Dry Run</button></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;");
print("<a href=\"index.php?-action=repair_database_2\"><button>Live Run</button></a></p>\n");

//==============================================================================
