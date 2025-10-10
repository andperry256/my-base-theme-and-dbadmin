<?php
//==============================================================================

require_once(__DIR__.'/../wp-content/themes/my-base-theme/shared_functions.php');
require_once(__DIR__.'/date_funct.php');

function update_blog_feed($title,$description)
{
    global $base_dir, $base_url;
    $header_lines = [
        "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>",
        "<rss version=\"2.0\">",
        "<channel>",
        "  <title>$title</title>",
        "  <link>$base_url</link>",
        "  <description>$description</description>",
    ];
    $footer_lines = [
        "</channel>",
        "</rss>",
    ];

    $db = db_connect(1);
    $ofp = fopen("$base_dir/blog_feed.xml",'w');

    // Output header lines.
    foreach ($header_lines as $line) {
        fprintf($ofp,"$line\n");
    }

    // Main loop to process posts.
    $where_clause = "post_type='post' AND post_status='publish'";
    $add_clause = "ORDER BY post_date DESC";
    $query_result = mysqli_select_query($db,'wp_posts','*',$where_clause,[],$add_clause);
    $count = 0;
    while ($row = mysqli_fetch_assoc($query_result)) {
        $post_access_level = get_post_meta($row['ID'],'access_level',true);
        if (($post_access_level !== '') && (defined('DEFAULT_ACCESS_LEVEL')) && ($post_access_level > DEFAULT_ACCESS_LEVEL)) {
            // Omit post above default access level.
        }
        else {
            fprintf($ofp,"  <item>\n");
            $title_par = str_replace('%','%%',$row['post_title']);
            fprintf($ofp,"    <title>$title_par</title>\n");
            fprintf($ofp,"    <link>$base_url/{$row['post_name']}</link>\n");

            // Format description, including featured image where available.
            fprintf($ofp,"    <description>\n");
            fprintf($ofp,"      <![CDATA[\n");
            fprintf($ofp,"        <p>$title_par</p>\n");
            $where_clause = "post_id=? AND meta_key='_thumbnail_id'";
            $where_values = ['i',$row['ID']];
            if ($row2 = mysqli_fetch_assoc(mysqli_select_query($db,'wp_postmeta','meta_value',$where_clause,$where_values,''))) {
                $where_clause = "ID=? AND post_type='attachment'";
                $where_values = ['i',$row2['meta_value']];
                if ($row3 = mysqli_fetch_assoc(mysqli_select_query($db,'wp_posts','guid',$where_clause,$where_values,''))) {
                    $image_url = get_modified_image_url($row3['guid'],'webp192','webp');
                    if (!empty($image_url)) {
                        fprintf($ofp,"        <img src=\"$image_url\" />\n");
                    }
                }
            }
            fprintf($ofp,"      ]]>\n");
            fprintf($ofp,"    </description>\n");

            // Format and include publication date.
            $date = date('D, d M Y H:i:s',strtotime($row['post_date_gmt'])).' GMT';
            fprintf($ofp,"    <pubDate>$date</pubDate>\n");

            fprintf($ofp,"  </item>\n");
            $count++;
        }
        if ($count >= 30) {
            break;
        }
    }

    // Output footer lines.
    foreach ($footer_lines as $line) {
        fprintf($ofp,"$line\n");
    }
    fclose($ofp);
}

//==============================================================================
