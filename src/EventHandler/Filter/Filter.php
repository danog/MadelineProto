<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Filter;

use AssertionError;
use danog\MadelineProto\EventHandler;
use danog\MadelineProto\EventHandler\Filter\Combinator\FiltersAnd;
use danog\MadelineProto\EventHandler\Filter\Combinator\FiltersOr;
use danog\MadelineProto\EventHandler\Filter\Media\FilterAudio;
use danog\MadelineProto\EventHandler\Filter\Media\FilterDocument;
use danog\MadelineProto\EventHandler\Filter\Media\FilterDocumentPhoto;
use danog\MadelineProto\EventHandler\Filter\Media\FilterGif;
use danog\MadelineProto\EventHandler\Filter\Media\FilterPhoto;
use danog\MadelineProto\EventHandler\Filter\Media\FilterRoundVideo;
use danog\MadelineProto\EventHandler\Filter\Media\FilterSticker;
use danog\MadelineProto\EventHandler\Filter\Media\FilterVideo;
use danog\MadelineProto\EventHandler\Filter\Media\FilterVoice;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\EventHandler\Message\ChannelMessage;
use danog\MadelineProto\EventHandler\Message\GroupMessage;
use danog\MadelineProto\EventHandler\Message\PrivateMessage;
use danog\MadelineProto\EventHandler\Message\ServiceMessage;
use danog\MadelineProto\EventHandler\SimpleFilter\FromAdmin;
use danog\MadelineProto\EventHandler\SimpleFilter\FromAdminOrOutgoing;
use danog\MadelineProto\EventHandler\SimpleFilter\HasAudio;
use danog\MadelineProto\EventHandler\SimpleFilter\HasDocument;
use danog\MadelineProto\EventHandler\SimpleFilter\HasDocumentPhoto;
use danog\MadelineProto\EventHandler\SimpleFilter\HasGif;
use danog\MadelineProto\EventHandler\SimpleFilter\HasMedia;
use danog\MadelineProto\EventHandler\SimpleFilter\HasNoMedia;
use danog\MadelineProto\EventHandler\SimpleFilter\HasPhoto;
use danog\MadelineProto\EventHandler\SimpleFilter\HasRoundVideo;
use danog\MadelineProto\EventHandler\SimpleFilter\HasSticker;
use danog\MadelineProto\EventHandler\SimpleFilter\HasVideo;
use danog\MadelineProto\EventHandler\SimpleFilter\HasVoice;
use danog\MadelineProto\EventHandler\SimpleFilter\Incoming;
use danog\MadelineProto\EventHandler\SimpleFilter\IsForwarded;
use danog\MadelineProto\EventHandler\SimpleFilter\IsReply;
use danog\MadelineProto\EventHandler\SimpleFilter\IsReplyToSelf;
use danog\MadelineProto\EventHandler\SimpleFilter\Outgoing;
use danog\MadelineProto\EventHandler\Update;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;

abstract class Filter
{
    abstract public function apply(Update $update): bool;
    /** Run some initialization logic, optionally returning a new filter to replace the current one. */
    public function initialize(EventHandler $API): Filter
    {
        return $this;
    }

    public static function fromReflectionType(ReflectionType $type): Filter
    {
        return match (true) {
            $type instanceof ReflectionUnionType => new FiltersOr(
                ...\array_map(
                    self::fromReflectionType(...),
                    $type->getTypes()
                )
            ),
            $type instanceof ReflectionIntersectionType => new FiltersAnd(
                ...\array_map(
                    self::fromReflectionType(...),
                    $type->getTypes()
                )
            ),
            $type instanceof ReflectionNamedType => match ($type->getName()) {
                Incoming::class => new FilterIncoming,
                Outgoing::class => new FilterOutgoing,
                Update::class => new FilterAllowAll,
                Message::class => new FilterMessage,
                PrivateMessage::class => new FilterPrivate,
                GroupMessage::class => new FilterGroup,
                ChannelMessage::class => new FilterChannel,
                ServiceMessage::class => new FilterService,
                IsForwarded::class => new FilterForwarded,
                IsReply::class => new FilterReply,
                IsReplyToSelf::class => new FilterReplyToSelf,
                HasMedia::class => new FilterMedia,
                HasNoMedia::class => new FilterNoMedia,
                FromAdmin::class => new FilterFromAdmin,
                HasAudio::class => new FilterAudio,
                HasDocument::class => new FilterDocument,
                HasDocumentPhoto::class => new FilterDocumentPhoto,
                HasGif::class => new FilterGif,
                HasPhoto::class => new FilterPhoto,
                HasRoundVideo::class => new FilterRoundVideo,
                HasSticker::class => new FilterSticker,
                HasVideo::class => new FilterVideo,
                HasVoice::class => new FilterVoice,
                FromAdminOrOutgoing::class => new FiltersOr(new FilterFromAdmin, new FilterOutgoing),
                default => \is_subclass_of($type->getName(), Update::class)
                    ? new class($type->getName()) extends Filter {
                        public function __construct(private readonly string $class)
                        {
                        }
                        public function apply(Update $update): bool
                        {
                            return $update instanceof $this->class;
                        }
                    }
                    : throw new AssertionError("Unknown type ".$type->getName().", did you forget to `use` it?")
            }
        };
    }
}
