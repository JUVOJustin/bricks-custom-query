<?php
/**
 * @license GPL-3.0-or-later
 *
 * Modified by Justin Vogt on 27-August-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace juvo\Bricks_Custom_Queries;


use WP_Query;
use WP_Term_Query;
use WP_User_Query;

class Query {
	private readonly string $name;

	private string $label;

	private array $controls = [];

	private $callback;

	private Query_Type $type;

	private array $config_flags = [
		'wpgb'                 => true,
		'per_page_control'     => true,
		'fix_empty_post_ins'   => true,
		'loop_object_callback' => false,
		'multisite_control'    => false
	];

	/**
	 * @param string $name
	 * @param string $label
	 * @param callable $callback
	 * @param Query_Type $type
	 */
	public function __construct( string $name, string $label, callable $callback, Query_Type $type ) {
		$this->name     = $name;
		$this->label    = $label;
		$this->callback = $callback;
		$this->type     = $type;

		// On init iterate all config flags and set their default
		foreach ( $this->config_flags as $key => $value ) {
			if ( $value === true && method_exists( $this, $key ) ) {
				$this->$key( true );
			}
		}
	}

	/**
	 * Callback that actually runs our query
	 *
	 * @param array $results
	 * @param \Bricks\Query $query_obj
	 *
	 * @return array
	 */
	public function bricks_query( array $results, \Bricks\Query $query_obj ): array {

		// Only modify queries for benefits
		if ( $query_obj->object_type !== $this->name ) {
			return $results;
		}

		// Start profiling with query monitor
		do_action( 'qm/start', "bricks-$this->name-query" );

		if ( $this->type === Query_Type::Other ) {
			// For non WordPress native types the callback must return the results
			$results = call_user_func_array( $this->callback, [
				$results,
				$query_obj,
				&$this
			] );
		} else {
			$results = $this->process_wordpress_queries( $results, $query_obj );
		}

		// Stop profiling with query monitor
		do_action( 'qm/stop', "bricks-$this->name-query" );

		return $results;
	}

	/**
	 * Process wordpress native queries
	 *
	 * @param $results
	 * @param $query_obj
	 *
	 * @return array
	 */
	private function process_wordpress_queries(array $results, \Bricks\Query $query_obj): array {
		$prepared_args = [];

		// --- Config Flag: wpgb ---
		if ( $this->config_flags['wpgb'] ) {
			$prepared_args['wpgb_bricks'] = 'bricks-element-' . $query_obj->element_id;
		}

		// --- Config Flag: per_page_control ---
		if ( $this->config_flags['per_page_control'] instanceof Per_Page_Control ) {
			$prepared_args = $this->config_flags['per_page_control']->add_per_page_arg( $prepared_args, $query_obj, $this->type );
		}

		// For wordpress native types the callback must return the query args. This allows manipulation and caching
		$args = call_user_func_array( $this->callback, [
			$prepared_args,
			$query_obj,
			&$this
		] );

		// If the callback had an error do not proceed
		if ( $args === false || is_wp_error( $args ) ) {
			return $results;
		}

		// --- Config Flag: multisite_control ---
		if ( $this->config_flags['multisite_control'] instanceof Multisite_Control ) {
			$blog_id = $this->config_flags['multisite_control']->get_blog_id( $query_obj );
		}

		// switch multisite if needed
		if ( ! empty( $blog_id ) ) {
			switch_to_blog( $blog_id );
		}

		switch ( $this->type ) {
			case Query_Type::Post:

				// --- Config Flag: fix_empty_post_ins ---
				if ( $this->config_flags['fix_empty_post_ins'] ) {
					foreach (
						[
							'post__in',
							'post_parent__in',
							'category__in',
							'tag__in',
							'author__in',
							'tag_slug__in'
						] as $in
					) {
						if ( isset( $args[ $in ] ) && empty( $args[ $in ] ) ) {
							$args[ $in ] = [ 0 ];
						}
					}
				}

				$query   = new WP_Query( $args );
				$results = $query->get_posts();
				break;
			case Query_Type::User:
				$query   = new WP_User_Query( $args );
				$results = $query->get_results();
				break;
			case Query_Type::Term:
				$query   = new WP_Term_Query( $args );
				$results = $query->get_terms();
				break;
		}
		
		// restore site if needed
		if ( ! empty( $blog_id ) ) {
			add_action( 'posts_results', function( $posts ) {
				restore_current_blog();
				return $posts;
			}, 100 );
		}

		return $results;
	}

	/**
	 * Make query type selectable in bricks
	 *
	 * @param $control_options
	 *
	 * @return array
	 * @see https://academy.bricksbuilder.io/article/filter-bricks-setup-control_options/
	 */
	public function bricks_add_query_type( $control_options ): array {
		$control_options['queryTypes'][ $this->name ] = $this->label ?? "";

		return $control_options;
	}

	/**
	 * Registers additional controls to elements.
	 * Controls will be added just below the loop selector
	 *
	 * @param $controls
	 *
	 * @return array
	 * @see https://academy.bricksbuilder.io/article/filter-bricks-elements-element_name-controls/
	 */
	public function bricks_register_controls( $controls ): array {
		// --- Config Flag: per_page_control ---
		if ( $this->config_flags['per_page_control'] instanceof Per_Page_Control ) {
			$this->controls = $this->config_flags['per_page_control']->per_page_control() + $this->controls;
		}

		// --- Config Flag: multisite_control ---
		if ( $this->config_flags['multisite_control'] instanceof Multisite_Control ) {
			$this->controls = $this->config_flags['multisite_control']->multisite_control() + $this->controls;
		}

		// Continue if no controls to add
		if ( empty( $this->controls ) ) {
			return $controls;
		}

		// Get key before separator to add loop controls
		$key = array_search( "loopSeparator", array_keys( $controls ) );

		// Add Limit at right position
		$controls_start = array_slice( $controls, 0, $key, true );
		$controls_end   = array_slice( $controls, $key, count( $controls ) - $key, true );

		$controls_new = [];
		foreach ( $this->controls as $key => $control ) {

			// If field with same key already exists merge required field
			if ( ! empty( $controls_new[ $key ] ) ) {
				$controls_new[ $key ]['required'][1][2][] = $this->name;
				continue;
			}

			// Set basics
			$raw_control          = [
				'tab'      => 'content',
				'required' => [
					[ 'hasLoop', '!=', false ],
					[ 'query.objectType', '=', [ $this->name ] ]
				]
			];
			$control              = $raw_control + $control;
			$controls_new[ $key ] = $control;
		}

		return $controls_start + $controls_new + $controls_end;
	}

	/**
	 * When iterating the results of the query this function is used to manipulate the objects.
	 * For type "other" this function creates a global variable that can be accessed in the loop.
	 *
	 * @param $loop_object
	 * @param $loop_key
	 * @param $query_obj
	 *
	 * @return mixed
	 * @see https://academy.bricksbuilder.io/article/filter-bricks-query-loop_object/
	 */
	public function bricks_query_loop_object( $loop_object, $loop_key, $query_obj ) {

		if ( $query_obj->object_type !== $this->name ) {
			return $loop_object;
		}

		if ( $this->config_flags['loop_object_callback'] !== false ) {
			// Users can provide their own loop object callback. If so skip automatic parsing
			$loop_object = call_user_func_array( $this->config_flags['loop_object_callback'], [
				$loop_object,
				$loop_key,
				$query_obj,
				&$this
			] );
		} else {

			// If no callback is provided we try to parse the loop object automatically
			switch ( $this->type ) {
				case Query_Type::Post:
					global $post;
					$post = get_post( $loop_object );
					setup_postdata( $post );
					break;
				case Query_Type::Other:
					$GLOBALS[ $this->name . '_obj' ] = $loop_object;
					break;
			}
		}

		return $loop_object;
	}

	/**
	 * Callback used to remove super global after loop
	 *
	 * @param $query
	 * @param $args
	 *
	 * @return void
	 * @see https://academy.bricksbuilder.io/article/action-bricks-query-after_loop/
	 */
	public function bricks_query_after_loop( $query, $args ): void {

		if ( $query->object_type !== $this->name ) {
			return;
		}

		// If is type other and the super global exists, remove it after loop
		if ( $this->type === Query_Type::Other && isset( $GLOBALS[ $this->name . '_obj' ] ) ) {
			unset( $GLOBALS[ $this->name . '_obj' ] );
		}
	}

	/**
	 * Allows adding custom controls for the query
	 *
	 * @param array $controls
	 *
	 * @return $this
	 */
	public function set_controls( array $controls ): static {
		$this->controls = array_merge( $this->controls, $controls );

		return $this;
	}

	/**
	 * Compatibility layer to add Gridbuilder query attribute as check in WP_Grid_Builder_Bricks\Includes\Providers::set_query_args
	 *
	 * @return $this
	 */
	public function wpgb( bool $set = true ): static {
		$this->config_flags['wpgb'] = $set;

		return $this;
	}

	/**
	 * Adds a limit control to the query. Also adds the query args by default
	 *
	 * @param bool $set
	 * @param string $label
	 *
	 * @return Query
	 */
	public function per_page_control( bool $set = true, string $label = 'Limit' ): static {

		if ( $set === false ) {
			$this->config_flags['per_page_control'] = false;

			return $this;
		}
		$this->config_flags['per_page_control'] = new Per_Page_Control( $this->name, $label );

		return $this;
	}

	/**
	 * Adds a limit control to the query. Also adds the query args by default
	 *
	 * @param bool $set
	 * @param string $label
	 *
	 * @return Query
	 */
	public function multisite_control( bool $set = true, string $label = 'Site' ): static {
		if ( $set === false || ! is_multisite() ) {
			$this->config_flags['multisite_control'] = false;
			return $this;
		}
		
		return $this->config_flags['multisite_control'] = new Multisite_Control( $this->name, $label );
	}

	/**
	 * Sets 'post__in', 'post_parent__in', 'category__in', 'tag__in', 'author__in', 'tag_slug__in' to [0] if empty, to avoid returning all posts
	 *
	 * @see https://core.trac.wordpress.org/ticket/28099
	 *
	 * @return $this
	 */
	public function fix_empty_post_ins( bool $set = true ): static {
		$this->config_flags['fix_empty_post__in'] = $set;

		return $this;
	}

	/**
	 * Allows to set a custom loop object callback. It is used to setup the actual loop object e.g. making the data globally available
	 *
	 * @param callable $callback
	 *
	 * @return $this
	 */
	public function loop_object_callback( callable $callback ): static {
		$this->config_flags['loop_object_callback'] = $callback;

		return $this;
	}

}
