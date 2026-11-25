/**
 * Блок «Настройки cookie»: переключатели показывают сохранённый выбор (window.trustConsent из
 * assets/js/cookie.js; выбора нет — выключены). Переключение меняет только вид — сохраняют
 * кнопки: «Сохранить выбор» — как отмечено, «Принять все» — всё, «Только необходимые» — ничего
 * лишнего. Строка состояния (role="status") говорит, что сейчас отмечено и что выбор сохранён.
 */
( () => {
	document.querySelectorAll( '[data-cookie-settings]' ).forEach( ( box ) => {
		const switches = [ ...box.querySelectorAll( '[data-cookie-switch]' ) ];
		const status = box.querySelector( '[data-cookie-status]' );
		const consent = () => window.trustConsent;

		const state = () => Object.fromEntries( switches.map( ( sw ) => [ sw.dataset.cookieSwitch, 'true' === sw.getAttribute( 'aria-checked' ) ] ) );

		const list = ( values ) => switches.map( ( sw ) => {
			const name = ( document.getElementById( sw.getAttribute( 'aria-labelledby' ) ) || sw ).textContent.trim().toLowerCase();
			return `${ name } — ${ values[ sw.dataset.cookieSwitch ] ? box.dataset.on : box.dataset.off }`;
		} ).join( ', ' );

		const show = ( saved ) => {
			status.textContent = ( saved ? box.dataset.saved : box.dataset.current ).replace( '{list}', list( state() ) );
			status.classList.toggle( 'is-saved', saved );
		};

		const setSwitches = ( values ) => {
			switches.forEach( ( sw ) => sw.setAttribute( 'aria-checked', values[ sw.dataset.cookieSwitch ] ? 'true' : 'false' ) );
		};

		const sync = () => {
			const api = consent();
			const saved = api ? api.get() : null;
			setSwitches( saved || {} );
		};

		switches.forEach( ( sw ) => {
			sw.addEventListener( 'click', () => {
				sw.setAttribute( 'aria-checked', 'true' === sw.getAttribute( 'aria-checked' ) ? 'false' : 'true' );
				show( false );
			} );
		} );

		box.querySelectorAll( '[data-cookie-save]' ).forEach( ( button ) => {
			button.addEventListener( 'click', () => {
				const choice = button.dataset.cookieSave;
				if ( 'all' === choice || 'necessary' === choice ) {
					const all = 'all' === choice;
					setSwitches( Object.fromEntries( switches.map( ( sw ) => [ sw.dataset.cookieSwitch, all ] ) ) );
				}
				const api = consent();
				if ( api ) {
					api.set( state(), choice );
				}
				show( true );
			} );
		} );

		// Выбор сделан на плашке — переключатели показывают его.
		window.addEventListener( 'trust:consent', () => {
			sync();
		} );

		sync();
		show( false );
	} );
} )();
