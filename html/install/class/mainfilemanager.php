<?php
//  ------------------------------------------------------------------------ //
//                XOOPS - PHP Content Management System                      //
//                    Copyright (c) 2000 XOOPS.org                           //
//                       <http://www.xoops.org/>                             //
//  ------------------------------------------------------------------------ //
//  This program is free software; you can redistribute it and/or modify     //
//  it under the terms of the GNU General Public License as published by     //
//  the Free Software Foundation; either version 2 of the License, or        //
//  (at your option) any later version.                                      //
//                                                                           //
//  You may not change or alter any portion of this comment or credits       //
//  of supporting developers from this source code or any supporting         //
//  source code which is considered copyrighted (c) material of the          //
//  original comment or credit authors.                                      //
//                                                                           //
//  This program is distributed in the hope that it will be useful,          //
//  but WITHOUT ANY WARRANTY; without even the implied warranty of           //
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            //
//  GNU General Public License for more details.                             //
//                                                                           //
//  You should have received a copy of the GNU General Public License        //
//  along with this program; if not, write to the Free Software              //
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA //
//  ------------------------------------------------------------------------ //

/**
 * mainfile manager for XOOPS installer
 *
 * @author Haruki Setoyama  <haruki@planewave.org>
 * @version $Id: mainfilemanager.php,v 1.1 2007/05/15 02:35:13 minahito Exp $
 * @access public
 **/
class mainfile_manager
{

    public $path = '../mainfile.php';
    public $distfile = '../mainfile.dist.php';
    public $rewrite = [];

    public $report = [];
    public $error = false;

    public function setRewrite($def, $val)
    {
        $this->rewrite[$def] = $val;
    }

    public function copyDistFile()
    {
        if (!copy($this->distfile, $this->path)) {
            $this->report[] = _NGIMG . sprintf(_INSTALL_L126, '<b>' . $this->path . '</b>');
            $this->error = true;
            return false;
        }
        $this->report[] = _OKIMG . sprintf(_INSTALL_L125, '<b>' . $this->path . '</b>', '<b>' . $this->distfile . '</b>');
        return true;
    }

    public function doRewrite()
    {
        if (!$file = fopen($this->path, 'r')) {
            $this->error = true;
            return false;
        }
        clearstatcache();
        $content = fread($file, filesize($this->path));
        fclose($file);

        foreach ($this->rewrite as $key => $val) {
            if (is_int($val) &&
                preg_match("/(define\()([\"'])(" . $key . ")\\2,\s*([0-9]+)\s*\)/", $content)) {
                $content = preg_replace("/(define\()([\"'])(" . $key . ")\\2,\s*([0-9]+)\s*\)/", "define('" . $key . "', " . $val . ')', $content);
                $this->report[] = _OKIMG . sprintf(_INSTALL_L121, "<b>$key</b>", $val);
            } elseif (preg_match("/(define\()([\"'])(" . $key . ")\\2,\s*([\"'])(.*?)\\4\s*\)/", $content)) {
                if ('XOOPS_DB_TYPE' === $key && 'mysql' === $val) {
                    $content = preg_replace("/(define\()([\"'])(" . $key . ")\\2,\s*([\"'])(.*?)\\4\s*\)/", "extension_loaded('mysql')? define('XOOPS_DB_TYPE', 'mysql') : define('XOOPS_DB_TYPE', 'mysqli')", $content);
                    $this->report[] = _OKIMG . sprintf(_INSTALL_L121, '<b>' . $key . '</b>', $val);
                } else {
                    $content = preg_replace("/(define\()([\"'])(" . $key . ")\\2,\s*([\"'])(.*?)\\4\s*\)/", "define('" . $key . "', '" . $this->sanitize($val) . "')", $content);
                    $this->report[] = _OKIMG . sprintf(_INSTALL_L121, '<b>' . $key . '</b>', $val);
                }
            } else {
                $this->error = true;
                $this->report[] = _NGIMG . sprintf(_INSTALL_L122, '<b>' . $val . '</b>');
            }
        }

        if (!$file = fopen($this->path, 'wb')) {
            $this->error = true;
            return false;
        }

        if (fwrite($file, $content) === -1) {
            fclose($file);
            $this->error = true;
            return false;
        }

        fclose($file);

        return true;
    }

    public function report()
    {
        return $this->report;
    }

    public function error()
    {
        return $this->error;
    }

    public function sanitize($val)
    {
        $val = addslashes($val);
        return str_replace('$', '\$', $val);
    }
}
