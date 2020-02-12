<?php
/**
 * SK_Webshop_Category_Menu
 * ========================
 *
 * Handles the custom product category menu.
 *
 * @since   20200130
 * @package SK_Webshop
 */

class SK_Webshop_Category_Menu {

	/**
	 * @var boolean
	 */
	private $should_output = false;

	/**
	 * Hooks.
	 */
	public function __construct() {
		add_filter( 'nav_menu_css_class', [ $this, 'set_init_vars' ], 10, 4 );
		add_action( 'storefront_header', [ $this, 'maybe_output_category_menu' ], 100 );
		add_action( 'wp_enqueue_scripts', [ $this, 'category_menu_assets' ] );
	}

	/**
	 * Enqueue styles and scripts
	 * @return void
	 */
	public function category_menu_assets() {
		wp_enqueue_style( 'category-menu', plugin_dir_url(__FILE__) . '../assets/category-menu.css' );
		wp_enqueue_script( 'vue', plugin_dir_url(__FILE__) . '../assets/vendor/vue.min.js' );
		wp_enqueue_script( 'sk-megamenu', plugin_dir_url(__FILE__) . '../assets/sk-megamenu.js', ['jquery', 'vue'] );
	}

	/**
	 * Sets the initial variables, such as $should_output
	 * and css classes in a nav menu item if this is
	 * the correct item.
	 * @param  array    $classes
	 * @param  WP_Post  $item
	 * @param  stdClass $args
	 * @param  integer  $depths
	 * @return array
	 */
	public function set_init_vars( $classes, $item, $args, $depths ) {
		if ( 'custom' === $item->type && 'Sortiment' === $item->title ) {
			$this->should_output = true;
			$classes[] = 'js-category-menu';
		}
		return $classes;
	}


	/**
	 * Outputs the category menu if applicable.
	 * @return null
	 */
	public function maybe_output_category_menu() {
		if ( $this->should_output ) {
			$this->output_category_menu( true );
		}
		return;
	}

	/**
	 * Outputs the category menu markup.
	 * @param  boolean $echo
	 * @return string
	 */
	public function output_category_menu( $echo = true ) {
		$cats = $this->get_category_hierarchy();

		$template = __DIR__ . '/views/category-menu.twig';
		$args     = [
			'categories' => $cats,
		];
		if ( $echo ) {
			\Timber::render( $template, $args );
		} else {
			return Timber::compile( $template, $args );
		}
	}

	/**
	 * Returns the direct hierarchy for a
	 * given parent_id.
	 * @param  integer $parent
	 * @return array
	 */
	private function get_category_hierarchy( $parent = 0 ) {
		$terms = get_terms( [
			'taxonomy'   => 'product_cat',
			'parent'     => $parent,
			'hide_empty' => false,
			'fields'     => 'ids',
		] );		
		$children = [];
		
		foreach ( $terms as $term_id ) {
			$term = new SK_Term( $term_id );
			$term->children = $this->get_category_hierarchy( $term_id );
			$children[ $term_id ] = $term;
		}
		
		return $children;
	}

}
new SK_Webshop_Category_Menu;
