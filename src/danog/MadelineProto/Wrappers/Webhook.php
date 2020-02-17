<?php

/**
 * Webhook module.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Wrappers;

/**
 * Manages logging in and out.
 */
trait Webhook
{
    /**
     * Set webhook update handler.
     *
     * @param string $hook_url Webhook URL
     * @param string $pem_path PEM path for self-signed certificate
     *
     * @return void
     */
    public function setWebhook(string $hook_url, string $pem_path = ''): void
    {
        $this->pem_path = $pem_path;
        $this->hook_url = $hook_url;
        $this->settings['updates']['callback'] = [$this, 'pwrWebhook'];
        $this->settings['updates']['run_callback'] = true;
        $this->settings['updates']['handle_updates'] = true;
        $this->startUpdateSystem();
    }
}
