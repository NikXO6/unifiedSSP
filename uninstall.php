<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}

$options = get_option( Unified_SSO::OPTION_KEY );
if ( ! empty( $options['delete_data'] ) ) {
    delete_option( Unified_SSO::OPTION_KEY );
}
