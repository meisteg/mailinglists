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
 * @version $Id: controlpanel.php,v 1.3 2005/09/12 00:36:10 blindman1344 Exp $
 * @author  Greg Meiste <blindman1344 [at] users dot sourceforge dot net>
 */

$image['name'] = 'mailinglists.png';
$image['alt'] = 'Manage Mailing Lists';

$image2['name'] = 'mailinglists.png';
$image2['alt'] = 'Mailing Lists';


$link[] = array('label'=>'Mailing Lists Admin',
		'module'=>'mailinglists',
		'description'=>'Lets you create and manage Mailing Lists.  Send the mass mailings here!',
		'url'=>'index.php?module=mailinglists&amp;op=admin',
		'image'=>$image,
		'admin'=>TRUE,
		'tab'=>'administration');

$link[] = array('label'=>'Site Mailing Lists',
		'module'=>'mailinglists',
		'description'=>'Lets you subscribe and unsubscribe from website mailing lists.',
		'url'=>'index.php?module=mailinglists&amp;op=user',
		'image'=>$image2,
		'admin'=>FALSE,
		'tab'=>'my_settings');

?>