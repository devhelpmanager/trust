/**
 * Блок «Приезжайте в гости»: интерактивная карта — только по кнопке «Показать карту» (до этого
 * сторонний сервис не загружается). Код карты из настроек лежит в <template>; при нажатии он
 * вставляется вместо схемы, скрипты конструктора Яндекс Карт создаются заново (иначе не запустятся).
 */
( () => {
	document.querySelectorAll( '[data-office-map]' ).forEach( ( map ) => {
		const button = map.querySelector( '[data-office-map-show]' );
		const template = map.querySelector( 'template[data-office-map-embed]' );
		if ( ! button || ! template ) {
			return;
		}
		button.addEventListener( 'click', () => {
			const live = document.createElement( 'div' );
			live.className = 'b-omap__live';
			live.tabIndex = -1;
			live.append( template.content.cloneNode( true ) );
			live.querySelectorAll( 'script' ).forEach( ( old ) => {
				const script = document.createElement( 'script' );
				[ ...old.attributes ].forEach( ( attr ) => script.setAttribute( attr.name, attr.value ) );
				script.text = old.text;
				old.replaceWith( script );
			} );
			map.replaceChildren( live );
			map.removeAttribute( 'role' );
			map.removeAttribute( 'aria-label' );
			map.classList.add( 'is-live' );
			live.focus();
		} );
	} );
} )();
