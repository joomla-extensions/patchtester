<?php
/**
 * Patch testing component for the Joomla! CMS
 *
 * @copyright  Copyright (C) 2011 - 2012 Ian MacLennan, Copyright (C) 2013 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

namespace PatchTester\Model;

use Exception;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\ListModel;
use PatchTester\GitHub\Exception\UnexpectedResponse;
use PatchTester\Helper;

/**
 * Model class for the pulls list view
 *
 * @since  2.0
 */
class PullsModel extends ListModel
{
	/**
	 * The object context
	 *
	 * @var    string
	 * @since  2.0
	 */
	protected $context;

	/**
	 * Array of fields the list can be sorted on
	 *
	 * @var    array
	 * @since  2.0
	 */
	protected $sortFields = array('pulls.pull_id', 'pulls.title');

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @since   4.0.0
	 * @throws  Exception
	 *
	 */
	public function __construct($config = [])
	{
		$config = [];

		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = [
				'applied',
				'rtc',
				'npm',
				'draft',
				'label',
				'branch',
			];
		}

		parent::__construct($config);
	}

	/**
	 * Method to get an array of data items.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since   2.0
	 */
	public function getItems()
	{
		$store = $this->getStoreId();

		if (isset($this->cache[$store]))
		{
			return $this->cache[$store];
		}

		$items = $this->getList(
			$this->getListQueryCache(), $this->getStart(),
			$this->getState()->get('list.limit')
		);

		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName(['name', 'color']))
			->from($db->quoteName('#__patchtester_pulls_labels'));

		array_walk(
			$items,
			static function ($item) use ($db, $query) {
				$query->clear('where');
				$query->where(
					$db->quoteName('pull_id') . ' = ' . $item->pull_id
				);
				$db->setQuery($query);

				$item->labels = $db->loadObjectList();
			}
		);

		$this->cache[$store] = $items;

		return $this->cache[$store];
	}

	/**
	 * Method to get a store id based on the model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param   string  $id  An identifier string to generate the store id.
	 *
	 * @return  string  A store id.
	 *
	 * @since   2.0
	 */
	protected function getStoreId($id = '')
	{
		// Add the list state to the store id.
		$id .= ':' . $this->getState()->get('list.start');
		$id .= ':' . $this->getState()->get('list.limit');
		$id .= ':' . $this->getState()->get('list.ordering');
		$id .= ':' . $this->getState()->get('list.direction');

		return md5($this->context . ':' . $id);
	}

	/**
	 * Gets an array of objects from the results of database query.
	 *
	 * @param   \JDatabaseQuery|string  $query       The query.
	 * @param   integer                 $limitstart  Offset.
	 * @param   integer                 $limit       The number of records.
	 *
	 * @return  array  An array of results.
	 *
	 * @since   2.0
	 * @throws  RuntimeException
	 */
	protected function getList($query, $limitstart = 0, $limit = 0)
	{
		return $this->getDbo()->setQuery($query, $limitstart, $limit)
			->loadObjectList();
	}

	/**
	 * Method to cache the last query constructed.
	 *
	 * This method ensures that the query is constructed only once for a given state of the model.
	 *
	 * @return  \JDatabaseQuery  A JDatabaseQuery object
	 *
	 * @since   2.0
	 */
	protected function getListQueryCache()
	{
		// Capture the last store id used.
		static $lastStoreId;

		// Compute the current store id.
		$currentStoreId = $this->getStoreId();

		// If the last store id is different from the current, refresh the query.
		if ($lastStoreId != $currentStoreId || empty($this->query))
		{
			$lastStoreId = $currentStoreId;
			$this->query = $this->getListQuery();
		}

		return $this->query;
	}

	/**
	 * Method to get a JDatabaseQuery object for retrieving the data set from a database.
	 *
	 * @return  \JDatabaseQuery  A JDatabaseQuery object to retrieve the data set.
	 *
	 * @since   2.0
	 */
	protected function getListQuery()
	{
		$db         = $this->getDbo();
		$query      = $db->getQuery(true);
		$labelQuery = $db->getQuery(true);

		$query->select('pulls.*')
			->select($db->quoteName('tests.id', 'applied'))
			->from($db->quoteName('#__patchtester_pulls', 'pulls'))
			->leftJoin(
				$db->quoteName('#__patchtester_tests', 'tests')
				. ' ON ' . $db->quoteName('tests.pull_id') . ' = '
				. $db->quoteName('pulls.pull_id')
			);

		$search = $this->getState()->get('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where(
					$db->quoteName('pulls.pull_id') . ' = ' . (int) substr(
						$search, 3
					)
				);
			}
			elseif (is_numeric($search))
			{
				$query->where(
					$db->quoteName('pulls.pull_id') . ' = ' . (int) $search
				);
			}
			else
			{
				$query->where(
					'(' . $db->quoteName('pulls.title') . ' LIKE ' . $db->quote(
						'%' . $db->escape($search, true) . '%'
					) . ')'
				);
			}
		}

		$applied = $this->getState()->get('filter.applied');

		if (!empty($applied))
		{
			// Not applied patches have a NULL value, so build our value part of the query based on this
			$value = $applied === 'no' ? ' IS NULL' : ' = 1';

			$query->where($db->quoteName('applied') . $value);
		}

		$branch = $this->getState()->get('filter.branch');

		if (!empty($branch))
		{
			$query->where(
				$db->quoteName('pulls.branch') . ' IN (' . implode(',', $db->quote($branch)) . ')'
			);
		}

		$applied = $this->getState()->get('filter.rtc');

		if (!empty($applied))
		{
			// Not applied patches have a NULL value, so build our value part of the query based on this
			$value = $applied === 'no' ? '0' : '1';

			$query->where($db->quoteName('pulls.is_rtc') . ' = ' . $value);
		}

		$npm = $this->getState()->get('filter.npm');

		if (!empty($npm))
		{
			// Not applied patches have a NULL value, so build our value part of the query based on this
			$value = $npm === 'no' ? '0' : '1';

			$query->where($db->quoteName('pulls.is_npm') . ' = ' . $value);
		}

		$draft = $this->getState()->get('filter.draft');

		if (!empty($draft))
		{
			// Not applied patches have a NULL value, so build our value part of the query based on this
			$value = $draft === 'no' ? '0' : '1';

			$query->where($db->quoteName('pulls.is_draft') . ' = ' . $value);
		}

		$labels = $this->getState()->get('filter.label');

		if (!empty($labels) && $labels[0] !== '')
		{
			$labelQuery
				->select($db->quoteName('pulls_labels.pull_id'))
				->select(
					'COUNT(' . $db->quoteName('pulls_labels.name') . ') AS '
					. $db->quoteName('labelCount')
				)
				->from(
					$db->quoteName(
						'#__patchtester_pulls_labels', 'pulls_labels'
					)
				)
				->where(
					$db->quoteName('pulls_labels.name') . ' IN (' . implode(
						',', $db->quote($labels)
					) . ')'
				)
				->group($db->quoteName('pulls_labels.pull_id'));

			$query->leftJoin(
				'(' . $labelQuery->__toString() . ') AS ' . $db->quoteName(
					'pulls_labels'
				)
				. ' ON ' . $db->quoteName('pulls_labels.pull_id') . ' = '
				. $db->quoteName('pulls.pull_id')
			)
				->where(
					$db->quoteName('pulls_labels.labelCount') . ' = ' . count(
						$labels
					)
				);
		}

		$ordering  = $this->getState()->get('list.ordering', 'pulls.pull_id');
		$direction = $this->getState()->get('list.direction', 'DESC');

		if (!empty($ordering))
		{
			$query->order(
				$db->escape($ordering) . ' ' . $db->escape($direction)
			);
		}

		return $query;
	}

	/**
	 * Retrieves the array of authorized sort fields
	 *
	 * @return  array
	 *
	 * @since   2.0
	 */
	public function getSortFields()
	{
		return $this->sortFields;
	}

	/**
	 * Method to request new data from GitHub
	 *
	 * @param   integer  $page  The page of the request
	 *
	 * @return  array
	 *
	 * @since   2.0
	 * @throws  \RuntimeException
	 */
	public function requestFromGithub($page)
	{
		if ($page === 1)
		{
			$this->getDbo()->truncateTable('#__patchtester_pulls');
			$this->getDbo()->truncateTable('#__patchtester_pulls_labels');
		}

		try
		{
			// TODO - Option to configure the batch size
			$batchSize = 100;

			$pullsResponse = Helper::initializeGithub()->getOpenPulls(
				$this->getState()->get('github_user'),
				$this->getState()->get('github_repo'),
				$page,
				$batchSize
			);

			$pulls = json_decode($pullsResponse->body);
		}
		catch (UnexpectedResponse $exception)
		{
			throw new \RuntimeException(
				Text::sprintf(
					'COM_PATCHTESTER_ERROR_GITHUB_FETCH',
					$exception->getMessage()
				),
				$exception->getCode(),
				$exception
			);
		}

		// If this is page 1, let's check to see if we need to paginate
		if ($page === 1)
		{
			// Default this to being a single page of results
			$lastPage = 1;

			if (isset($pullsResponse->headers['link']))
			{
				$linkHeader = $pullsResponse->headers['link'];

				// The `joomla/http` 2.0 package uses PSR-7 Responses which has a different format for headers, check for this
				if (is_array($linkHeader))
				{
					$linkHeader = $linkHeader[0];
				}

				preg_match(
					'/(\?page=[0-9]{1,3}&per_page=' . $batchSize
					. '+>; rel=\"last\")/', $linkHeader, $matches
				);

				if ($matches && isset($matches[0]))
				{
					$pageSegment = str_replace(
						'&per_page=' . $batchSize, '', $matches[0]
					);

					preg_match('/\d+/', $pageSegment, $pages);
					$lastPage = (int) $pages[0];
				}
			}
		}

		// If there are no pulls to insert then bail, assume we're finished
		if (count($pulls) === 0)
		{
			return ['complete' => true];
		}

		$data   = [];
		$labels = [];

		foreach ($pulls as $pull)
		{
			// Check if this PR is RTC and has a `PR-` branch label
			$isRTC  = false;
			$isNPM  = false;
			$branch = $pull->base->ref;

			foreach ($pull->labels as $label)
			{
				if (strtolower($label->name) === 'rtc')
				{
					$isRTC = true;
				}
				elseif (in_array(
					strtolower($label->name),
					['npm resource changed', 'composer dependency changed'],
					true
				))
				{
					$isNPM = true;
				}

				$labels[] = implode(
					',',
					[
						(int) $pull->number,
						$this->getDbo()->quote($label->name),
						$this->getDbo()->quote($label->color),
					]
				);
			}

			// Build the data object to store in the database
			$pullData = [
				(int) $pull->number,
				$this->getDbo()->quote(
					HTMLHelper::_('string.truncate', $pull->title, 150)
				),
				$this->getDbo()->quote(
					HTMLHelper::_('string.truncate', $pull->body, 100)
				),
				$this->getDbo()->quote($pull->html_url),
				(int) $isRTC,
				(int) $isNPM,
				$this->getDbo()->quote($branch),
				($pull->draft ? 1 : 0)
			];

			$data[] = implode(',', $pullData);

		}

		// If there are no pulls to insert then bail, assume we're finished
		if (count($data) === 0)
		{
			return array('complete' => true);
		}

		try
		{
			$this->getDbo()->setQuery(
				$this->getDbo()->getQuery(true)
					->insert('#__patchtester_pulls')
					->columns(
						['pull_id', 'title', 'description', 'pull_url',
						 'is_rtc', 'is_npm', 'branch', 'is_draft']
					)
					->values($data)
			);

			$this->getDbo()->execute();
		}
		catch (\RuntimeException $exception)
		{
			throw new \RuntimeException(
				Text::sprintf(
					'COM_PATCHTESTER_ERROR_INSERT_DATABASE',
					$exception->getMessage()
				),
				$exception->getCode(),
				$exception
			);
		}

		if ($labels)
		{
			try
			{
				$this->getDbo()->setQuery(
					$this->getDbo()->getQuery(true)
						->insert('#__patchtester_pulls_labels')
						->columns(['pull_id', 'name', 'color'])
						->values($labels)
				);
				$this->getDbo()->execute();
			}
			catch (\RuntimeException $exception)
			{
				throw new \RuntimeException(
					Text::sprintf(
						'COM_PATCHTESTER_ERROR_INSERT_DATABASE',
						$exception->getMessage()
					),
					$exception->getCode(),
					$exception
				);
			}
		}

		return [
			'complete' => false,
			'page'     => ($page + 1),
			'lastPage' => $lastPage ?? false,
		];
	}

	/**
	 * Truncates the pulls table
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function truncateTable()
	{
		$this->getDbo()->truncateTable('#__patchtester_pulls');
	}
}
