/**
 * Группа «SEO»: счётчики символов с подсветкой и предпросмотр сниппета (ТЗ, раздел 10.1).
 *
 * Без сборки. Данные — window.trustSeo (Trust\Seo_Admin::assets()): ключи полей, пределы,
 * шаблон title с %%TITLE%% вместо заголовка, адрес, описание по умолчанию.
 * Заголовок записи берётся из редактора блоков, классического экрана или формы термина.
 */
( function ( $, config ) {
	if ( ! $ || ! config ) {
		return;
	}

	const field = ( name ) => $( `.acf-field[data-key="${ config.keys[ name ] }"]` );
	const input = ( name ) => field( name ).find( 'input[type="text"], input[type="url"], textarea' ).first();

	/** Заголовок записи или термина, как он сейчас введён. */
	function currentTitle() {
		const data = window.wp && window.wp.data;
		const editor = data && data.select( 'core/editor' );
		if ( editor && editor.getCurrentPostId && editor.getCurrentPostId() ) {
			return editor.getEditedPostAttribute( 'title' ) || '';
		}
		const source = $( '#title, #name, #tag-name' ).first();
		return source.length ? source.val() : '';
	}

	/** Адрес записи: из редактора блоков, если он есть, иначе из данных страницы. */
	function currentUrl() {
		const data = window.wp && window.wp.data;
		const editor = data && data.select( 'core/editor' );
		const link = editor && editor.getPermalink && editor.getPermalink();
		return link || config.url || config.home;
	}

	/** Адрес в виде «сайт › раздел › страница». */
	function breadcrumbUrl( url ) {
		try {
			const parsed = new URL( url );
			const parts = parsed.pathname.split( '/' ).filter( Boolean ).map( decodeURIComponent );
			return [ parsed.host ].concat( parts ).join( ' › ' );
		} catch ( e ) {
			return url;
		}
	}

	/** Счётчик символов под полем: красный после предела. */
	function counter( name, limit ) {
		const el = input( name );
		if ( ! el.length ) {
			return;
		}
		const out = $( '<p class="trust-seo-count" aria-live="polite"></p>' ).insertAfter( el );
		const update = () => {
			const length = el.val().length;
			out.text( `${ length } / ${ limit }` ).toggleClass( 'is-over', length > limit );
			out.attr( 'title', length > limit ? `Длиннее ${ limit } символов — в поиске текст обрежется` : '' );
		};
		el.on( 'input change', update );
		update();
	}

	/** Предпросмотр: как запись выглядит в выдаче. */
	function preview() {
		const box = field( 'preview' ).find( '.trust-seo-preview' );
		if ( ! box.length ) {
			return;
		}
		box.html( '<div class="trust-seo-preview__url"></div><div class="trust-seo-preview__title"></div><div class="trust-seo-preview__desc"></div>' );

		const update = () => {
			const own = input( 'title' ).val().trim();
			const title = own
				? own.replaceAll( '{site}', config.site ).replaceAll( '{sep}', config.sep )
				: config.template.replace( '%%TITLE%%', currentTitle().trim() || 'Заголовок' );
			const desc = input( 'description' ).val().trim() || config.description;

			box.find( '.trust-seo-preview__url' ).text( breadcrumbUrl( currentUrl() ) );
			box.find( '.trust-seo-preview__title' ).text( title ).toggleClass( 'is-over', title.length > config.limits.title );
			box.find( '.trust-seo-preview__desc' )
				.text( desc || 'Описание не задано: поисковик возьмёт текст со страницы сам.' )
				.toggleClass( 'is-empty', ! desc );
		};

		$( document ).on( 'input change', '#title, #name, #tag-name', update );
		input( 'title' ).on( 'input', update );
		input( 'description' ).on( 'input', update );
		if ( window.wp && window.wp.data ) {
			window.wp.data.subscribe( () => {
				const title = currentTitle();
				if ( title !== preview.lastTitle ) {
					preview.lastTitle = title;
					update();
				}
			} );
		}
		update();
	}

	$( () => {
		counter( 'title', config.limits.title );
		counter( 'description', config.limits.description );
		preview();
	} );
}( window.jQuery, window.trustSeo ) );
