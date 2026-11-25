/**
 * Блок «Кейсы»: фильтры по услуге и отрасли без перезагрузки, «Показать ещё кейсы».
 *
 * Список всех кейсов с услугами и отраслями (data-index) — для чисел на кнопках услуг (по
 * выбранной отрасли) и строки «Найдено: N». Карточки — из REST (GET trust/v1/cases): при смене
 * фильтров список заменяется, по «Показать ещё» — дополняется; фокус — на первую новую карточку.
 * Пока фильтры не выбраны, главный кейс стоит отдельно (в сетке его нет — параметр skip).
 * Выбор пишется в адрес (?service=…&industry=…), чтобы ссылкой можно было поделиться.
 */
( () => {
	document.querySelectorAll( '[data-cases]' ).forEach( ( root ) => {
		const index = JSON.parse( root.dataset.index || '[]' );
		const per = Number( root.dataset.per ) || 9;
		const featured = Number( root.dataset.featured ) || 0;
		const words = JSON.parse( root.dataset.words || '["кейс","кейса","кейсов"]' );
		const chips = [ ...root.querySelectorAll( '[data-service]' ) ];
		const select = root.querySelector( '[data-industry-select]' );
		const list = root.querySelector( '[data-cases-list]' );
		const lead = root.querySelector( '[data-cases-lead]' );
		const found = root.querySelector( '[data-cases-found]' );
		const empty = root.querySelector( '[data-cases-empty]' );
		const moreWrap = root.querySelector( '[data-cases-more-wrap]' );
		const more = root.querySelector( '[data-cases-more]' );
		const shownLine = root.querySelector( '[data-cases-shown]' );
		const resetTop = root.querySelector( '.b-cases__reset' );
		const state = { service: root.dataset.service || '', industry: root.dataset.industry || '', total: 0, loaded: list.children.length };
		let controller = null;

		const word = ( n ) => {
			const m10 = n % 10;
			const m100 = n % 100;
			if ( 1 === m10 && 11 !== m100 ) {
				return `${ n } ${ words[ 0 ] }`;
			}
			return `${ n } ${ m10 >= 2 && m10 <= 4 && ( m100 < 12 || m100 > 14 ) ? words[ 1 ] : words[ 2 ] }`;
		};
		const matches = ( item, service, industry ) => ( ! industry || item.n.includes( industry ) ) && ( ! service || item.s.includes( service ) );
		const filtered = () => '' !== state.service || '' !== state.industry;
		const withLead = () => featured && ! filtered();

		/** Числа на кнопках, строка «Найдено», сброс, главный кейс, пустой результат. */
		const render = () => {
			chips.forEach( ( chip ) => {
				chip.setAttribute( 'aria-pressed', String( chip.dataset.service === state.service ) );
				const count = chip.querySelector( '[data-count]' );
				if ( count ) {
					count.textContent = index.filter( ( item ) => matches( item, chip.dataset.service, state.industry ) ).length;
				}
			} );
			if ( select ) {
				select.value = state.industry;
			}
			const total = index.filter( ( item ) => matches( item, state.service, state.industry ) ).length;
			state.total = total;
			found.textContent = ( filtered() ? root.dataset.someLabel : root.dataset.allLabel ).replace( '%n%', word( filtered() ? total : index.length ) );
			resetTop.hidden = ! filtered();
			if ( lead ) {
				lead.hidden = ! withLead();
			}
			empty.hidden = 0 !== total;
			list.hidden = 0 === list.children.length;
			const shown = state.loaded + ( withLead() ? 1 : 0 );
			moreWrap.hidden = shown >= total;
			shownLine.textContent = root.dataset.shownLabel.replace( '%shown%', shown ).replace( '%total%', total );
		};

		/** Карточки из REST: replace — новый выбор фильтров, иначе — следующая страница. */
		const load = async ( replace ) => {
			if ( controller ) {
				controller.abort();
			}
			controller = new AbortController();
			const url = new URL( root.dataset.endpoint );
			url.searchParams.set( 'service', state.service );
			url.searchParams.set( 'industry', state.industry );
			url.searchParams.set( 'offset', replace ? 0 : state.loaded );
			url.searchParams.set( 'per', replace && withLead() ? per - 1 : per );
			url.searchParams.set( 'skip', withLead() ? featured : 0 );
			list.setAttribute( 'aria-busy', 'true' );
			try {
				const response = await fetch( url, { signal: controller.signal, headers: { Accept: 'application/json' } } );
				const data = await response.json();
				const first = list.children.length;
				if ( replace ) {
					list.innerHTML = data.html;
					state.loaded = data.count;
				} else {
					list.insertAdjacentHTML( 'beforeend', data.html );
					state.loaded += data.count;
					const card = list.children[ first ] && list.children[ first ].querySelector( 'a' );
					if ( card ) {
						card.focus( { preventScroll: false } );
					}
				}
				render();
			} catch ( error ) {
				if ( 'AbortError' !== error.name && more ) {
					// Без ответа сервера — обычная ссылка на страницу со всеми кейсами.
					window.location.href = more.href;
				}
			} finally {
				list.removeAttribute( 'aria-busy' );
			}
		};

		/** Выбор фильтров: адрес страницы, числа, карточки. */
		const choose = ( service, industry ) => {
			state.service = service;
			state.industry = industry;
			const address = new URL( window.location.href );
			[ [ 'service', service ], [ 'industry', industry ] ].forEach( ( [ key, value ] ) => {
				if ( value ) {
					address.searchParams.set( key, value );
				} else {
					address.searchParams.delete( key );
				}
			} );
			address.searchParams.delete( 'cases' );
			window.history.replaceState( null, '', address );
			render();
			load( true );
		};

		chips.forEach( ( chip ) => chip.addEventListener( 'click', () => choose( chip.dataset.service, state.industry ) ) );
		if ( select ) {
			select.addEventListener( 'change', () => choose( state.service, select.value ) );
		}
		root.querySelectorAll( '[data-cases-reset]' ).forEach( ( button ) => button.addEventListener( 'click', () => {
			choose( '', '' );
			// Фокус — на «Все»: кнопка сброса исчезает.
			if ( chips[ 0 ] ) {
				chips[ 0 ].focus();
			}
		} ) );
		if ( more ) {
			more.addEventListener( 'click', ( event ) => {
				event.preventDefault();
				load( false );
			} );
		}
		render();
	} );
} )();
