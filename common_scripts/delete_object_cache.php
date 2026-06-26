<?php
//==============================================================================
/*
This script deletes the object cache, and must be invoked by a WordPress page or
post, as it will not work outside the WordPress environment.
*/
//==============================================================================

if (!empty($_GET['post_name'])) {
    // Delete cache for given post
    $db = db_connect(1);
    $where_clause = 'post_name=?';
    $where_values = ['s',$_GET['post_name']];
    if ($row = mysqli_fetch_assoc(mysqli_select_query($db,'wp_posts','*',$where_clause,$where_values,''))) {
        wp_cache_delete($row['ID'],'posts');
        wp_cache_delete($row['ID'],'post_meta');
        print("Object cache deleted for post {$row['ID']} [{$_GET['post_name']}].\n");
    }
}
else {
    // Delete all cache
    wp_cache_flush();
    print("Object cache flushed.\n");
}

//==============================================================================
