<?php

declare(strict_types=1);

// Renders a page of the web UI from the phar given as argument: the phar must contain its templates and assets.

use danog\MadelineProto\Settings\Templates;
use danog\MadelineProto\WebUI\Page;
use danog\MadelineProto\WebUI\WebUI;

require $argv[1];

$html = WebUI::render(new Templates, new Page(title: 'Test'));
if (!str_contains($html, '<title>')) {
    echo "The web UI rendered from $argv[1] has no title:".PHP_EOL.$html.PHP_EOL;
    exit(1);
}
echo 'The web UI renders from the phar'.PHP_EOL;
