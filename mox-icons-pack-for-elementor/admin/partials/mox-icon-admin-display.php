<?php
/**
 * Admin settings page view.
 *
 * @package MoxIcon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mox_icon_libraries   = MoxIcon::get_library_definitions();
$mox_icon_settings    = get_option( 'mox_icon_settings', MoxIcon::get_default_settings() );
$mox_icon_total_icons = array_sum( array_column( $mox_icon_libraries, 'count' ) );

$mox_icon_tabs = array(
	'tab-info'      => __( 'Icon Info', 'mox-icons-pack-for-elementor' ),
	'tab-settings'  => __( 'Icon Settings', 'mox-icons-pack-for-elementor' ),
	'tab-changelog' => __( 'Info Update', 'mox-icons-pack-for-elementor' ),
);

// Land on the settings tab after a save so the user returns where they were.
$mox_icon_saved      = isset( $_GET['settings-updated'] ); // phpcs:ignore WordPress.Security.NonceVerification
$mox_icon_active_tab = $mox_icon_saved ? 'tab-settings' : 'tab-info';
?>
<?php // Without JavaScript the tab script never runs, so show every panel instead of hiding all but one. ?>
<noscript>
	<style>
		.moxicon-tabs-nav { display: none; }
		.moxicon-tab-pane { display: block; }
	</style>
</noscript>
<div class="moxicon-wrap">
	<div class="moxicon-header">
		<div class="moxicon-header__logo">
			<img class="moxicon-header__brand-icon" src="<?php echo esc_url( MOX_ICON_URL . 'assets/images/mox-icons-logo.svg' ); ?>" width="48" height="55" alt="" aria-hidden="true" />
			<div>
				<h1><?php esc_html_e( 'Mox Icons', 'mox-icons-pack-for-elementor' ); ?></h1>
				<p><?php esc_html_e( 'By', 'mox-icons-pack-for-elementor' ); ?> <a href="https://moxcreative.com/" target="_blank" rel="noopener noreferrer">Moxcreative</a></p>
			</div>
		</div>
		<div class="moxicon-header__meta">
			<span class="moxicon-badge"><?php echo esc_html( 'v' . MOX_ICON_VERSION ); ?></span>
		</div>
	</div>

	<?php if ( $mox_icon_saved ) : ?>
		<div class="moxicon-notice moxicon-notice--success" role="status">
			<span aria-hidden="true">&#10003;</span> <?php esc_html_e( 'Settings saved successfully.', 'mox-icons-pack-for-elementor' ); ?>
		</div>
	<?php endif; ?>

	<div class="moxicon-tabs-nav" role="tablist" aria-label="<?php esc_attr_e( 'Mox Icons sections', 'mox-icons-pack-for-elementor' ); ?>">
		<?php foreach ( $mox_icon_tabs as $mox_icon_tab_id => $mox_icon_tab_label ) :
			$mox_icon_is_current = ( $mox_icon_active_tab === $mox_icon_tab_id );
		?>
		<button
			type="button"
			id="moxicon-tabbtn-<?php echo esc_attr( $mox_icon_tab_id ); ?>"
			class="moxicon-tab-btn<?php echo $mox_icon_is_current ? ' is-active' : ''; ?>"
			role="tab"
			aria-controls="<?php echo esc_attr( $mox_icon_tab_id ); ?>"
			aria-selected="<?php echo $mox_icon_is_current ? 'true' : 'false'; ?>"
			tabindex="<?php echo $mox_icon_is_current ? '0' : '-1'; ?>"
			data-target="<?php echo esc_attr( $mox_icon_tab_id ); ?>"
		><?php echo esc_html( $mox_icon_tab_label ); ?></button>
		<?php endforeach; ?>
	</div>

	<div class="moxicon-tabs-content">

		<!-- TAB 1: ICON INFO -->
		<div id="tab-info" class="moxicon-tab-pane<?php echo 'tab-info' === $mox_icon_active_tab ? ' is-active' : ''; ?>" role="tabpanel" aria-labelledby="moxicon-tabbtn-tab-info" tabindex="0">
			<div class="moxicon-section">
				<h2 class="moxicon-section__title"><?php esc_html_e( 'Library Information', 'mox-icons-pack-for-elementor' ); ?></h2>
				<p class="moxicon-section__desc">
					<?php
					printf(
						wp_kses(
							/* translators: 1: Total icons count wrapped in strong tag, 2: Total libraries count */
							__( 'Mox Icons currently provides a total of %1$s ready-to-use icons across %2$d libraries.', 'mox-icons-pack-for-elementor' ),
							array(
								'strong' => array(),
							)
						),
						'<strong>' . esc_html( number_format_i18n( $mox_icon_total_icons ) ) . '</strong>',
						esc_html( count( $mox_icon_libraries ) )
					);
					?>
				</p>

				<table class="moxicon-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Library Name', 'mox-icons-pack-for-elementor' ); ?></th>
							<th><?php esc_html_e( 'Total Icons', 'mox-icons-pack-for-elementor' ); ?></th>
							<th><?php esc_html_e( 'License', 'mox-icons-pack-for-elementor' ); ?></th>
							<th><?php esc_html_e( 'Credit / Official Site', 'mox-icons-pack-for-elementor' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $mox_icon_libraries as $mox_icon_lib ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $mox_icon_lib['label'] ); ?></strong></td>
							<td><?php echo esc_html( number_format_i18n( $mox_icon_lib['count'] ) ); ?></td>
							<td><span class="moxicon-tag moxicon-tag--license"><?php echo esc_html( $mox_icon_lib['license'] ); ?></span></td>
							<td><a href="<?php echo esc_url( $mox_icon_lib['credit'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $mox_icon_lib['credit'] ); ?> <span aria-hidden="true">&#8599;</span></a></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>

		<!-- TAB 2: ICON SETTINGS -->
		<div id="tab-settings" class="moxicon-tab-pane<?php echo 'tab-settings' === $mox_icon_active_tab ? ' is-active' : ''; ?>" role="tabpanel" aria-labelledby="moxicon-tabbtn-tab-settings" tabindex="0">
			<form method="post" action="options.php" id="moxicon-settings-form">
				<?php settings_fields( 'mox_icon_settings_group' ); ?>

				<div class="moxicon-section">
					<h2 class="moxicon-section__title">
						<?php esc_html_e( 'Manage Libraries', 'mox-icons-pack-for-elementor' ); ?>
					</h2>
					<p class="moxicon-section__desc">
						<?php esc_html_e( 'Enable or disable icon libraries. Disabled libraries will not load in Elementor, keeping your site fast.', 'mox-icons-pack-for-elementor' ); ?>
					</p>

					<div class="moxicon-libraries">
						<?php foreach ( $mox_icon_libraries as $mox_icon_key => $mox_icon_lib ) :
							$mox_icon_enabled = isset( $mox_icon_settings[ $mox_icon_key ] ) ? (bool) $mox_icon_settings[ $mox_icon_key ] : true;
						?>
						<div class="moxicon-library-card <?php echo esc_attr( $mox_icon_enabled ? 'is-active' : '' ); ?>" data-key="<?php echo esc_attr( $mox_icon_key ); ?>">
							<div class="moxicon-library-card__header">
								<div class="moxicon-library-card__info">
									<h3><?php echo esc_html( $mox_icon_lib['label'] ); ?></h3>
									<p><?php echo esc_html( $mox_icon_lib['description'] ); ?></p>
								</div>
								<label class="moxicon-toggle" for="mox_<?php echo esc_attr( $mox_icon_key ); ?>">
									<span class="screen-reader-text">
										<?php
										printf(
											/* translators: %s: Icon library name */
											esc_html__( 'Enable %s', 'mox-icons-pack-for-elementor' ),
											esc_html( $mox_icon_lib['label'] )
										);
										?>
									</span>
									<input
										type="checkbox"
										id="mox_<?php echo esc_attr( $mox_icon_key ); ?>"
										name="mox_icon_settings[<?php echo esc_attr( $mox_icon_key ); ?>]"
										value="1"
										<?php checked( $mox_icon_enabled ); ?>
									/>
									<span class="moxicon-toggle__slider" aria-hidden="true"></span>
								</label>
							</div>
							<div class="moxicon-library-card__meta">
								<span class="moxicon-tag moxicon-tag--count">
									<?php echo esc_html( number_format_i18n( $mox_icon_lib['count'] ) ); ?> <?php esc_html_e( 'icons', 'mox-icons-pack-for-elementor' ); ?>
								</span>
								<span class="moxicon-status-tag moxicon-tag <?php echo esc_attr( $mox_icon_enabled ? 'moxicon-tag--active' : 'moxicon-tag--inactive' ); ?>">
									<?php echo $mox_icon_enabled ? esc_html__( 'Active', 'mox-icons-pack-for-elementor' ) : esc_html__( 'Inactive', 'mox-icons-pack-for-elementor' ); ?>
								</span>
							</div>
						</div>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="moxicon-footer">
					<?php submit_button( __( 'Save Settings', 'mox-icons-pack-for-elementor' ), 'primary', 'submit', false ); ?>
				</div>
			</form>
		</div>

		<!-- TAB 3: CHANGELOG -->
		<div id="tab-changelog" class="moxicon-tab-pane<?php echo 'tab-changelog' === $mox_icon_active_tab ? ' is-active' : ''; ?>" role="tabpanel" aria-labelledby="moxicon-tabbtn-tab-changelog" tabindex="0">
			<div class="moxicon-section">
				<h2 class="moxicon-section__title"><?php esc_html_e( 'Changelog & Updates', 'mox-icons-pack-for-elementor' ); ?></h2>
				<p class="moxicon-section__desc"><?php esc_html_e( 'Keep track of the latest additions to Mox Icons.', 'mox-icons-pack-for-elementor' ); ?></p>

				<div class="moxicon-changelog">
					<div class="moxicon-changelog__item">
						<div class="moxicon-changelog__version">
							<span class="moxicon-badge">v1.1.7</span>
							<span class="moxicon-changelog__date">October 5, 2026</span>
						</div>
						<ul class="moxicon-changelog__list">
							<li><strong><?php esc_html_e( 'Fix:', 'mox-icons-pack-for-elementor' ); ?></strong> <?php esc_html_e( 'Material Design Icons, and Simple Line Icons placed with 1.1.4 or earlier, no longer lose their stylesheet when Elementor serves the page from its element cache.', 'mox-icons-pack-for-elementor' ); ?></li>
							<li><strong><?php esc_html_e( 'Fix:', 'mox-icons-pack-for-elementor' ); ?></strong> <?php esc_html_e( 'Icons no longer lose their styles in a template, header, footer or loop item that was cached while shown next to another document using the same icon.', 'mox-icons-pack-for-elementor' ); ?></li>
							<li><strong><?php esc_html_e( 'Fix:', 'mox-icons-pack-for-elementor' ); ?></strong> <?php esc_html_e( 'The mox_icon_use_full_stylesheet filter now works for icon classes written outside Elementor\'s icon controls.', 'mox-icons-pack-for-elementor' ); ?></li>
							<li><strong><?php esc_html_e( 'Update:', 'mox-icons-pack-for-elementor' ); ?></strong> <?php esc_html_e( 'Material Design Icons now ships only its WOFF2 font, making the installed plugin about 1.9 MB smaller, and its picker no longer lists an invisible "blank" entry (7,447 icons).', 'mox-icons-pack-for-elementor' ); ?></li>
							<li><strong><?php esc_html_e( 'Performance:', 'mox-icons-pack-for-elementor' ); ?></strong> <?php esc_html_e( 'Icon library definitions are now built once per request instead of once for every icon rendered.', 'mox-icons-pack-for-elementor' ); ?></li>
						</ul>
					</div>
					<div class="moxicon-changelog__item">
						<div class="moxicon-changelog__version">
							<span class="moxicon-badge">v1.1.5</span>
							<span class="moxicon-changelog__date">September 25, 2026</span>
						</div>
						<ul class="moxicon-changelog__list">
							<li><strong><?php esc_html_e( 'Performance:', 'mox-icons-pack-for-elementor' ); ?></strong> <?php esc_html_e( 'Pages now load only the icon rules they use. A single icon used to pull in its library\'s entire stylesheet, up to about 4 MB. A page with five icons from four libraries now carries about 4 KB of icon CSS instead of about 12 MB.', 'mox-icons-pack-for-elementor' ); ?></li>
							<li><strong><?php esc_html_e( 'Performance:', 'mox-icons-pack-for-elementor' ); ?></strong> <?php esc_html_e( 'Bundled icon stylesheets are about 22% smaller, from 26.3 MB to 20.6 MB.', 'mox-icons-pack-for-elementor' ); ?></li>
							<li><strong><?php esc_html_e( 'Fix:', 'mox-icons-pack-for-elementor' ); ?></strong> <?php esc_html_e( 'Simple Line Icons is rebuilt from the Iconify icon set and rendered from SVG instead of a webfont. It used the unnamespaced "icon" and "icon-*" classes, which many themes and icon fonts also style, and a collision stopped the glyphs from appearing. All 189 icon names are unchanged and icons placed with earlier versions keep working.', 'mox-icons-pack-for-elementor' ); ?></li>
							<li><strong><?php esc_html_e( 'Fix:', 'mox-icons-pack-for-elementor' ); ?></strong> <?php esc_html_e( 'Icon manifests are now versioned, so a browser can no longer pair a fresh stylesheet with a cached older icon list.', 'mox-icons-pack-for-elementor' ); ?></li>
							<li><strong><?php esc_html_e( 'Fix:', 'mox-icons-pack-for-elementor' ); ?></strong> <?php esc_html_e( 'Uninstalling now removes plugin settings from every site of a multisite network.', 'mox-icons-pack-for-elementor' ); ?></li>
							<li><strong><?php esc_html_e( 'Update:', 'mox-icons-pack-for-elementor' ); ?></strong> <?php esc_html_e( 'Settings page tabs are now keyboard and screen-reader accessible, work without JavaScript, and text colours meet WCAG AA contrast.', 'mox-icons-pack-for-elementor' ); ?></li>
							<li><strong><?php esc_html_e( 'Update:', 'mox-icons-pack-for-elementor' ); ?></strong> <?php esc_html_e( 'Every bundled library now ships the full text of its license, including the ones that were missing a license file.', 'mox-icons-pack-for-elementor' ); ?></li>
						</ul>
					</div>
					<div class="moxicon-changelog__item">
						<div class="moxicon-changelog__version">
							<span class="moxicon-badge">v1.1.4</span>
							<span class="moxicon-changelog__date">September 22, 2026</span>
						</div>
						<ul class="moxicon-changelog__list">
							<li><strong><?php esc_html_e( 'New:', 'mox-icons-pack-for-elementor' ); ?></strong> <?php esc_html_e( 'Added Material Symbols Light (3,978 icons), Myna UI Icons (2,892 icons), and Health Icons (3,391 icons).', 'mox-icons-pack-for-elementor' ); ?></li>
							<li><strong><?php esc_html_e( 'Removed:', 'mox-icons-pack-for-elementor' ); ?></strong> <?php esc_html_e( 'Material Line Icons — its animations cannot render when icons are used as CSS mask images.', 'mox-icons-pack-for-elementor' ); ?></li>
						</ul>
					</div>
					<div class="moxicon-changelog__item">
						<div class="moxicon-changelog__version">
							<span class="moxicon-badge">v1.1.2</span>
							<span class="moxicon-changelog__date">July 6, 2026</span>
						</div>
						<ul class="moxicon-changelog__list">
							<li><strong><?php esc_html_e( 'New:', 'mox-icons-pack-for-elementor' ); ?></strong> <?php esc_html_e( 'Added Fluent UI System Color Icons (890 icons), Pixelarticons (1,172 icons), and Material Line Icons (1,279 icons).', 'mox-icons-pack-for-elementor' ); ?></li>
							<li><strong><?php esc_html_e( 'Fix:', 'mox-icons-pack-for-elementor' ); ?></strong> <?php esc_html_e( 'Improved generated Iconify SVG data so bundled icons render consistently from local CSS assets.', 'mox-icons-pack-for-elementor' ); ?></li>
						</ul>
					</div>
					<div class="moxicon-changelog__item">
						<div class="moxicon-changelog__version">
							<span class="moxicon-badge">v1.1.1</span>
							<span class="moxicon-changelog__date">June 6, 2026</span>
						</div>
						<ul class="moxicon-changelog__list">
							<li><strong><?php esc_html_e( 'Fix:', 'mox-icons-pack-for-elementor' ); ?></strong> <?php esc_html_e( 'Reduced generated asset file count to improve upload, activation, and deletion reliability.', 'mox-icons-pack-for-elementor' ); ?></li>
						</ul>
					</div>
					<div class="moxicon-changelog__item">
						<div class="moxicon-changelog__version">
							<span class="moxicon-badge">v1.1.0</span>
							<span class="moxicon-changelog__date">June 5, 2026</span>
						</div>
						<ul class="moxicon-changelog__list">
							<li><strong><?php esc_html_e( 'New:', 'mox-icons-pack-for-elementor' ); ?></strong> <?php esc_html_e( 'Added Lucide (1,962 icons) with ISC license credit.', 'mox-icons-pack-for-elementor' ); ?></li>
							<li><strong><?php esc_html_e( 'New:', 'mox-icons-pack-for-elementor' ); ?></strong> <?php esc_html_e( 'Added Tabler Icons (5,093 icons) with MIT license credit.', 'mox-icons-pack-for-elementor' ); ?></li>
							<li><strong><?php esc_html_e( 'New:', 'mox-icons-pack-for-elementor' ); ?></strong> <?php esc_html_e( 'Added Phosphor Icons (1,512 icons) with MIT license credit.', 'mox-icons-pack-for-elementor' ); ?></li>
							<li><strong><?php esc_html_e( 'New:', 'mox-icons-pack-for-elementor' ); ?></strong> <?php esc_html_e( 'Added Bootstrap Icons (2,078 icons) with MIT license credit.', 'mox-icons-pack-for-elementor' ); ?></li>
							<li><strong><?php esc_html_e( 'New:', 'mox-icons-pack-for-elementor' ); ?></strong> <?php esc_html_e( 'Added Iconoir (1,383 icons) with MIT license credit.', 'mox-icons-pack-for-elementor' ); ?></li>
							<li><strong><?php esc_html_e( 'New:', 'mox-icons-pack-for-elementor' ); ?></strong> <?php esc_html_e( 'Added Feather Icons (287 icons) with MIT license credit.', 'mox-icons-pack-for-elementor' ); ?></li>
							<li><strong><?php esc_html_e( 'Update:', 'mox-icons-pack-for-elementor' ); ?></strong> <?php esc_html_e( 'Added bundled third-party license files and expanded Icon Info credits.', 'mox-icons-pack-for-elementor' ); ?></li>
						</ul>
					</div>
					<div class="moxicon-changelog__item">
						<div class="moxicon-changelog__version">
							<span class="moxicon-badge">v1.0.0</span>
							<span class="moxicon-changelog__date">May 25, 2026</span>
						</div>
						<ul class="moxicon-changelog__list">
							<li><strong><?php esc_html_e( 'New:', 'mox-icons-pack-for-elementor' ); ?></strong> <?php esc_html_e( 'Initial release of Mox Icons.', 'mox-icons-pack-for-elementor' ); ?></li>
							<li><strong><?php esc_html_e( 'New:', 'mox-icons-pack-for-elementor' ); ?></strong> <?php esc_html_e( 'Added Heroicons Outline (324 icons).', 'mox-icons-pack-for-elementor' ); ?></li>
							<li><strong><?php esc_html_e( 'New:', 'mox-icons-pack-for-elementor' ); ?></strong> <?php esc_html_e( 'Added Heroicons Solid (324 icons).', 'mox-icons-pack-for-elementor' ); ?></li>
							<li><strong><?php esc_html_e( 'New:', 'mox-icons-pack-for-elementor' ); ?></strong> <?php esc_html_e( 'Added Material Design Icons (7,448 icons).', 'mox-icons-pack-for-elementor' ); ?></li>
							<li><strong><?php esc_html_e( 'New:', 'mox-icons-pack-for-elementor' ); ?></strong> <?php esc_html_e( 'Added Simple Line Icons (189 icons).', 'mox-icons-pack-for-elementor' ); ?></li>
							<li><strong><?php esc_html_e( 'Feature:', 'mox-icons-pack-for-elementor' ); ?></strong> <?php esc_html_e( 'Native integration with Elementor Icon Picker.', 'mox-icons-pack-for-elementor' ); ?></li>
							<li><strong><?php esc_html_e( 'Feature:', 'mox-icons-pack-for-elementor' ); ?></strong> <?php esc_html_e( 'Settings dashboard with library toggle.', 'mox-icons-pack-for-elementor' ); ?></li>
						</ul>
					</div>
				</div>
			</div>
		</div>

	</div>
</div>
