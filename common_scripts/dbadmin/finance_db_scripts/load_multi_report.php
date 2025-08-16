<?php
  // Variables $local_site_dir and $relative_path must be set up beforehand
  require("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
  $url = "$base_url/$relative_path/?-action=display_transaction_report";
  if (!empty($_POST['account'])) {
      $url .= "&account={$_POST['account']}";
  }
  if (!empty($_POST['fund'])) {
      $url .= "&fund={$_POST['fund']}";
  }
  if (!empty($_POST['category'])) {
      $url .= "&category={$_POST['category']}";
  }
  if (!empty($_POST['payee'])) {
      $url .= "&payee={$_POST['payee']}";
  }
  if (!empty($_POST['currency'])) {
      $url .= "&currency={$_POST['currency']}";
  }
  header("Location: $url");
  exit;
