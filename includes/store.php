<?php
/**
 * Settings, shared state and access rules.
 *
 * Two options, neither autoloaded:
 *   mmcl_settings — private details typed in Chile 2026 → Ustawienia.
 *   mmcl_state    — what the travellers change on the dashboard:
 *                   ticks, booking statuses and dates, custom checklist items.
 *
 * @package MM_Chile_2026
 */

defined( 'ABSPATH' ) || exit;

const MMCL_SETTINGS = 'mmcl_settings';
const MMCL_STATE    = 'mmcl_state';

/** Allowed text-scale levels: key => multiplier. */
function mmcl_scales() {
	return array(
		'standard'   => array( 'label' => 'Standardowy', 'value' => 1 ),
		'duzy'       => array( 'label' => 'Duży',        'value' => 1.125 ),
		'wiekszy'    => array( 'label' => 'Większy',     'value' => 1.25 ),
		'najwiekszy' => array( 'label' => 'Największy',  'value' => 1.4 ),
	);
}

/** Settings fields: key => [label, type, section]. */
function mmcl_setting_fields() {
	return array(
		'host_santiago_names'   => array( 'Imiona gospodarzy', 'text', 'santiago' ),
		'host_santiago_address' => array( 'Adres', 'textarea', 'santiago' ),
		'host_santiago_phone'   => array( 'Telefon / WhatsApp', 'text', 'santiago' ),
		'lodge_contact'         => array( 'Lake Lodge — telefon, adres, wskazówki dojazdu', 'textarea', 'pucon' ),
		'host_spa_names'        => array( 'U kogo nocujemy', 'text', 'atacama' ),
		'host_spa_address'      => array( 'Adres', 'textarea', 'atacama' ),
		'host_spa_phone'        => array( 'Telefon / WhatsApp', 'text', 'atacama' ),
		'guide_name'            => array( 'Przewodnik', 'text', 'atacama' ),
		'guide_phone'           => array( 'Telefon przewodnika', 'text', 'atacama' ),
		'insurance'             => array( 'Ubezpieczenie: nr polisy i telefon alarmowy', 'textarea', 'inne' ),
		'emergency_pl'          => array( 'Kontakt w Polsce w razie czego', 'textarea', 'inne' ),
		'notes'                 => array( 'Inne notatki (widoczne tylko dla podróżnych)', 'textarea', 'inne' ),
	);
}

/** Settings with defaults. */
function mmcl_settings() {
	$defaults = array(
		'travellers' => array(),
		'visibility' => 'private',
		'scale'      => 'duzy',
		'fx'         => mmcl_facts()['fx_default'],
	);
	foreach ( array_keys( mmcl_setting_fields() ) as $key ) {
		$defaults[ $key ] = '';
	}
	$saved = get_option( MMCL_SETTINGS, array() );
	return wp_parse_args( is_array( $saved ) ? $saved : array(), $defaults );
}

/** Traveller user IDs (max two). */
function mmcl_traveller_ids() {
	$ids = array_map( 'absint', (array) mmcl_settings()['travellers'] );
	return array_values( array_filter( array_unique( $ids ) ) );
}

/** Travellers for the UI: id, first name, initial. */
function mmcl_travellers() {
	$out = array();
	foreach ( mmcl_traveller_ids() as $id ) {
		$u = get_userdata( $id );
		if ( ! $u ) {
			continue;
		}
		$name  = $u->first_name ? $u->first_name : $u->display_name;
		$out[] = array(
			'id'      => $id,
			'name'    => $name,
			'initial' => mb_strtoupper( mb_substr( $name, 0, 1 ) ),
		);
	}
	return $out;
}

/** Can the current user see private details and change state? */
function mmcl_can_use() {
	if ( ! is_user_logged_in() ) {
		return false;
	}
	if ( current_user_can( 'manage_options' ) ) {
		return true;
	}
	return in_array( get_current_user_id(), mmcl_traveller_ids(), true );
}

/** Shared state with defaults. */
function mmcl_state() {
	$state = get_option( MMCL_STATE, array() );
	$state = is_array( $state ) ? $state : array();
	return wp_parse_args(
		$state,
		array(
			'ticks'     => array(), // item_id => [ who => [ 'by' => uid, 'at' => ts ] ], who = 's' or uid.
			'overrides' => array(), // event_id => [ date, time, end_date, end_time, note, status ].
			'custom'    => array(), // custom checklist items.
		)
	);
}

function mmcl_save_state( $state ) {
	update_option( MMCL_STATE, $state, false );
}

/** Placeholders used in data.php, filled from settings for travellers. */
function mmcl_placeholders() {
	$s = mmcl_settings();
	return array(
		'{gospodarze_santiago}' => $s['host_santiago_names'] ? $s['host_santiago_names'] : 'przyjaciele w Santiago',
		'{gospodarze_sanpedro}' => $s['host_spa_names'] ? $s['host_spa_names'] : 'znajomi w San Pedro',
		'{przewodnik}'          => $s['guide_name'] ? $s['guide_name'] : 'do wpisania w ustawieniach',
	);
}

function mmcl_fill( $text ) {
	return strtr( (string) $text, mmcl_placeholders() );
}

/** Known IDs, used to reject unknown input. */
function mmcl_event_ids() {
	return wp_list_pluck( mmcl_events(), 'id' );
}

function mmcl_item_ids() {
	$ids = wp_list_pluck( mmcl_checklist_items(), 'id' );
	foreach ( mmcl_state()['custom'] as $c ) {
		$ids[] = $c['id'];
	}
	return $ids;
}

/** Find an item's scope (shared/personal). */
function mmcl_item_scope( $id ) {
	foreach ( mmcl_checklist_items() as $i ) {
		if ( $i['id'] === $id ) {
			return $i['scope'];
		}
	}
	foreach ( mmcl_state()['custom'] as $c ) {
		if ( $c['id'] === $id ) {
			return $c['scope'];
		}
	}
	return null;
}

/** Validate a Y-m-d date within the trip, or empty. */
function mmcl_clean_date( $v ) {
	$v = sanitize_text_field( (string) $v );
	if ( '' === $v ) {
		return '';
	}
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $v ) ) {
		return null;
	}
	$t = mmcl_trip();
	if ( $v < $t['first_day'] || $v > $t['last_day'] ) {
		return null;
	}
	return $v;
}

/** Validate H:i or empty. */
function mmcl_clean_time( $v ) {
	$v = sanitize_text_field( (string) $v );
	if ( '' === $v ) {
		return '';
	}
	return preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $v ) ? $v : null;
}
