<?php
  $local_site_dir = $_POST['site'];
  $account = $_POST['account'];
  $account_type = $_POST['account_type'];
  $RelativePath = $_POST['relpath'];
  $bank_transaction = $_POST['bank_transaction'];
  $account_transaction = $_POST['account_transaction'];
  require("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
  require("$PrivateScriptsDir/mysql_connect.php");
  require("$BaseDir/common_scripts/date_funct.php");
  require("$DBAdminDir/common_funct.php");
  require("$CustomPagesPath/$RelativePath/db_funct.php");
  require("finance_funct.php");
  $db = finance_db_connect();

  $bank_rec_id = strtok($bank_transaction,'[');
  $bank_amount = strtok(']');
  $dummy_str = strtok('[');
  $bank_balance = strtok(']');
  $user_message = '';
  if ($bank_amount >= 0)
  {
    $credit_amount = $bank_amount;
    $debit_amount = 0;
  }
  else
  {
    $debit_amount = -$bank_amount;
    $credit_amount = 0;
  }
  $account_seq_no = strtok($account_transaction,'[');
  $account_amount = strtok(']');
  if (($bank_transaction == 'IMPORT') && ($account_transaction == 'IMPORT'))
  {
    // Populate bank import table from CSV file
    mysqli_delete_query($db,'bank_import','1',array());
  	$import_data = array();
  	$import_data = file("$BankImportDir/Account_$account.csv");
  	$first_line_skipped = false;
  	foreach ($import_data as $line)
  	{
  		if (!$first_line_skipped)
  		{
  			$first_line_skipped = true;
  		}
  		else
  		{
  			$line_elements = array();
  			$line_elements = str_getcsv($line,',','"');
  			$date = $line_elements[0];
  			$day = substr($date,0,2);
  			$month = substr($date,3,2);
  			$year = substr($date,6,4);
  			$mysql_date = "$year-$month-$day";
  			if ($account_type == 'bank')
  			{
  				$description = $line_elements[4];
  				$debit_amount = $line_elements[5];
  				$credit_amount = $line_elements[6];
  				if (!empty($debit_amount))
          {
            $amount = -$debit_amount;
          }
  				elseif (!empty($credit_amount))
  				{
  					$amount = $credit_amount;
  				}
  				$balance = $line_elements[7];
  			}
  			elseif ($account_type == 'credit-card')
  			{
  				$description = $line_elements[3];
  				$amount = $line_elements[4];
  				$amount = - $amount;
  				$balance = 0;
  			}
  			$description = addslashes($description);
  			$description = substr($description,0,31);
        $fields = 'date,description,amount,balance';
        $values = array('s',$mysql_date,'s',$description,'d',$amount,'d',$balance);
        mysqli_insert_query($db,'bank_import',$fields,$values);
  		}
  	}
  }
  elseif (($bank_transaction == 'IMPORT') || ($account_transaction == 'IMPORT'))
  {
    // No action
  }
  elseif ($account_transaction == 'NONE')
  {
    // Bank transaction not to be matched
    mysqli_query_normal($db,"UPDATE bank_import SET reconciled=1 WHERE rec_id=$bank_rec_id");
    $user_message = "<p>Bank transaction discarded.</p>\n";
  }
  elseif ($account_transaction == 'NEW')
  {
    $where_clause = 'rec_id=?';
    $where_values = array('i',$bank_rec_id);
    $query_result = mysqli_select_query($db,'bank_import','*',$where_clause,$where_values,'');
    if ($row = mysqli_fetch_assoc($query_result))
    {
      // Create new transaction. Update payee if regex match is found.
      // N.B. Multiple matches can be made against a given payee by creating additional table entries
      // using the original payee name with variable numbers of underscores added to the end.
      $date = $row['date'];
      $payee = addslashes($row['description']);
      $where_clause = "regex_match<>'^$'";
      $query_result2 = mysqli_select_query($db,'payees','*',$where_clause,array(),'');
      while ($row2 = mysqli_fetch_assoc($query_result2))
      {
        $pattern = "/{$row2['regex_match']}/i";
        if (preg_match($pattern,$payee))
        {
          $payee = addslashes(rtrim($row2['name'],'_'));
          break;
        }
      }
      $seq_no = next_seq_no($account);
      $fields = 'account,seq_no,date,payee,credit_amount,debit_amount,bank_import_id';
      $values = array('s',$account,'i',$seq_no,'s',$date,'s',$payee,'d',$credit_amount,'d',$debit_amount,'i',$bank_rec_id);
      mysqli_insert_query($db,'transactions',$fields,$values);

      // Go to the record edit screen.
      $primary_keys = array();
      $primary_keys['account'] = $account;
      $primary_keys['seq_no'] = $seq_no;
      $record_id = encode_record_id($primary_keys);
      header("Location: $BaseURL/$RelativePath/?-table=_view_account_$account&-action=edit&-recordid=$record_id");
      exit;
    }
  }
  else
  {
    if (($bank_transaction == 'null') || ($account_transaction == 'null'))
    {
      $user_message = "<p>No action specified.</p>\n";
    }
    elseif ((round($bank_amount,2) != round($account_amount,2)) && (!isset($_POST['auto_adjust'])) && (!isset($_POST['update_schedule'])))
    {
      // Non-matching amounts
      $user_message = "<p class=\"error-text\"><b>ERROR</b> - Attempt to reconcile non-matching amounts.</p>\n";
    }
    else
    {
      // Transaction to be reconciled
      $where_clause = 'rec_id=?';
      $where_values = array('i',$bank_rec_id);
      if ((is_numeric($bank_rec_id)) &&
          ($row = mysqli_fetch_assoc(mysqli_select_query($db,'bank_import','*',$where_clause,$where_values,''))))
      {
        mysqli_query_normal($db,"UPDATE bank_import SET reconciled=1 WHERE rec_id=$bank_rec_id");
        mysqli_query_normal($db,"UPDATE _view_account_$account SET reconciled=1 WHERE seq_no=$account_seq_no");
        if ((isset($_POST['auto_adjust'])) || (isset($_POST['update_schedule'])))
        {
          // Change register amount to match bank transaction
          mysqli_query_normal($db,"UPDATE _view_account_$account SET credit_amount=$credit_amount,debit_amount=$debit_amount WHERE seq_no=$account_seq_no");
          if (isset($_POST['update_schedule']))
          {
            // Update associated scheduled transaction
            $where_clause = 'seq_no=?';
            $where_values = array('i',$account_seq_no);
            $query_result2 = mysqli_select_query($db,"_view_account_$account",'*',$where_clause,$where_values,'');
            if ($row2 = mysqli_fetch_assoc($query_result2))
            {
              $payee = addslashes($row2['payee']);
              mysqli_query_normal($db,"UPDATE transactions SET credit_amount=$credit_amount,debit_amount=$debit_amount WHERE account='$account' AND payee='$payee' AND sched_freq<>'#'");
            }
          }
        }
        $where_clause = 'seq_no=?';
        $where_values = array('i',$account_seq_no);
        $query_result2 = mysqli_select_query($db,"_view_account_$account",'*',$where_clause,$where_values,'');
        if ($row2 = mysqli_fetch_assoc($query_result2))
        {
          update_account_balances($account,$row2['date']);
        }
        $add_clause = 'ORDER BY date DESC, seq_no DESC LIMIT 1';
        $query_result2 = mysqli_select_query($db,"_view_account_$account",'*','',array(),$add_clause);
        if ($row2 = mysqli_fetch_assoc($query_result2))
        {
          $user_message = "<p>Transaction reconciled. Bank balance = $bank_balance. Register balance = {$row2['reconciled_balance']}</p>\n";
        }
      }
    }
  }
  $message = urlencode($user_message);
  header("Location: $BaseURL/$RelativePath/?-action=reconcile_account&-account=$account&message=$message");
  exit;
?>
