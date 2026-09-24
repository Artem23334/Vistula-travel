<?php
/**
 * The catch-all template. Required by WordPress as the theme's fallback
 * when no more specific template matches. Kept generic on purpose.
 *
 * @package Vistula_Travel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<?php if ( have_posts() ) : ?>

	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<header class="entry-header">
				<h2 class="entry-title">
					<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
				</h2>
			</header>
			<div class="entry-summary">
				<?php the_excerpt(); ?>
			</div>
		</article>
	<?php endwhile; ?>

<?php else : ?>

	<p><?php esc_html_e( 'Nothing found.', 'vistula-travel' ); ?></p>

<?php endif; ?>

<?php get_footer(); ?>
