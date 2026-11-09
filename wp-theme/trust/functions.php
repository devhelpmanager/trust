<?php
/**
 * Тема Trust: точка входа.
 *
 * Здесь только константы, автозагрузка классов и запуск модулей.
 * Логика лежит в классах пространства имён Trust\ в папке inc/.
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

define( 'TRUST_VERSION', '0.1.0' );
define( 'TRUST_DIR', get_template_directory() );
define( 'TRUST_URI', get_template_directory_uri() );

/*
 * Автозагрузка классов: Trust\Foo_Bar → inc/class-foo-bar.php,
 * Trust\Schema\Graph → inc/schema/class-graph.php.
 */
spl_autoload_register(
	static function ( string $class_name ): void {
		$prefix = 'Trust\\';
		if ( ! str_starts_with( $class_name, $prefix ) ) {
			return;
		}

		$parts = explode( '\\', substr( $class_name, strlen( $prefix ) ) );
		$name  = array_pop( $parts );
		$dir   = $parts ? strtolower( str_replace( '_', '-', implode( '/', $parts ) ) ) . '/' : '';
		$file  = TRUST_DIR . '/inc/' . $dir . 'class-' . strtolower( str_replace( '_', '-', $name ) ) . '.php';

		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

Trust\Theme::boot();
