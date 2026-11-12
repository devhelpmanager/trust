/**
 * Поле «Иконка» в блоках темы (ТЗ, п. 8.1): в выпадающем списке и в выбранном значении
 * рядом с подписью показывается сама иконка.
 *
 * Поле — select SCF с ui (Select2) и классом обёртки trust-icon-select (Trust\Icons::field()).
 * Разметка иконок — window.trustIcons (имя => SVG из спрайта темы).
 */
( () => {
	if ( ! window.acf || ! window.jQuery ) {
		return;
	}
	const $ = window.jQuery;
	const icons = window.trustIcons || {};

	const template = ( item ) => {
		if ( ! item.id || ! icons[ item.id ] ) {
			return item.text;
		}
		const span = document.createElement( 'span' );
		span.className = 'trust-icon-option';
		span.innerHTML = icons[ item.id ];
		span.append( document.createTextNode( item.text ) );
		return $( span );
	};

	window.acf.add_filter( 'select2_args', ( args, $select, settings, field ) => {
		const $wrap = field && field.$el ? field.$el : $( $select ).closest( '.acf-field' );
		if ( ! $wrap.hasClass( 'trust-icon-select' ) ) {
			return args;
		}
		args.templateResult = template;
		args.templateSelection = template;
		// Список шире поля, если поле узкое (колонка таблицы, боковая панель редактора).
		args.dropdownAutoWidth = true;
		return args;
	} );
} )();
