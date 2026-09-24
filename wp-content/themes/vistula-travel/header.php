<?php
/**
 * The theme header.
 *
 * Exists in the theme root as required by docs/project-architecture.md §17.2
 * ("Non-negotiable header/footer rules") to guard against WordPress's
 * theme-compat fallback. Every template must call get_header().
 *
 * @package Vistula_Travel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="#site-content">
	<?php esc_html_e( 'Skip to content', 'vistula-travel' ); ?>
</a>

<?php get_template_part( 'template-parts/layout/site-header' ); ?>

<main id="site-content">
