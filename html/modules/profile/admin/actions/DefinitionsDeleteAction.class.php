<?php
/**
 * @file
 * @package profile
 * @version $Id$
 */

if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

require_once XOOPS_MODULE_PATH . '/profile/class/AbstractDeleteAction.class.php';

class Profile_Admin_DefinitionsDeleteAction extends Profile_AbstractDeleteAction
{
    /**
     * @protected
     */
    public function _getId()
    {
        return (int)xoops_getrequest('field_id');
    }

    /**
     * @protected
     */
    public function &_getHandler()
    {
        $handler =& $this->mAsset->load('handler', 'definitions');
        return $handler;
    }

    /**
     * @protected
     */
    public function _setupActionForm()
    {
        // $this->mActionForm =new Profile_Admin_DefinitionsDeleteForm();
        $this->mActionForm =& $this->mAsset->create('form', 'admin.delete_definitions');
        $this->mActionForm->prepare();
    }

    /**
     * @public
     * @param $render
     */
    public function executeViewInput(&$render)
    {
        $render->setTemplateName('definitions_delete.html');
        $render->setAttribute('actionForm', $this->mActionForm);
        #cubson::lazy_load('definitions', $this->mObject);
        $render->setAttribute('object', $this->mObject);
    }

    /**
     * @public
     * @param $render
     */
    public function executeViewSuccess(&$render)
    {
        $this->mRoot->mController->executeForward('./index.php?action=DefinitionsList');
    }

    /**
     * @public
     * @param $render
     */
    public function executeViewError(&$render)
    {
        $this->mRoot->mController->executeRedirect('./index.php?action=DefinitionsList', 1, _MD_PROFILE_ERROR_DBUPDATE_FAILED);
    }

    /**
     * @public
     * @param $render
     */
    public function executeViewCancel(&$render)
    {
        $this->mRoot->mController->executeForward('./index.php?action=DefinitionsList');
    }
}
