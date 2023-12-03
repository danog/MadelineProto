<?php declare(strict_types=1);

use danog\MadelineProto\EventHandler\Attributes\Handler;
use danog\MadelineProto\EventHandler\Filter\FilterCommand;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\EventHandler\SimpleFilter\HasAudio;
use danog\MadelineProto\EventHandler\SimpleFilter\HasDocument;
use danog\MadelineProto\EventHandler\SimpleFilter\Incoming;
use danog\MadelineProto\Ogg;
use danog\MadelineProto\ParseMode;
use danog\MadelineProto\SimpleEventHandler;

use function Amp\async;

if (class_exists(API::class)) {
    // Otherwise, if a stable version of MadelineProto was installed via composer, load composer autoloader
} elseif (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
} else {
    // Otherwise download an !!! alpha !!! version of MadelineProto via madeline.php
    if (!file_exists('madeline.php')) {
        copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
    }
    require_once 'madeline.php';
}

class LibtgvoipEventHandler extends SimpleEventHandler
{
    // !!! Change this to your username! !!!
    private const ADMIN = 'danogentili';
    public function getReportPeers(): string
    {
        return self::ADMIN;
    }
    #[FilterCommand('start')]
    public function startCmd(Incoming&Message $message): void
    {
        $message->reply(
            message: "This bot can be used to convert files to be played by a @MadelineProto Telegram webradio!".
            "\n\nSee https://docs.madelineproto.xyz/docs/CALLS.html for more info, and call @magicalcrazypony to hear some nice tunes!".
            "\n\nSend me an audio file to start.".
            "\n\nPowered by @MadelineProto, [source code](https://github.com/danog/MadelineProto/blob/v8/examples/libtgvoipbot.php).",
            parseMode: ParseMode::MARKDOWN
        );
    }
    #[Handler]
    public function convertCmd((Incoming&Message&HasAudio)|(Incoming&Message&HasDocument) $message): void
    {
        $reply = $message->reply("Conversion in progress...");
        async(function () use ($message, $reply): void {
            $pipe = self::getStreamPipe();
            $sink = $pipe->getSink();
            async(
                Ogg::convert(...),
                $message->media->getStream(),
                $sink
            )->finally($sink->close(...));

            $this->sendDocument(
                peer: $message->chatId,
                file: $pipe->getSource(),
                fileName: $message->media->fileName.".ogg",
                replyToMsgId: $message->id
            );
        })->finally($reply->delete(...));
    }
}

if (!getenv('TOKEN')) {
    throw new AssertionError("You must define a TOKEN environment variable with the token of the bot!");
}

LibtgvoipEventHandler::startAndLoopBot($argv[1] ?? 'libtgvoipbot.madeline', getenv('TOKEN'));
