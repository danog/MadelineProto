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
 * A form field.
 */
final class Field implements JsonSerializable
{
    /**
     * @param string                    $name         Name of the POST parameter
     * @param string                    $label        Label
     * @param array<string, string>     $options      Value => label, for {@see FieldType::CHOICE}
     * @param ?array{string, string}    $showWhen     Only show (and submit) the field when the CHOICE field with the given name has the given value
     * @param ?string                   $autocomplete Value of the autocomplete attribute
     */
    public function __construct(
        public string $name,
        public string $label,
        public FieldType $type = FieldType::TEXT,
        public string $value = '',
        public string $placeholder = '',
        public string|SafeHtml $hint = '',
        public bool $required = true,
        public ?string $autocomplete = null,
        public array $options = [],
        public ?array $showWhen = null,
        public bool $autofocus = false,
        public ?int $maxLength = null,
    ) {
    }

    #[\Override]
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'label' => $this->label,
            'type' => $this->type->value,
            'value' => $this->value,
            'placeholder' => $this->placeholder,
            'hint' => $this->hint,
            'required' => $this->required,
            'autocomplete' => $this->autocomplete,
            'options' => (object) $this->options,
            'showWhen' => $this->showWhen,
            'autofocus' => $this->autofocus,
            'maxLength' => $this->maxLength,
        ];
    }

    /**
     * Get the attributes of the input element.
     *
     * @return array<string, scalar|null>
     */
    public function getInputAttributes(string $id): array
    {
        $type = match ($this->type) {
            // Hidden like the other secrets typed in the login
            FieldType::CODE => 'password',
            FieldType::TEXTAREA, FieldType::CHOICE => null,
            default => $this->type->value,
        };
        return [
            'type' => $type,
            'id' => $id,
            'name' => $this->name,
            'placeholder' => $this->placeholder ?: null,
            // Without JavaScript, all the conditional fields are shown: requiring them would block the form
            'required' => $this->required && $this->showWhen === null,
            'autocomplete' => $this->autocomplete ?? match ($this->type) {
                FieldType::CODE => 'one-time-code',
                FieldType::PHONE => 'tel',
                FieldType::PASSWORD => 'current-password',
                default => 'off',
            },
            'inputmode' => match ($this->type) {
                FieldType::CODE => 'numeric',
                FieldType::PHONE => 'tel',
                default => null,
            },
            'autocapitalize' => $this->type === FieldType::TEXT || $this->type === FieldType::CODE ? 'off' : null,
            'spellcheck' => $this->type === FieldType::TEXTAREA ? null : 'false',
            'autofocus' => $this->autofocus,
            'maxlength' => $this->maxLength,
        ];
    }
}
