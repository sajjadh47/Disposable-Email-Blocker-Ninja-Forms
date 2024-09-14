<?php
/**
 * Plugin Name: Disposable Email Blocker - Ninja Forms
 * Plugin URI: https://wordpress.org/plugins/disposable-email-blocker-ninja-forms/
 * Author: Sajjad Hossain Sagor
 * Description: Block Spammy Disposable/Temporary Emails To Submit On Ninja Forms.
 * Version: 1.0.3
 * Author URI: https://sajjadhsagor.com
 * Text Domain: disposable-email-blocker-ninja-forms
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// plugin root path....
define( 'DEBNF_ROOT_DIR', dirname( __FILE__ ) );

// plugin root url....
define( 'DEBNF_ROOT_URL', plugin_dir_url( __FILE__ ) );

// plugin version
define( 'DEBNF_VERSION', '1.0.3' );

// Checking if 'Ninja Forms' plugin is either installed or active
add_action( 'admin_init', 'debnf_check' );

register_activation_hook( __FILE__, 'debnf_check' );

function debnf_check()
{
	if ( ! in_array( 'ninja-forms/ninja-forms.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) )
	{
		// Deactivate the plugin
		deactivate_plugins( __FILE__ );

		// Throw an error in the wordpress admin console
		$error_message = __( 'Disposable Email Blocker - Ninja Forms requires <a href="https://wordpress.org/plugins/ninja-forms/">Ninja Forms</a> plugin to be active!', 'disposable-email-blocker-ninja-forms' );

		wp_die( $error_message, __( 'Ninja Forms Not Found', 'disposable-email-blocker-ninja-forms' ) );
	}
}

// load translation files...
add_action( 'plugins_loaded', 'debnf_load_plugin_textdomain' );

function debnf_load_plugin_textdomain()
{	
	load_plugin_textdomain( 'disposable-email-blocker-ninja-forms', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

add_filter( 'ninja_forms_submit_data', 'debnf_block_disposable_emails' );

function debnf_block_disposable_emails( $form_data )
{
	// if not blocking is enabled return early	
	if ( $form_data['settings']['block_disposable_emails'] !== 1 ) return $form_data;
	
	if ( isset( $form_data['fields'] ) && ! empty( $form_data['fields'] ) )
	{
		$default_error_msg = __( "Disposable/Temporary emails are not allowed! Please use a non temporary email", 'disposable-email-blocker-ninja-forms' );
		
		// get domains list from json file
		$disposable_emails_db = file_get_contents( DEBNF_ROOT_DIR . '/assets/data/domains.min.json' );		

		// convert json to php array
		$disposable_emails = json_decode( $disposable_emails_db );
		
		foreach ( $form_data['fields']  as $field_id => $field )
		{	
			if ( stripos( $field['key'], 'email' ) !== false )
			{
				$email = $field['value'];
				
				if( filter_var( $email, FILTER_VALIDATE_EMAIL ) )
				{	
					// split on @ and return last value of array (the domain)
					$domain = explode('@', $field_submit );

					$domain = array_pop( $domain );

					// check if domain is in disposable db
					if ( in_array( $domain, $disposable_emails ) )
					{
						if ( ! empty( $form_data['settings']['disposableEmailFoundMsg'] ) )
						{	
							$default_error_msg = $form_data['settings']['disposableEmailFoundMsg'];
						}

						$form_data['errors']['fields'][$field_id] = $default_error_msg;
					}
				}	
			}
		}
	}

	return $form_data;
}

// add disposable emails found message to show on form validation
add_filter( 'ninja_forms_form_display_settings', 'debnf_disposable_emails_found_msg' );

function debnf_disposable_emails_found_msg( $message )
{
	$message['custom_messages']['settings'][] = array(
		'name' => 'disposableEmailFoundMsg',
		'type' => 'textbox',
		'label' => esc_html__( 'Message when a disposable/temporary Email found', 'disposable-email-blocker-ninja-forms' ),
		'width' => 'full',
		'placeholder' => __( "Disposable/Temporary emails are not allowed! Please use a non temporary email", 'disposable-email-blocker-ninja-forms' )
	);

	$message['custom_messages']['settings'][] = array(
		'name' => 'block_disposable_emails',
		'type' => 'toggle',
		'label' => esc_html__( 'Block Disposable/Temporary Emails', 'disposable-email-blocker-ninja-forms' ),
		'width' => 'full',
		'group' => 'primary',
		'value' => 1,
	);

	return $message;
}
