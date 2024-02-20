<?php

namespace juvo\Bricks_Custom_Queries;

class Per_Page_Control
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
    public function add_per_page_arg(array $args, $query_obj, Query_Type $type): array {
        if (empty($query_obj->settings['limit_'.$this->name])) {
            return $args;
        }

        switch ($type) {
            case Query_Type::Post:
                $args['posts_per_page'] = $query_obj->settings['limit_'.$this->name];
                break;
            case Query_Type::User:
            case Query_Type::Term:
                $args['number'] = $query_obj->settings['limit_'.$this->name];
                break;
            default:
                break;
        }

        return $args;
    }

    public function per_page_control()
    {
        return ['limit_'.$this->name => [
            'label' => esc_html__($this->label),
            'type' => 'number',
        ]];
    }


}