<?php
/**
 *
 * @package Legacy
 * @version $Id: install_updateTables.inc.php,v 1.3 2008/09/25 15:12:22 kilica Exp $
 * @copyright Copyright 2005-2020 XOOPS Cube Project  <https://github.com/xoopscube/legacy>
 * @license https://github.com/xoopscube/legacy/blob/master/docs/GPL_V2.txt GNU GENERAL PUBLIC LICENSE Version 2
 *
 */
include_once '../mainfile.php';
include_once './class/dbmanager.php';

$db = new db_manager();
$sql = 'SELECT * FROM ' . $db->prefix('groups');
$result = $db->query($sql);
$content = '<h5>' . _INSTALL_L157 . '</h5>';
$content .= '<table align="center" cellspacing="0" border="1"><tr><td>' . _INSTALL_L158 . '</td><td>' . _INSTALL_L159 . '</td><td>' . _INSTALL_L160 . '</td><td>' . _INSTALL_L161 . '</td></tr>';
while ($myrow = $db->fetchArray($result)) {
    if ('Admin' === $myrow['type']) {
        $content .= '<tr><td>' . $myrow['name'] . '</td><td><input type="radio" name="g_webmasters" value="' . $myrow['groupid'] . '" /></td><td>&nbsp;</td><td>&nbsp;</td></tr>';
    } elseif ('User' === $myrow['type']) {
        $content .= '<tr><td>' . $myrow['name'] . '</td><td>&nbsp;</td><td><input type="radio" name="g_users" value="' . $myrow['groupid'] . '" /></td><td>&nbsp;</td></tr>';
    } else {
        $content .= '<tr><td>' . $myrow['name'] . '</td><td>&nbsp;</td><td>&nbsp;</td><td><input type="radio" name="g_anonymous" value="' . $myrow['groupid'] . '" /></td></tr>';
    }
}
$content .= '</table>';
$b_back = [];
$b_next = ['updateTables_go', _INSTALL_L132];

include './install_tpl.php';
