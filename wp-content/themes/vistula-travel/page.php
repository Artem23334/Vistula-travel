<?php
/**
 * Default template for WordPress Pages.
 *
 * Deliberately generic. Page-role-specific templates (Tours listing,
 * Destinations listing, About, FAQ, Contact, ...) are page-templates/
 * files added in their own stages, per docs/project-architecture.md §17.1
 * — none of those exist yet.
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
		</header>

		<div class="entry-content">
			<?php the_content(); ?>
		</div>
	</article>

<?php endwhile; ?>

<?php get_footer(); ?>
