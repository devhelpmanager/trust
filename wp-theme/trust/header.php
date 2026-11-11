<?php
/**
 * Начало страницы: <head>, ссылка «Перейти к содержимому», шапка.
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main"><?php esc_html_e( 'Перейти к содержимому', 'trust' ); ?></a>
<?php get_template_part( 'template-parts/header/site-header' ); ?>
<main id="main" class="site-main" tabindex="-1">
