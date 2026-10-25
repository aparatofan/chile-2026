<?php
/**
 * Plugin Name:       Chile 2026 — panel podróży
 * Description:       Panel przygotowań do wyprawy do Chile (grudzień 2026 – styczeń 2027): odliczanie, trasa, plan dzień po dniu, rezerwacje, loty i wspólna lista kontrolna. Shortcode: [chile-dashboard].
 * Version:           1.0.0
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            Mariusz Mirecki
 * Text Domain:       mm-chile-2026
 *
 * @package MM_Chile_2026
 */

defined( 'ABSPATH' ) || exit;

define( 'MMCL_VERSION', '1.0.0' );
define( 'MMCL_FILE', __FILE__ );
define( 'MMCL_DIR', plugin_dir_path( __FILE__ ) );
define( 'MMCL_URL', plugin_dir_url( __FILE__ ) );

require_once MMCL_DIR . 'includes/data.php';
require_once MMCL_DIR . 'includes/store.php';
require_once MMCL_DIR . 'includes/rest.php';
require_once MMCL_DIR . 'includes/render.php';

if ( is_admin() ) {
	require_once MMCL_DIR . 'includes/admin.php';
}

register_activation_hook( __FILE__, 'mmcl_activate' );

/** Create options without autoload; make the installing admin the first traveller. */
function mmcl_activate() {
	if ( false === get_option( MMCL_SETTINGS, false ) ) {
		$uid = get_current_user_id();
		add_option( MMCL_SETTINGS, array( 'travellers' => $uid ? array( $uid ) : array() ), '', false );
	}
	if ( false === get_option( MMCL_STATE, false ) ) {
		add_option( MMCL_STATE, array(), '', false );
	}
}

/** Settings link on the Plugins screen. */
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function ( $links ) {
	array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=mmcl' ) ) . '">Ustawienia</a>' );
	return $links;
} );
