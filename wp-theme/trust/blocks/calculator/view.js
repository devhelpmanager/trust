/**
 * Блок «Калькулятор стоимости» (ТЗ, п. 8.3.1).
 *
 * Шаги: вкладки (нажатие, клавиши ← → Home End — по образцу WAI-ARIA «Вкладки»), «Назад»,
 * «Далее», шкала «Шаг N из 7 · название». Шаг GEO: список AI-систем виден при «Адаптируем».
 * «Рассчитать стоимость»: цена по формуле ТЗ —
 * (база + надбавки выбранных вариантов + AI-систем + запросы × цена запроса) × наибольший
 * коэффициент выбранных регионов (свой регион — коэффициент «другого региона»; ничего не
 * выбрано — 1), округление вверх до шага, не меньше минимальной цены. Экран результата —
 * у всех вкладок галочки; «Изменить параметры» или вкладка возвращают к шагам.
 * Если в блоке есть форма контактов — перед показом результата скрытые поля формы получают
 * ответы и цену (уходят в заявку).
 * Каждый расчёт сохраняется на сайте («Заявки → Просчёт стоимости работ»): ответы и цена уходят
 * в REST trust/v1/estimates с токеном и nonce форм (свежие берутся при первом касании калькулятора:
 * страница могла пролежать в кэше). Повтор того же расчёта не сохраняется второй раз.
 */
( () => {
	const number = ( value ) => {
		const n = parseFloat( value );
		return Number.isFinite( n ) ? n : 0;
	};
	const money = new Intl.NumberFormat( 'ru-RU', { maximumFractionDigits: 0 } );

	function init( card ) {
		const tabs = Array.from( card.querySelectorAll( '[data-calc-tab]' ) );
		const panels = Array.from( card.querySelectorAll( '[data-calc-panel]' ) );
		const body = card.querySelector( '[data-calc-body]' );
		const nav = card.querySelector( '[data-calc-nav]' );
		const prev = card.querySelector( '[data-calc-prev]' );
		const next = card.querySelector( '[data-calc-next]' );
		const go = card.querySelector( '[data-calc-go]' );
		const label = card.querySelector( '[data-calc-label]' );
		const bar = card.querySelector( '[data-calc-bar]' );
		const result = card.querySelector( '[data-calc-result]' );
		const priceOut = card.querySelector( '[data-calc-price]' );
		const edit = card.querySelector( '[data-calc-edit]' );
		const ai = card.querySelector( '[data-calc-ai]' );
		const total = tabs.length;
		if ( ! total || total !== panels.length ) {
			return;
		}
		const names = tabs.map( ( tab ) => tab.querySelector( '.b-calc__tab-name' ).textContent.trim() );
		let step = 0;

		const tablist = card.querySelector( '[role="tablist"]' );

		/**
		 * Показывает шаг.
		 *
		 * @param {number}  index    Номер шага с 0.
		 * @param {boolean} focusTab Фокус на вкладку (клавиатура).
		 * @param {boolean} toTop    Вернуть к началу карточки, если оно ушло за экран («Назад», «Далее»).
		 */
		function show( index, focusTab = false, toTop = false ) {
			step = Math.max( 0, Math.min( total - 1, index ) );
			tabs.forEach( ( tab, i ) => {
				const on = i === step;
				tab.classList.toggle( 'is-active', on );
				tab.classList.toggle( 'is-done', i < step );
				tab.setAttribute( 'aria-selected', String( on ) );
				tab.tabIndex = on ? 0 : -1;
			} );
			panels.forEach( ( panel, i ) => ( panel.hidden = i !== step ) );
			body.hidden = false;
			nav.hidden = false;
			result.hidden = true;
			prev.disabled = 0 === step;
			next.hidden = step === total - 1;
			go.hidden = step !== total - 1;
			label.textContent = ( card.dataset.progress || '' )
				.replace( '{n}', String( step + 1 ) )
				.replace( '{total}', String( total ) )
				.replace( '{name}', names[ step ] );
			bar.style.width = `${ Math.round( ( ( step + 1 ) / total ) * 100 ) }%`;
			// На узком экране вкладки прокручиваются вбок: текущая — в середину полосы.
			const tab = tabs[ step ];
			tablist.scrollLeft = tab.offsetLeft - ( tablist.clientWidth - tab.offsetWidth ) / 2;
			if ( focusTab ) {
				tab.focus();
			}
			if ( toTop && card.getBoundingClientRect().top < 0 ) {
				card.scrollIntoView( { block: 'start' } );
			}
		}

		const checked = ( selector ) => Array.from( card.querySelectorAll( `${ selector }:checked` ) );
		const value = ( name ) => {
			const field = card.querySelector( `[name="${ name }"]` );
			return field ? field.value.trim() : '';
		};
		const titleOf = ( name ) => {
			const input = card.querySelector( `input[name="${ name }"]:checked` );
			return input ? input.dataset.title || input.value : '';
		};

		/** Цена по формуле ТЗ. */
		function price() {
			const geoOn = card.querySelector( 'input[name="step_geo"][value="on"]:checked' );
			const queries = value( 'step_queries' ).split( /\r?\n/ ).filter( ( line ) => '' !== line.trim() ).length;
			let sum = number( card.dataset.base ) + queries * number( card.dataset.queryPrice );
			checked( '.b-calc__radio' ).forEach( ( input ) => ( sum += number( input.dataset.price ) ) );
			if ( geoOn ) {
				checked( 'input[name="step_ai[]"]' ).forEach( ( input ) => ( sum += number( input.dataset.price ) ) );
			}
			const coefs = checked( 'input[name="step_region[]"]' ).map( ( input ) => number( input.dataset.coef ) || 1 );
			if ( '' !== value( 'step_region_other' ) ) {
				coefs.push( number( card.dataset.otherCoef ) || 1 );
			}
			const coef = coefs.length ? Math.max( ...coefs ) : 1;
			const round = Math.max( 1, number( card.dataset.round ) );
			const amount = Math.ceil( ( sum * coef ) / round ) * round;
			return Math.max( amount, number( card.dataset.min ) );
		}

		/** Ответы для заявки (скрытые поля формы контактов). */
		function answers( text ) {
			const regions = checked( 'input[name="step_region[]"]' ).map( ( input ) => input.value );
			if ( '' !== value( 'step_region_other' ) ) {
				regions.push( value( 'step_region_other' ) );
			}
			const geo = card.querySelector( 'input[name="step_geo"]:checked' );
			let geoText = geo ? geo.dataset.title : '';
			if ( geo && 'on' === geo.value ) {
				const systems = checked( 'input[name="step_ai[]"]' ).map( ( input ) => input.value );
				geoText += systems.length ? `: ${ systems.join( ', ' ) }` : '';
			}
			return {
				calc_price: text,
				calc_region: regions.join( ', ' ),
				calc_queries: value( 'step_queries' ),
				calc_content: titleOf( 'step_content' ),
				calc_external: titleOf( 'step_external' ),
				calc_reputation: titleOf( 'step_reputation' ),
				calc_geo: geoText,
				calc_url: value( 'step_url' ),
				calc_topic: value( 'step_topic' ),
				calc_pages: value( 'step_pages' ),
				calc_goal: value( 'step_goal' ),
				calc_age: titleOf( 'step_age' ),
				calc_traffic: titleOf( 'step_traffic' ),
			};
		}

		// --- Сохранение просчёта ---
		let access = null;
		let saved = '';
		/**
		 * Свежие токен и nonce (один раз; без связи — сохранение просто не случится). Сервер
		 * принимает токен не раньше чем через 3 секунды — промис выполняется не раньше.
		 */
		function fetchAccess() {
			if ( ! access && card.dataset.token ) {
				const ready = new Promise( ( resolve ) => window.setTimeout( resolve, 3100 ) );
				access = fetch( card.dataset.token, { cache: 'no-store', credentials: 'same-origin' } )
					.then( ( response ) => ( response.ok ? response.json() : Promise.reject( new Error( 'token' ) ) ) )
					.then( ( pass ) => ready.then( () => pass ) )
					.catch( () => ( access = null ) );
			}
			return access || Promise.resolve( null );
		}

		/**
		 * Отправляет просчёт. too_fast — подождать и ещё раз; stale — новые токен и nonce и ещё раз.
		 *
		 * @param {Object} data  Ответы calc_*.
		 * @param {number} tries Осталось попыток.
		 */
		function save( data, tries = 2 ) {
			const key = JSON.stringify( data );
			if ( ! card.dataset.estimate || key === saved ) {
				return;
			}
			fetchAccess().then( ( pass ) => {
				if ( ! pass || ! pass.token ) {
					return;
				}
				const body = new FormData();
				Object.entries( data ).forEach( ( [ name, text ] ) => body.append( name, text ) );
				body.append( '_trust_token', pass.token );
				body.append( '_trust_nonce', pass.nonce );
				body.append( 'page', window.location.href );
				fetch( card.dataset.estimate, { method: 'POST', body, credentials: 'same-origin' } )
					.then( ( response ) => response.json().catch( () => ( {} ) ) )
					.then( ( reply ) => {
						if ( reply.ok ) {
							saved = key;
						} else if ( tries > 0 && 'too_fast' === reply.code ) {
							window.setTimeout( () => save( data, tries - 1 ), 3000 );
						} else if ( tries > 0 && 'stale' === reply.code ) {
							access = null;
							save( data, tries - 1 );
						}
					} )
					.catch( () => {} );
			} );
		}

		function calculate() {
			const template = card.dataset.template || '{price}';
			const sum = money.format( price() );
			const main = template.split( ' / ' )[ 0 ];
			priceOut.textContent = main.replace( '{price}', sum );
			const data = answers( template.replace( '{price}', sum ) );
			Object.entries( data ).forEach( ( [ key, text ] ) => {
				const field = card.querySelector( `[data-calc-answer="${ key }"]` );
				if ( field ) {
					field.value = text;
				}
			} );
			save( data );
			tabs.forEach( ( tab, i ) => {
				tab.classList.remove( 'is-active' );
				tab.classList.add( 'is-done' );
				tab.setAttribute( 'aria-selected', 'false' );
				tab.tabIndex = 0 === i ? 0 : -1;
			} );
			body.hidden = true;
			nav.hidden = true;
			result.hidden = false;
			result.focus();
		}

		// Токен и nonce — при первом касании калькулятора (к расчёту пройдёт больше 3 секунд).
		[ 'pointerdown', 'focusin', 'keydown' ].forEach( ( type ) => card.addEventListener( type, fetchAccess, { once: true } ) );
		tabs.forEach( ( tab, i ) => tab.addEventListener( 'click', () => show( i ) ) );
		tablist.addEventListener( 'keydown', ( event ) => {
			const current = tabs.indexOf( document.activeElement );
			if ( -1 === current ) {
				return;
			}
			const to = { ArrowRight: current + 1, ArrowLeft: current - 1, Home: 0, End: total - 1 }[ event.key ];
			if ( undefined !== to ) {
				event.preventDefault();
				show( ( to + total ) % total, true );
			}
		} );
		prev.addEventListener( 'click', () => show( step - 1, false, true ) );
		next.addEventListener( 'click', () => show( step + 1, false, true ) );
		go.addEventListener( 'click', calculate );
		edit.addEventListener( 'click', () => show( total - 1, true ) );
		if ( ai ) {
			card.querySelectorAll( 'input[name="step_geo"]' ).forEach( ( input ) =>
				input.addEventListener( 'change', () => ( ai.hidden = ! card.querySelector( 'input[name="step_geo"][value="on"]:checked' ) ) )
			);
		}
		// Enter в однострочном поле шага — «Далее» (полей формы здесь нет, отправлять нечего).
		body.addEventListener( 'keydown', ( event ) => {
			if ( 'Enter' === event.key && 'INPUT' === event.target.tagName && ! [ 'checkbox', 'radio' ].includes( event.target.type ) ) {
				event.preventDefault();
				( step === total - 1 ? go : next ).click();
			}
		} );

		show( 0 );
	}

	document.querySelectorAll( '[data-calc]' ).forEach( init );
} )();
