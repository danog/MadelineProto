# Web templates for `$MadelineProto->start()`

You get the web template used for the `$MadelineProto->start()` web UI thusly:

```php
$template = $MadelineProto->get_web_template();
```

By default, it is equal to:
```html
<!DOCTYPE html>
<html>
        <head>
        <title>MadelineProto</title>
        </head>
        <body>
        <h1>MadelineProto</h1>
        <form method="POST">
        %s
        <button type="submit"/>Go</button>
        </form>
        <p>%s</p>
        </body>
</html>
```

To modify the web template, use:
```php
$MadelineProto->set_web_template($new_template);
```

The new template must have a structure similar the the default template.

<amp-form method="GET" target="_top" action="https://docs.madelineproto.xyz/#very-complex-and-complete-examples"><input type="submit" value="Next section" /></amp-form>