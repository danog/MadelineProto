<?php

/*
Copyright 2016-2018 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto\Wrappers;

/**
 * Manages logging in and out.
 */
trait Webhook
{

    public function setWebhook($url, $pem_path = null) {
        $this->pem_path = $pem_path;
        $this->hook_url = $hook_url;
        $this->settings['updates']['callback'] = [$this, 'pwr_webhook'];
        $this->settings['updates']['handle_updates'] = true;
    }
}
