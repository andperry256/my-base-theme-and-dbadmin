<?php
//==============================================================================

$db = admin_db_connect();
$local_site_dir = $_GET['site'];
$account = $_GET['account'];

print("<form method=\"post\" action=\"$custom_pages_url/$relative_path/bulk_reconcile_action.php\">\n");
print("<table>\n");
print("<tr><td colspan=2>Bank Transaction</td><td>Amount</td><td colspan=2>Register Transaction</td><td></td></tr>\n");

// Loop through unreconciled bank transactions.
$where_clause = 'reconciled=0';
$add_clause = 'ORDER BY rec_id DESC';
$query_result = mysqli_select_query($db,'bank_import','*',$where_clause,[],$add_clause);
while ($row = mysqli_fetch_assoc($query_result)) {
    $match = find_matching_transaction($account,$row['date'],$row['amount']);
    if ($match > 0) {
        // Unique match found.
        $where_clause = 'account=? AND seq_no=?';
        $where_values = ['s',$account,'i',$match];
        if ($row2 = mysqli_fetch_assoc(mysqli_select_query($db,'transactions','*',$where_clause,$where_values,''))) {
            print("<tr><td>{$row['date']}</td><td>{$row['description']}</td>");
            $amount = ($row['amount'] > 0)
                ? 'C'.sprintf("%01.2f",$row['amount'])
                : 'D'.sprintf("%01.2f",-$row['amount']);
            print("<td style=\"text-align:right\">$amount</td>");
            print("<td>{$row2['date']}</td><td>{$row2['payee']}</td>");
            print("<td><input type=\"checkbox\" name=\"chk_{$row['rec_id']}\" checked></td>");
            print("</tr>\n");
        }
    }
}
print("</table>\n");
print("<input type=\"hidden\" name=\"site\" value=\"$local_site_dir\">");
print("<input type=\"hidden\" name=\"account\" value=\"$account\">");
print("<input type=\"submit\" value=\"Continue\">");
print("</form>\n");
print("<br /><a href=\"$base_url/$relative_path/?-action=reconcile_account&-account=$account\"><button>Go Back</button></a>\n");

//==============================================================================
