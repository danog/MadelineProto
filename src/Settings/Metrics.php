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

use Amp\Socket\SocketAddress;
use danog\MadelineProto\SettingsAbstract;

/**
 * Metric settings.
 */
final class Metrics extends SettingsAbstract
{
    /**
     * Whether to enable additional prometheus stat collection for this session.
     */
    protected bool $enablePrometheusCollection = false;
    /**
     * Whether to enable memprof memory stat collection for this session.
     */
    protected bool $enableMemprofCollection = false;

    /**
     * Whether to expose metrics on the specified endpoint via HTTP.
     */
    protected ?SocketAddress $metricsBindTo = null;
    /**
     * Whether to expose metrics with startAndLoop, by providing a ?metrics or ?pprof query string.
     */
    protected bool $returnMetricsFromStartAndLoop = false;

    /**
     * Whether to expose prometheus/memprof metrics with startAndLoop, by providing a ?metrics or ?pprof query string.
     */
    public function setReturnMetricsFromStartAndLoop(bool $enable): self
    {
        $this->returnMetricsFromStartAndLoop = $enable;
        return $this;
    }
    /**
     * Whether to expose prometheus/memprof metrics with startAndLoop, by providing a ?metrics or ?pprof query string.
     */
    public function getReturnMetricsFromStartAndLoop(): bool
    {
        return $this->returnMetricsFromStartAndLoop;
    }

    /**
     * Whether to enable additional prometheus stat collection for this session.
     */
    public function setEnablePrometheusCollection(bool $enable): self
    {
        $this->enablePrometheusCollection = $enable;
        return $this;
    }
    /**
     * Whether additional prometheus stat collection is enabled for this session.
     */
    public function getEnablePrometheusCollection(): bool
    {
        return $this->enablePrometheusCollection;
    }

    /**
     * Whether to enable memprof memory stat collection for this session.
     */
    public function setEnableMemprofCollection(bool $enable): self
    {
        $this->enableMemprofCollection = $enable;
        return $this;
    }
    /**
     * Whether to enable memprof memory stat collection for this session.
     */
    public function getEnableMemprofCollection(): bool
    {
        return $this->enableMemprofCollection;
    }

    /**
     * Whether to expose metrics on the specified endpoint via HTTP.
     */
    public function setMetricsBindTo(?SocketAddress $metricsBindTo): self
    {
        $this->metricsBindTo = $metricsBindTo;
        return $this;
    }

    /**
     * Whether to expose metrics on the specified endpoint via HTTP.
     */
    public function getMetricsBindTo(): ?SocketAddress
    {
        return $this->metricsBindTo;
    }
}
