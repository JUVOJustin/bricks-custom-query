<?php
namespace juvo\Bricks_Custom_Queries;

/**
 * Class Multisite_Control
 *
 * This class is used to manage multisite-related controls and settings for custom queries.
 * It provides methods to retrieve the blog ID from query settings and to generate control configurations for multisite selection.
 */
class Multisite_Control
{
    /**
     * The name of the multisite control.
     *
     * @var string
     */
    private string $name;

    /**
     * The label for the multisite control.
     *
     * @var string
     */
    private string $label;

    /**
     * Multisite_Control constructor.
     *
     * Initializes the multisite control with a name and label.
     *
     * @param string $name  The name of the multisite control.
     * @param string $label The label for the multisite control.
     */
    public function __construct(string $name, string $label)
    {
        $this->name = $name;
        $this->label = $label;
    }

    /**
     * Retrieves the blog ID from the query object's settings.
     *
     * Returns the blog ID if it's set in the query object settings; otherwise, returns false.
     *
     * @param object $query_obj The query object that may contain settings related to multisite blog ID.
     * @return bool|int         The blog ID if found, or false if not.
     */
    public function get_blog_id(object $query_obj): bool|int
    {
        if (empty($query_obj->settings['blog_id_' . $this->name])) {
            return false;
        }

        return $query_obj->settings['blog_id_' . $this->name];
    }

    /**
     * Generates the control configuration for the multisite blog selection.
     *
     * This method returns an array configuration for a select dropdown of available blogs in a multisite setup.
     * The options are populated with the blog IDs and their respective names.
     *
     * @return array The control configuration array.
     */
    public function multisite_control(): array
    {
        // Get all blog IDs and names from the multisite setup
        $blogs = get_sites();
        $blog_options = [];
        foreach ($blogs as $blog) {
            $blog_details = get_blog_details(['blog_id' => $blog->blog_id]);
            $blog_options[$blog->blog_id] = $blog_details->blogname;
        }

        return [
            'blog_id_' . $this->name => [
                'label'   => esc_html__($this->label),
                'type'    => 'select',
                'options' => $blog_options,
            ]
        ];
    }
}
