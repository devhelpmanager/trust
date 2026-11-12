/**
 * Формы сайта: проверка полей, защита от спама, отправка заявок (ТЗ, раздел 12.2).
 *
 * Форма — <form data-trust-form="{тип}"> со скрытыми полями Trust\Forms::hidden_fields().
 * Поля с атрибутом data-error проверяются при отправке теми же правилами, что на сервере
 * (Trust\Forms\Handler); текст ошибки — из data-error, выводится в элемент из aria-describedby.
 *
 * При первом касании формы скрипт берёт свежие токен и nonce (страница могла пролежать в кэше)
 * и, если форме нужна капча, загружает виджет провайдера. Отправка — fetch на
 * /wp-json/trust/v1/forms/{тип} без перезагрузки; без JavaScript форма уходит на admin-post.php.
 * Вместе с полями уходят адрес страницы и источник перехода из cookie trust_source: UTM-метки,
 * откуда пришёл посетитель, первая страница и время первого визита (запоминается на 90 дней).
 *
 * window.trustForms:
 * - validate( form ) → первое поле с ошибкой или null (ошибки показаны под полями);
 * - check( input ) → проверяет одно поле, показывает или снимает ошибку;
 * - setError( input, message );
 * - submit( form ) → Promise<boolean>: true — заявка принята; иначе ошибки показаны в форме.
 *
 * Формы без своего скрипта (без атрибута data-trust-form-custom) подключаются сами: проверка,
 * отправка, затем экран успеха — элемент с id из data-success (кнопка data-form-again
 * возвращает форму) или сообщение в строке состояния.
 */
( () => {
	const config = window.trustFormsConfig || {};
	const messages = config.messages || {};
	const MIN_MS = 3200; // Сервер принимает форму не раньше чем через 3 с после выдачи токена.
	const SOURCE_COOKIE = 'trust_source';
	const SOURCE_DAYS = 90;
	const UTM = [ 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term' ];
	const state = new WeakMap();

	const sleep = ( ms ) => new Promise( ( resolve ) => setTimeout( resolve, ms ) );

	// --- Источник перехода: запоминается при первом визите ---

	function readSource() {
		const match = document.cookie.match( /(?:^|;\s*)trust_source=([^;]*)/ );
		if ( ! match ) {
			return null;
		}
		try {
			return JSON.parse( decodeURIComponent( match[ 1 ] ) );
		} catch ( e ) {
			return null;
		}
	}

	function rememberSource() {
		if ( readSource() ) {
			return;
		}
		const params = new URLSearchParams( location.search );
		const source = {};
		UTM.forEach( ( key ) => {
			const value = params.get( key );
			if ( value ) {
				source[ key ] = value.slice( 0, 100 );
			}
		} );
		try {
			if ( document.referrer && new URL( document.referrer ).host !== location.host ) {
				source.referrer = document.referrer.slice( 0, 300 );
			}
		} catch ( e ) {}
		source.landing = ( location.origin + location.pathname + location.search ).slice( 0, 300 );
		source.at = new Date().toISOString();

		const secure = 'https:' === location.protocol ? '; Secure' : '';
		document.cookie = `${ SOURCE_COOKIE }=${ encodeURIComponent( JSON.stringify( source ) ) }; max-age=${ SOURCE_DAYS * 86400 }; path=/; SameSite=Lax${ secure }`;
	}

	// --- Проверка полей ---

	function errorOf( input ) {
		const ids = ( input.getAttribute( 'aria-describedby' ) || '' ).split( ' ' );
		return ids[ 0 ] ? document.getElementById( ids[ 0 ] ) : null;
	}

	function setError( input, message ) {
		const error = errorOf( input );
		if ( message ) {
			input.setAttribute( 'aria-invalid', 'true' );
		} else {
			input.removeAttribute( 'aria-invalid' );
		}
		if ( error ) {
			error.textContent = message || '';
			error.hidden = ! message;
		}
	}

	const isPhone = ( value ) => {
		const digits = value.replace( /\D/g, '' ).length;
		return digits >= 10 && digits <= 15 && ! /[^\d\s()+\-.]/.test( value );
	};
	const isEmail = ( value ) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( value );
	const isUrl = ( value ) => {
		try {
			return new URL( /^https?:\/\//i.test( value ) ? value : `https://${ value }` ).hostname.includes( '.' );
		} catch ( e ) {
			return false;
		}
	};

	function isValid( input ) {
		if ( 'checkbox' === input.type ) {
			return ! input.required || input.checked;
		}
		const value = input.value.trim();
		if ( ! value ) {
			return ! input.required;
		}
		if ( ( input.minLength > 0 && value.length < input.minLength ) || ( input.maxLength > 0 && value.length > input.maxLength ) ) {
			return false;
		}
		switch ( input.dataset.type || input.type ) {
			case 'tel':
				return isPhone( value );
			case 'email':
				return isEmail( value );
			case 'contact':
				return isEmail( value ) || isPhone( value );
			case 'url':
				return isUrl( value );
		}
		return true;
	}

	function check( input ) {
		const ok = isValid( input );
		setError( input, ok ? '' : input.dataset.error );
		return ok;
	}

	function validate( form ) {
		const invalid = [ ...form.querySelectorAll( '[data-error]' ) ].filter( ( input ) => ! check( input ) );
		return invalid[ 0 ] || null;
	}

	function showStatus( form, message, ok = false ) {
		const status = form.querySelector( '.form__status' );
		if ( status ) {
			status.textContent = message || '';
			status.hidden = ! message;
			status.classList.toggle( 'form__status--ok', !! message && ok );
		}
	}

	// --- Капча: скрипт провайдера загружается только для форм с капчей ---

	let captchaApi = null;

	function captchaScript() {
		if ( ! captchaApi ) {
			const recaptcha = 'recaptcha' === config.captcha.provider;
			captchaApi = new Promise( ( resolve, reject ) => {
				window.trustCaptchaReady = () => resolve( recaptcha ? window.grecaptcha : window.smartCaptcha );
				const script = document.createElement( 'script' );
				script.src = recaptcha
					? 'https://www.google.com/recaptcha/api.js?onload=trustCaptchaReady&render=explicit&hl=ru'
					: 'https://smartcaptcha.yandexcloud.net/captcha.js?render=onload&onload=trustCaptchaReady';
				script.async = true;
				script.onerror = () => {
					captchaApi = null;
					reject( new Error( 'captcha' ) );
				};
				document.head.appendChild( script );
			} );
		}
		return captchaApi;
	}

	function loadCaptcha( form, s ) {
		const box = form.querySelector( '[data-trust-captcha]' );
		if ( ! box || ! config.captcha || s.captcha ) {
			return;
		}
		s.captcha = captchaScript()
			.then( ( api ) => {
				s.widget = api.render( box, {
					sitekey: config.captcha.siteKey,
					hl: 'ru',
					callback: () => setError( box, '' ),
				} );
				return api;
			} )
			.catch( () => {
				s.captcha = null;
				return null;
			} );
	}

	// --- Токен и nonce ---

	function hidden( form, name ) {
		return form.querySelector( `input[type="hidden"][name="${ name }"]` );
	}

	function prepare( form ) {
		let s = state.get( form );
		if ( ! s ) {
			// at — когда выдан токен; 0 — неизвестно (токен из разметки страницы).
			s = { ready: null, at: 0, captcha: null, widget: null };
			state.set( form, s );
		}
		if ( ! s.ready ) {
			s.ready = fetch( `${ config.endpoint }token`, { cache: 'no-store', credentials: 'same-origin' } )
				.then( ( response ) => ( response.ok ? response.json() : Promise.reject( new Error( 'token' ) ) ) )
				.then( ( data ) => {
					hidden( form, '_trust_token' ).value = data.token;
					hidden( form, '_trust_nonce' ).value = data.nonce;
					s.at = Date.now();
				} )
				.catch( () => {} ); // Нет связи — остаются токен и nonce из разметки.
		}
		loadCaptcha( form, s ); // Не загрузилась — следующая попытка при следующем касании.
		return s.ready;
	}

	// --- Отправка ---

	async function send( form, s, captcha ) {
		await prepare( form );
		const wait = s.at ? MIN_MS - ( Date.now() - s.at ) : 0;
		if ( wait > 0 ) {
			await sleep( wait );
		}

		const data = new FormData( form );
		data.set( 'page', location.href.split( '#' )[ 0 ] );
		const source = readSource();
		if ( source ) {
			data.set( 'source', JSON.stringify( source ) );
		}
		if ( null !== captcha ) {
			data.set( 'captcha', captcha );
		}

		let response = null;
		try {
			response = await fetch( config.endpoint + encodeURIComponent( form.dataset.trustForm ), {
				method: 'POST',
				body: data,
				credentials: 'same-origin',
			} );
			return await response.json();
		} catch ( e ) {
			return { ok: false, message: response ? messages.failed : messages.network };
		}
	}

	async function submit( form ) {
		showStatus( form, '' );
		await prepare( form );
		const s = state.get( form );

		// Капча: ответ виджета или ошибка без запроса к серверу.
		const box = form.querySelector( '[data-trust-captcha]' );
		const api = s.captcha ? await s.captcha : null;
		const captcha = api ? api.getResponse( s.widget ) || '' : null;
		if ( box && api && ! captcha ) {
			setError( box, messages.captcha );
			return false;
		}

		let body = await send( form, s, captcha );
		if ( ! body.ok && 'stale' === body.code ) {
			// Страница пролежала открытой дольше срока токена: берём новый и повторяем один раз.
			s.ready = null;
			body = await send( form, s, captcha );
		} else if ( ! body.ok && 'too_fast' === body.code ) {
			s.at = Date.now();
			body = await send( form, s, captcha );
		}
		if ( api ) {
			api.reset( s.widget ); // Ответ капчи одноразовый.
		}

		if ( body.ok ) {
			form.dispatchEvent( new CustomEvent( 'trust:form-sent', { detail: body } ) );
			return true;
		}

		const errors = body.errors || {};
		const rest = [];
		let first = null;
		Object.keys( errors ).forEach( ( name ) => {
			const input = 'captcha' === name ? box : form.elements[ name ];
			if ( input && input.nodeType ) {
				setError( input, errors[ name ] );
				first = first || input;
			} else {
				rest.push( errors[ name ] );
			}
		} );
		if ( first && 'function' === typeof first.focus && first !== box ) {
			first.focus();
		}
		if ( ! first || rest.length ) {
			showStatus( form, [ body.message || messages.failed, ...rest ].join( ' ' ) );
		}
		return false;
	}

	// --- Формы без своего скрипта ---

	function done( form ) {
		form.reset();
		const success = form.dataset.success ? document.getElementById( form.dataset.success ) : null;
		if ( ! success ) {
			showStatus( form, messages.sent, true );
			return;
		}
		form.hidden = true;
		success.hidden = false;
		const again = success.querySelector( '[data-form-again]' );
		if ( again ) {
			again.addEventListener(
				'click',
				() => {
					success.hidden = true;
					form.hidden = false;
					const first = form.querySelector( '[data-error]' );
					if ( first ) {
						first.focus();
					}
				},
				{ once: true }
			);
			again.focus();
		}
	}

	function bind( form ) {
		if ( form.hasAttribute( 'data-trust-form-custom' ) ) {
			return;
		}
		form.noValidate = true;
		const button = form.querySelector( '[type="submit"]' );

		const recheck = ( event ) => {
			if ( event.target.hasAttribute && event.target.hasAttribute( 'aria-invalid' ) && event.target.dataset.error ) {
				check( event.target );
			}
		};
		form.addEventListener( 'input', recheck );
		form.addEventListener( 'change', recheck );

		form.addEventListener( 'submit', async ( event ) => {
			event.preventDefault();
			const invalid = validate( form );
			if ( invalid ) {
				invalid.focus();
				return;
			}
			if ( button ) {
				button.disabled = true;
			}
			try {
				if ( await submit( form ) ) {
					done( form );
				}
			} finally {
				if ( button ) {
					button.disabled = false;
				}
			}
		} );
	}

	// Первое касание формы: свежий токен и капча.
	document.addEventListener( 'focusin', ( event ) => {
		const form = event.target.closest ? event.target.closest( 'form[data-trust-form]' ) : null;
		if ( form ) {
			prepare( form );
		}
	} );

	try {
		rememberSource();
	} catch ( e ) {}

	document.querySelectorAll( 'form[data-trust-form]' ).forEach( bind );

	window.trustForms = { validate, check, setError, submit };
} )();
