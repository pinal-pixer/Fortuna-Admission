<?php
/**
 * Free vs Pro screen.
 *
 * @package GutSliderBlocks
 *
 * @var array  $blocks  Decorated block definitions.
 * @var string $current Current page slug.
 * @var bool   $has_pro Whether the Pro plugin is active.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$gs_free_total = 0;
$gs_pro_total  = 0;

foreach ( $blocks as $gs_block ) {
	if ( ! empty( $gs_block['is_pro'] ) ) {
		++$gs_pro_total;
	} else {
		++$gs_free_total;
	}
}

require GUTSLIDER_DIR . '/admin/views/header.php';
?>

<div class="gs-shell gs-stack">
	<?php require GUTSLIDER_DIR . '/admin/views/compare.php'; ?>
</div>

<?php
require GUTSLIDER_DIR . '/admin/views/footer.php';
