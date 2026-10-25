<?php
/**
 * REST API: mm-chile/v1. Every route needs a logged-in traveller (or an admin)
 * and the standard wp_rest nonce, which the dashboard sends as X-WP-Nonce.
 *
 *   GET  /state   — ticks, booking overrides, custom items
 *   POST /tick    — { item, who: "s" | user id, done }
 *   POST /event   — { id, date, time, end_date, end_time, note, status }
 *   POST /custom  — { op: "add", cat, label, scope } | { op: "delete", id }
 *
 * @package MM_Chile_2026
 */

defined( 'ABSPATH' ) || exit;

add_action( 'rest_api_init', 'mmcl_register_routes' );

function mmcl_register_routes() {
	$ns   = 'mm-chile/v1';
	$perm = 'mmcl_can_use';

	register_rest_route( $ns, '/state', array(
		'methods'             => 'GET',
		'callback'            => 'mmcl_rest_state',
		'permission_callback' => $perm,
	) );
	register_rest_route( $ns, '/tick', array(
		'methods'             => 'POST',
		'callback'            => 'mmcl_rest_tick',
		'permission_callback' => $perm,
	) );
	register_rest_route( $ns, '/event', array(
		'methods'             => 'POST',
		'callback'            => 'mmcl_rest_event',
		'permission_callback' => $perm,
	) );
	register_rest_route( $ns, '/custom', array(
		'methods'             => 'POST',
		'callback'            => 'mmcl_rest_custom',
		'permission_callback' => $perm,
	) );
}

/** State as sent to the browser. */
function mmcl_public_state() {
	$st = mmcl_state();
	return array(
		'ticks'     => (object) $st['ticks'],
		'overrides' => (object) $st['overrides'],
		'custom'    => array_values( $st['custom'] ),
	);
}

function mmcl_rest_state() {
	return rest_ensure_response( mmcl_public_state() );
}

function mmcl_rest_tick( WP_REST_Request $r ) {
	$item = sanitize_key( $r->get_param( 'item' ) );
	$who  = (string) $r->get_param( 'who' );
	$done = (bool) $r->get_param( 'done' );

	$scope = mmcl_item_scope( $item );
	if ( ! $scope ) {
		return new WP_Error( 'mmcl_item', 'Nie ma takiego punktu na liście.', array( 'status' => 400 ) );
	}
	if ( 'shared' === $scope || ! mmcl_traveller_ids() ) {
		$who = 's';
	} else {
		$who = (string) absint( $who );
		// Personal ticks belong to a traveller; either traveller may tick for the other.
		$allowed = array_map( 'strval', mmcl_traveller_ids() );
		if ( ! in_array( $who, $allowed, true ) ) {
			return new WP_Error( 'mmcl_who', 'Ta osoba nie jest podróżnym.', array( 'status' => 400 ) );
		}
	}

	$st = mmcl_state();
	if ( $done ) {
		$st['ticks'][ $item ][ $who ] = array( 'by' => get_current_user_id(), 'at' => time() );
	} else {
		unset( $st['ticks'][ $item ][ $who ] );
		if ( empty( $st['ticks'][ $item ] ) ) {
			unset( $st['ticks'][ $item ] );
		}
	}
	mmcl_save_state( $st );
	return rest_ensure_response( mmcl_public_state() );
}

function mmcl_rest_event( WP_REST_Request $r ) {
	$id = sanitize_key( $r->get_param( 'id' ) );
	if ( ! in_array( $id, mmcl_event_ids(), true ) ) {
		return new WP_Error( 'mmcl_event', 'Nie ma takiego punktu w planie.', array( 'status' => 400 ) );
	}

	$st  = mmcl_state();
	$cur = isset( $st['overrides'][ $id ] ) ? $st['overrides'][ $id ] : array();

	foreach ( array( 'date', 'end_date' ) as $k ) {
		if ( null !== $r->get_param( $k ) ) {
			$v = mmcl_clean_date( $r->get_param( $k ) );
			if ( null === $v ) {
				return new WP_Error( 'mmcl_date', 'Data musi mieścić się między 20.12.2026 a 17.01.2027.', array( 'status' => 400 ) );
			}
			$cur[ $k ] = $v;
		}
	}
	foreach ( array( 'time', 'end_time' ) as $k ) {
		if ( null !== $r->get_param( $k ) ) {
			$v = mmcl_clean_time( $r->get_param( $k ) );
			if ( null === $v ) {
				return new WP_Error( 'mmcl_time', 'Godzina w formacie GG:MM.', array( 'status' => 400 ) );
			}
			$cur[ $k ] = $v;
		}
	}
	if ( null !== $r->get_param( 'note' ) ) {
		$cur['note'] = mb_substr( sanitize_textarea_field( $r->get_param( 'note' ) ), 0, 2000 );
	}
	if ( null !== $r->get_param( 'status' ) ) {
		$status = sanitize_key( $r->get_param( 'status' ) );
		if ( ! in_array( $status, array( 'booked', 'todo' ), true ) ) {
			return new WP_Error( 'mmcl_status', 'Nieznany status.', array( 'status' => 400 ) );
		}
		$cur['status'] = $status;
	}

	$cur['by']                 = get_current_user_id();
	$cur['at']                 = time();
	$st['overrides'][ $id ]    = $cur;
	mmcl_save_state( $st );
	return rest_ensure_response( mmcl_public_state() );
}

function mmcl_rest_custom( WP_REST_Request $r ) {
	$op = sanitize_key( $r->get_param( 'op' ) );
	$st = mmcl_state();

	if ( 'add' === $op ) {
		$cat   = sanitize_key( $r->get_param( 'cat' ) );
		$label = trim( sanitize_text_field( $r->get_param( 'label' ) ) );
		$scope = 'personal' === $r->get_param( 'scope' ) ? 'personal' : 'shared';
		$cats  = wp_list_pluck( mmcl_checklist_cats(), 'id' );
		if ( ! in_array( $cat, $cats, true ) || '' === $label ) {
			return new WP_Error( 'mmcl_custom', 'Wpisz treść punktu.', array( 'status' => 400 ) );
		}
		if ( count( $st['custom'] ) >= 200 ) {
			return new WP_Error( 'mmcl_custom', 'Lista jest pełna (200 własnych punktów).', array( 'status' => 400 ) );
		}
		$st['custom'][] = array(
			'id'     => 'w' . strtolower( wp_generate_password( 10, false, false ) ),
			'cat'    => $cat,
			'label'  => mb_substr( $label, 0, 200 ),
			'scope'  => $scope,
			'custom' => true,
			'by'     => get_current_user_id(),
		);
	} elseif ( 'delete' === $op ) {
		$id           = sanitize_key( $r->get_param( 'id' ) );
		$st['custom'] = array_values( array_filter( $st['custom'], function ( $c ) use ( $id ) {
			return $c['id'] !== $id;
		} ) );
		unset( $st['ticks'][ $id ] );
	} else {
		return new WP_Error( 'mmcl_op', 'Nieznana operacja.', array( 'status' => 400 ) );
	}

	mmcl_save_state( $st );
	return rest_ensure_response( mmcl_public_state() );
}
