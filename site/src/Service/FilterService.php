<?php

namespace FDShop\Component\FDShop\Site\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\DatabaseQuery;
use Joomla\Database\ParameterType;

final class FilterService
{
    private const KEYS = ['manufacturer', 'availability', 'duration', 'caliber', 'nem'];
    private const RANGE_KEYS = ['duration', 'caliber', 'nem'];

    public function __construct(private readonly DatabaseInterface $db)
    {
    }

    public function getDefinitions(bool $activeOnly = true): array
    {
        $query = $this->db->getQuery(true)
            ->select(['f.id', 'f.filter_key', 'f.label', 'f.is_active', 'f.ordering'])
            ->from($this->db->quoteName('#__fdshop_filters', 'f'))
            ->order('f.ordering ASC, f.id ASC');
        if ($activeOnly) {
            $query->where('f.is_active = 1');
        }
        $this->db->setQuery($query);
        $definitions = [];
        foreach ($this->db->loadObjectList() ?: [] as $filter) {
            if (!in_array($filter->filter_key, self::KEYS, true)) {
                continue;
            }
            $filter->ranges = [];
            $definitions[$filter->filter_key] = $filter;
        }
        if ($definitions === []) {
            return [];
        }
        $ids = array_map(static fn ($filter): int => (int) $filter->id, $definitions);
        $query = $this->db->getQuery(true)
            ->select(['r.id', 'r.filter_id', 'r.label', 'r.value_from', 'r.value_to', 'r.is_active', 'r.ordering'])
            ->from($this->db->quoteName('#__fdshop_filter_ranges', 'r'))
            ->whereIn('r.filter_id', $ids)
            ->order('r.ordering ASC, r.id ASC');
        if ($activeOnly) {
            $query->where('r.is_active = 1');
        }
        $this->db->setQuery($query);
        $byId = [];
        foreach ($definitions as $key => $filter) {
            $byId[(int) $filter->id] = $key;
        }
        foreach ($this->db->loadObjectList() ?: [] as $range) {
            if (isset($byId[(int) $range->filter_id])) {
                $definitions[$byId[(int) $range->filter_id]]->ranges[] = $range;
            }
        }
        return $definitions;
    }

    public function normaliseState(array $raw, ?array $definitions = null): array
    {
        $definitions ??= $this->getDefinitions();
        $state = [];
        foreach (self::KEYS as $key) {
            if (!isset($definitions[$key])) {
                continue;
            }
            $values = $raw[$key] ?? [];
            $values = is_array($values) ? $values : [$values];
            $values = array_values(array_unique(array_filter(array_map('strval', $values), static fn ($v) => $v !== '')));
            if ($key === 'manufacturer') {
                $values = array_values(array_unique(array_filter(array_map('intval', $values), static fn ($v) => $v > 0)));
                if ($values !== []) {
                    $query = $this->db->getQuery(true)->select('id')->from($this->db->quoteName('#__fdshop_manufacturers'))->where('is_active = 1')->whereIn('id', $values);
                    $this->db->setQuery($query);
                    $values = array_map('intval', $this->db->loadColumn() ?: []);
                }
            } elseif ($key === 'availability') {
                $values = array_values(array_intersect($values, ['available', 'unavailable']));
            } else {
                $valid = array_map(static fn ($range): int => (int) $range->id, $definitions[$key]->ranges);
                $values = array_values(array_intersect(array_map('intval', $values), $valid));
            }
            if ($values !== []) {
                $state[$key] = $values;
            }
        }
        return $state;
    }

    public function apply(DatabaseQuery $query, array $state, array $definitions): void
    {
        if (!empty($state['manufacturer'])) {
            $query->whereIn('p.manufacturer_id', array_map('intval', $state['manufacturer']));
        }
        if (!empty($state['availability'])) {
            $query->leftJoin($this->db->quoteName('#__fdshop_products_details', 'pfd') . ' ON pfd.product_id = p.id');
            $availability = array_map('strval', $state['availability']);
            if (count($availability) === 1) {
                $query->where('COALESCE(pfd.is_in_stock, 0) = ' . ($availability[0] === 'available' ? '1' : '0'));
            }
        }
        $expressions = [
            'duration' => "CAST(REPLACE(p.burn_time, ',', '.') AS DECIMAL(12,3))",
            'caliber' => "CAST(REPLACE(p.caliber, ',', '.') AS DECIMAL(12,3))",
            'nem' => 'p.nem',
        ];
        $validExpressions = [
            'duration' => "TRIM(p.burn_time) REGEXP '^[0-9]+([.,][0-9]+)?'",
            'caliber' => "TRIM(p.caliber) REGEXP '^[0-9]+([.,][0-9]+)?'",
            'nem' => 'p.nem > 0',
        ];
        foreach (self::RANGE_KEYS as $key) {
            if (empty($state[$key]) || empty($definitions[$key])) {
                continue;
            }
            $selected = array_flip(array_map('intval', $state[$key]));
            $parts = [];
            foreach ($definitions[$key]->ranges as $range) {
                if (!isset($selected[(int) $range->id])) {
                    continue;
                }
                $bounds = [$validExpressions[$key]];
                if ($range->value_from !== null) {
                    $bounds[] = $expressions[$key] . ' >= ' . (float) $range->value_from;
                }
                if ($range->value_to !== null) {
                    $bounds[] = $expressions[$key] . ' < ' . (float) $range->value_to;
                }
                if ($bounds !== []) {
                    $parts[] = '(' . implode(' AND ', $bounds) . ')';
                }
            }
            if ($parts !== []) {
                $query->where('(' . implode(' OR ', $parts) . ')');
            }
        }
    }

    public function getFacets(int $categoryId, array $state, array $definitions): array
    {
        $facets = [];
        foreach ($definitions as $key => $definition) {
            $options = $key === 'manufacturer'
                ? $this->manufacturerOptions($categoryId, $state, $definitions)
                : ($key === 'availability'
                    ? $this->availabilityOptions($categoryId, $state, $definitions)
                    : $this->rangeOptions($key, $categoryId, $state, $definitions));
            $facets[$key] = ['definition' => $definition, 'options' => $options];
        }
        return $facets;
    }

    public function chips(array $state, array $facets): array
    {
        $chips = [];
        foreach ($state as $key => $values) {
            foreach ($facets[$key]['options'] ?? [] as $option) {
                if (in_array((string) $option['value'], array_map('strval', $values), true)) {
                    $chips[] = ['key' => $key, 'value' => (string) $option['value'], 'label' => (string) $option['label']];
                }
            }
        }
        return $chips;
    }

    private function baseCountQuery(int $categoryId, array $state, array $definitions, string $excluded): DatabaseQuery
    {
        unset($state[$excluded]);
        $query = $this->db->getQuery(true)
            ->from($this->db->quoteName('#__fdshop_products', 'p'))
            ->innerJoin($this->db->quoteName('#__fdshop_product_category_map', 'pcm') . ' ON pcm.product_id = p.id')
            ->where('pcm.category_id = ' . $categoryId)
            ->where('p.is_active = 1')->where('p.is_deleted = 0');
        $this->apply($query, $state, $definitions);
        return $query;
    }

    private function manufacturerOptions(int $categoryId, array $state, array $definitions): array
    {
        $query = $this->baseCountQuery($categoryId, $state, $definitions, 'manufacturer')
            ->select(['m.id AS value', 'm.manufacturer_name AS label', 'COUNT(DISTINCT p.id) AS hits'])
            ->innerJoin($this->db->quoteName('#__fdshop_manufacturers', 'm') . ' ON m.id = p.manufacturer_id')
            ->where('m.is_active = 1')->group(['m.id', 'm.manufacturer_name'])->order('m.manufacturer_name ASC');
        $this->db->setQuery($query);
        return array_map(static fn ($row): array => ['value' => (int) $row->value, 'label' => $row->label, 'count' => (int) $row->hits], $this->db->loadObjectList() ?: []);
    }

    private function availabilityOptions(int $categoryId, array $state, array $definitions): array
    {
        $query = $this->baseCountQuery($categoryId, $state, $definitions, 'availability')
            ->select(['COALESCE(pfd_count.is_in_stock, 0) AS available', 'COUNT(DISTINCT p.id) AS hits'])
            ->leftJoin($this->db->quoteName('#__fdshop_products_details', 'pfd_count') . ' ON pfd_count.product_id = p.id')
            ->group('COALESCE(pfd_count.is_in_stock, 0)');
        $this->db->setQuery($query);
        $counts = [0 => 0, 1 => 0];
        foreach ($this->db->loadObjectList() ?: [] as $row) {
            $counts[(int) $row->available] = (int) $row->hits;
        }
        return [
            ['value' => 'available', 'label' => 'Auf Lager', 'count' => $counts[1]],
            ['value' => 'unavailable', 'label' => 'Nicht auf Lager', 'count' => $counts[0]],
        ];
    }

    private function rangeOptions(string $key, int $categoryId, array $state, array $definitions): array
    {
        $expression = ['duration' => "CAST(REPLACE(p.burn_time, ',', '.') AS DECIMAL(12,3))", 'caliber' => "CAST(REPLACE(p.caliber, ',', '.') AS DECIMAL(12,3))", 'nem' => 'p.nem'][$key];
        $valid = ['duration' => "TRIM(p.burn_time) REGEXP '^[0-9]+([.,][0-9]+)?'", 'caliber' => "TRIM(p.caliber) REGEXP '^[0-9]+([.,][0-9]+)?'", 'nem' => 'p.nem > 0'][$key];
        $query = $this->baseCountQuery($categoryId, $state, $definitions, $key);
        $aliases = [];
        foreach ($definitions[$key]->ranges as $range) {
            $conditions = [$valid];
            if ($range->value_from !== null) {
                $conditions[] = $expression . ' >= ' . (float) $range->value_from;
            }
            if ($range->value_to !== null) {
                $conditions[] = $expression . ' < ' . (float) $range->value_to;
            }
            $alias = 'range_' . (int) $range->id;
            $aliases[(int) $range->id] = $alias;
            $query->select('COUNT(DISTINCT CASE WHEN ' . implode(' AND ', $conditions) . ' THEN p.id END) AS ' . $this->db->quoteName($alias));
        }
        if ($aliases === []) return [];
        $this->db->setQuery($query);
        $counts = (array) ($this->db->loadAssoc() ?: []);
        $options = [];
        foreach ($definitions[$key]->ranges as $range) {
            $options[] = ['value' => (int) $range->id, 'label' => (string) $range->label, 'count' => (int) ($counts[$aliases[(int) $range->id]] ?? 0)];
        }
        return $options;
    }
}
