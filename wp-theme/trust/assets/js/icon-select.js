/**
 * Поле «Иконка» в блоках темы (ТЗ, п. 8.1): в выпадающем списке и в выбранном значении
 * рядом с подписью показывается сама иконка.
 *
 * Поле — select SCF с ui (Select2) и классом обёртки trust-icon-select (Trust\Icons::field()).
 * Разметка иконок — window.trustIcons (имя => SVG из спрайта темы). Поле «Иконка справа» первого
 * экрана (класс trust-art-select, Trust\Hero_Art::field()) — рисунки из window.trustArts.
 */
( () => {
	if ( ! window.acf || ! window.jQuery ) {
		return;
	}
	const $ = window.jQuery;
	const template = ( icons, className ) => ( item ) => {
		if ( ! item.id || ! icons[ item.id ] ) {
			return item.text;
		}
		const span = document.createElement( 'span' );
		span.className = className;
		span.innerHTML = icons[ item.id ];
		span.append( document.createTextNode( item.text ) );
		return $( span );
	};
	const iconTemplate = template( window.trustIcons || {}, 'trust-icon-option' );
	const artTemplate = template( window.trustArts || {}, 'trust-icon-option trust-art-option' );

	window.acf.add_filter( 'select2_args', ( args, $select, settings, field ) => {
		const $wrap = field && field.$el ? field.$el : $( $select ).closest( '.acf-field' );
		if ( ! $wrap.hasClass( 'trust-icon-select' ) ) {
			return args;
		}
		const art = $wrap.hasClass( 'trust-art-select' );
		args.templateResult = art ? artTemplate : iconTemplate;
		args.templateSelection = art ? artTemplate : iconTemplate;
		// Список шире поля, если поле узкое (колонка таблицы, боковая панель редактора).
		args.dropdownAutoWidth = true;
		return args;
	} );
} )();
