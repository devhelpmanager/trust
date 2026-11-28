/**
 * Тег блога (tag.php): фильтр «Все / Статьи / Ответы на вопросы» меняет список без перезагрузки —
 * GET /wp-json/trust/v1/tags/{id}?type=posts&page=1, адрес страницы меняется (history), «Назад» в
 * браузере возвращает прежний фильтр. Без скрипта и со страниц 2+ фильтр — обычные ссылки.
 *
 * Данные — window.trustTag: { rest, page }.
 */
( function () {
	'use strict';

	const data = window.trustTag || {};
	const list = document.querySelector( '[data-tag-list]' );
	const links = Array.prototype.slice.call( document.querySelectorAll( '[data-tag-type]' ) );
	if ( ! list || ! links.length || ! data.rest || ! window.fetch || ! window.history || 1 !== Number( data.page ) ) {
		return;
	}

	const typeOf = ( url ) => {
		const type = new URL( url, window.location.href ).searchParams.get( 'type' );
		return 'posts' === type || 'answers' === type ? type : 'all';
	};
	let active = typeOf( window.location.href );
	let request = 0;

	const mark = ( type ) => {
		links.forEach( ( link ) => {
			if ( link.dataset.tagType === type ) {
				link.setAttribute( 'aria-current', 'true' );
			} else {
				link.removeAttribute( 'aria-current' );
			}
		} );
	};

	const load = ( type, push, fallback ) => {
		const id = ++request;
		list.setAttribute( 'aria-busy', 'true' );
		mark( type );
		const url = data.rest + ( data.rest.indexOf( '?' ) === -1 ? '?' : '&' ) + 'type=' + encodeURIComponent( type ) + '&page=1';
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
				active = type;
				if ( push ) {
					const next = new URL( json.url, window.location.href );
					next.hash = '';
					window.history.pushState( { trustTagType: type }, '', next.pathname + next.search );
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
			const type = link.dataset.tagType;
			if ( type === active ) {
				return;
			}
			load( type, true, link.href );
		} );
	} );

	window.addEventListener( 'popstate', () => {
		const type = typeOf( window.location.href );
		if ( type !== active ) {
			load( type, false, window.location.href );
		}
	} );
}() );
