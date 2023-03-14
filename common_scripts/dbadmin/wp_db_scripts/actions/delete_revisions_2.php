<h1>Delete Post Revisions</h1>
<?php
	$db = admin_db_connect();

	// Remove post records
	mysqli_query_normal($db,"DELETE FROM wp_posts WHERE post_type='revision' OR post_status='auto-draft'");

	// Remove orphan post meta records
	$query_result = mysqli_query_normal($db,"SELECT post_id,post_name FROM wp_postmeta LEFT JOIN wp_posts ON wp_posts.ID=wp_postmeta.post_ID WHERE ID IS NULL");
	while ($row = mysqli_fetch_assoc($query_result))
	{
		mysqli_query_normal($db,"DELETE FROM wp_postmeta WHERE post_id={$row['post_id']}");
	}
?>
<p>Action Completed</p>
