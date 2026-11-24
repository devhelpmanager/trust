/**
 * Блок «Что говорят клиенты»: фильтр по услугам и «Показать ещё» без перезагрузки страницы.
 *
 * Карточки приходят готовой разметкой из REST (GET /wp-json/trust/v1/reviews?service=…&offset=…
 * &per=…; Trust\Reviews_List). Кнопка услуги заменяет список первыми отзывами этой услуги,
 * «Показать ещё» добавляет следующие; фокус переходит на первую новую карточку, итог
 * («Показано: N из M») объявляется скринридеру. Без JavaScript кнопок услуг нет, а «Показать
 * ещё» — обычная ссылка на страницу со всеми отзывами.
 */
( () => {
	document.querySelectorAll( '[data-reviews-list]' ).forEach( ( root ) => {
		const list = root.querySelector( '.b-rlist__list' );
		const more = root.querySelector( '[data-reviews-more]' );
		const moreText = more ? more.querySelector( '[data-reviews-more-text]' ) : null;
		const status = root.querySelector( '[data-reviews-status]' );
		const chips = [ ...root.querySelectorAll( '.filter-chip[data-service]' ) ];
		const per = Number( root.dataset.per ) || 6;
		let service = '';
		let total = Number( root.dataset.total ) || 0;
		let busy = false;

		if ( ! list ) {
			return;
		}

		const shown = () => list.children.length;

		const update = () => {
			const rest = total - shown();
			if ( more ) {
				more.hidden = rest <= 0;
				if ( moreText ) {
					moreText.textContent = `${ more.dataset.label } ${ rest }`;
				}
			}
			if ( status ) {
				status.textContent = `Показано отзывов: ${ shown() } из ${ total }`;
			}
		};

		const load = async ( reset ) => {
			if ( busy ) {
				return;
			}
			busy = true;
			root.setAttribute( 'aria-busy', 'true' );
			try {
				const url = new URL( root.dataset.endpoint, location.href );
				url.searchParams.set( 'service', service );
				url.searchParams.set( 'offset', reset ? 0 : shown() );
				url.searchParams.set( 'per', per );
				const response = await fetch( url, { credentials: 'same-origin' } );
				const data = response.ok ? await response.json() : null;
				if ( ! data ) {
					return;
				}
				const before = reset ? 0 : shown();
				if ( reset ) {
					list.innerHTML = '';
				}
				list.insertAdjacentHTML( 'beforeend', data.html );
				total = data.total;
				update();
				if ( ! reset ) {
					const first = list.children[ before ];
					if ( first ) {
						first.tabIndex = -1;
						first.focus( { preventScroll: false } );
					}
				}
			} catch ( e ) {
				// Нет связи — список остаётся прежним, кнопку можно нажать ещё раз.
			} finally {
				busy = false;
				root.removeAttribute( 'aria-busy' );
			}
		};

		chips.forEach( ( chip ) => {
			chip.addEventListener( 'click', () => {
				if ( chip.getAttribute( 'aria-pressed' ) === 'true' ) {
					return;
				}
				chips.forEach( ( c ) => c.setAttribute( 'aria-pressed', String( c === chip ) ) );
				service = chip.dataset.service;
				load( true );
			} );
		} );

		if ( more ) {
			more.addEventListener( 'click', ( event ) => {
				event.preventDefault();
				load( false );
			} );
		}
	} );
} )();
