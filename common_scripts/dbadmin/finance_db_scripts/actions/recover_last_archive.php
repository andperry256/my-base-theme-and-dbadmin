<?php
//==============================================================================

$db = admin_db_connect();
print("<h1>Recover Last Archive</h1>\n");

if (isset($_POST['archive_end_date']))
{
  $archive_end_date = $_POST['archive_end_date'];
  $archive_end_month = accounting_month($archive_end_date);
  $archive_year = substr($archive_end_month,0,4);
  print("<p>Deleting 'Balance B/F' transactions<br />\n");
  $where_clause = "payee='Balance B/F' AND acct_month=?";
  $where_values = array('s',$archive_end_month);
  $query_result = mysqli_select_query($db,'transactions','*',$where_clause,$where_values,'');
  while ($row = mysqli_fetch_assoc($query_result))
  {
    $where_clause = 'account=? AND transact_seq_no=?';
    $where_values = array('s',$row['account'],'i',$row['seq_no']);
    mysqli_delete_query($db,'splits',$where_clause,$where_values);
  }
  $where_clause = "payee='Balance B/F' AND acct_month=?";
  $where_values = array('s',$archive_end_month);
  mysqli_delete_query($db,'transactions',$where_clause,$where_values);
  print("Copying transactions from archive<br />\n");
  $query = "INSERT INTO transactions SELECT * FROM archived_transactions_$archive_year";
  mysqli_query_normal($db,$query);
  $query = "INSERT INTO splits SELECT * FROM archived_splits_$archive_year";
  mysqli_query_normal($db,$query);
  print("Operation completed.</p>\n");
}
else
{
  $this_year = (int)date('Y');
  for ($year=START_YEAR; $year<$this_year; $year++)
  {
    $year_start = sprintf("%04d-%02d-%02d",$year,YEAR_START_MONTH,MONTH_START_DAY);
    $next_year_start = sprintf("%04d-%02d-%02d",$year+1,YEAR_START_MONTH,MONTH_START_DAY);
    $where_clause = 'date>=? AND date<?';
    $where_values = array('s',$year_start,'s',$next_year_start);
    if (mysqli_num_rows(mysqli_select_query($db,'transactions','*',$where_clause,$where_values,'')) > 20)
    {
      // Full year found
      $archive_end_date = AddDays($year_start,-1);
      break;
    }
  }
  if (!isset($archive_end_date))
  {
    exit("Error - this should not occur!!");
  }
  print("<p>You are about to recover the archive for the year ending $archive_end_date. Are you sure?</p>\n");
  print("<form method=\"post\">\n");
  print("<input type=\"submit\" value=\"Continue\">\n");
  print("<input type=\"hidden\" name=\"archive_end_date\" value=\"$archive_end_date\">\n");
  print("</form>\n");
}

//==============================================================================
?>
