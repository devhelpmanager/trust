/**
 * Фильтр списка без перезагрузки страницы: блок «Команда по направлениям» (дальше — «Карьера»).
 *
 * Разметка: обёртка [data-trust-filter]; кнопки [data-filter-value] с aria-pressed (пустое
 * значение — «Все»); элементы списка [data-filter-tags="seo dev"]; строка [data-filter-status]
 * (aria-live) с шаблонами data-all («…: %total%») и data-some («Показано: %shown% из %total%»).
 * Без JavaScript кнопок не видно (filter.css), список показан целиком.
 */
( () => {
	document.querySelectorAll( '[data-trust-filter]' ).forEach( ( root ) => {
		const buttons = [ ...root.querySelectorAll( '[data-filter-value]' ) ];
		const items = [ ...root.querySelectorAll( '[data-filter-tags]' ) ];
		const status = root.querySelector( '[data-filter-status]' );

		const apply = ( value ) => {
			let shown = 0;
			items.forEach( ( item ) => {
				const on = '' === value || item.dataset.filterTags.split( ' ' ).includes( value );
				item.hidden = ! on;
				shown += on ? 1 : 0;
			} );
			buttons.forEach( ( button ) => button.setAttribute( 'aria-pressed', String( button.dataset.filterValue === value ) ) );
			if ( status ) {
				status.textContent = ( '' === value ? status.dataset.all : status.dataset.some )
					.replace( '%shown%', shown )
					.replace( '%total%', items.length );
			}
		};

		buttons.forEach( ( button ) => button.addEventListener( 'click', () => apply( button.dataset.filterValue ) ) );
	} );
} )();
