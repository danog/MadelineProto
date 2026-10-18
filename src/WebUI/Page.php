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

namespace danog\MadelineProto\WebUI;

use JsonSerializable;

/**
 * A page of the web UI: what the builtin login and status pages show.
 *
 * The page is rendered on the server by the template named by {@see self::$template} (by default `page`), which
 * gets it in the `$page` variable; in the browser, the web app renders it again with the view named by
 * {@see self::$view} (by default `setup`), from its JSON serialization.
 */
final class Page implements JsonSerializable
{
    /**
     * @param string                   $title        Title
     * @param string|SafeHtml          $lead         Introductory text
     * @param ?Step                    $step         Current step of the setup flow, if any
     * @param list<Field>              $fields       Form fields
     * @param ?string                  $submit       Label of the submit button, or null if the page has no form
     * @param list<Alert>              $alerts       Alerts
     * @param list<Action>             $actions      Secondary actions of the form
     * @param list<string|SafeHtml>    $instructions Ordered list of instructions
     * @param ?string                  $qrEndpoint   URL of the QR code login endpoint, to show the QR code login panel
     * @param string                   $template     Template used to render the page on the server
     * @param string                   $view         View used to render the page in the web app
     * @param array<string, mixed>     $data         Additional variables for custom templates
     */
    public function __construct(
        public string $title,
        public string|SafeHtml $lead = '',
        public ?Step $step = null,
        public array $fields = [],
        public ?string $submit = null,
        public array $alerts = [],
        public array $actions = [],
        public array $instructions = [],
        public ?string $qrEndpoint = null,
        public string $template = 'page',
        public string $view = 'setup',
        public array $data = [],
    ) {
    }

    #[\Override]
    public function jsonSerialize(): array
    {
        return [
            'view' => $this->view,
            'title' => $this->title,
            'lead' => $this->lead,
            'step' => $this->step?->value,
            'fields' => $this->fields,
            'submit' => $this->submit,
            'alerts' => $this->alerts,
            'actions' => $this->actions,
            'instructions' => $this->instructions,
            'qrEndpoint' => $this->qrEndpoint,
            'data' => (object) $this->data,
        ];
    }

    /**
     * Add alerts to the page.
     */
    public function addAlert(Alert ...$alerts): self
    {
        array_push($this->alerts, ...$alerts);
        return $this;
    }
}
