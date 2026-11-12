<?php
/**
 * Запуск модулей темы.
 *
 * @package Trust
 */

namespace Trust;

defined( 'ABSPATH' ) || exit;

/**
 * Подключает модули темы в нужном порядке.
 */
final class Theme {

	/**
	 * Регистрирует хуки всех модулей.
	 */
	public static function boot(): void {
		Setup::register();
		Cleanup::register();
		Assets::register();
		Dependencies::register();
		Translit::register();
		Urls::register();
		Content_Types::register();
		Content_Fields::register();
		Blog::register();
		Cases::register();
		Reviews::register();
		Team::register();
		Vacancies::register();
		Questions::register();
		Admin::register();
		Page_Types::register();
		Allowed_Blocks::register();
		Seo::register();
		Seo_Admin::register();
		Breadcrumbs::register();
		Sitemap::register();
		Schema\Graph::register();
		Forms\Handler::register();
		Forms\Leads::register();
		Forms\Notifications::register();
		Cookie::register();
		Settings::register();
		Blocks::register();

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'trust demo', CLI\Demo_Command::class );
		}
	}
}
