<?php

namespace danog\MadelineProto\Settings;

use danog\MadelineProto\SettingsAbstract;

/**
 * File management settings.
 */
class Files extends SettingsAbstract
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

    public function mergeArray(array $settings): void
    {
        if (isset($settings['upload']['allow_automatic_upload'])) {
            $this->setAllowAutomaticUpload($settings['upload']['allow_automatic_upload']);
        }
        if (isset($settings['download']['report_broken_media'])) {
            $this->setReportBrokenMedia($settings['download']['report_broken_media']);
        }
        if (isset($settings['upload']['parallel_chunks'])) {
            $this->setUploadParallelChunks($settings['upload']['parallel_chunks']);
        }
        if (isset($settings['download']['parallel_chunks'])) {
            $this->setDownloadParallelChunks($settings['download']['parallel_chunks']);
        }
    }

    /**
     * Get allow automatic upload of files from file paths present in constructors?
     *
     * @return bool
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
     * @return self
     */
    public function setAllowAutomaticUpload(bool $allowAutomaticUpload): self
    {
        $this->allowAutomaticUpload = $allowAutomaticUpload;

        return $this;
    }

    /**
     * Get upload parallel chunk count.
     *
     * @return int
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
     * @return self
     */
    public function setUploadParallelChunks(int $uploadParallelChunks): self
    {
        $this->uploadParallelChunks = $uploadParallelChunks;

        return $this;
    }

    /**
     * Get download parallel chunk count.
     *
     * @return int
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
     * @return self
     */
    public function setDownloadParallelChunks(int $downloadParallelChunks): self
    {
        $this->downloadParallelChunks = $downloadParallelChunks;

        return $this;
    }

    /**
     * Get whether to report undownloadable media to TSF.
     *
     * @return bool
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
     * @return self
     */
    public function setReportBrokenMedia(bool $reportBrokenMedia): self
    {
        $this->reportBrokenMedia = $reportBrokenMedia;

        return $this;
    }
}
