<?php
/**
 *
 * @package Legacy
 * @version $Id: BlockUninstallAction.class.php,v 1.3 2008/09/25 15:11:53 kilica Exp $
 * @copyright Copyright 2005-2020 XOOPS Cube Project  <https://github.com/xoopscube/legacy>
 * @license https://github.com/xoopscube/legacy/blob/master/docs/GPL_V2.txt GNU GENERAL PUBLIC LICENSE Version 2
 *
 */

if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

require_once XOOPS_MODULE_PATH . '/legacy/class/AbstractEditAction.class.php';
require_once XOOPS_MODULE_PATH . '/legacy/admin/forms/BlockUninstallForm.class.php';

class Legacy_BlockUninstallAction extends Legacy_AbstractEditAction
{
    public function _getId()
    {
        return isset($_REQUEST['bid']) ? xoops_getrequest('bid') : 0;
    }

    public function &_getHandler()
    {
        $handler =& xoops_getmodulehandler('newblocks');
        return $handler;
    }

    public function _setupActionForm()
    {
        $this->mActionForm =new Legacy_BlockUninstallForm();
        $this->mActionForm->prepare();
    }

    public function _isEditable()
    {
        if (is_object($this->mObject)) {
            return (1 == $this->mObject->get('visible'));
        } else {
            return false;
        }
    }

    public function executeViewInput(&$controller, &$xoopsUser, &$render)
    {
        $render->setTemplateName('block_uninstall.html');
        $render->setAttribute('actionForm', $this->mActionForm);

        //
        // lazy loading
        //
        $this->mObject->loadModule();
        $this->mObject->loadColumn();
        $this->mObject->loadCachetime();

        $render->setAttribute('object', $this->mObject);
    }

    public function executeViewSuccess(&$controller, &$xoopsUser, &$render)
    {
        $controller->executeForward('./index.php?action=BlockList');
    }

    public function executeViewError(&$controller, &$xoopsUser, &$render)
    {
        $controller->executeRedirect('./index.php?action=BlockList', 1, _MD_LEGACY_ERROR_DBUPDATE_FAILED);
    }

    public function executeViewCancel(&$controller, &$xoopsUser, &$render)
    {
        $controller->executeForward('./index.php?action=BlockList');
    }
}
