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

namespace danog\MadelineProto;

/**
 * Container format of a call recording, as passed to {@see Call::setOutput()}.
 *
 * {@see self::Webm} and {@see self::Mkv} both mux the incoming audio and video into a Matroska
 * file in pure PHP (the peer's frames are stored as-is, so the video track is whatever codec the
 * peer sends — VP8/VP9/H.264/AV1 — and the audio is OPUS); they differ only in the declared EBML
 * DocType. {@see self::Opus} keeps the audio-only behaviour, writing an OGG OPUS stream.
 *
 * When no format is passed to {@see Call::setOutput()}, it is autodetected from the extension of the
 * target — but only if a {@see LocalFile} was given; a raw stream, whose extension is unknown,
 * defaults to {@see self::Opus}.
 */
enum RecordingFormat
{
    /** Matroska container with the `webm` DocType. */
    case Webm;
    /** Matroska container with the `matroska` DocType. */
    case Mkv;
    /** OGG OPUS audio-only stream. */
    case Opus;

    /**
     * Autodetect the recording format from a file's extension: `.mkv` and `.webm` map to the
     * matching Matroska format, and anything else (including an unknown extension) to {@see self::Opus}.
     */
    public static function fromFile(LocalFile $file): self
    {
        return match (strtolower(pathinfo($file->file, PATHINFO_EXTENSION))) {
            'mkv' => self::Mkv,
            'webm' => self::Webm,
            default => self::Opus,
        };
    }

    /** Whether this format muxes video (and audio) into a Matroska container, rather than audio-only OGG. */
    public function isMatroska(): bool
    {
        return $this !== self::Opus;
    }

    /** The EBML DocType to declare in the Matroska header for this format. */
    public function docType(): string
    {
        return match ($this) {
            self::Webm => 'webm',
            self::Mkv => 'matroska',
            self::Opus => throw new \LogicException('OPUS is not a Matroska format'),
        };
    }
}
