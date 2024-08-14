<?php

namespace juvo\Bricks_Custom_Queries;

class Query_Registry
{

	/**
	 * Query history through the lifetime of a request
	 *
	 * @var array
	 */
	private static array $history = [];
    private static ?Query_Registry $instance = null;
    private array $storage = [];

    /**
     * We store each query made with its unique id inside this array
     *
     * @var array
     */
    public static array $query_history = [];

    private function __construct() {
        add_action('pre_get_posts', function() {
            $this->registerQuery();
        }, 99);
    }

    public static function getInstance(): Query_Registry {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @param string $tag
     * @param string $label
     * @param callable $callback Callable type for PHP callbacks.
     * @param Query_Type $type
     * @return Query
     */
    public static function set(string $tag, string $label, callable $callback, Query_Type $type = Query_Type::Post): Query
    {
        $instance = self::getInstance();
        $instance->storage[$tag] = new Query($tag, $label, $callback, $type);
        return $instance->storage[$tag];
    }

    /**
     * @param string $tag
     * @return Query|null
     */
    public function get(string $tag) {
        return $this->storage[$tag] ?? null;
    }

    public function getAll():array {
        return $this->storage;
    }

    /**
     * Legacy function to register triggers that do not use the factory
     *
     * @return void
     * @Deprecated
     */
    public function registerQuery(): void
    {
        $queries = apply_filters('juvo/custom_queries/register', $this->storage);
        foreach ($queries as $query) {
            add_filter('bricks/query/run', [$query, 'bricks_query'], 10, 2);
            add_filter('bricks/query/loop_object', [$query, 'bricks_query_loop_object'], 10, 3);
            add_action('bricks/query/after_loop', [$query, 'bricks_query_after_loop'], 10, 2);
            add_filter('bricks/setup/control_options', [$query, 'bricks_add_query_type']);
            add_filter('bricks/query/result_max_num_pages', [$query, 'bricks_query_result_max_num_pages'], 10, 2);

            // Register Loops for some elements
            $elements = ['container', 'block', 'div'];
            foreach ($elements as $element) {
                add_filter("bricks/elements/{$element}/controls", [$query, 'bricks_register_controls']);
            }
        }

    }

	/**
	 * Generate a universal deterministic key for the query.
	 *
	 * @param string $element_id
	 *
	 * @return string
	 */
	private static function build_history_key(string $element_id): string {
		return $element_id;
	}

	/**
	 * Adds a query to the global history.
	 *
	 * @param \Bricks\Query $bricks_query
	 * @param Query $query
	 * @param array $results
	 *
	 * @return void
	 */
	public static function add_to_history(\Bricks\Query $bricks_query, Query $query, array $results = []): void {
		self::$history[self::build_history_key($bricks_query->element_id)] = [
			'results' => $results,
			'max_pages' => $query->getMaxPages(),
			'total' => $query->getTotal()
		];
	}

	/**
	 * Tries to receive a query from global history
	 *
	 * @param string $id
	 *
	 * @return false|array
	 */
	public static function get_from_history(string $element_id): false|array {

		$key = self::build_history_key($element_id);

		if (!in_array($key, array_keys(self::$history))) {
			return false;
		}

		return self::$history[$key];
	}

}
