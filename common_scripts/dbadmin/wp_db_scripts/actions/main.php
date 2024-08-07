<?php
$dbname = (isset($_GET['dbname'])) ? $_GET['dbname'] : get_session_var('dbname');
if (!empty($dbname))
{
  print("<p><strong>Database - <span class=\"highlight-success\">$dbname</span></strong></p>\n");
}
$db = admin_db_connect();
create_view_structure('_view_pages','wp_posts',"post_type='page' AND (post_status='draft' OR post_status='publish') ORDER BY post_name ASC");
create_view_structure('_view_posts','wp_posts',"post_type='post' AND (post_status='draft' OR post_status='publish') ORDER BY post_name ASC");
$set_fields = 'post_content';
$set_values = array('s','[No WordPress content - see custom scripts]');
$where_clause = "post_type='page' AND (post_content IS NULL OR post_content=''";
$where_values = array();
mysqli_update_query($db,'wp_posts',$set_fields,$set_values,$where_clause,$where_values);
?>
<p><strong>WARNING</strong> - You are editing the main WordPress database. Please do so with caution!!<p>
  <ul>
    <li><a href="<?php echo $base_url; ?>/wp-admin" target="_blank">Wordpress Dashboard</a></li>
  </ul>
<div class="horizontal-divide">&nbsp;</div>
<h2>Tables/Views</h2>
<ul>
  <li><a href="?-table=wp_posts">Posts Table (Full)</a></li>
  <li><a href="?-table=_view_pages">Posts Table (Pages)</a></li>
  <li><a href="?-table=_view_posts">Posts Table (Posts)</a></li>
  <li><a href="?-table=wp_postmeta">Post Meta</a></li>
  <li><a href="?-table=wp_terms">Terms</a></li>
  <li><a href="?-table=wp_termmeta">Term Meta</a></li>
  <li><a href="?-table=wp_term_relationships">Term Relationships</a></li>
  <li><a href="?-table=wp_term_taxonomy">Term Taxonomy</a></li>
  <li><a href="?-table=wp_users">Users</a></li>
  <li><a href="?-table=wp_usermeta">User Meta</a></li>
</ul>
<div class="horizontal-divide">&nbsp;</div>
<h2>Special Views/Actions</h2>
<div class="halfspace"></div>
<ul>
  <li><a href="./?-action=delete_revisions_1">Delete Post Revisions</a></li>
</ul>
<div class="horizontal-divide">&nbsp;</div>
