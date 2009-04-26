<?php

/**
 * Mailing Lists - phpWebSite Module
 *
 * See docs/credits.txt for copyright information
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @version $Id: install.php,v 1.9 2005/09/12 00:36:09 blindman1344 Exp $
 * @author  Greg Meiste <blindman1344 [at] users dot sourceforge dot net>
 */

if (!$_SESSION['OBJ_user']->isDeity()){
  header('location:index.php');
  exit();
}

$status = 0;

// Need to do core version check
if(version_compare($GLOBALS['core']->version, '0.9.3-4') < 0) {
    $content .= 'This module requires a phpWebSite core version of 0.9.3-4 or greater to install.<br />';
    $content .= 'You are currently using phpWebSite core version ' . $GLOBALS['core']->version . '.<br />';
    return;
}

if($status = $GLOBALS['core']->sqlImport(PHPWS_SOURCE_DIR . 'mod/mailinglists/boost/install.sql', TRUE)) {
  $content .= 'All Mailing List tables successfully written.<br />';
} else {
  $content .= 'There was a problem writing to the database.<br />';
}

?>