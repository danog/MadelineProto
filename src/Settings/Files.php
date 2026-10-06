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

namespace danog\MadelineProto\Settings;

use danog\MadelineProto\SettingsAbstract;

/**
 * File management settings.
 *
 * @psalm-external-mutation-free
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
     * Whether to resume interrupted calls that upload files after a restart.
     */
    protected bool $resumeInterruptedCalls = true;

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
     *
     * @psalm-external-mutation-free
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
     *
     * @psalm-external-mutation-free
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
     *
     * @psalm-external-mutation-free
     */
    public function setDownloadParallelChunks(int $downloadParallelChunks): self
    {
        $this->downloadParallelChunks = $downloadParallelChunks;

        return $this;
    }

    /**
     * Get whether to resume interrupted calls that upload files after a restart.
     */
    public function getResumeInterruptedCalls(): bool
    {
        return $this->resumeInterruptedCalls;
    }

    /**
     * Set whether to resume interrupted calls that upload files after a restart.
     *
     * If enabled, method calls and sendMedia calls (sendDocument, sendPhoto, ...) uploading a file that survives a restart
     * (a local file, a URL, a Telegram file or a resumable stream) are saved in the session until they complete:
     * if the session is restarted in the meantime, the call is made again once the session starts, resuming the interrupted upload.
     *
     * Disable it if your code already makes interrupted calls again after a restart, to avoid sending files twice.
     *
     * @param bool $resumeInterruptedCalls Whether to resume interrupted calls that upload files after a restart
     *
     * @psalm-external-mutation-free
     */
    public function setResumeInterruptedCalls(bool $resumeInterruptedCalls): self
    {
        $this->resumeInterruptedCalls = $resumeInterruptedCalls;

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
     *
     * @psalm-external-mutation-free
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
     * @psalm-external-mutation-free
     */
    public function setDownloadLink(?string $downloadLink): self
    {
        $this->downloadLink = $downloadLink;

        return $this;
    }
}
