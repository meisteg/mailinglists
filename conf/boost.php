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
 * @version $Id: boost.php,v 1.12 2005/09/12 00:36:10 blindman1344 Exp $
 * @author  Greg Meiste <blindman1344 [at] users dot sourceforge dot net>
 */

$mod_title = 'mailinglists';
$mod_pname = 'Mailing Lists';
$mod_directory = 'mailinglists';
$mod_filename = 'index.php';
$allow_view = array('home'=>1, 'mailinglists'=>1);
$user_op = '&op=user';
$user_mod = 1;
$admin_op = '&op=admin';
$admin_mod = 1;
$mod_icon = 'mailinglists.png';
$active = 'on';
$priority = 50;
$version = '0.5.6';

$mod_class_files = array('mailinglists.php');

$mod_sessions = array('SES_MAILINGLISTS_MANAGER');

?>