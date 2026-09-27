<?php
/**
 * Site footer content: company info, navigation, tours/destinations
 * placeholders, social links, legal links, copyright, language switcher.
 *
 * Tours/Destinations lists become real dynamic queries once those CPTs
 * exist (Stage 5 / Stage 8) — see docs/project-architecture.md §4.6. No
 * tour or destination names are hard-coded here even as placeholders.
 *
 * @package Vistula_Travel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$vistula_social_links = vistula_setting( 'social_links', array() );
?>
<footer class="site-footer">

	<div class="site-footer__company">
		<p class="site-footer__phone">
			<?php
			$vistula_phone = vistula_setting( 'phone' );
			if ( $vistula_phone ) :
				?>
				<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', (string) $vistula_phone ) ); ?>">
					<?php echo esc_html( $vistula_phone ); ?>
				</a>
				<?php
			endif;
			?>
		</p>
		<p class="site-footer__email">
			<?php
			$vistula_email = vistula_setting( 'email' );
			if ( $vistula_email ) :
				?>
				<a href="mailto:<?php echo esc_attr( $vistula_email ); ?>">
					<?php echo esc_html( $vistula_email ); ?>
				</a>
				<?php
			endif;
			?>
		</p>
		<address class="site-footer__address">
			<?php echo esc_html( vistula_formatted_address() ); ?>
		</address>
	</div>

	<nav
		class="site-footer__nav"
		aria-label="<?php esc_attr_e( 'Footer', 'vistula-travel' ); ?>"
	>
		<?php
		wp_nav_menu(
			array(
				'theme_location' => 'footer_nav',
				'container'      => false,
				'fallback_cb'    => false,
			)
		);
		?>
	</nav>

	<div class="site-footer__tours" data-role="dynamic-tours-list">
		<?php // Populated by a real WP_Query once the `tour` CPT exists — Stage 5. ?>
	</div>

	<div class="site-footer__destinations" data-role="dynamic-destinations-list">
		<?php // Populated by a real WP_Query once the `destination` CPT exists — Stage 8. ?>
	</div>

	<?php if ( ! empty( $vistula_social_links ) && is_array( $vistula_social_links ) ) : ?>
		<ul class="site-footer__social">
			<?php foreach ( $vistula_social_links as $vistula_label => $vistula_url ) : ?>
				<li>
					<a href="<?php echo esc_url( (string) $vistula_url ); ?>">
						<?php echo esc_html( (string) $vistula_label ); ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<nav
		class="site-footer__legal"
		aria-label="<?php esc_attr_e( 'Legal', 'vistula-travel' ); ?>"
	>
		<?php
		wp_nav_menu(
			array(
				'theme_location' => 'footer_legal',
				'container'      => false,
				'fallback_cb'    => false,
			)
		);
		?>
	</nav>

	<div class="site-footer__lang-switcher" aria-hidden="true">
		<?php // Language switcher — implemented in Stage 11 (Multilingual). Intentionally empty. ?>
	</div>

	<p class="site-footer__copyright">
		&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>
	</p>

</footer>
