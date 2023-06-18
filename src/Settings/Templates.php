<?php

declare(strict_types=1);

namespace danog\MadelineProto\Settings;

use danog\MadelineProto\SettingsAbstract;

/**
 * Web and CLI template settings for login.
 */
final class Templates extends SettingsAbstract
{
    /**
     * Web template used for querying app information.
     */
    protected string $htmlTemplate = '<!DOCTYPE html><html><head><title>MadelineProto</title></head><body><h1>MadelineProto</h1><p>%s</p><form method="POST">%s<button type="submit"/>%s</button></form>%s</body></html>';

    /**
     * Get web template used for querying app information.
     */
    public function getHtmlTemplate(): string
    {
        return $this->htmlTemplate;
    }

    /**
     * Set web template used for querying app information.
     *
     * @param string $htmlTemplate Web template used for querying app information.
     */
    public function setHtmlTemplate(string $htmlTemplate): self
    {
        $this->htmlTemplate = $htmlTemplate;

        return $this;
    }
}
