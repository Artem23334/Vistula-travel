<?php
/**
 * 404 (Not Found) template.
 *
 * @package Vistula_Travel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<section class="error-404 not-found">
	<header class="page-header">
		<h1 class="page-title"><?php esc_html_e( 'Page not found', 'vistula-travel' ); ?></h1>
	</header>

	<div class="page-content">
		<p>
			<?php esc_html_e( 'The page you were looking for could not be found.', 'vistula-travel' ); ?>
		</p>
		<p>
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<?php esc_html_e( 'Return to the homepage', 'vistula-travel' ); ?>
			</a>
		</p>
	</div>
</section>

<?php get_footer(); ?>
