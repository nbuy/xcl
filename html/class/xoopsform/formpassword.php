<?php
/**
 * *
 *  * Form password field
 *  *
 *  * @package    kernel
 *  * @subpackage form
 *  * @author     Original Authors: Kazumi Ono (aka onokazu)
 *  * @author     Other Authors : Minahito
 *  * @copyright  2000-2020 The XOOPSCube Project
 *  * @license    Legacy : https://github.com/xoopscube/xcl/blob/master/GPL_V2.txt
 *  * @license    Cube : https://github.com/xoopscube/xcl/blob/master/BSD_license.txt
 *  * @version    Release: @package_230@
 *  * @link       https://github.com/xoopscube/xcl
 * *
 */

if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

class XoopsFormPassword extends XoopsFormElement
{

    /**
     * Size of the field.
     * @var	int
     * @access	private
     */
    public $_size;

    /**
     * Maximum length of the text
     * @var	int
     * @access	private
     */
    public $_maxlength;

    /**
     * Initial content of the field.
     * @var	string
     * @access	private
     */
    public $_value;

    /**
     * Constructor
     *
     * @param string $caption         Caption
     * @param string $name            "name" attribute
     * @param int    $size            Size of the field
     * @param int    $maxlength       Maximum length of the text
     * @param string $value           Initial value of the field.
     *                                <b>Warning:</b> this is readable in cleartext in the page's source!
     */
    public function __construct($caption, $name, $size, $maxlength, $value= '')
    {
        $this->setCaption($caption);
        $this->setName($name);
        $this->_size = (int)$size;
        $this->_maxlength = (int)$maxlength;
        $this->setValue($value);
    }
    public function XoopsFormPassword($caption, $name, $size, $maxlength, $value= '')
    {
        return $this->__construct($caption, $name, $size, $maxlength, $value);
    }

    /**
     * Get the field size
     *
     * @return	int
     */
    public function getSize()
    {
        return $this->_size;
    }

    /**
     * Get the max length
     *
     * @return	int
     */
    public function getMaxlength()
    {
        return $this->_maxlength;
    }

    /**
     * Get the initial value
     *
     * @return	string
     */
    public function getValue()
    {
        return $this->_value;
    }

    /**
     * Set the initial value
     *
     * @patam    $value    string
     * @param $value
     */
    public function setValue($value)
    {
        $this->_value = $value;
    }

    /**
     * Prepare HTML for output
     *
     * @return	string	HTML
     */
    public function render()
    {
        $root =& XCube_Root::getSingleton();
        $renderSystem =& $root->getRenderSystem(XOOPSFORM_DEPENDENCE_RENDER_SYSTEM);

        $renderTarget =& $renderSystem->createRenderTarget('main');

        $renderTarget->setAttribute('legacy_module', 'legacy');
        $renderTarget->setTemplateName('legacy_xoopsform_password.html');
        $renderTarget->setAttribute('element', $this);

        $renderSystem->render($renderTarget);

        return $renderTarget->getResult();
    }
}
