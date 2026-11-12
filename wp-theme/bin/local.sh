#!/usr/bin/env bash
#
# Локальный стенд для разработки темы Trust:
# WordPress + SQLite (без MySQL) + Secure Custom Fields + WP-CLI, встроенный сервер PHP.
#
# Всё скачивается с GitHub (официальные репозитории WordPress и WP-CLI), поэтому стенд
# поднимается и там, где wordpress.org недоступен.
#
# Использование:
#   wp-theme/bin/local.sh setup        — скачать и установить (повторный запуск ничего не ломает)
#   wp-theme/bin/local.sh start        — запустить сервер в фоне (http://localhost:8080)
#   wp-theme/bin/local.sh stop         — остановить сервер
#   wp-theme/bin/local.sh wp <команда> — выполнить команду WP-CLI на стенде
#   wp-theme/bin/local.sh reset        — удалить базу и установить WordPress заново
#
# Переменные окружения (необязательно):
#   TRUST_DEV_DIR (по умолчанию ~/wp-dev), TRUST_PORT (8080),
#   WP_VERSION (7.1.4), SCF_VERSION (6.9.8), SQLITE_VERSION (v3.1.3).
#
# Вход в админку: http://localhost:8080/wp-admin/, логин admin, пароль admin.

set -euo pipefail

WP_VERSION="${WP_VERSION:-7.1.4}"
SCF_VERSION="${SCF_VERSION:-6.9.8}"
SQLITE_VERSION="${SQLITE_VERSION:-v3.1.3}"
DEV_DIR="${TRUST_DEV_DIR:-$HOME/wp-dev}"
PORT="${TRUST_PORT:-8080}"
URL="http://localhost:${PORT}"

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
THEME_SRC="${REPO_ROOT}/wp-theme/trust"
WP_DIR="${DEV_DIR}/wordpress"
WP_CLI="${DEV_DIR}/wp-cli.phar"
PID_FILE="${DEV_DIR}/server.pid"
LOG_FILE="${DEV_DIR}/server.log"

say() { printf '\033[1;35m▸\033[0m %s\n' "$*"; }

SQLITE_SRC="${DEV_DIR}/sqlite-src"
SQLITE_PLUGIN_DIR="${WP_DIR}/wp-content/plugins/sqlite-database-integration"

# В контейнерах всё работает от root — WP-CLI тогда нужен флаг --allow-root.
ROOT_FLAG=()
if [ "$(id -u)" = "0" ]; then
	ROOT_FLAG=(--allow-root)
fi

wp_cli() {
	php -d memory_limit=512M "${WP_CLI}" --path="${WP_DIR}" "${ROOT_FLAG[@]}" "$@"
}

# Неглубокий клон нужного тега, если папки ещё нет.
clone() {
	local url="$1" tag="$2" dir="$3"
	if [ ! -d "${dir}" ]; then
		say "Скачиваю ${url} (${tag})"
		git clone --quiet --depth 1 --branch "${tag}" "${url}" "${dir}"
		rm -rf "${dir}/.git"
	fi
}

download() {
	mkdir -p "${DEV_DIR}"

	clone https://github.com/WordPress/WordPress.git "${WP_VERSION}" "${WP_DIR}"
	# SCF — готовый архив релиза с GitHub (тот же, что в каталоге WordPress.org): в исходниках
	# репозитория нет собранных JS/CSS и папки vendor.
	local scf="${WP_DIR}/wp-content/plugins/secure-custom-fields"
	if [ ! -f "${scf}/assets/build/js/acf.min.js" ]; then
		say "Скачиваю Secure Custom Fields ${SCF_VERSION}"
		local tmp
		tmp="$(mktemp -d)"
		curl -fsSL -o "${tmp}/scf.zip" \
			"https://github.com/WordPress/secure-custom-fields/releases/download/${SCF_VERSION}/secure-custom-fields.zip"
		rm -rf "${scf}"
		unzip -q "${tmp}/scf.zip" -d "${WP_DIR}/wp-content/plugins/"
		rm -rf "${tmp}"
	fi
	# SQLite — монорепозиторий: плагин в packages/plugin-sqlite-database-integration, а его
	# wp-includes/database — ссылка на packages/mysql-on-sqlite/src. Копируем плагин, раскрывая ссылку.
	clone https://github.com/WordPress/sqlite-database-integration.git "${SQLITE_VERSION}" "${SQLITE_SRC}"
	if [ ! -d "${SQLITE_PLUGIN_DIR}" ]; then
		cp -rL "${SQLITE_SRC}/packages/plugin-sqlite-database-integration" "${SQLITE_PLUGIN_DIR}"
	fi

	if [ ! -f "${WP_CLI}" ]; then
		say "Скачиваю WP-CLI"
		curl -fsSL -o "${WP_CLI}" https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
	fi
}

configure() {
	local content="${WP_DIR}/wp-content"

	# Драйвер SQLite: wp-content/db.php из шаблона плагина.
	if [ ! -s "${content}/db.php" ]; then
		say "Подключаю SQLite"
		sed -e "s#{SQLITE_IMPLEMENTATION_FOLDER_PATH}#${SQLITE_PLUGIN_DIR}#g" \
			-e "s#{SQLITE_PLUGIN}#sqlite-database-integration/load.php#g" \
			"${SQLITE_PLUGIN_DIR}/db.copy" > "${content}/db.php"
	fi

	if [ ! -f "${WP_DIR}/wp-config.php" ]; then
		say "Создаю wp-config.php"
		wp_cli config create --dbname=wordpress --dbuser=wp --dbpass=wp --dbhost=localhost --locale=ru_RU --skip-check --quiet
	fi

	# Настройки стенда (повторный запуск просто перезаписывает их).
	# Адрес задаём явно: при установке из командной строки WordPress угадывает его по пути к папке.
	wp_cli config set WP_HOME "${URL}" --quiet
	wp_cli config set WP_SITEURL "${URL}" --quiet
	# Язык сайта (<html lang="ru-RU">). Настройку WPLANG WordPress принимает только для установленных
	# переводов, а переводы ядра на стенд не скачать (wordpress.org недоступен) — поэтому константа.
	wp_cli config set WPLANG ru_RU --quiet
	wp_cli config set DB_DIR "${content}/database" --quiet
	wp_cli config set DB_FILE ".ht.sqlite" --quiet
	wp_cli config set WP_DEBUG true --raw --quiet
	wp_cli config set WP_DEBUG_LOG "${DEV_DIR}/debug.log" --quiet
	wp_cli config set WP_DEBUG_DISPLAY false --raw --quiet
	wp_cli config set SCRIPT_DEBUG true --raw --quiet
	wp_cli config set WP_ENVIRONMENT_TYPE local --quiet
	wp_cli config set AUTOMATIC_UPDATER_DISABLED true --raw --quiet
	wp_cli config set DISABLE_WP_CRON true --raw --quiet
	# Стенд не ходит во внешний интернет (проверки обновлений и т. п.).
	wp_cli config set WP_HTTP_BLOCK_EXTERNAL true --raw --quiet

	# Только для стенда: без проверок обновлений и Gravatar — из контейнера они недоступны,
	# а попытки засоряют журнал ошибок и консоль браузера. В тему это не входит.
	mkdir -p "${content}/mu-plugins"
	cat > "${content}/mu-plugins/trust-local.php" <<-'PHP'
	<?php
	/**
	 * Локальный стенд Trust: не обращаться к wordpress.org и Gravatar.
	 */
	foreach ( array( 'update_core', 'update_plugins', 'update_themes' ) as $trust_local_transient ) {
		add_filter(
			"pre_site_transient_{$trust_local_transient}",
			static fn() => (object) array(
				'last_checked'    => time(),
				'version_checked' => get_bloginfo( 'version' ),
				'updates'         => array(),
				'response'        => array(),
				'translations'    => array(),
			)
		);
	}
	add_filter( 'pre_option_show_avatars', '__return_zero' );
	PHP

	# Тема — символическая ссылка на папку из репозитория: правки видны сразу.
	if [ ! -e "${content}/themes/trust" ]; then
		ln -s "${THEME_SRC}" "${content}/themes/trust"
	fi
}

install() {
	if wp_cli core is-installed 2>/dev/null; then
		say "WordPress уже установлен"
	else
		say "Устанавливаю WordPress ${WP_VERSION}"
		wp_cli core install --url="${URL}" --title="Trust — локальный стенд" \
			--admin_user=admin --admin_password=admin --admin_email=admin@example.test --skip-email --quiet
		wp_cli option update timezone_string Europe/Moscow --quiet
		wp_cli option update date_format 'j F Y' --quiet
		wp_cli rewrite structure '/blog/%postname%/' --quiet
	fi

	wp_cli plugin activate sqlite-database-integration secure-custom-fields --quiet
	wp_cli theme activate trust --quiet
	say "Готово: ${URL}/ (админка: ${URL}/wp-admin/, admin / admin)"
}

start() {
	# «Запущен» — только если сервер отвечает: после перезапуска контейнера номер процесса
	# из pid-файла может достаться другому процессу.
	if curl -fs -o /dev/null "${URL}/wp-login.php"; then
		say "Сервер уже запущен: ${URL}/"
		return
	fi
	# Старый процесс останавливаем, только если это действительно наш сервер.
	if [ -f "${PID_FILE}" ] && ps -p "$(cat "${PID_FILE}")" -o args= 2>/dev/null | grep -q "wp-cli.phar.*server"; then
		kill "$(cat "${PID_FILE}")" 2>/dev/null || true
	fi
	rm -f "${PID_FILE}"
	say "Запускаю сервер: ${URL}/ (журнал: ${LOG_FILE})"
	nohup php -d memory_limit=512M "${WP_CLI}" --path="${WP_DIR}" "${ROOT_FLAG[@]}" server --host=localhost --port="${PORT}" > "${LOG_FILE}" 2>&1 &
	echo $! > "${PID_FILE}"
	for _ in $(seq 1 30); do
		curl -fs -o /dev/null "${URL}/wp-login.php" && return
		sleep 0.3
	done
	say "Сервер не ответил, смотрите ${LOG_FILE}" && exit 1
}

stop() {
	if [ -f "${PID_FILE}" ]; then
		kill "$(cat "${PID_FILE}")" 2>/dev/null || true
		pkill -f "wp-cli.phar --path=${WP_DIR} server" 2>/dev/null || true
		rm -f "${PID_FILE}"
		say "Сервер остановлен"
	fi
}

reset() {
	stop
	rm -rf "${WP_DIR}/wp-content/database"
	install
}

case "${1:-}" in
	setup)
		# Отдельными командами: внутри цепочки через && «set -e» не останавливает скрипт.
		download
		configure
		install
		;;
	start) start ;;
	stop) stop ;;
	reset) reset ;;
	wp) shift && wp_cli "$@" ;;
	*) sed -n '2,24p' "$0" | sed 's/^# \{0,1\}//' ;;
esac
