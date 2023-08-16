<h1>Delete Post Revisions</h1>
<?php
	$db = admin_db_connect();

	// Remove post records
	$where_clause = "post_type='revision' OR post_status='auto-draft'";
  $where_values = array();
  mysqli_delete_query($db,'wp_posts',$where_clause,$where_values);

	// Remove orphan post meta records
	$query_result = mysqli_query_normal($db,"SELECT post_id,post_name FROM wp_postmeta LEFT JOIN wp_posts ON wp_posts.ID=wp_postmeta.post_ID WHERE ID IS NULL");
	while ($row = mysqli_fetch_assoc($query_result))
	{
		$where_clause = 'post_id=?';
	  $where_values = array('i',$row['post_id']);
	  mysqli_delete_query($db,'wp_postmeta',$where_clause,$where_values);
	}
?>
<p>Action Completed</p>
