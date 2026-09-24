<?php
/**
 * Default template for a single post (core `post` type — the Travel Guide
 * blog, Stage 10). A dedicated single-tour.php arrives in Stage 7, per
 * docs/implementation-roadmap.md — this file deliberately does not
 * anticipate that template.
 *
 * @package Vistula_Travel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<?php while ( have_posts() ) : ?>
	<?php the_post(); ?>

	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
		<header class="entry-header">
			<h1 class="entry-title"><?php the_title(); ?></h1>
			<p class="entry-meta">
				<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>">
					<?php echo esc_html( get_the_date() ); ?>
				</time>
			</p>
		</header>

		<div class="entry-content">
			<?php the_content(); ?>
		</div>
	</article>

<?php endwhile; ?>

<?php get_footer(); ?>
