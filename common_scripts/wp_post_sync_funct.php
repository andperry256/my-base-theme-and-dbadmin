<?php
//==============================================================================

function sync_post_data($source_dbid,$source_user,$target_dbid,$target_user,$option,$category='')
{
    $db1 = db_connect($source_dbid,'p',$source_user);
    $db2 = db_connect($target_dbid,'p',$target_user);
    $where_clause = "post_type='post' AND post_status='publish'";
    $where_values = array();
    $query_result = mysqli_select_query($db1,'wp_posts','*',$where_clause,$where_values,'');

    // Loop through posts in source database.
    while ($row1 = mysqli_fetch_assoc($query_result))
    {
        $where_clause = "post_name=? AND post_status='publish'";
        $where_values = array('s',$row1['post_name']);
        if ($row2 = mysqli_fetch_assoc(mysqli_select_query($db2,'wp_posts','*',$where_clause,$where_values,'')))
        {
            // Matching post name found in target database.
            $query = "SELECT * FROM wp_terms LEFT JOIN wp_term_taxonomy ON wp_terms.term_id=wp_term_taxonomy.term_ID WHERE slug='$category' AND taxonomy='category'";
            $category_match = false;
            if (empty($category))
            {
                $category_match = true;
            }
            elseif (($row3 = mysqli_fetch_assoc(mysqli_query($db1,$query))) &&
                    ($row4 = mysqli_fetch_assoc(mysqli_query($db2,$query))))
            {
                // Category exists in both DBs. Now check if both posts are in that category.
                if (($row5 = mysqli_fetch_assoc(mysqli_query($db1,"SELECT * FROM wp_term_relationships WHERE object_id={$row1['ID']} AND term_taxonomy_id={$row3['term_taxonomy_id']}"))) &&
                    ($row6 = mysqli_fetch_assoc(mysqli_query($db2,"SELECT * FROM wp_term_relationships WHERE object_id={$row2['ID']} AND term_taxonomy_id={$row4['term_taxonomy_id']}"))))   
                {
                    $category_match = true;
                }
            }
            if ($category_match)
            {
                if (($option == 'timestamp') && ($row1['post_date'] != $row2['post_date']))
                {
                    // Synchronise post timestamp.
                    echo "Synchronising timestamp for post {$row1['post_name']}\n";
                    $fields = ('post_date,post_date_gmt');
                    $values = array ('s',$row1['post_date'],'s',$row1['post_date_gmt']);
                    mysqli_update_query($db2,'wp_posts',$fields,$values,$where_clause,$where_values);
                }
                elseif (($option == 'content') && ($row1['post_content'] != $row2['post_content']))
                {
                    // Synchronise post content.
                    echo "Synchronising content for post {$row1['post_name']}\n";
                    $fields = ('post_content');
                    $values = array ('s',$row1['post_content']);
                    mysqli_update_query($db2,'wp_posts',$fields,$values,$where_clause,$where_values);
                }
            }    
        }
    }
}

//==============================================================================
?>
