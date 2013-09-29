	/**
	 * Array containing top level non-production folders
	 *
	 * @var    array
	 * @since  2.0
	 */
	protected $nonProductionFolders = array('build', 'docs', 'installation', 'tests');

	 * @throws  RuntimeException
		$options->set('userAgent', 'JPatchTester/2.0');
		// Make sure we can use the cURL driver
		$driver = JHttpFactory::getAvailableDriver($options, 'curl');

		if (!($driver instanceof JHttpTransportCurl))
		{
			throw new RuntimeException('Cannot use the PHP cURL adapter in this environment, cannot use patchtester', 500);
		}

		$this->transport = new JHttp($options, $driver);

						/*
						 * Check if the patch tester is running in a production environment
						 * If so, do not patch certain files as errors will be thrown
						 */
						if (!file_exists(JPATH_ROOT . '/installation/CHANGELOG'))
						{
							$filePath = explode('/', $file->new);

							if (in_array($filePath[0], $this->nonProductionFolders))
							{
								continue;
							}
						}

			if (!$files)
			{
				JFactory::getApplication()->enqueueMessage(JText::_('COM_PATCHTESTER_NO_FILES_TO_PATCH', 'message'));

				return true;
			}
