<?php
namespace juvo\Bricks_Custom_Queries;

/**
 * Class Language_Control
 *
 * This class is used to manage language-related controls and arguments for custom queries.
 * It provides methods to add language arguments to query arrays and to generate control configurations.
 */
class Language_Control
{
    /**
     * The name of the language control.
     *
     * @var string
     */
    private string $name;

    /**
     * The label for the language control.
     *
     * @var string
     */
    private string $label;

    /**
     * Language_Control constructor.
     *
     * Initializes the language control with a name and label.
     *
     * @param string $name  The name of the language control.
     * @param string $label The label for the language control.
     */
    public function __construct(string $name, string $label)
    {
        $this->name = $name;
        $this->label = $label;
    }

    /**
     * Gets the setted language if present
     *
     * @param object $query_obj    The query object that may contain settings related to language.
     * @return bool|string         Either false if there is no language set, or the language string
     */
    public function get_language( object $query_obj ): bool|string
    {
        if (empty($query_obj->settings['language_' . $this->name])) {
            return false;
        }

        return $query_obj->settings['language_' . $this->name];
    }

    /**
     * Generates the control configuration for the language control.
     *
     * This method returns an array configuration for a language control,
     * including a label and a type (in this case, 'text').
     *
     * @return array The control configuration array.
     */
    public function language_control(): array
    {
        return [
            'language_' . $this->name => [
                'label' => esc_html__($this->label),
                'type'  => 'text',
            ]
        ];
    }
}
