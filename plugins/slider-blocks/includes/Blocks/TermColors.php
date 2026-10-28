<?php
declare( strict_types=1 );

namespace GutSlider\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A "Badge colour" for categories, tags and other public taxonomies.
 *
 * Used by the Blog Post block's category badges (terms without one get a colour
 * from a fixed palette). Stored as term meta `gutslider_color`.
 *
 * @package GutSlider\Blocks
 * @since   3.1.1
 */
final class TermColors {

	const META_KEY = 'gutslider_color';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Register the meta and the form fields on every public taxonomy with an admin screen.
	 */
	public function register() {
		register_meta(
			'term',
			self::META_KEY,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_hex_color',
				'auth_callback'     => static function () {
					return current_user_can( 'manage_categories' );
				},
			)
		);

		foreach ( get_taxonomies( array( 'public' => true, 'show_ui' => true ) ) as $taxonomy ) {
			if ( 'post_format' === $taxonomy ) {
				continue;
			}
			add_action( "{$taxonomy}_add_form_fields", array( $this, 'add_field' ) );
			add_action( "{$taxonomy}_edit_form_fields", array( $this, 'edit_field' ) );
			add_action( "created_{$taxonomy}", array( $this, 'save' ) );
			add_action( "edited_{$taxonomy}", array( $this, 'save' ) );
		}
	}

	/**
	 * Colour picker on the term screens.
	 *
	 * @param string $hook Admin page.
	 */
	public function enqueue( $hook ) {
		if ( 'edit-tags.php' !== $hook && 'term.php' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_add_inline_script( 'wp-color-picker', 'jQuery(function($){$(".gutslider-term-color").wpColorPicker();});' );
	}

	/**
	 * Field on "Add new".
	 */
	public function add_field() {
		?>
		<div class="form-field">
			<label for="gutslider-term-color"><?php esc_html_e( 'Badge colour', 'slider-blocks' ); ?></label>
			<?php wp_nonce_field( 'gutslider_term_color', 'gutslider_term_color_nonce' ); ?>
			<input type="text" id="gutslider-term-color" name="gutslider_term_color" class="gutslider-term-color" value="" />
			<p><?php esc_html_e( 'Used by GutSlider category badges. Leave empty for an automatic colour.', 'slider-blocks' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Field on "Edit".
	 *
	 * @param \WP_Term $term Term.
	 */
	public function edit_field( $term ) {
		$color = (string) get_term_meta( $term->term_id, self::META_KEY, true );
		?>
		<tr class="form-field">
			<th scope="row"><label for="gutslider-term-color"><?php esc_html_e( 'Badge colour', 'slider-blocks' ); ?></label></th>
			<td>
				<?php wp_nonce_field( 'gutslider_term_color', 'gutslider_term_color_nonce' ); ?>
				<input type="text" id="gutslider-term-color" name="gutslider_term_color" class="gutslider-term-color" value="<?php echo esc_attr( $color ); ?>" />
				<p class="description"><?php esc_html_e( 'Used by GutSlider category badges. Leave empty for an automatic colour.', 'slider-blocks' ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Save the colour.
	 *
	 * @param int $term_id Term id.
	 */
	public function save( $term_id ) {
		if ( ! isset( $_POST['gutslider_term_color_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['gutslider_term_color_nonce'] ) ), 'gutslider_term_color' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_categories' ) ) {
			return;
		}
		$color = isset( $_POST['gutslider_term_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['gutslider_term_color'] ) ) : '';
		if ( $color ) {
			update_term_meta( $term_id, self::META_KEY, $color );
		} else {
			delete_term_meta( $term_id, self::META_KEY );
		}
		PostSlider::flush_cache();
	}
}
