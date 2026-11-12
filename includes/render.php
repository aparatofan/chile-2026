<?php
/**
 * Shortcode [chile-dashboard]: prints the shell and a JSON payload; dashboard.js renders it.
 *
 * Visitors who are not travellers never receive private data: notes, contacts,
 * hosts' names, the checklist or the REST nonce.
 *
 * @package MM_Chile_2026
 */

defined( 'ABSPATH' ) || exit;

add_shortcode( 'chile-dashboard', 'mmcl_shortcode' );
add_shortcode( 'chile_dashboard', 'mmcl_shortcode' );

/** Text scale: shortcode attribute (allowlisted) wins over the saved default. */
function mmcl_text_scale( $attr = '' ) {
	$scales = mmcl_scales();
	$key    = sanitize_key( (string) $attr );
	if ( ! isset( $scales[ $key ] ) ) {
		$key = mmcl_settings()['scale'];
	}
	if ( ! isset( $scales[ $key ] ) ) {
		$key = 'duzy';
	}
	return $key;
}

function mmcl_enqueue() {
	wp_enqueue_style(
		'mmcl-fonts',
		'https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible+Next:ital,wght@0,400;0,600;0,700;1,400&family=Bricolage+Grotesque:opsz,wght@12..96,500..800&display=swap',
		array(),
		null
	);
	wp_enqueue_style( 'mmcl', MMCL_URL . 'assets/css/dashboard.css', array( 'mmcl-fonts' ), MMCL_VERSION );
	wp_enqueue_script( 'mmcl', MMCL_URL . 'assets/js/dashboard.js', array(), MMCL_VERSION, true );
}

/** Public-safe placeholder text. */
function mmcl_fill_public( $text ) {
	return strtr(
		(string) $text,
		array(
			'{gospodarze_santiago}' => 'przyjaciele',
			'{gospodarze_sanpedro}' => 'znajomi',
			'{przewodnik}'          => 'lokalny przewodnik',
		)
	);
}

/** Events for the payload. */
function mmcl_payload_events( $private ) {
	$out = array();
	foreach ( mmcl_events() as $e ) {
		// Tasks (shopping, check-in, …) are the travellers' own to-dos: never shown to guests.
		if ( ! $private && 'task' === $e['type'] ) {
			continue;
		}
		$e = wp_parse_args( $e, array(
			'date' => null, 'time' => null, 'end_date' => null, 'end_time' => null,
			'title_public' => '', 'place' => '', 'detail' => '', 'note' => '',
			'status' => '', 'booking' => false, 'link' => '', 'important' => false,
		) );
		$e['important'] = (bool) $e['important'];
		if ( $private ) {
			$e['title']  = mmcl_fill( $e['title'] );
			$e['detail'] = mmcl_fill( $e['detail'] );
			$e['note']   = mmcl_fill( $e['note'] );
		} else {
			$e['title']  = $e['title_public'] ? $e['title_public'] : mmcl_fill_public( $e['title'] );
			$e['detail'] = mmcl_fill_public( $e['detail'] );
			$e['note']   = '';
		}
		unset( $e['title_public'] );
		$out[] = $e;
	}
	return $out;
}

/** Contact cards from settings, travellers only. */
function mmcl_payload_contacts() {
	$s   = mmcl_settings();
	$out = array();
	$add = function ( $title, $rows ) use ( &$out ) {
		$rows = array_values( array_filter( $rows, function ( $r ) {
			return '' !== trim( (string) $r['v'] );
		} ) );
		if ( $rows ) {
			$out[] = array( 'title' => $title, 'rows' => $rows );
		}
	};
	$add( 'Santiago', array(
		array( 'k' => 'Gospodarze', 'v' => $s['host_santiago_names'] ),
		array( 'k' => 'Adres', 'v' => $s['host_santiago_address'] ),
		array( 'k' => 'Telefon', 'v' => $s['host_santiago_phone'], 'tel' => true ),
	) );
	$add( 'Lake Lodge, Pucón', array(
		array( 'k' => 'Kontakt', 'v' => $s['lodge_contact'] ),
	) );
	$add( 'San Pedro de Atacama', array(
		array( 'k' => 'Nocleg', 'v' => $s['host_spa_names'] ),
		array( 'k' => 'Adres', 'v' => $s['host_spa_address'] ),
		array( 'k' => 'Telefon', 'v' => $s['host_spa_phone'], 'tel' => true ),
		array( 'k' => 'Przewodnik', 'v' => $s['guide_name'] ),
		array( 'k' => 'Telefon przewodnika', 'v' => $s['guide_phone'], 'tel' => true ),
	) );
	$add( 'Ubezpieczenie i bliscy', array(
		array( 'k' => 'Ubezpieczenie', 'v' => $s['insurance'] ),
		array( 'k' => 'W Polsce', 'v' => $s['emergency_pl'] ),
	) );
	$add( 'Notatki', array(
		array( 'k' => '', 'v' => $s['notes'] ),
	) );
	return $out;
}

function mmcl_payload( $private, $scale ) {
	$s = mmcl_settings();
	$p = array(
		'trip'      => mmcl_trip(),
		'stages'    => mmcl_stages(),
		'places'    => mmcl_places(),
		'legs'      => mmcl_legs(),
		'events'    => mmcl_payload_events( $private ),
		'apps'      => mmcl_apps(),
		'facts'     => mmcl_facts(),
		'fx'        => (float) $s['fx'],
		'baggage'   => array(
			array( 'what' => 'Mała torba', 'size' => '40 × 30 × 15 cm', 'kg' => '4 kg', 'n' => 1 ),
			array( 'what' => 'Bagaż podręczny', 'size' => '55 × 40 × 23 cm', 'kg' => '8 kg', 'n' => 1 ),
			array( 'what' => 'Bagaż rejestrowany', 'size' => 'do 158 cm (suma wymiarów)', 'kg' => '23 kg', 'n' => 2 ),
		),
		'scales'    => mmcl_scales(),
		'scale'     => $scale,
		'private'   => $private,
	);
	if ( $private ) {
		$p['cats']       = mmcl_checklist_cats();
		$p['items']      = array_map( function ( $i ) {
			$i['label'] = mmcl_fill( $i['label'] );
			if ( isset( $i['hint'] ) ) {
				$i['hint'] = mmcl_fill( $i['hint'] );
			}
			return $i;
		}, mmcl_checklist_items() );
		$p['travellers'] = mmcl_travellers();
		$p['me']         = get_current_user_id();
		$p['state']      = mmcl_public_state();
		$p['contacts']   = mmcl_payload_contacts();
		$p['rest']       = array(
			'url'   => esc_url_raw( rest_url( 'mm-chile/v1/' ) ),
			'nonce' => wp_create_nonce( 'wp_rest' ),
		);
		$p['adminUrl']   = current_user_can( 'manage_options' ) ? admin_url( 'admin.php?page=mmcl' ) : '';
	}
	return $p;
}

function mmcl_shortcode( $atts ) {
	$atts    = shortcode_atts( array( 'skala' => '' ), $atts, 'chile-dashboard' );
	$scale   = mmcl_text_scale( $atts['skala'] );
	$private = mmcl_can_use();
	$mult    = mmcl_scales()[ $scale ]['value'];

	mmcl_enqueue();

	if ( ! $private && 'public' !== mmcl_settings()['visibility'] ) {
		ob_start();
		?>
		<div class="mmcl mmcl--locked" style="--mmcl-scale:<?php echo esc_attr( $mult ); ?>">
			<div class="mmcl-locked">
				<span class="mmcl-stamp">Privado</span>
				<h2 class="mmcl-locked__title">Panel naszej wyprawy do Chile jest prywatny</h2>
				<p>Zaloguj się, żeby zobaczyć plan, rezerwacje i listę.</p>
				<p><a class="mmcl-btn" href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>">Zaloguj się</a></p>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	$json = wp_json_encode( mmcl_payload( $private, $scale ), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP );

	ob_start();
	?>
	<div class="mmcl" data-mmcl style="--mmcl-scale:<?php echo esc_attr( $mult ); ?>">
		<script type="application/json" class="mmcl-data"><?php echo $json; // phpcs:ignore WordPress.Security.EscapeOutput -- JSON with HEX_TAG. ?></script>
		<noscript><p class="mmcl-noscript">Panel potrzebuje włączonego JavaScriptu.</p></noscript>
	</div>
	<?php
	return ob_get_clean();
}
