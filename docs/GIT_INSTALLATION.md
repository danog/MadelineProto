### git

Run the following commands in a console:

```
mkdir MadelineProtoBot
cd MadelineProtoBot
git init .
git submodule add https://github.com/danog/MadelineProto
cd MadelineProto
composer update
cp .env.example .env
cp -a *php tests userbots .env* ..
```

Now open `.env` and edit its values as needed.

