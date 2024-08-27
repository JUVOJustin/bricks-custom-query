<?php
/**
 * @license GPL-3.0-or-later
 *
 * Modified by Justin Vogt on 27-August-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace juvo\Bricks_Custom_Queries;

class Multisite_Control
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
    public function add_multisite_arg(array $args, $query_obj, Query_Type $type): array {

        if (empty($query_obj->settings['blog_id_'.$this->name])) {
            return $args;
        }

        switch ($type) {
            case Query_Type::Post:
                $args['blog_id'] = $query_obj->settings['blog_id_'.$this->name];
                break;
            default:
                break;
        }

        return $args;
    }

    public function multisite_control()
    {
        // get all blog ids
        $blogs = get_sites();
        $blog_options = [];
        foreach ( $blogs as $blog ) {
            $blog_details = get_blog_details( array( 'blog_id' => $blog->blog_id ) );
            $blog_options[ $blog->blog_id ] = $blog_details->blogname;
        }

        return ['blog_id_' . $this->name => [
            'label' => esc_html__( $this->label ),
            'type'  => 'select',
            'options' => $blog_options
        ]];
    }
}