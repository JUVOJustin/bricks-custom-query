<?php

namespace juvo\Bricks_Custom_Queries;


use WP_Query;
use WP_Term_Query;
use WP_User_Query;

class Query
{
    private readonly string $name;

    private string $label;

    private array $controls = [];

    private $callback;

    private Query_Type $type;

    private array $config_flags = [
        'wpgb'             => true,
        'cache'            => false,
        'per_page_control' => true
    ];

    /**
     * @param string $name
     * @param string $label
     * @param callable $callback
     * @param Query_Type $type
     */
    public function __construct(string $name, string $label, callable $callback, Query_Type $type)
    {
        $this->name = $name;
        $this->label = $label;
        $this->callback = $callback;
        $this->type = $type;

        // On init iterate all config flags and set their default
        foreach ($this->config_flags as $key => $value) {
            if ($value === true && method_exists($this, $key)) {
                $this->$key(true);
            }
        }
    }

    /**
     * Callback that actually runs our query
     *
     * @param $results
     * @param $query_obj
     * @return array
     */
    public function bricks_query($results, $query_obj): array
    {

        // Only modify queries for benefits
        if ($query_obj->object_type !== $this->name) {
            return $results;
        }

        // Start profiling with query monitor
        do_action('qm/start', "bricks-$this->name-query");

        if ($this->type === Query_Type::Other) {
            // For non WordPress native types the callback must return the results
            $results = call_user_func_array($this->callback, [
                $results,
                $query_obj,
                &$this
            ]);
        } else {

            $args = [];

            // --- Config Flag: wpgb ---
            if ($this->config_flags['wpgb']) {
                $args['wpgb_bricks'] = 'bricks-element-' . $query_obj->element_id;
            }

            // --- Config Flag: per_page_control ---
            if ($this->config_flags['per_page_control'] instanceof Per_Page_Control) {
                $args = $this->config_flags['per_page_control']->add_per_page_arg($args, $query_obj, $this->type);
            }

            // For wordpress native types the callback must return the query args. This allows manipulation and caching
            $args = call_user_func_array($this->callback, [
                $args,
                $query_obj,
                &$this
            ]);

            switch ($this->type) {
                case Query_Type::Post:
                    $query = new WP_Query($args);
                    $results = $query->get_posts();
                    break;
                case Query_Type::User:
                    $query = new WP_User_Query($args);
                    $results = $query->get_results();
                    break;
                case Query_Type::Term:
                    $query = new WP_Term_Query($args);
                    $results = $query->get_terms();
                    break;
            }
        }

        // Stop profiling with query monitor
        do_action('qm/stop', "bricks-$this->name-query");

        if (empty($results)) {
            return [];
        }

        return $results;
    }

    /**
     * Make query type selectable in bricks
     *
     * @param $control_options
     * @return array
     */
    public function bricks_add_query_type($control_options): array
    {
        $control_options['queryTypes'][$this->name] = $this->label ?? "";
        return $control_options;
    }

    /**
     * Registers additional controls to elements.
     * Controls will be added just below the loop selector
     *
     * @param $controls
     * @return array
     */
    public function bricks_register_controls($controls): array
    {
        // --- Config Flag: per_page_control ---
        if ($this->config_flags['per_page_control'] instanceof Per_Page_Control) {
            $this->controls = $this->config_flags['per_page_control']->per_page_control() + $this->controls;
        }

        // Continue if no controls to add
        if (empty($this->controls)) {
            return $controls;
        }

        // Get key before separator to add loop controls
        $key = array_search("loopSeparator", array_keys($controls));

        // Add Limit at right position
        $controls_start = array_slice($controls, 0, $key, true);
        $controls_end = array_slice($controls, $key, count($controls) - $key, true);

        $controls_new = [];
        foreach ($this->controls as $key => $control) {

            // If field with same key already exists merge required field
            if (!empty($controls_new[$key])) {
                $controls_new[$key]['required'][1][2][] = $this->name;
                continue;
            }

            // Set basics
            $raw_control = [
                'tab'      => 'content',
                'required' => [
                    ['hasLoop', '!=', false],
                    ['query.objectType', '=', [$this->name]]
                ]
            ];
            $control = $raw_control + $control;
            $controls_new[$key] = $control;
        }

        return $controls_start + $controls_new + $controls_end;
    }

    public function bricks_query_loop_object($loop_object, $query_obj, $element_id)
    {

        if ($loop_object instanceof \WP_Post) {
            global $post;
            $post = get_post($loop_object);
            setup_postdata($post);
        }

        return $loop_object;
    }

    /**
     * Allows adding custom controls for the query
     *
     * @param array $controls
     * @return $this
     */
    public function set_controls(array $controls): static {
        $this->controls = array_merge($this->controls, $controls);
        return $this;
    }

    /**
     * Compatibility layer to add Gridbuilder query attribute as check in WP_Grid_Builder_Bricks\Includes\Providers::set_query_args
     *
     * @return $this
     */
    public function wpgb(bool $set = true): static
    {
        $this->config_flags['wpgb'] = $set;
        return $this;
    }

    /**
     * Adds a limit control to the query. Also adds the query args by default
     *
     * @param bool $set
     * @param string $label
     * @return Query
     */
    public function per_page_control(bool $set = true, string $label = 'Limit'): static
    {
        $this->config_flags['per_page_control'] = new Per_Page_Control($this->name, $label);
        return $this;
    }

}
