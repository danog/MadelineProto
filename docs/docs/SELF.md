# Getting info about the current user

```php
$me = $MadelineProto->get_self();

\danog\MadelineProto\Logger::log("Hi ".$me['first_name']."!");
```

[`get_self`](https://docs.madelineproto.xyz/get_self.html) returns a [User object](../API_docs/types/User.html) that contains info about the currently logged in user/bot, or false if the current instance is not logged in.

<amp-form method="GET" target="_top" action="https://docs.madelineproto.xyz/docs/SETTINGS.html"><input type="submit" value="Previous section" /></amp-form><amp-form action="https://docs.madelineproto.xyz/docs/EXCEPTIONS.html" method="GET" target="_top"><input type="submit" value="Next section" /></amp-form>