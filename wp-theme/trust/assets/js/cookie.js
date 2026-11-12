/**
 * Плашка cookie и согласие посетителя (ТЗ, раздел 14; макет seo-bloki/17-cookie.html).
 *
 * Выбор хранится в cookie trust_consent: { v: версия, necessary, analytics, marketing, choice,
 * date } на срок из настроек. Нет выбора или изменилась версия согласия — показывается плашка.
 * Кнопки: «Принять» — все категории, «Принять только обязательные» и «Отклонить всё» — только
 * обязательные. После выбора плашка скрывается, появляется подсказка с кнопкой «Изменить»
 * (через 10 секунд прячется; пока на ней фокус или курсор — нет).
 *
 * Код счётчиков и пикселей лежит в <template data-trust-consent="analytics|marketing"> и
 * вставляется в страницу только при согласии на свою категорию — сразу, без перезагрузки.
 * Если согласие на уже вставленный код отозвано, страница перезагружается (выполненный код
 * иначе не остановить), а cookie известных счётчиков удаляются.
 *
 * Открыть плашку снова: элемент с атрибутом data-trust-cookie-settings или ссылка на
 * #cookie-settings (её можно добавить в любое меню).
 *
 * window.trustConsent: get() → выбор или null; has( категория ) → bool; set( { analytics,
 * marketing }, choice ) — сохранить выбор (блок «Настройки cookie» на странице политики); open().
 * Событие trust:consent на window — после каждого сохранения выбора.
 */
( () => {
	const config = window.trustCookieConfig || {};
	const NAME = config.name || 'trust_consent';
	const CATEGORIES = [ 'analytics', 'marketing' ];
	// Cookie счётчиков, которые удаляются при отзыве согласия (Метрика, Google, VK, Meta).
	const TRACKERS = /^(_ym|_ga|_gid|_gat|_gcl|_fbp|_fbc|tmr_|_ymab|yandexuid|yabs-)/;
	const banner = document.querySelector( '[data-trust-cookie-banner]' );
	const saved = document.querySelector( '[data-trust-cookie-saved]' );
	const inserted = new Set();
	let hideTimer = 0;

	function read() {
		const match = document.cookie.match( new RegExp( `(?:^|;\\s*)${ NAME }=([^;]*)` ) );
		if ( ! match ) {
			return null;
		}
		try {
			const value = JSON.parse( decodeURIComponent( match[ 1 ] ) );
			return value && String( value.v ) === String( config.version ) ? value : null;
		} catch ( e ) {
			return null;
		}
	}

	function write( value ) {
		const secure = 'https:' === location.protocol ? '; Secure' : '';
		const age = Math.max( 1, Number( config.days ) || 365 ) * 86400;
		document.cookie = `${ NAME }=${ encodeURIComponent( JSON.stringify( value ) ) }; max-age=${ age }; path=/; SameSite=Lax${ secure }`;
	}

	// --- Код счётчиков ---

	function insert( category ) {
		if ( inserted.has( category ) ) {
			return;
		}
		inserted.add( category );
		document.querySelectorAll( `template[data-trust-consent="${ category }"]` ).forEach( ( template ) => {
			const fragment = template.content.cloneNode( true );
			// Скрипты из <template> пересоздаются — иначе браузер их не выполнит.
			fragment.querySelectorAll( 'script' ).forEach( ( old ) => {
				const script = document.createElement( 'script' );
				[ ...old.attributes ].forEach( ( attr ) => script.setAttribute( attr.name, attr.value ) );
				script.text = old.textContent;
				old.replaceWith( script );
			} );
			template.after( fragment );
		} );
	}

	function apply( value ) {
		CATEGORIES.forEach( ( category ) => {
			if ( value && value[ category ] ) {
				insert( category );
			}
		} );
	}

	function removeTrackers() {
		const host = location.hostname;
		const parts = host.split( '.' );
		const domains = [ '', host, `.${ host }` ];
		for ( let i = 1; i < parts.length - 1; i++ ) {
			domains.push( `.${ parts.slice( i ).join( '.' ) }` );
		}
		document.cookie.split( ';' ).map( ( c ) => c.split( '=' )[ 0 ].trim() ).filter( ( name ) => TRACKERS.test( name ) ).forEach( ( name ) => {
			domains.forEach( ( domain ) => {
				document.cookie = `${ name }=; max-age=0; path=/${ domain ? `; domain=${ domain }` : '' }`;
			} );
		} );
	}

	// --- Плашка и подсказка ---

	function showSaved( choice ) {
		if ( ! saved ) {
			return;
		}
		clearTimeout( hideTimer );
		saved.hidden = false;
		saved.querySelector( '.cookie-saved__text' ).textContent = ( config.messages || {} )[ choice ] || '';
		const hide = () => {
			hideTimer = setTimeout( () => {
				if ( ! saved.matches( ':hover, :focus-within' ) ) {
					saved.hidden = true;
				}
			}, 10000 );
		};
		saved.onmouseleave = hide;
		saved.onfocusout = hide;
		hide();
	}

	function open() {
		if ( ! banner ) {
			return;
		}
		clearTimeout( hideTimer );
		if ( saved ) {
			saved.hidden = true;
		}
		banner.hidden = false;
		const current = read();
		const button = banner.querySelector( `[data-trust-consent-choice="${ current ? current.choice : 'all' }"]` ) || banner.querySelector( 'button' );
		// Открыта по просьбе посетителя — фокус на плашку; при первом визите фокус не уводим.
		return button;
	}

	function set( categories, choice ) {
		const before = read();
		const value = { v: config.version, necessary: true, choice: choice || 'custom', date: new Date().toISOString() };
		CATEGORIES.forEach( ( category ) => {
			value[ category ] = !! ( categories && categories[ category ] );
		} );
		write( value );
		window.dispatchEvent( new CustomEvent( 'trust:consent', { detail: value } ) );

		const revoked = CATEGORIES.some( ( category ) => ( inserted.has( category ) || ( before && before[ category ] ) ) && ! value[ category ] );
		if ( revoked ) {
			removeTrackers();
			if ( CATEGORIES.some( ( category ) => inserted.has( category ) && ! value[ category ] ) ) {
				location.reload();
				return value;
			}
		}
		apply( value );
		return value;
	}

	function choose( choice ) {
		const all = 'all' === choice;
		set( { analytics: all, marketing: all }, choice );
		if ( banner ) {
			const hadFocus = banner.contains( document.activeElement );
			banner.hidden = true;
			showSaved( choice );
			if ( hadFocus && saved ) {
				saved.querySelector( 'button' ).focus();
			}
		}
	}

	if ( banner ) {
		banner.addEventListener( 'click', ( event ) => {
			const button = event.target.closest( '[data-trust-consent-choice]' );
			if ( button ) {
				choose( button.dataset.trustConsentChoice );
			}
		} );
	}

	document.addEventListener( 'click', ( event ) => {
		const trigger = event.target.closest( '[data-trust-cookie-settings], a[href$="#cookie-settings"]' );
		if ( trigger && banner ) {
			event.preventDefault();
			const button = open();
			if ( button ) {
				button.focus();
			}
		}
	} );

	const current = read();
	if ( current ) {
		apply( current );
	} else if ( banner ) {
		banner.hidden = false;
	}

	window.trustConsent = {
		get: read,
		has: ( category ) => 'necessary' === category || !! ( read() || {} )[ category ],
		set,
		open: () => {
			const button = open();
			if ( button ) {
				button.focus();
			}
		},
	};
} )();
