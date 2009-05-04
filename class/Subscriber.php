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

class MailingLists_Subscriber
{
    var $id          = 0;
    var $user_id     = 0;
    var $email       = NULL;
    var $list_id     = 0;
    var $html        = 1;
    var $active      = 0;
    var $active_key  = 0;
    var $subscribed  = 0;


    function MailingLists_Subscriber($id=NULL)
    {
        if (!empty($id))
        {
            $this->setId($id);

            $db = new PHPWS_DB('mailinglists_subscribers');
            PHPWS_Error::logIfError($db->loadObject($this));
        }
    }

    function setId($id)
    {
        $this->id = (int)$id;
    }

    function getId()
    {
        return $this->id;
    }

    function setUserId($user_id)
    {
        $this->user_id = (int)$user_id;
    }

    function getUserId()
    {
        return $this->user_id;
    }

    function setEmail($email)
    {
        PHPWS_Core::initCoreClass('Mail.php');
        if (PHPWS_Mail::checkAddress($email))
        {
            $this->email = $email;
        }
    }

    function getEmail()
    {
        return $this->email;
    }

    function setListId($list_id)
    {
        $this->list_id = (int)$list_id;
    }

    function getListId()
    {
        return $this->list_id;
    }

    function setHtml($html)
    {
        $this->html = (int)$html;
    }

    function getHtml($format=true)
    {
        if ($format)
        {
            if ($this->html)
            {
                return dgettext('mailinglists', 'Yes');
            }

            return dgettext('mailinglists', 'No');
        }

        return $this->html;
    }

    function setActive($active)
    {
        $this->active = (int)$active;
    }

    function getActive($format=true)
    {
        if ($format)
        {
            if ($this->active)
            {
                return dgettext('mailinglists', 'Yes');
            }

            return dgettext('mailinglists', 'No');
        }

        return $this->active;
    }

    function setActiveKey()
    {
        if (!$this->getId())
        {
            $this->active_key = rand(1000000,999999999);
        }
    }

    function getActiveKey()
    {
        return $this->active_key;
    }

    function setSubscribed()
    {
        if (!$this->getId())
        {
            $this->subscribed = mktime();
        }
    }

    function getSubscribed($format=MAILINGLISTS_DATE_FORMAT)
    {
        return strftime($format, PHPWS_Time::getUserTime($this->subscribed));
    }

    function getUser()
    {
        if ($this->getUserId() > 0)
        {
            $db = new PHPWS_DB('users');
            $db->addWhere('id', $this->getUserId());
            $db->addColumn('display_name');
            $result = $db->select('col');

            if (PHPWS_Error::logIfError($result) || empty($result))
            {
                return dgettext('mailinglists', 'N/A');
            }

            return $result[0];
        }

        return $this->email;
    }

    function save()
    {
        if (!Current_User::authorized('mailinglists', 'subscriber_admin') &&
            !Current_User::isUser($this->getUserId()) &&
            !(($this->getUserId() == 0) && PHPWS_Settings::get('mailinglists', 'anon_subscribe')))
        {
            Current_User::disallow();
            return;
        }

        if ((($this->user_id != 0) || ($this->email != NULL)) && ($this->list_id != 0))
        {
            $this->setSubscribed();
            $this->setActiveKey();

            $db = new PHPWS_DB('mailinglists_subscribers');
            $result = $db->saveObject($this);
            if (PEAR::isError($result))
            {
                return $result;
            }
        }
        else
        {
            return PHPWS_Error::get(MAILINGLISTS_OBJ_INCOMPLETE, 'mailinglists', 'MailingLists_Subscriber::save');
        }
    }

    function clearQueue()
    {
        $db = new PHPWS_DB('mailinglists_queue');
        $db->addWhere('subscriber_id', $this->getId());
        PHPWS_Error::logIfError($db->delete());
    }

    function kill($key=NULL)
    {
        if (!Current_User::authorized('mailinglists', 'subscriber_admin') &&
            !Current_User::isUser($this->getUserId()) &&
            ($key != $this->getActiveKey()))
        {
            Current_User::disallow();
            return;
        }

        $this->clearQueue();

        $db = new PHPWS_DB('mailinglists_subscribers');
        $db->addWhere('id', $this->getId());

        PHPWS_Error::logIfError($db->delete());
    }

    function getTpl()
    {
        $vars['subscriber_id'] = $this->getId();
        $vars['list_id'] = $this->getListId();
        $vars['action'] = 'unsubscribeSubscriberAdmin';
        $confirm_vars['QUESTION'] = dgettext('mailinglists',
                                             'Are you sure you want to unsubscribe this user from the list?');
        $confirm_vars['ADDRESS'] = PHPWS_Text::linkAddress('mailinglists', $vars, true);
        $confirm_vars['LINK'] = dgettext('mailinglists', 'Unsubscribe');

        $template['ACTION'] = javascript('confirm', $confirm_vars);
        $template['USER'] = $this->getUser();
        $template['HTML'] = $this->getHtml();
        $template['SUBSCRIBED'] = $this->getSubscribed();
        $template['ACTIVE'] = $this->getActive();

        return $template;
    }
}

?>