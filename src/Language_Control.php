<?php
/**
 * @license GPL-3.0-or-later
 *
 * Modified by Justin Vogt on 27-August-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace juvo\Bricks_Custom_Queries;

class Language_Control
{

    private string $name;
    private string $label;

    public function __construct(string $name, string $label)
    {
        $this->name = $name;
        $this->label = $label;
    }

    /**
     * @param array $args
     * @param $query_obj
     * @param Query_Type $type
     * @return array
     */
    public function add_language_arg(array $args, $query_obj, Query_Type $type): array {

        if (empty($query_obj->settings['language_'.$this->name])) {
            return $args;
        }
        
        $args['language'] = $query_obj->settings['language_'.$this->name];
        
        return $args;
    }

    public function language_control()
    {
        return ['language_' . $this->name => [
            'label' => esc_html__( $this->label ),
            'type'  => 'text'
        ]];
    }
}