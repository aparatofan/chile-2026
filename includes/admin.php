<?php
/**
 * Admin screen: Chile 2026 → Ustawienia.
 * Private details live here (database), never in the public repository.
 *
 * @package MM_Chile_2026
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', 'mmcl_admin_menu' );
add_action( 'admin_init', 'mmcl_admin_init' );

function mmcl_admin_menu() {
	add_menu_page( 'Chile 2026', 'Chile 2026', 'manage_options', 'mmcl', 'mmcl_admin_page', 'dashicons-location-alt', 58 );
	add_submenu_page( 'mmcl', 'Chile 2026 — ustawienia', 'Ustawienia', 'manage_options', 'mmcl', 'mmcl_admin_page' );
}

function mmcl_admin_init() {
	register_setting( 'mmcl', MMCL_SETTINGS, array(
		'type'              => 'array',
		'sanitize_callback' => 'mmcl_sanitize_settings',
		'default'           => array(),
	) );
}

function mmcl_sanitize_settings( $in ) {
	$in  = is_array( $in ) ? $in : array();
	$out = array();

	$out['travellers'] = array();
	foreach ( (array) ( $in['travellers'] ?? array() ) as $id ) {
		$id = absint( $id );
		if ( $id && get_userdata( $id ) ) {
			$out['travellers'][] = $id;
		}
	}
	$out['travellers'] = array_slice( array_values( array_unique( $out['travellers'] ) ), 0, 2 );

	$out['visibility'] = ( isset( $in['visibility'] ) && 'public' === $in['visibility'] ) ? 'public' : 'private';
	$out['scale']      = ( isset( $in['scale'] ) && isset( mmcl_scales()[ $in['scale'] ] ) ) ? $in['scale'] : 'duzy';

	$fx        = isset( $in['fx'] ) ? (float) str_replace( ',', '.', $in['fx'] ) : 0;
	$out['fx'] = ( $fx > 1 && $fx < 10000 ) ? round( $fx, 2 ) : mmcl_facts()['fx_default'];

	foreach ( mmcl_setting_fields() as $key => $f ) {
		$v           = $in[ $key ] ?? '';
		$out[ $key ] = 'textarea' === $f[1] ? sanitize_textarea_field( $v ) : sanitize_text_field( $v );
	}
	return $out;
}

function mmcl_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$s        = mmcl_settings();
	$name     = MMCL_SETTINGS;
	$users    = get_users( array( 'fields' => array( 'ID', 'display_name' ), 'orderby' => 'display_name' ) );
	$sections = array(
		'santiago' => 'Santiago',
		'pucon'    => 'Pucón',
		'atacama'  => 'San Pedro de Atacama',
		'inne'     => 'Inne',
	);
	?>
	<div class="wrap">
		<h1>Chile 2026 — ustawienia</h1>
		<p>Wstaw panel na stronę shortcodem <code>[chile-dashboard]</code>. Opcjonalnie: <code>[chile-dashboard skala="wiekszy"]</code>.</p>
		<p>Dane z tej strony są zapisane w bazie WordPressa, a nie w kodzie wtyczki. Na panelu widzą je tylko zalogowani podróżni.</p>

		<form method="post" action="options.php">
			<?php settings_fields( 'mmcl' ); ?>

			<h2>Podróżni i widoczność</h2>
			<table class="form-table" role="presentation">
				<?php for ( $i = 0; $i < 2; $i++ ) : ?>
				<tr>
					<th scope="row"><label for="mmcl-t<?php echo (int) $i; ?>">Podróżny <?php echo (int) ( $i + 1 ); ?></label></th>
					<td>
						<select id="mmcl-t<?php echo (int) $i; ?>" name="<?php echo esc_attr( $name ); ?>[travellers][]">
							<option value="">—</option>
							<?php foreach ( $users as $u ) : ?>
								<option value="<?php echo (int) $u->ID; ?>" <?php selected( (int) ( $s['travellers'][ $i ] ?? 0 ), (int) $u->ID ); ?>><?php echo esc_html( $u->display_name ); ?></option>
							<?php endforeach; ?>
						</select>
						<?php if ( 1 === $i ) : ?>
							<p class="description">Każdy podróżny potrzebuje konta (wystarczy rola Subskrybent). Imię z profilu pojawia się przy osobnych punktach listy.</p>
						<?php endif; ?>
					</td>
				</tr>
				<?php endfor; ?>
				<tr>
					<th scope="row">Kto widzi panel</th>
					<td>
						<label><input type="radio" name="<?php echo esc_attr( $name ); ?>[visibility]" value="private" <?php checked( $s['visibility'], 'private' ); ?>> Tylko zalogowani podróżni</label><br>
						<label><input type="radio" name="<?php echo esc_attr( $name ); ?>[visibility]" value="public" <?php checked( $s['visibility'], 'public' ); ?>> Wszyscy — goście widzą trasę, plan i aplikacje, bez list, notatek i kontaktów</label>
						<p class="description">Publiczny plan zdradza, kiedy nie ma Was w domu.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="mmcl-scale">Domyślna wielkość tekstu</label></th>
					<td>
						<select id="mmcl-scale" name="<?php echo esc_attr( $name ); ?>[scale]">
							<?php foreach ( mmcl_scales() as $k => $sc ) : ?>
								<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $s['scale'], $k ); ?>><?php echo esc_html( $sc['label'] ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description">Każdy może to zmienić u siebie na panelu.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="mmcl-fx">Kurs: ile CLP za 1 zł</label></th>
					<td><input id="mmcl-fx" type="text" class="small-text" name="<?php echo esc_attr( $name ); ?>[fx]" value="<?php echo esc_attr( $s['fx'] ); ?>"> <span class="description">25.10.2026: 253,1</span></td>
				</tr>
			</table>

			<?php foreach ( $sections as $sec => $title ) : ?>
				<h2><?php echo esc_html( $title ); ?></h2>
				<table class="form-table" role="presentation">
					<?php
					foreach ( mmcl_setting_fields() as $key => $f ) :
						if ( $f[2] !== $sec ) {
							continue;
						}
						$id = 'mmcl-' . $key;
						?>
						<tr>
							<th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $f[0] ); ?></label></th>
							<td>
								<?php if ( 'textarea' === $f[1] ) : ?>
									<textarea id="<?php echo esc_attr( $id ); ?>" class="large-text" rows="3" name="<?php echo esc_attr( $name . '[' . $key . ']' ); ?>"><?php echo esc_textarea( $s[ $key ] ); ?></textarea>
								<?php else : ?>
									<input id="<?php echo esc_attr( $id ); ?>" type="text" class="regular-text" name="<?php echo esc_attr( $name . '[' . $key . ']' ); ?>" value="<?php echo esc_attr( $s[ $key ] ); ?>">
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>
			<?php endforeach; ?>

			<?php submit_button( 'Zapisz ustawienia' ); ?>
		</form>
	</div>
	<?php
}
