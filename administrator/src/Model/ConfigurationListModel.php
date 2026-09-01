<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\ListModel;

abstract class ConfigurationListModel extends ListModel
{
    protected string $requestScope = '';

    public function getFilterForm($data = [], $loadData = true): ?Form
    {
        if ($this->filterFormName === '') {
            return null;
        }

        try {
            $form = $this->loadForm(
                $this->context . '.filter',
                $this->filterFormName,
                [
                    'control'   => $this->requestScope,
                    'load_data' => $loadData,
                ]
            );

            return $form instanceof Form ? $form : null;
        } catch (\RuntimeException) {
            return null;
        }
    }

    protected function populateScopedState(string $ordering, string $direction): void
    {
        $app          = Factory::getApplication();
        $defaultLimit = (int) $app->get('list_limit', 20);
        $input        = $app->getInput();
        $stateKey     = $this->context . '.request';
        $previous     = $app->getUserState($stateKey, []);

        if ($input->exists($this->requestScope)) {
            $request = $input->get($this->requestScope, [], 'array');
            $previousList = isset($previous['list']) && is_array($previous['list']) ? $previous['list'] : [];
            $requestList = isset($request['list']) && is_array($request['list']) ? $request['list'] : [];
            $previousFilters = isset($previous['filter']) && is_array($previous['filter']) ? $previous['filter'] : [];
            $requestFilters = isset($request['filter']) && is_array($request['filter']) ? $request['filter'] : [];

            if ($requestFilters !== $previousFilters || $requestList !== $previousList) {
                $request['limitstart'] = 0;
            }

            $app->setUserState($stateKey, $request);
        } else {
            $request = is_array($previous) ? $previous : [];
        }

        $filters      = isset($request['filter']) && is_array($request['filter']) ? $request['filter'] : [];
        $list         = isset($request['list']) && is_array($request['list']) ? $request['list'] : [];

        $search = trim((string) ($filters['search'] ?? ''));
        $this->setState('filter.search', $search);

        $published = (string) ($filters['published'] ?? '');
        $published = in_array($published, ['0', '1'], true) ? $published : '';
        $this->setState('filter.published', $published);

        $listOrdering = $ordering;
        $listDirection = strtoupper($direction);
        $fullOrdering = trim((string) ($list['fullordering'] ?? ''));

        if ($fullOrdering !== '') {
            $parts = preg_split('/\s+/', $fullOrdering) ?: [];
            $candidateDirection = strtoupper((string) array_pop($parts));
            $candidateOrdering = implode(' ', $parts);

            if (in_array($candidateOrdering, $this->filter_fields, true)
                && in_array($candidateDirection, ['ASC', 'DESC'], true)) {
                $listOrdering = $candidateOrdering;
                $listDirection = $candidateDirection;
            }
        }

        $limit = isset($list['limit']) ? max(0, (int) $list['limit']) : $defaultLimit;
        $start = max(0, (int) ($request['limitstart'] ?? 0));
        $start = $limit > 0 ? (int) (floor($start / $limit) * $limit) : 0;

        $this->setState('list.ordering', $listOrdering);
        $this->setState('list.direction', $listDirection);
        $this->setState('list.limit', $limit);
        $this->setState('list.start', $start);
    }

    protected function loadFormData(): object
    {
        return (object) [
            'filter' => (object) [
                'search'    => $this->getState('filter.search'),
                'published' => $this->getState('filter.published'),
            ],
            'list' => (object) [
                'fullordering' => $this->getState('list.ordering') . ' ' . $this->getState('list.direction'),
                'limit'        => $this->getState('list.limit'),
            ],
        ];
    }
}
