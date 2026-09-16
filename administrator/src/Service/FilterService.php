<?php
namespace FDShop\Component\FDShop\Administrator\Service;
defined('_JEXEC') or die;
use Joomla\Database\DatabaseInterface;

final class FilterService implements FilterServiceInterface
{
    private const KEYS = ['manufacturer', 'availability', 'duration', 'caliber', 'nem'];
    private const RANGE_KEYS = ['duration', 'caliber', 'nem'];
    public function __construct(private readonly DatabaseInterface $db) {}

    public function getFilters(): array
    {
        $query = $this->db->getQuery(true)->select(['f.*', 'COUNT(r.id) AS range_count'])
            ->from($this->db->quoteName('#__fdshop_filters', 'f'))
            ->leftJoin($this->db->quoteName('#__fdshop_filter_ranges', 'r') . ' ON r.filter_id=f.id')
            ->group('f.id')->order('f.ordering ASC, f.id ASC');
        $this->db->setQuery($query);
        return $this->db->loadObjectList() ?: [];
    }

    public function getFilter(int $id): ?object
    {
        $query = $this->db->getQuery(true)->select('*')->from($this->db->quoteName('#__fdshop_filters'))->where('id=' . $id);
        $this->db->setQuery($query);
        $filter = $this->db->loadObject();
        if (!$filter || !in_array($filter->filter_key, self::KEYS, true)) return null;
        $filter->ranges = [];
        if (in_array($filter->filter_key, self::RANGE_KEYS, true)) {
            $query = $this->db->getQuery(true)->select('*')->from($this->db->quoteName('#__fdshop_filter_ranges'))->where('filter_id=' . $id)->order('ordering ASC, id ASC');
            $this->db->setQuery($query);
            $filter->ranges = $this->db->loadObjectList() ?: [];
        }
        return $filter;
    }

    public function save(array $data): int
    {
        $id = (int) ($data['id'] ?? 0);
        $current = $this->getFilter($id);
        if (!$current) throw new \RuntimeException('Unbekannter Systemfilter.');
        $label = trim((string) ($data['label'] ?? ''));
        if ($label === '') throw new \RuntimeException('Die sichtbare Bezeichnung ist erforderlich.');
        $filter = (object) ['id' => $id, 'label' => $label, 'is_active' => empty($data['is_active']) ? 0 : 1, 'ordering' => (int) ($data['ordering'] ?? 0)];
        $this->db->updateObject('#__fdshop_filters', $filter, 'id');
        if (!in_array($current->filter_key, self::RANGE_KEYS, true)) return $id;
        $ranges = is_array($data['ranges'] ?? null) ? $data['ranges'] : [];
        $normalised = [];
        foreach ($ranges as $range) {
            if (!empty($range['delete'])) continue;
            $label = trim((string) ($range['label'] ?? ''));
            $from = trim((string) ($range['value_from'] ?? ''));
            $to = trim((string) ($range['value_to'] ?? ''));
            $active = empty($range['is_active']) ? 0 : 1;
            if ($label === '' && $from === '' && $to === '') continue;
            if ($label === '') throw new \RuntimeException('Jeder Bereich benötigt eine Bezeichnung.');
            if ($from === '' && $to === '') throw new \RuntimeException('Ein Bereich benötigt mindestens eine Grenze.');
            $from = $from === '' ? null : (float) str_replace(',', '.', $from);
            $to = $to === '' ? null : (float) str_replace(',', '.', $to);
            if ($from !== null && $to !== null && $from >= $to) throw new \RuntimeException('Die Untergrenze muss kleiner als die Obergrenze sein.');
            $normalised[] = ['id' => (int) ($range['id'] ?? 0), 'label' => $label, 'value_from' => $from, 'value_to' => $to, 'is_active' => $active, 'ordering' => (int) ($range['ordering'] ?? 0)];
        }
        $activeRanges = array_values(array_filter($normalised, static fn ($r) => $r['is_active'] === 1));
        for ($i = 0; $i < count($activeRanges); $i++) for ($j = $i + 1; $j < count($activeRanges); $j++) {
            $a = $activeRanges[$i]; $b = $activeRanges[$j];
            $aFrom = $a['value_from'] ?? -INF; $aTo = $a['value_to'] ?? INF; $bFrom = $b['value_from'] ?? -INF; $bTo = $b['value_to'] ?? INF;
            if ($aFrom < $bTo && $bFrom < $aTo) throw new \RuntimeException('Die aktiven Bereiche „' . $a['label'] . '“ und „' . $b['label'] . '“ überschneiden sich.');
        }
        $this->db->transactionStart();
        try {
            $kept = [];
            foreach ($normalised as $range) {
                $row = (object) ($range + ['filter_id' => $id]);
                if ($row->id > 0) { $this->db->updateObject('#__fdshop_filter_ranges', $row, 'id'); $kept[] = $row->id; }
                else { unset($row->id); $this->db->insertObject('#__fdshop_filter_ranges', $row, 'id'); $kept[] = (int) $row->id; }
            }
            $query = $this->db->getQuery(true)->delete($this->db->quoteName('#__fdshop_filter_ranges'))->where('filter_id=' . $id);
            if ($kept !== []) $query->whereNotIn('id', $kept);
            $this->db->setQuery($query)->execute();
            $this->db->transactionCommit();
        } catch (\Throwable $e) { $this->db->transactionRollback(); throw $e; }
        return $id;
    }
}
