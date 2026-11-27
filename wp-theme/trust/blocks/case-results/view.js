/**
 * Блок «Результаты» кейса: вкладки графиков (шаблон WAI-ARIA «Tabs», выбор сразу при переходе
 * стрелками) и таблица «до / после» — если она не помещается и листается вбок, её можно
 * прокрутить с клавиатуры (tabindex).
 */
( () => {
	document.querySelectorAll( '.b-case-results [data-case-tabs]' ).forEach( ( list ) => {
		const tabs = [ ...list.querySelectorAll( '[role="tab"]' ) ];
		const select = ( tab, focus ) => {
			tabs.forEach( ( item ) => {
				const on = item === tab;
				item.setAttribute( 'aria-selected', String( on ) );
				item.tabIndex = on ? 0 : -1;
				const panel = document.getElementById( item.getAttribute( 'aria-controls' ) );
				if ( panel ) {
					panel.classList.toggle( 'is-active', on );
				}
			} );
			if ( focus ) {
				tab.focus();
			}
		};
		tabs.forEach( ( tab, i ) => {
			tab.addEventListener( 'click', () => select( tab, false ) );
			tab.addEventListener( 'keydown', ( event ) => {
				const next = { ArrowRight: i + 1, ArrowLeft: i - 1, Home: 0, End: tabs.length - 1 }[ event.key ];
				if ( undefined === next ) {
					return;
				}
				event.preventDefault();
				select( tabs[ ( next + tabs.length ) % tabs.length ], true );
			} );
		} );
	} );

	const tables = [ ...document.querySelectorAll( '.b-case-results [data-case-table]' ) ];
	const scrollable = () => tables.forEach( ( wrap ) => {
		if ( wrap.scrollWidth > wrap.clientWidth + 1 ) {
			wrap.tabIndex = 0;
		} else {
			wrap.removeAttribute( 'tabindex' );
		}
	} );
	scrollable();
	window.addEventListener( 'resize', scrollable );
} )();
