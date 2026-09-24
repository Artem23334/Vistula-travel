<?php
/**
 * The theme footer.
 *
 * Exists in the theme root as required by docs/project-architecture.md §17.2.
 * Every template must call get_footer(). Closes the <main> opened in header.php.
 *
 * @package Vistula_Travel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
</main>

<?php get_template_part( 'template-parts/layout/site-footer' ); ?>

<?php wp_footer(); ?>
</body>
</html>
