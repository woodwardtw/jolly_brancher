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
    wp_enqueue_script('jolly-rancher-main-js', plugin_dir_url( __FILE__) . 'js/jolly-rancher-main.js', $deps, $version, $in_footer); 
    wp_enqueue_style( 'jolly-rancher-main-css', plugin_dir_url( __FILE__) . 'css/jolly-rancher-main.css');
}


function make_brancher_html(){
 if (is_user_logged_in()) {                
                $blog_select = "
                <p>
                    <label>Which of your blogs would you like to fork this content to?</label><br/>
                    <select id='blog-select' name='blog-select'>
                        <option value=''>Select your blog</option>" . create_blogs_dropdown( get_blogs_of_current_user_by_role() ) . "</select>
                </p>
                  <fieldset id='submit'>
                    <input type='hidden' name='submit' value='1'/>
                    <input type='submit' value='Submit' />
                </fieldset>";
                $blog_select_login_prompt = "";
            } 
            else {
                $blog_select = "";
                $blog_select = "<p>To fork this you'll have to <a href='" . wp_login_url(get_the_permalink()) . "'>login</a>.</p>";
            }
           if (!is_admin() && $_POST) {

                $form_response = "";
                
                if ($_POST['email']) {
                    die('<p>An error occurred. You have not been connected.</p>');
                } 
                else if (is_user_logged_in() && $_POST['blog-select'] && !$_POST['email']) {

                    $remote_blog = get_remote_blog_info( $_POST['blog-select'] );
                     
                  
                    
                    if ( $sub_categories ){
                       $form_response .= '<h2>SUCCESS!</h2>';
                       $form_response .= '<p>The following category and sub categories have been added your blog "<strong>' . $remote_blog->name . '</strong>".</p>';
                       $form_response .= list_created_categories( $a{'category'},$sub_categories );
                       $form_response .= '<p>Only posts you create in these categories on your blog "<strong>' . $remote_blog->name . '</strong>" will appear on this site.</p>';
                       $form_response .= '<a href="' . $remote_blog->url . '">Return to your site '.$remote_blog->name.'</a>';

                    } else {
                        $form_response .= '<h2>SUCCESS!</h2>';
                        $form_response .= '<p>The category "<strong>' . $a{'category'} . '</strong>" has been added to your blog "<strong>' . $remote_blog->name . '</strong>".</p>
                    <p>Only posts you create in the "' . $a{'category'} . '" category on your blog "<strong>' . $remote_blog->name . '</strong>" will appear on this site.</p>';
                        $form_response .= '<a href="' . $remote_blog->url . '">Return to your site '.$remote_blog->name.'</a>';
                    }

                    return $form_response;

                } 
                else if ($_POST['blog-feed'] && !$_POST['email']) {
                    create_fwp_link_off_network();
                    
                    $form_response .= '<h2>SUCCESS!</h2>';
                    $form_response .= "<p>You submitted the feed <a href='" . $_POST['blog-feed'] . "'>" . $_POST['blog-feed'] . "</a> to this site.<br/>
                    Only posts that appear in the feed you submitted will appear on this site.</p>";

                    return $form_response;
                } 
                else {
                    $form_response .= "<h2>CRUSHING DEFEAT!</h2>";
                    $form_response .= "<p>An error occurred. You have not been connected. But you should totally try again.</p>";
                    $form_response .= "<p>An error occurred. You have not been subscribed. But you should totally try again.</p>";
                    $form_response = "<p>An error occurred. You have not been connected.</p>";
                    $form_response .= "<h2>CRUSHING DEFEAT!</h2>";
                    $form_response .= "<p>An error occurred. You have not been subscribed. But you should totally try again.</p>";


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