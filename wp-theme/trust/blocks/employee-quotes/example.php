<?php
/**
 * Превью блока «Сотрудники о работе у нас»: цитаты первых сотрудников из раздела «Сотрудники».
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

$trust_members = get_posts(
	array(
		'post_type'     => 'team',
		'post_status'   => 'publish',
		'numberposts'   => 3,
		'fields'        => 'ids',
		'no_found_rows' => true,
		'orderby'       => 'menu_order',
		'order'         => 'ASC',
	)
);

return array(
	'eyebrow' => 'Изнутри',
	'title'   => 'Сотрудники о работе у нас',
	'quotes'  => array_map(
		static fn( $id ): array => array(
			'member' => $id,
			'text'   => 'Цитата сотрудника: как пришёл в команду, чему научился, что нравится в работе — 2–3 предложения.',
		),
		$trust_members
	),
);
