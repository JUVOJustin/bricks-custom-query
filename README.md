# God damn easy Bricks Builder Custom Queries

You are a developer and want to add custom queries to the bricks builder? This package is for you. From now on you
can add your own logic and functionality to loops. Ever wanted to iterate your custom database, external APIs or WordPress Data Structures in a highly complex loop? Here you go.

Feature overview:

- Simple registration of Custom Queries
- Automatic Performance Profiling for the [Query Monitor Plugin](https://de.wordpress.org/plugins/query-monitor/)
- Add custom controls to you query
- WP Gridbuilder Support (Only for native WordPress Data Types)

## Installation

To install the package you can use composer. Run the following command in your terminal:

```bash
composer require juvo/bricks-custom-query
```

You should initiate the registry as early as possible. The best place to do this is in your plugin's main file or the
functions.php of your theme.

```php
add_action('init', function() {
    juvo\Bricks_Custom_Queries\Query_Registry::getInstance();
});
```

## Usage

To register a simple query you can use the following code snippet. The first parameter is the query name, the second
parameter is the query label, the third parameter is the callback that returns a callback.

```php
Query_Registry::set(
    'collection_prompts', // Query name
    'Collection Prompts', // Query label
    function(array $args, $query_obj, Query $query) { // Callback for query args
        return array_merge($args, [
                'post_type' => 'posts',
            ]
        );
    }
);
```

### Query Types

There is an optional fourth parameter that allows you to set the type of the query. If you query something other than a
post please change the type accordingly. Supported types are set up as a PHP Enum. The default type
is `Query_Type::Post`.

```php
enum Query_Type
{
    case Post;
    case User;
    case Term;
    case Other;
}
```

Choose `Query_Type::Other` if you are not working with native wordpress data types.

### Query Callback

For native wordpress data types the callback must return valid query arguments. For custom data types you need to return
the actual data. For the later the return value will not be processed further.

To register another tag to the same group you simply do:

### WP Gridbuilder Support
By default each query is setup to support WP Gridbuilder. In your callback you will see the requried arguments. If you don´t want wpgb support, simply do:
```php
Query_Registry::set(
    'collection_prompts', // Query name
    'Collection Prompts', // Query label
    function(array $args, $query_obj, Query $query) { // Callback for query args
        return array_merge($args, [
                'post_type' => 'posts',
            ]
        );
    }
)->wpgb(false);
```

### Per Page Control
By default each query is setup to have a control field for the number of items to be displayed. In your callback you will see the requried arguments. To disable simply do:
```php
Query_Registry::set(
    'collection_prompts', // Query name
    'Collection Prompts', // Query label
    function(array $args, $query_obj, Query $query) { // Callback for query args
        return array_merge($args, [
                'post_type' => 'posts',
            ]
        );
    }
)->per_page_control(false);
```
To keep the control but change the label pass a second parameter: `->per_page_control(true, 'Per page');`

### Additional Controls
You can add additional controls to your query. The full list of controls can be found here: https://academy.bricksbuilder.io/topic/controls/
You don´t need to set the tab.

```php
Query_Registry::set(
    'collection_prompts', // Query name
    'Collection Prompts', // Query label
    function(array $args, $query_obj, Query $query) { // Callback for query args
        
        // Check setting and apply your logic
        if (!empty($query_obj->settings['return_all'])) {
            $args['posts_per_page'] = -1;
        }
        
        return array_merge($args, [
                'post_type' => 'posts',
            ]
        );
    }
)->set_controls([
    'return_all' => [
        'label' => esc_html('Return all'),
        'type'  => 'checkbox',
    ]
]);
```
