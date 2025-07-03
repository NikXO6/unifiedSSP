<?php
/*
Plugin Name: Unified SSO for Google & Microsoft
Description: Enable Single Sign-On (SSO) using Google and Microsoft accounts.
Version: 0.2.0
Author: Codex Bot
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Define plugin path.
define( 'UNIFIED_SSO_PATH', plugin_dir_path( __FILE__ ) );
define( 'UNIFIED_SSO_URL', plugin_dir_url( __FILE__ ) );
define( 'UNIFIED_SSO_VERSION', '0.2.0' );

require_once UNIFIED_SSO_PATH . 'includes/class-unified-sso.php';

// Initialize plugin.
add_action( 'plugins_loaded', array( 'Unified_SSO', 'init' ) );
