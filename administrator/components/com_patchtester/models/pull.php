 * @package		PatchTester
 * @copyright	Copyright (C) 2011 Ian MacLennan, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 * @package	    PatchTester

		$this->setState('github_user', $params->get('org'));
		$this->setState('github_repo', $params->get('repo'));
		foreach ($patch AS $line) {
					if (strpos($line, 'diff --git') === 0) {
					if (strpos($line, 'index') === 0) {
					if (strpos($line, '---') === 0) {
					if (strpos($line, '+++') === 0) {
					if (strpos($line, 'new file mode') === 0) {
					if (strpos($line, 'deleted file mode') === 0) {
					if (strpos($line, '@@') === 0) {
		jimport('joomla.client.github');
		jimport('joomla.client.http');
		$github = new JGithub();
		$patchUrl = $pull->diff_url;
		$http = new JHttp;
		$patch = $http->get($patchUrl)->body;
		$patch = explode("\n", $patch);


		if (is_null($pull->head->repo)) {
			$this->setError(JText::_('COM_PATCHTESTER_REPO_IS_GONE'));
			return false;
		}
		foreach($files AS $file) {
			if ($file->action == 'added' || $file->action == 'modified') {
				$http = new JHttp;

				$url = 'https://raw.github.com/' . $pull->head->user->login . '/' . $pull->head->repo->name . '/' .
				$pull->head->ref . '/' . $file->new;

				if ($file->action != 'deleted' && file_exists(JPATH_COMPONENT . '/backups/' . md5($file->new) . '.txt')) {
					$this->setError(JText::_('COM_PATCHTESTER_CONFLICT'));
					return false;
				if (($file->action == 'deleted' || $file->action == 'modified') && !file_exists(JPATH_ROOT . '/' . $file->old)) {
					$this->setError(JText::_('COM_PATCHTESTER_FILE_DELETED_MODIFIED_DOES_NOT_EXIST'));
					return false;
				try {
					$file->body = $http->get($url)->body;
				} catch (Exception $e) {
					$this->setError(JText::_('COM_PATCHTESTER_APPLY_FAILED_ERROR_RETRIEVING_FILE'));
					return false;
				}
		foreach ($files AS $file)
			if ($file->action == 'deleted' || (file_exists(JPATH_ROOT . '/' . $file->new) && $file->action == 'modified')) {
				JFile::copy(JPath::clean(JPATH_ROOT . '/' . $file->old), JPATH_COMPONENT . '/backups/' . md5($file->old) . '.txt');
					JFile::write(JPath::clean(JPATH_ROOT . '/' . $file->new), $file->body);
					JFile::delete(JPATH::clean(JPATH_ROOT . '/' . $file->old));
		$result = $table->store();
		if ($result) {
			return true;
		} else {
			return false;
		if ($table->applied_version != $version->getShortVersion()) {
		foreach ($files AS $file) {
			switch ($file->action) {
					JFile::copy(JPATH_COMPONENT . '/backups/' . md5($file->old) . '.txt', JPATH_ROOT . '/' . $file->old);
					JFile::delete(JPATH_COMPONENT . '/backups/' . md5($file->old) . '.txt');
					JFile::delete(JPath::clean(JPATH_ROOT . '/' . $file->new));