<?php

namespace juvo\Bricks_Custom_Queries;

class Query_Registry
{

    private static ?Query_Registry $instance = null;
    private array $storage = [];

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
            add_filter('bricks/setup/control_options', [$query, 'bricks_add_query_type']);

            // Register Loops for some elements
            $elements = ['container', 'block', 'div'];
            foreach ($elements as $element) {
                add_filter("bricks/elements/{$element}/controls", [$query, 'bricks_register_controls']);
            }
        }

    }

}
