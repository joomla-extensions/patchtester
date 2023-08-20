<?php

namespace Joomla\Component\Patchtester\Administrator;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\Registry\Registry;

/**
 * @package     ${NAMESPACE}
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */
trait GithubCredentialsTrait
{
    protected function getCredentials() {
        $state = new Registry();
        $params = ComponentHelper::getParams('com_patchtester');
        $state->set('github_user', $params->get('org', 'joomla'));
        $state->set('github_repo', $params->get('repo', 'joomla-cms'));

        return $state;
    }
}
