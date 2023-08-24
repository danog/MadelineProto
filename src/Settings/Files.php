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
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Settings;

use danog\MadelineProto\SettingsAbstract;

/**
 * File management settings.
 */
final class Files extends SettingsAbstract
{
    /**
     * Allow automatic upload of files from file paths present in constructors?
     */
    protected bool $allowAutomaticUpload = true;
    /**
     * Upload parallel chunk count.
     */
    protected int $uploadParallelChunks = 20;
    /**
     * Download parallel chunk count.
     */
    protected int $downloadParallelChunks = 20;

    /**
     * Whether to report undownloadable media to TSF.
     */
    protected bool $reportBrokenMedia = true;

    /**
     * Custom download link URL for CLI bots, used by `getDownloadLink`.
     */
    protected ?string $downloadLink = null;

    /**
     * Get allow automatic upload of files from file paths present in constructors?
     */
    public function getAllowAutomaticUpload(): bool
    {
        return $this->allowAutomaticUpload;
    }

    /**
     * Set allow automatic upload of files from file paths present in constructors?
     *
     * @param bool $allowAutomaticUpload Allow automatic upload of files from file paths present in constructors?
     */
    public function setAllowAutomaticUpload(bool $allowAutomaticUpload): self
    {
        $this->allowAutomaticUpload = $allowAutomaticUpload;

        return $this;
    }

    /**
     * Get upload parallel chunk count.
     */
    public function getUploadParallelChunks(): int
    {
        return $this->uploadParallelChunks;
    }

    /**
     * Set upload parallel chunk count.
     *
     * @param int $uploadParallelChunks Upload parallel chunk count
     */
    public function setUploadParallelChunks(int $uploadParallelChunks): self
    {
        $this->uploadParallelChunks = $uploadParallelChunks;

        return $this;
    }

    /**
     * Get download parallel chunk count.
     */
    public function getDownloadParallelChunks(): int
    {
        return $this->downloadParallelChunks;
    }

    /**
     * Set download parallel chunk count.
     *
     * @param int $downloadParallelChunks Download parallel chunk count
     */
    public function setDownloadParallelChunks(int $downloadParallelChunks): self
    {
        $this->downloadParallelChunks = $downloadParallelChunks;

        return $this;
    }

    /**
     * Get whether to report undownloadable media to TSF.
     */
    public function getReportBrokenMedia(): bool
    {
        return $this->reportBrokenMedia;
    }

    /**
     * Set whether to report undownloadable media to TSF.
     *
     * @param bool $reportBrokenMedia Whether to report undownloadable media to TSF
     */
    public function setReportBrokenMedia(bool $reportBrokenMedia): self
    {
        $this->reportBrokenMedia = $reportBrokenMedia;

        return $this;
    }

    /**
     * Get custom download link URL for CLI bots, used by `getDownloadLink`.
     *
     * @return ?string
     */
    public function getDownloadLink(): ?string
    {
        return $this->downloadLink;
    }

    /**
     * Only needed for CLI bots, not bots started via web.
     *
     * Sets custom download link URL for CLI bots, used by `getDownloadLink`.
     *
     * Can be null, in which case MadelineProto will automatically generate a download link.
     *
     * @param ?string $downloadLink Custom download link URL for CLI bots, used by `getDownloadLink`.
     *
     */
    public function setDownloadLink(?string $downloadLink): self
    {
        $this->downloadLink = $downloadLink;

        return $this;
    }
}
