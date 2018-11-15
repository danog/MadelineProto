<?php
/**
 * Buffer interface
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2018 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Stream;

use Amp\Socket\ClientConnectContext;
use Amp\CancellationToken;
use Amp\Uri\Uri;
use function Amp\call;
use function Amp\coroutine;

class ConnectionContext
{
    private $secure = false;
    private $uri = '';
    private $socketContext;
    private $cancellationToken;
    private $dc = 0;
    private $nextStreams = [];
    private $key = 0;

    public function setSocketContext(ClientConnectContext $socketContext): self
    {
        
        $this->socketContext = $socketContext;
        return $this;
    }
    public function getSocketContext(): ClientConnectContext
    {
        return $this->socketContext;
    }
    public function setUri($uri): self
    {
        $this->uri = $uri instanceof Uri ? $uri : new Uri($uri);
        return $this;
    }
    public function getStringUri(): string
    {
        return (string) $this->uri;
    }
    public function getUri(): Uri
    {
        return $this->uri;
    }
    public function setCancellationToken(CancellationToken $cancellationToken): self
    {
        
        $this->cancellationToken = $cancellationToken;
        return $this;
    }
    public function getCancellationToken(): CancellationToken
    {
        return $this->cancellationToken;
    }
    public function secure(bool $secure): self
    {
        $this->secure = $secure;
        return $this;
    }
    public function isSecure(): bool
    {
        return $this->secure;
    }
    public function setDc($dc): self
    {
        $this->dc = $dc;
        return $this;
    }
    public function getDc()
    {
        return $this->dc;
    }
    public function getCtx(): self
    {
        return clone $this;
    }
    public function addStream(string $streamName, $extra = null): self
    {
        $this->nextStreams[] = [$streamName, $extra];
        return $this;
    }
    public function getStream(): Promise
    {
        return coroutine([$this, 'getStreamAsync'])();
    }
    public function getStreamAsync(): \Generator
    {
        list($clazz, $extra) = $this->nextStreams[$this->key++];
        $obj = new $clazz();
        if ($obj instanceof ProxyStreamInterface) {
            $obj->setExtra($extra);
        }
        yield $obj->connect($this);
        return $obj;
    }
}