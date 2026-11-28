/**
 * Рубрика блога (category.php):
 * - полоса рубрик на телефоне листается строкой — текущая рубрика прокручивается в видимую область;
 * - «Новые / Популярные» меняют список без перезагрузки: GET /wp-json/trust/v1/rubrics/{id}
 *   (?sort=popular&page=1), адрес страницы меняется (history), «Назад» в браузере возвращает
 *   прежнюю сортировку. Без скрипта и со страниц 2+ переключатель — обычные ссылки.
 *
 * Данные — window.trustRubric: { rest, page }.
 */
( function () {
	'use strict';

	const data = window.trustRubric || {};

	// Полоса рубрик: текущая — в видимой области (по центру, если помещается).
	const strip = document.querySelector( '[data-rubric-strip]' );
	const current = strip && strip.querySelector( '[aria-current="page"]' );
	if ( current && strip.scrollWidth > strip.clientWidth ) {
		strip.scrollLeft = Math.max( 0, current.offsetLeft - strip.offsetLeft - ( strip.clientWidth - current.offsetWidth ) / 2 );
	}

	const list = document.querySelector( '[data-rubric-list]' );
	const links = Array.prototype.slice.call( document.querySelectorAll( '[data-rubric-sort]' ) );
	if ( ! list || ! links.length || ! data.rest || ! window.fetch || ! window.history || 1 !== Number( data.page ) ) {
		return;
	}

	const sortOf = ( url ) => ( 'popular' === new URL( url, window.location.href ).searchParams.get( 'sort' ) ? 'popular' : 'new' );
	let active = sortOf( window.location.href );
	let request = 0;

	const mark = ( sort ) => {
		links.forEach( ( link ) => {
			if ( link.dataset.rubricSort === sort ) {
				link.setAttribute( 'aria-current', 'true' );
			} else {
				link.removeAttribute( 'aria-current' );
			}
		} );
	};

	const load = ( sort, push, fallback ) => {
		const id = ++request;
		list.setAttribute( 'aria-busy', 'true' );
		mark( sort );
		const url = data.rest + ( data.rest.indexOf( '?' ) === -1 ? '?' : '&' ) + 'sort=' + encodeURIComponent( sort ) + '&page=1';
		return fetch( url, { credentials: 'same-origin', headers: { Accept: 'application/json' } } )
			.then( ( response ) => {
				if ( ! response.ok ) {
					throw new Error( String( response.status ) );
				}
				return response.json();
			} )
			.then( ( json ) => {
				if ( id !== request ) {
					return;
				}
				list.innerHTML = json.html;
				list.removeAttribute( 'aria-busy' );
				active = sort;
				if ( push ) {
					const next = new URL( json.url, window.location.href );
					next.hash = '';
					window.history.pushState( { trustSort: sort }, '', next.pathname + next.search );
				}
			} )
			.catch( () => {
				// Не получилось — обычный переход по ссылке.
				window.location.assign( fallback );
			} );
	};

	links.forEach( ( link ) => {
		link.addEventListener( 'click', ( event ) => {
			if ( event.defaultPrevented || 0 !== event.button || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey ) {
				return;
			}
			event.preventDefault();
			const sort = link.dataset.rubricSort;
			if ( sort === active ) {
				return;
			}
			load( sort, true, link.href );
		} );
	} );

	window.addEventListener( 'popstate', () => {
		const sort = sortOf( window.location.href );
		if ( sort !== active ) {
			load( sort, false, window.location.href );
		}
	} );
}() );
