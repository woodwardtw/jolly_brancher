<?php 
/*
Plugin Name: Jolly Brancher 
Plugin URI:  https://github.com/
Description: Let people fork your content into their own sites on a multisite blog
Version:     1.0
Author:      Tom Woodward
Author URI:  http://bionicteaching.com
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Domain Path: /languages
Text Domain: my-toolset

*/
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );


add_action('wp_enqueue_scripts', 'jolly_rancher_load_scripts');

function jolly_rancher_load_scripts() {                           
    $deps = array('jquery');
    $version= '1.0'; 
    $in_footer = true;    
    wp_enqueue_script('jolly-brancher-main-js', plugin_dir_url( __FILE__) . 'js/jolly-brancher-main.js', $deps, $version, $in_footer); 
    wp_enqueue_style( 'jolly-brancher-main-css', plugin_dir_url( __FILE__) . 'css/jolly-brancher-main.css');
}


function make_brancher_html(){
	global $post;
 if (is_user_logged_in()) {  
                $blog_select = "
                <form id='jollbrancher-fork-form' action='" . get_the_permalink() . "' method='post'>
                <p>
                    <label>Which of your blogs would you like to fork this content to?</label><br/>
                    <select id='blog-select' name='blog-select'>
                        <option value=''>Select your blog</option>" . create_blogs_dropdown( get_blogs_of_current_user_by_role() ) . "</select>
                </p>
                  <fieldset id='submit'>
                    <input type='hidden' name='submit' value='1'/>
                    <input type='submit' value='Submit' />
                </fieldset></form>";
                $blog_select_login_prompt = "";
            } 
            else {
                $blog_select = "";
                $blog_select = "<p>To fork this you'll have to <a href='" . wp_login_url(get_the_permalink()) . "'>login</a>.</p>";
            }
           if ($_POST) {

                $form_response = "";
              
               if (is_user_logged_in() && $_POST['blog-select'] ) {               	              
               	    //go elsewhere
               	    $base_content = $post->post_content;//$_POST['blog-post-content'];
               	    $home_url = $post->guid;
               	    $base_title = $post->post_title;
                    $remote_blog = get_remote_blog_info( $_POST['blog-select'] );
                    switch_to_blog($_POST['blog-select']);
                    $forked_post = array(
						  'post_title'    => 'Fork of ' . $base_title,
						  'post_content'  => $base_content . '<div style="width: 100%; display:block; margin: 20px 0;">Forked from <a href="'.$home_url.'">'.$base_title.'</a></p>',
						  'post_status'   => 'publish',						 
						);
						 
						// Insert the post into the database
						wp_insert_post( $forked_post );                   
                    
                    if ( $remote_blog ){
                       $form_response .= '<h2>SUCCESS!</h2>';  
                              

                    } else {
                        $form_response .= '<h2>SUCCESS2!</h2>';
					                   
                    }

                    return $form_response;

                } 
                
                else {
                    $form_response .= "<h2>CRUSHING DEFEAT!</h2>";                   

                    return $form_response;
                }
            }
                        

        return $blog_select;
  
}


function make_post_jolly($content){
	return $content . make_brancher_html();
}


add_filter( 'the_content', 'make_post_jolly' );


 function create_blogs_dropdown($blogs){
                $choices = '';

                foreach ($blogs as $blog) {
                    $choices.= "<option value='" . $blog->userblog_id . "'>" . $blog->blogname . "</option>";
                }

                return $choices;
            }


function get_blogs_of_current_user_by_role() {

                $user_id = get_current_user_id();
                $role = 'administrator';

                $blogs = get_blogs_of_user( $user_id );

                foreach ( $blogs as $blog_id => $blog ) {

                    // Get the user object for the user for this blog.
                    $user = new WP_User( $user_id, '', $blog_id );

                    // Remove this blog from the list if the user doesn't have the role for it.
                    if ( ! in_array( $role, $user->roles ) ) {
                        unset( $blogs[ $blog_id ] );
                    }
                }

                return $blogs;
            }


 function get_network_name() {
                $network_id = get_blog_details()->site_id;
                $network_name = get_blog_details($network_id)->blogname;
                
                return $network_name;
            }

            function get_network_signup_url() {
                $network_signup_url = get_option('altlab_motherblog_options');

                if ( !$network_signup_url && ( $_SERVER['HTTP_HOST'] === 'rampages.us' ) ){
                    $network_signup_url['network-signup-url'] = "http://rampages.us/vcu-wp-signup.php";
                }

                return $network_signup_url['network-signup-url'];
            }

function get_remote_blog_info( $blogID ) {
                $remote_blog = new stdClass;
                
                switch_to_blog($blogID);
                    
                $remote_blog->url = get_site_url();
                $remote_blog->name = get_bloginfo('name');                  
                        
                // switch back to motherblog
                restore_current_blog();
                
                return $remote_blog;
            }            