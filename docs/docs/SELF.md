# Getting info about the current user

```
$me = $MadelineProto->get_self();

\danog\MadelineProto\Logger::log("Hi ".$me['first_name']."!");
```

[`get_self`](https://docs.madelineproto.xyz/get_self.html) returns a [User object](API_docs/types/User.md) that contains info about the currently logged in user/bot.
