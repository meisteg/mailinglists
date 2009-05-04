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
 * @package Mailing_Lists
 * @author Greg Meiste <greg.meiste+github@gmail.com>
 */

$link[] = array('label'       => dgettext('mailinglists', 'Mailing Lists'),
                'restricted'  => TRUE,
                'url'         => 'index.php?module=mailinglists',
                'description' => dgettext('mailinglists', 'Create and manage Mailing Lists.  Send mass mailings here!'),
                'image'       => 'mailinglists.png',
                'tab'         => 'admin'
               );

?>