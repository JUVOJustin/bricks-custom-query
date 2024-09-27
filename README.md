# God damn easy Bricks Builder Custom Queries

You are a developer and want to add custom queries to the bricks builder? This package is for you. From now on you
can add your own logic and functionality to loops. Ever wanted to iterate your custom database, external APIs or WordPress Data Structures in a highly complex loop? Here you go.

Feature overview:

- Simple registration of Custom Queries
- Automatic Performance Profiling for the [Query Monitor Plugin](https://de.wordpress.org/plugins/query-monitor/)
- Add custom controls to your query
- WP Gridbuilder Support (Only for native WordPress Data Types)
- [Multisite Queries](https://github.com/JUVOJustin/bricks-custom-query/wiki/Query-Configs#multisite-control]) (Only for native WordPress Data Types)

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
    function(array $args, \Bricks\Query $query_obj, juvo\Bricks_Custom_Queries\Query $query) { // Callback for query args
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

Choose `Query_Type::Other` if you are not working with native wordpress data types. A more in detail guide for "Other" queries can be found in [this guide](https://github.com/JUVOJustin/bricks-custom-query/wiki/Queries-of-type-%22Other%22)

### Query Callback

For native wordpress data types the callback must return valid query arguments. For custom data types you need to return
the actual data. For the later the return value will not be processed further.

### Query Configuration
You can configure a couple of query configurations using setter function. Full documentation for these can be found here:
https://github.com/JUVOJustin/bricks-custom-query/wiki/Query-Configs


### Additional Controls
You can add additional controls to your query. The full list of controls can be found here: https://academy.bricksbuilder.io/topic/controls/
You don´t need to set the tab.

```php
Query_Registry::set(
    'collection_prompts', // Query name
    'Collection Prompts', // Query label
    function(array $args, \Bricks\Query $query_obj, juvo\Bricks_Custom_Queries\Query $query) { // Callback for query args
        
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
