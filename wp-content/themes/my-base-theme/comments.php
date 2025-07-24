<?php
/**
 * The template for displaying comments
 *
 * This is the template that displays the area of the page that contains both the current comments
 * and the comment form.
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package My_Base_Theme
 */

/*
 * If the current post is protected by a password and
 * the visitor has not yet entered the password we will
 * return early without loading the comments.
 */
if ( post_password_required() )
{
    return;
}
?>

<div id="comments" class="comments-area">

<?php if ( have_comments() ) : ?>
    <h2 class="comments-title">
        <?php
        printf( // WPCS: XSS OK.
          esc_html( _nx( 'One thought on &ldquo;%2$s&rdquo;', '%1$s thoughts on &ldquo;%2$s&rdquo;', get_comments_number(), 'comments title', 'my-base-theme' ) ),
          number_format_i18n( get_comments_number() ),
          '<span>' . get_the_title() . '</span>'
        );
      ?>
    </h2><!-- .comments-title -->

    <?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) : // Are there comments to navigate through? ?>
        <nav id="comment-nav-above" class="navigation comment-navigation" role="navigation">
            <h2 class="screen-reader-text"><?php esc_html_e( 'Comment navigation', 'my-base-theme' ); ?></h2>
            <div class="nav-links">
                <div class="nav-previous"><?php previous_comments_link( esc_html__( 'Older Comments', 'my-base-theme' ) ); ?></div>
                <div class="nav-next"><?php next_comments_link( esc_html__( 'Newer Comments', 'my-base-theme' ) ); ?></div>
            </div><!-- .nav-links -->
        </nav><!-- #comment-nav-above -->
    <?php endif; // Check for comment navigation. ?>

    <ol class="comment-list">
        <?php wp_list_comments(['style' => 'ol', 'short_ping' => true]); ?>
    </ol><!-- .comment-list -->

    <?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) : // Are there comments to navigate through? ?>
        <nav id="comment-nav-below" class="navigation comment-navigation" role="navigation">
            <h2 class="screen-reader-text"><?php esc_html_e( 'Comment navigation', 'my-base-theme' ); ?></h2>
            <div class="nav-links">
                <div class="nav-previous"><?php previous_comments_link( esc_html__( 'Older Comments', 'my-base-theme' ) ); ?></div>
                <div class="nav-next"><?php next_comments_link( esc_html__( 'Newer Comments', 'my-base-theme' ) ); ?></div>
            </div><!-- .nav-links -->
        </nav><!-- #comment-nav-below -->
    <?php endif; // Check for comment navigation. ?>

 <?php endif; // Check for have_comments(). ?>


<?php if ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) : ?>
    <p class="no-comments"><?php esc_html_e( 'Comments are closed.', 'my-base-theme' ); ?></p>
<?php endif; ?>

<?php comment_form(); ?>

</div><!-- #comments -->
