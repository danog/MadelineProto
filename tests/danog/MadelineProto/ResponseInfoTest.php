<?php declare(strict_types=1);

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

namespace danog\MadelineProto\Test;

use danog\MadelineProto\MTProtoTools\ResponseInfo;
use PHPUnit\Framework\TestCase;

/**
 * Files served to browsers (download script, downloadToResponse) can't run as pages of the origin serving
 * them: their MIME type and name come from whoever sent them on Telegram, or from the query string.
 */
final class ResponseInfoTest extends TestCase
{
    private static function headers(string $mime, string $name, string $ext): array
    {
        return ResponseInfo::parseHeaders('GET', [], ['mime' => $mime, 'name' => $name, 'ext' => $ext, 'size' => 10])->getHeaders();
    }

    public function testEveryFileIsSandboxed(): void
    {
        foreach (['video/mp4', 'image/jpeg', 'text/html', 'application/octet-stream'] as $mime) {
            $headers = self::headers($mime, 'file', '.bin');
            $this->assertSame('sandbox', $headers['Content-Security-Policy'], $mime);
            $this->assertSame('nosniff', $headers['X-Content-Type-Options'], $mime);
        }
    }

    public function testDocumentTypesAreDownloaded(): void
    {
        foreach (['text/html', 'TEXT/HTML; charset=utf-8', 'image/svg+xml', 'application/xhtml+xml', 'text/javascript', 'application/xml', 'application/pdf', 'text/xsl', 'multipart/x-mixed-replace', ''] as $mime) {
            $this->assertStringStartsWith('attachment', self::headers($mime, 'file', '.x')['Content-Disposition'], $mime);
        }
        $this->assertStringStartsWith('inline', self::headers('video/mp4', 'film', '.mp4')['Content-Disposition']);
        $this->assertStringStartsWith('inline', self::headers('image/jpeg', 'photo', '.jpg')['Content-Disposition']);
    }

    public function testTheTypeIsNormalized(): void
    {
        foreach (['text/html, text/plain', "text/html\tx", 'text/html;x', 'video/mp4 text/html', '', 'garbage'] as $mime) {
            $headers = self::headers($mime, 'file', '.x');
            $this->assertStringStartsWith('attachment', $headers['Content-Disposition'], $mime);
            $this->assertMatchesRegularExpression('~^[a-z0-9!#$&^_.+-]+/[a-z0-9!#$&^_.+-]+$~D', $headers['Content-Type'], $mime);
        }
        $this->assertSame('text/html', self::headers('TEXT/HTML; charset=utf-8', 'file', '.x')['Content-Type']);
        $this->assertSame('application/octet-stream', self::headers('text/html, text/plain', 'file', '.x')['Content-Type']);
        $this->assertStringStartsWith('inline', self::headers('Video/MP4; codecs="avc1"', 'film', '.mp4')['Content-Disposition']);
    }

    public function testTheSandboxCanBeDisabled(): void
    {
        $headers = ResponseInfo::parseHeaders('GET', [], ['mime' => 'text/html; charset=utf-8', 'name' => 'page', 'ext' => '.html', 'size' => 10], false)->getHeaders();
        $this->assertSame('text/html; charset=utf-8', $headers['Content-Type']);
        $this->assertArrayNotHasKey('Content-Security-Policy', $headers);
        $this->assertArrayNotHasKey('X-Content-Type-Options', $headers);
        $this->assertStringStartsWith('inline', $headers['Content-Disposition']);
        // The name is still escaped.
        $headers = ResponseInfo::parseHeaders('GET', [], ['mime' => 'text/html', 'name' => 'x"y', 'ext' => '.html', 'size' => 10], false)->getHeaders();
        $this->assertStringStartsWith('inline; filename="x_y.html"', $headers['Content-Disposition']);
    }

    public function testTheNameCantBreakOutOfTheHeader(): void
    {
        $disposition = self::headers('image/jpeg', 'x"; filename="evil.html', '.jpg')['Content-Disposition'];
        $this->assertSame("inline; filename=\"x_; filename=_evil.html.jpg\"; filename*=UTF-8''x%22%3B%20filename%3D%22evil.html.jpg", $disposition);
        $this->assertStringContainsString("filename*=UTF-8''%D1%84%D0%BE%D1%82%D0%BE.jpg", self::headers('image/jpeg', 'фото', '.jpg')['Content-Disposition']);
    }
}
