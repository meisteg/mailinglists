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
 * @version $Id: remove_user.php,v 1.1 2008/06/11 02:53:33 blindman1344 Exp $
 * @author  Greg Meiste <blindman1344 [at] users dot sourceforge dot net>
 */

function mailinglists_remove_user($user_id)
{
    PHPWS_Core::initModClass('mailinglists', 'Subscriber.php');

    $db = new PHPWS_DB('mailinglists_subscribers');
    $db->addWhere('user_id', $user_id);
    $subscribers = $db->getObjects('MailingLists_Subscriber');

    if (!PHPWS_Error::logIfError($subscribers) && !empty($subscribers))
    {
        foreach ($subscribers as $sub)
        {
            /*
             * Need to pass the active key in case user performing delete doesn't
             * have Mailing Lists permissions.
             */
            $sub->kill($sub->getActiveKey());
        }
    }
}

?>