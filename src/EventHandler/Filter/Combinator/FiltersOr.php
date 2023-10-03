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

namespace danog\MadelineProto\EventHandler\Filter\Combinator;

use Attribute;
use danog\MadelineProto\EventHandler;
use danog\MadelineProto\EventHandler\Filter\Filter;
use danog\MadelineProto\EventHandler\Filter\FilterAllowAll;
use danog\MadelineProto\EventHandler\Update;
use Webmozart\Assert\Assert;

/**
 * ORs multiple filters.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class FiltersOr extends Filter
{
    /** @var array<Filter> */
    private readonly array $filters;
    public function __construct(Filter ...$filters)
    {
        Assert::notEmpty($filters);
        $this->filters = $filters;
    }
    public function initialize(EventHandler $API): Filter
    {
        $final = [];
        foreach ($this->filters as $filter) {
            $filter = $filter->initialize($API);
            if ($filter instanceof self) {
                $final = array_merge($final, $filter->filters);
            } else {
                $final []= $filter;
            }
        }
        foreach ($final as $f) {
            if ($f instanceof FilterAllowAll) {
                return $f;
            }
        }
        return match (\count($final)) {
            0 => new FilterAllowAll,
            1 => $final[0],
            default => new self(...$final)
        };
    }
    public function apply(Update $update): bool
    {
        foreach ($this->filters as $filter) {
            if ($filter->apply($update)) {
                return true;
            }
        }
        return false;
    }
}
