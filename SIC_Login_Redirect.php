<?php
/**
 * Plugin Name: SIC Login Redirect
 * Plugin URI: http://www.strategic-ic.co.uk
 * Description: This is a plugin to allow custom login redirection for registered users.By enabling this plugin from your settings menu you can access Settings-> SIC Login Redirect and choose the destination page for each user roles which doesn’t have a capability to publish posts. If you select the first option in the drop down ‘Use Default Settings’ the redirect url will be the Wordpress default one.
 * Version: 1.0
 * Author: Jipson Thomas
 * Author URI: http://www.jipsonthomas.com
 * License: A "Slug" license name e.g. GPL2
 *
 *
 * Copyright (c) 2014 Jipson Thomas <jipson@cstrategic-ic.co.uk>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit
 * persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 *   The above copyright notice and this permission notice shall be included in all copies or substantial portions of the
 * Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
 * OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 **/

defined('ABSPATH') or die("Cannot access pages directly.");
class sic_login_redirect_class {
    private $options;
	private $defaults = array();

	public function __construct() {
		 add_action('login_redirect', array( $this, 'sic_login_redirect' ),10,3);
        if ( is_admin() ){
            add_action('admin_menu', array( $this, 'add_sic_login_menu' ));
            add_action('admin_init', array( $this, 'register_settings' ));
        }
    }
	
	function sic_login_redirect( $redirect_to, $request, $user ) {
		//is there a user to check?
		global $user;
		 $options = wp_parse_args(get_option('sic_login_redirect'), null);
		if ( isset( $user->roles ) && is_array( $user->roles ) && isset($options) && is_array($options) ) {
			//check for redirect url
			foreach($options as $keyrole => $redval){
				if ( in_array( $keyrole, $user->roles ) && $redval != " " ) {
					// redirect them to the default place
					return $redval;
				}
			}
			
		} else {
			return $redirect_to;
		}
	}

    /* add menu */
	function add_sic_login_menu () {
        add_options_page( 'SIC Login Redirect Settings', 'SIC Login Redirect', 'manage_options', 'sic_login_redirect', array( $this, 'sicchng_set' ));
	}

    /* add menu page */
	function sicchng_set () {
        //include 'search_options.php';
        if ( !current_user_can( 'manage_options' ) )  {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }
        ?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2>SIC Login Redirect Settings</h2>
            <form method="post" action="options.php">
            <?php
                // Print out all hidden setting fields
                settings_fields( 'sic_login_redirect_group' );
                do_settings_sections( 'sic_login_redirect' );
                submit_button();
            ?>
            </form>
        </div>
        <?php
	}

    function register_settings(){
        $args = array(
		   'public'   => true,
		);
		register_setting(
            'sic_login_redirect_group', // Option group
            'sic_login_redirect', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );
		add_settings_section(
            'sic_setting_display', // ID
            'Display Settings', // Title
            array( $this, 'sic_setting_display' ), // Callback
            'sic_login_redirect' // Page
        );
		
		$output = 'names'; // names or objects, note names is the default
		//$operator = 'and'; // 'and' or 'or'
		
		//global $wp_roles;
     	//$roles = $wp_roles->get_names();
		//$editable_roles = apply_filters('editable_roles', $roles);
		$editable_roles = get_editable_roles();
		$non_admins	=	array();
		$admns	=	array();
		foreach($editable_roles as $erlsKEy	=>	$erlArr){
			if(isset($erlArr['capabilities']['publish_pages']) && $erlArr['capabilities']['publish_pages'] == 1){
				$admns[]	=	$erlsKEy;
			}else{
				$non_admins[]	=	$erlsKEy;
			}
		}
		//print_r($non_admins);exit;
		foreach ( $non_admins  as $role ) {
		
		   add_settings_field(
				$role.'redirect_url', // ID
				ucwords($role).' Redirect URL', // Title
				array( $this, 'title_text_callback' ), // Callback
				'sic_login_redirect', // Page
				'sic_setting_display', // Section
				array('rol' => $role)
			);
		    
		}

		
    }

     /* Sanitize each setting field */
    public function sanitize( $input ) {
		$args = array(
		   'public'   => true,
		);
		$output = 'names'; // names or objects, note names is the default
		//$operator = 'and'; // 'and' or 'or'
		
		global $wp_roles;
     	$roles = $wp_roles->get_names();
		
		foreach ( $roles  as $role ) {
			if(!empty( $input[$role] ) ) {
				$input[$role] = sanitize_text_field( $input[$role] );
			}
		}
		
        return $input;
    }

    /* Section text */
    public function sic_setting_display() {
        print 'Configure settings that control your users login redirection:';
    }

    function title_text_callback($args){
        $options = wp_parse_args(get_option('sic_login_redirect'), null);//print_r( $options);
		$flgs = array(
			'authors'      => '',
			'child_of'     => 0,
			'date_format'  => get_option('date_format'),
			'depth'        => 0,
			'echo'         => 1,
			'exclude'      => '',
			'include'      => '',
			'link_after'   => '',
			'link_before'  => '',
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'show_date'    => '',
			'sort_column'  => 'menu_order, post_title',
			'title_li'     => __('Pages'), 
			'walker'       => ''
		);
		$ppgs	=	get_pages( $flgs );
		echo '<select name="sic_login_redirect['.$args['rol'].']">';
		echo '<option value=" ">Use Default Settings</option>';
	   foreach($ppgs as $pgs){
		    printf('<option value="%s" %s >%s</option>', get_permalink($pgs->ID),in_array( get_permalink($pgs->ID), $options) ? 'selected="selected"' : '', $pgs->post_title);
	   }
	    
		 
	   echo '</select>';
    }
   
 /* Add settings link on plugin page */
	function settings_link($links) {
		$settings_link = '<a href="options-general.php?page=sic_login_redirect">Settings</a>';
		array_unshift($links, $settings_link);
		return $links;
	}
    function plugin_meta_links( $links, $file ) {
        $plugin = plugin_basename(__FILE__);
        if ( $file == $plugin ) {
            $links[] = '<a href="http://www.strategic-ic.co.uk/" target="_blank">Visit Strategic-IC</a>';
            $links[] = '<a href="mailto:jipson@strategic-ic.co.uk?subject=[SIC Login Redirect]">Email Author</a>';
        }
        return $links;
    }

    
   

   
}

/* initiate class */
$sic_login_redirect_class_obj = new sic_login_redirect_class;