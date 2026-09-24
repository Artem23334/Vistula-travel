<?php
/**
 * Site header content: logo, primary nav, language switcher, Book Now CTA,
 * mobile nav structure.
 *
 * Every piece of business content (logo, CTA label/target) comes through
 * the vistula-core data-access layer, not hard-coded strings — see
 * docs/project-architecture.md §3.3 rule 2 and §4.6.
 *
 * @package Vistula_Travel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$vistula_logo_id = vistula_setting( 'logo_id' );
?>
<header class="site-header">

	<div class="site-header__branding">
		<a class="site-header__logo-link" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<?php if ( $vistula_logo_id ) : ?>
				<?php echo wp_get_attachment_image( (int) $vistula_logo_id, 'medium' ); ?>
			<?php else : ?>
				<?php // No plugin-managed logo yet (Stage 3) — fall back to the site title. ?>
				<span class="site-header__logo-text"><?php bloginfo( 'name' ); ?></span>
			<?php endif; ?>
		</a>
	</div>

	<button
		type="button"
		class="site-header__menu-toggle"
		aria-expanded="false"
		aria-controls="primary-menu"
	>
		<span class="screen-reader-text"><?php esc_html_e( 'Menu', 'vistula-travel' ); ?></span>
	</button>

	<nav
		id="primary-menu"
		class="site-header__primary-nav"
		aria-label="<?php esc_attr_e( 'Primary', 'vistula-travel' ); ?>"
	>
		<?php
		wp_nav_menu(
			array(
				'theme_location' => 'primary',
				'container'      => false,
				'fallback_cb'    => false, // No fallback menu content — nothing to hard-code yet.
			)
		);
		?>
	</nav>

	<div class="site-header__lang-switcher" aria-hidden="true">
		<?php // Language switcher — implemented in Stage 11 (Multilingual). Intentionally empty. ?>
	</div>

	<a
		class="site-header__cta"
		href="<?php echo esc_url( vistula_page_url( 'booking' ) ); ?>"
	>
		<?php echo esc_html( vistula_setting( 'cta_label', __( 'Book Now', 'vistula-travel' ) ) ); ?>
	</a>

</header>
