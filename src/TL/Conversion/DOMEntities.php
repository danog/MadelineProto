<?php

declare(strict_types=1);

namespace danog\MadelineProto\TL\Conversion;

use danog\MadelineProto\Exception;
use danog\MadelineProto\StrTools;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use Throwable;

/**
 * Class that converts HTML to a message + set of entities.
 */
final class DOMEntities extends Entities
{
    /** Converted entities */
    public readonly array $entities;
    /** Converted message */
    public readonly string $message;
    /**
     * @param string $html HTML to parse
     */
    public function __construct(string $html)
    {
        try {
            $dom = new DOMDocument();
            $html = \preg_replace('/\<br(\s*)?\/?\>/i', "\n", $html);
            $dom->loadxml('<body>' . \trim($html) . '</body>');
            $message = '';
            $entities = [];
            self::parseNode($dom->getElementsByTagName('body')->item(0), 0, $message, $entities);
            $this->message = $message;
            $this->entities = $entities;
        } catch (Throwable $e) {
            throw new Exception("An error occurred while parsing $html: {$e->getMessage()}", $e->getCode());
        }
    }
    /**
     * @return integer Length of the node
     */
    private static function parseNode(DOMNode|DOMText $node, int $offset, string &$message, array &$entities): int
    {
        if ($node instanceof DOMText) {
            $message .= $node->wholeText;
            return StrTools::mbStrlen($node->wholeText);
        }
        if ($node->nodeName === 'br') {
            $message .= "\n";
            return 1;
        }
        $length = 0;
        if ($node->nodeName === 'li') {
            $message .= "- ";
            $length += 2;
        }
        /** @var DOMElement $node */
        $entity = match ($node->nodeName) {
            's', 'strike', 'del' =>['_' => 'messageEntityStrike'],
            'u' =>  ['_' => 'messageEntityUnderline'],
            'blockquote' => ['_' => 'messageEntityBlockquote'],
            'b', 'strong' => ['_' => 'messageEntityBold'],
            'i', 'em' => ['_' => 'messageEntityItalic'],
            'code' => ['_' => 'messageEntityCode'],
            'spoiler', 'tg-spoiler' => ['_' => 'messageEntitySpoiler'],
            'pre' => ['_' => 'messageEntityPre', 'language' => $node->getAttribute('language')],
            'tg-emoji' => ['_' => 'messageEntityCustomEmoji', 'document_id' => (int) $node->getAttribute('emoji-id')],
            'emoji' => ['_' => 'messageEntityCustomEmoji', 'document_id' => (int) $node->getAttribute('id')],
            'a' => self::handleLink($node->getAttribute('href')),
            default => null,
        };
        foreach ($node->childNodes as $sub) {
            $length += self::parseNode($sub, $offset+$length, $message, $entities);
        }
        if ($entity !== null) {
            $lengthReal = $length;
            for ($x = \strlen($message)-1; $x >= 0; $x--) {
                if (!(
                    $message[$x] === ' '
                    || $message[$x] === "\r"
                    || $message[$x] === "\n"
                )) {
                    break;
                }
                $lengthReal--;
            }
            if ($lengthReal > 0) {
                $entity['offset'] = $offset;
                $entity['length'] = $lengthReal;
                $entities []= $entity;
            }
        }
        return $length;
    }
}
