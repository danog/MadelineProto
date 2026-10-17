<?php

declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\FileRefExtractor\Ops;

use danog\MadelineProto\FileRefExtractor\ActionOp;
use danog\MadelineProto\FileRefExtractor\TLContext;
use danog\MadelineProto\FileRefExtractor\TypedOp;
use Webmozart\Assert\Assert;

final readonly class GetMessageOp implements ActionOp
{
    public function __construct(
        private readonly TypedOp $peer,
        private readonly TypedOp $id,
        private readonly ?TypedOp $quickReplyShortcutId,
        private readonly string $stored_constructor
    ) {
    }
    public function normalize(array $stack, string $current, bool $ignoreFlag): ?ActionOp
    {
        $peer = $this->peer->normalize($stack, $current, $ignoreFlag);
        if ($peer === null) {
            return null;
        }
        $id = $this->id->normalize($stack, $current, $ignoreFlag);
        if ($id === null) {
            return null;
        }
        $quickReplyShortcutId = $this->quickReplyShortcutId?->normalize($stack, $current, $ignoreFlag);
        if ($quickReplyShortcutId === null && $this->quickReplyShortcutId !== null) {
            return null;
        }
        if ($peer !== $this->peer || $id !== $this->id || $quickReplyShortcutId !== $this->quickReplyShortcutId) {
            return new self($peer, $id, $quickReplyShortcutId, $this->stored_constructor);
        }
        return $this;
    }
    public function getType(TLContext $tl): string
    {
        return 'messages.Messages';
    }

    public function build(TLContext $tl): void
    {
        Assert::eq($this->peer->getType($tl), 'InputPeer');
        Assert::eq($this->id->getType($tl), 'int');
        if ($this->quickReplyShortcutId !== null) {
            Assert::eq($this->quickReplyShortcutId->getType($tl), 'int');
        }
        $extra = [];
        if ($this->quickReplyShortcutId !== null) {
            $extra['quick_reply_shortcut_id'] = $tl->build($this->quickReplyShortcutId, 'quick_reply_shortcut_id');
        }
        $tl->buildMode->addNode($tl, [
            '_' => 'getMessageOp',
            'stored_constructor' => $this->stored_constructor,
            'peer' => $tl->build($this->peer, 'peer'),
            'id' => $tl->build($this->id, 'id'),
            ...$extra,
        ]);
    }
}
