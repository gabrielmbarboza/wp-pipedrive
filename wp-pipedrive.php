<?php
/*
  Plugin name: Pipedrive
  Plugin uri: http://www.gabrielmbarboza.com
  Description: Plugin de integração com pipedrive
  Version: 1.0
  Author: Gabriel Moraes Barboza
  License:  GPLv2 or later
*/
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

require_once( dirname(__FILE__) . '/class-tgm-plugin-activation.php' );
require_once( dirname(__FILE__) . '/pipedrive.php' );

add_action( 'tgmpa_register', 'pdwp_register_required_plugins' );

function pdwp_register_required_plugins()
{
    $plugins = array(
        array(
			'name'      => 'Contact Form 7',
			'slug'      => 'contact-form-7',
			'required'  => true,
		)
    );
    
    $config = array(
		'id'           => 'wp-pipedrive-dp',       // Unique ID for hashing notices for multiple instances of TGMPA.
		'default_path' => '',                      // Default absolute path to bundled plugins.
		'menu'         => 'tgmpa-install-plugins', // Menu slug.
		'parent_slug'  => 'plugins.php',           // Parent menu slug.
		'capability'   => 'manage_options',        // Capability needed to view plugin install page, should be a capability associated with the parent menu used.
		'has_notices'  => true,                    // Show admin notices or not.
		'dismissable'  => true,                    // If false, a user cannot dismiss the nag message.
		'dismiss_msg'  => '',                      // If 'dismissable' is false, this message will be output at top of nag.
		'is_automatic' => true,                    // Automatically activate plugins after installation or not.
		'message'      => '',
    );

    tgmpa( $plugins, $config );
}

add_action( 'admin_menu', 'pipedrive_menu' );

function pipedrive_menu() 
{
    add_menu_page('Pipedrive Settings', 'Pipedrive', 'manage_options', 'pipedrive', 'pipedrive_options');
}

function pipedrive_register_settings()
{ 
    add_option( 'pipedrive_option_api_url', '' );
    register_setting( 'pipedrive_options_group', 'pipedrive_option_api_url', 'pipedrive_callback' );

    add_option( 'pipedrive_option_email', '' );
    register_setting( 'pipedrive_options_group', 'pipedrive_option_email', 'pipedrive_callback' );

    add_option( 'pipedrive_option_token', '' );
    register_setting( 'pipedrive_options_group', 'pipedrive_option_token', 'pipedrive_callback' );

    add_option( 'pipedrive_option_owner_id', '' );
    register_setting( 'pipedrive_options_group', 'pipedrive_option_owner_id', 'pipedrive_callback' );
}

add_action( 'admin_init', 'pipedrive_register_settings' );

function pipedrive_options() 
{
    if( !current_user_can( 'manage_options' ) ) 
    {
      wp_die( __( 'You do not have a sufficient permissions to access this page.' ) );
    }
    ?>
        <div class="wrap">
            <h1>Pipedrive Settings</h1>
            <form method="post" action="options.php">
                <?php 
                    settings_fields("pipedrive_options_group");
                ?>
                <table class="form-table">
                    <tr>
                       <th>Url:</th>
                       <td>
                           <input class="regular-text" type="text" id="pidedrive_option_api_url" name="pipedrive_option_api_url" 
                                value="<?php echo get_option('pipedrive_option_api_url');?>"/><br/>&nbsp;<span style="font-size: small; color: #999;">Add the url pipedrive api</span>
                      </td>    
                    </tr>
                    <tr>
                       <th>Email:</th>
                       <td>
                           <input class="regular-text" type="text" id="pidedrive_option_email" name="pipedrive_option_email" 
                                value="<?php echo get_option('pipedrive_option_email');?>"/><br/>&nbsp;<span style="font-size: small; color: #999;">Add owner email registered on pipedrive</span>
                      </td>    
                    </tr>
                    <tr>
                       <th>Token:</th>
                       <td>
                           <input class="regular-text" type="text" id="pidedrive_option_token" name="pipedrive_option_token" 
                                value="<?php echo get_option('pipedrive_option_token');?>"/><br/>&nbsp;<span style="font-size: small; color: #999;">Add token provided by the pipedrive</span>
                      </td>    
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
    <?php
}

function wpcf7_send_contact_to_pipedrive ( $cf7 )
{
    $submission = WPCF7_Submission::get_instance();
    
    if( $submission ) {
        $posted_data = $submission->get_posted_data();

        $base_uri = get_option( 'pipedrive_option_api_url' ); 
        $owner_mail = get_option( 'pipedrive_option_email' );
        $owner_id = get_option( 'pipedrive_option_owner_id' );
        $token = get_option( 'pipedrive_option_token' );

        if( $token && $owner_mail )
        {   
            if( !$owner_id ) 
            {
                $url_user_find_by_email = build_url( $base_uri, "users/find", $token, [ "term" => $owner_mail, "search_by_email" => 1 ] );
                $user = find_by_email( $url_user_find_by_email );
                $owner_id = $user->id;
            
                update_option('pipedrive_option_owner_id', $owner_id);
            }
        
            $contact = ( object ) array(
                "owner_id" => $owner_id,
                "name" => $posted_data['cfpd-name'],
                "email" => $posted_data['cfpd-email'],
                "phone" => $posted_data['cfpd-phone'],
                "message" => $posted_data['cfpd-message'],
                "value" => "2.665,00", 
                "currency" => "USD", 
            );

            send_to_pipedrive($base_uri, $token, $contact);   
        }
    } 
}

add_action( "wpcf7_mail_sent", "wpcf7_send_contact_to_pipedrive" );

add_filter( 'wpcf7_load_js', '__return_false' );
add_filter( 'wpcf7_load_css', '__return_false' );
