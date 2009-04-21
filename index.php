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
 * @version $Id: index.php,v 1.27 2008/01/02 21:59:23 blindman1344 Exp $
 * @author  Greg Meiste <blindman1344 [at] users dot sourceforge dot net>
 */

if (!defined('PHPWS_SOURCE_DIR'))
{
    include '../../config/core/404.html';
    exit();
}

if (isset($_REQUEST['user_op']))
{
    PHPWS_Core::initModClass('mailinglists', 'MyPage.php');

    $template['TITLE'] = dgettext('mailinglists', 'Mailing Lists');
    MailingLists_MyPage::action($template['TITLE'], $template['CONTENT']);
    Layout::add(PHPWS_Template::process($template, 'layout', 'box.tpl'), 'mailinglists', 'bodybox', true);
}
else
{
    PHPWS_Core::initModClass('mailinglists', 'MailingListsManager.php');
    MailingLists_Manager::action();
}

?>