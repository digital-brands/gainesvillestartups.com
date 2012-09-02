<?php
/* ENQUEUE STYLESHEETS
***************************************************************************************/
add_action( 'wp_enqueue_scripts', 'gs_add_stylesheets' );
function gs_add_stylesheets() {
    wp_register_style( 'style-css', get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'style-css' );

    wp_register_script( 'gs-js', get_template_directory_uri() . '/js/gs_js.js', array( 'jquery' ) );
    wp_enqueue_script( 'gs-js' );

}

/* ADD POST THUMBNAILS TO FUNDRAISERS
*************************************************************************************/
add_theme_support( 'post-thumbnails' );
set_post_thumbnail_size( 285, 210, true );

/* Landing page email form check
*************************************************************************************/
function gs_check_view_projects_submit() {

    if ( !isset( $_POST['email'] ) )
        return;
    if ( !wp_verify_nonce( $_POST['view-projects-nonce'], 'view-projects-nonce') ) 
        die('Security check'); 

    wp_safe_redirect( get_home_url() . '/projects' );
}

/* REGISTER SIDEBAR FOR SINGLE PAGE
 * *********************************************************/
if ( function_exists('register_sidebar') ) {
    register_sidebar(array(
        'name' => 'Single Page Sidebar',
        'id' => 'single-sidebar',
        'description' => 'Appears as the sidebar on single.php',
        'before_widget' => '<li id="%1$s" class="widget %2$s">',
        'after_widget' => '</li>',
        'before_title' => '<h2 class="widgettitle">',
        'after_title' => '</h2>',
    ));
}

/* FILTER BUTTON TEXT
 * *********************************************************************************/
add_filter( 'wdf_backer_button', 'gs_backer_button_text' );
function gs_backer_button_text( $content ) {
    $link = get_permalink() . 'pledge';
    $link_text = 'Back This Project<br /><small>for as little as $1</small>';
	$content = "<a class='wdf_button' href='$link'><div>$link_text</div></a>";
    return $content;
}

/* GET FUNDRAISER LEVELS
************************************************************************************/
function gs_get_levels( $post_id ) {
    global $wdf, $post;

    $post_id = (empty($post_id) ? $post->ID : $post_id );
    if(!get_post($post_id))
        return;
		
    $meta = get_post_custom($post_id);
    $levels = false;
    if(wdf_has_rewards($post_id)) {
        if(isset($meta['wdf_levels'][0])) {
            $levels = $meta['wdf_levels'];
        }
    }

    return $levels;
}
?>
