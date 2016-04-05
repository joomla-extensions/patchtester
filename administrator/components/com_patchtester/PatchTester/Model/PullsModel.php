<?php
/**
 * Patch testing component for the Joomla! CMS
 *
 * @copyright  Copyright (C) 2011 - 2012 Ian MacLennan, Copyright (C) 2013 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

namespace PatchTester\Model;

use Joomla\Registry\Registry;

use PatchTester\Helper;

/**
 * Model class for the pulls list view
 *
 * @since  2.0
 */
class PullsModel extends \JModelDatabase
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
	protected $sortFields = array('a.pull_id', 'a.title', 'applied');

	/**
	 * Instantiate the model.
	 *
	 * @param   string            $context  The model context.
	 * @param   Registry          $state    The model state.
	 * @param   \JDatabaseDriver  $db       The database adpater.
	 *
	 * @since   2.0
	 */
	public function __construct($context, Registry $state = null, \JDatabaseDriver $db = null)
	{
		parent::__construct($state, $db);

		$this->context = $context;
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
		// Get a storage key.
		$store = $this->getStoreId();

		// Try to load the data from internal storage.
		if (isset($this->cache[$store]))
		{
			return $this->cache[$store];
		}

		// Load the list items and add the items to the internal cache.
		$this->cache[$store] = $this->_getList($this->_getListQuery(), $this->getStart(), $this->getState()->get('list.limit'));

		return $this->cache[$store];
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
		// Create a new query object.
		$db    = $this->getDb();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select('a.*');
		$query->from('#__patchtester_pulls AS a');

		// Join the tests table to get applied patches
		$query->select('t.id AS applied');
		$query->join('LEFT', '#__patchtester_tests AS t ON t.pull_id = a.pull_id');

		// Filter by search
		$search = $this->getState()->get('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('a.pull_id = ' . (int) substr($search, 3));
			}
			elseif (is_numeric($search))
			{
				$query->where('a.pull_id LIKE ' . $db->quote('%' . (int) $search . '%'));
			}
			else
			{
				$query->where('(a.title LIKE ' . $db->quote('%' . $db->escape($search, true) . '%') . ')');
			}
		}

		// Filter for applied patches
		$applied = $this->getState()->get('filter.applied');

		if (!empty($applied))
		{
			// Not applied patches have a NULL value, so build our value part of the query based on this
			$value = $applied == 'no' ? ' IS NULL' : ' = 1';

			$query->where($db->quoteName('applied') . $value);
		}

		// Filter for RTC patches
		$applied = $this->getState()->get('filter.rtc');

		if (!empty($applied))
		{
			// Not applied patches have a NULL value, so build our value part of the query based on this
			$value = $applied == 'no' ? '0' : '1';

			$query->where($db->quoteName('is_rtc') . ' = ' . $value);
		}

		// Handle the list ordering.
		$ordering  = $this->getState()->get('list.ordering');
		$direction = $this->getState()->get('list.direction');

		if (!empty($ordering))
		{
			$query->order($db->escape($ordering) . ' ' . $db->escape($direction));
		}

		// If $ordering is by applied patches, then append sort on pull_id also
		if ($ordering === 'applied')
		{
			$query->order('a.pull_id ' . $db->escape($direction));
		}

		return $query;
	}

	/**
	 * Method to get a JPagination object for the data set.
	 *
	 * @return  \JPagination  A JPagination object for the data set.
	 *
	 * @since   2.0
	 */
	public function getPagination()
	{
		// Get a storage key.
		$store = $this->getStoreId('getPagination');

		// Try to load the data from internal storage.
		if (isset($this->cache[$store]))
		{
			return $this->cache[$store];
		}

		// Create the pagination object and add the object to the internal cache.
		$this->cache[$store] = new \JPagination($this->getTotal(), $this->getStart(), (int) $this->getState()->get('list.limit', 20));

		return $this->cache[$store];
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
	 * Method to get the starting number of items for the data set.
	 *
	 * @return  integer  The starting number of items available in the data set.
	 *
	 * @since   2.0
	 */
	public function getStart()
	{
		$store = $this->getStoreId('getStart');

		// Try to load the data from internal storage.
		if (isset($this->cache[$store]))
		{
			return $this->cache[$store];
		}

		$start = $this->getState()->get('list.start', 0);
		$limit = $this->getState()->get('list.limit', 20);
		$total = $this->getTotal();

		if ($start > $total - $limit)
		{
			$start = max(0, (int) (ceil($total / $limit) - 1) * $limit);
		}

		// Add the total to the internal cache.
		$this->cache[$store] = $start;

		return $this->cache[$store];
	}

	/**
	 * Method to get the total number of items for the data set.
	 *
	 * @return  integer  The total number of items available in the data set.
	 *
	 * @since   2.0
	 */
	public function getTotal()
	{
		// Get a storage key.
		$store = $this->getStoreId('getTotal');

		// Try to load the data from internal storage.
		if (isset($this->cache[$store]))
		{
			return $this->cache[$store];
		}

		// Load the total and add the total to the internal cache.
		$this->cache[$store] = (int) $this->_getListCount($this->_getListQuery());

		return $this->cache[$store];
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
		// Get the Github object
		$github = Helper::initializeGithub();

		// If on page 1, dump the old data
		if ($page === 1)
		{
			$this->getDb()->truncateTable('#__patchtester_pulls');
		}

		try
		{
			// TODO - Option to configure the batch size
			$pulls = $github->issues->getListByRepository(
				$this->getState()->get('github_user'),
				$this->getState()->get('github_repo'),
				null,
				'open',
				null,
				null,
				null,
				null,
				null,
				null,
				$page,
				100
			);
		}
		catch (\DomainException $e)
		{
			throw new \RuntimeException(\JText::sprintf('COM_PATCHTESTER_ERROR_GITHUB_FETCH', $e->getMessage()), $e->getCode(), $e);
		}

		$count = is_array($pulls) ? count($pulls) : 0;

		// If there are no pulls to insert then bail, assume we're finished
		if ($count === 0 || empty($pulls))
		{
			return array('complete' => true);
		}

		$data = array();

		foreach ($pulls as $pull)
		{
			if (isset($pull->pull_request))
			{
				// Check if this PR is RTC
				$isRTC = false;

				foreach ($pull->labels as $label)
				{
					if ($label->name === 'RTC')
					{
						$isRTC = true;

						break;
					}
				}

				// Build the data object to store in the database
				$pullData = array(
					(int) $pull->number,
					$this->getDb()->quote(\JHtml::_('string.truncate', $pull->title, 150)),
					$this->getDb()->quote(\JHtml::_('string.truncate', $pull->body, 100)),
					$this->getDb()->quote($pull->html_url),
					(int) $isRTC,
				);

				$data[] = implode($pullData, ',');
			}
		}

		$this->getDb()->setQuery(
			$this->getDb()->getQuery(true)
				->insert('#__patchtester_pulls')
				->columns(array('pull_id', 'title', 'description', 'pull_url', 'is_rtc'))
				->values($data)
		);

		try
		{
			$this->getDb()->execute();
		}
		catch (\RuntimeException $e)
		{
			throw new \RuntimeException(\JText::sprintf('COM_PATCHTESTER_ERROR_INSERT_DATABASE', $e->getMessage()), $e->getCode(), $e);
		}

		// Need to make another request
		return array('complete' => false, 'page' => ($page + 1));
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
		$this->getDb()->truncateTable('#__patchtester_pulls');
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
	protected function _getList($query, $limitstart = 0, $limit = 0)
	{
		return $this->getDb()->setQuery($query, $limitstart, $limit)->loadObjectList();
	}

	/**
	 * Returns a record count for the query.
	 *
	 * @param   \JDatabaseQuery|string  $query  The query.
	 *
	 * @return  integer  Number of rows for query.
	 *
	 * @since   2.0
	 */
	protected function _getListCount($query)
	{
		// Use fast COUNT(*) on JDatabaseQuery objects if there no GROUP BY or HAVING clause:
		if ($query instanceof \JDatabaseQuery && $query->type == 'select' && $query->group === null && $query->having === null)
		{
			$query = clone $query;
			$query->clear('select')->clear('order')->select('COUNT(*)');

			$this->getDb()->setQuery($query);

			return (int) $this->getDb()->loadResult();
		}

		// Otherwise fall back to inefficient way of counting all results.
		$this->getDb()->setQuery($query)->execute();

		return (int) $this->getDb()->getNumRows();
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
	protected function _getListQuery()
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
}
