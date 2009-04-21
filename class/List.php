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
 * @version $Id: List.php,v 1.2 2008/09/16 04:02:23 blindman1344 Exp $
 * @author  Greg Meiste <blindman1344 [at] users dot sourceforge dot net>
 */

class MailingLists_List
{
    var $id                = 0;
    var $name              = NULL;
    var $description       = NULL;
    var $active            = 1;
    var $archive_link      = 1;
    var $double_opt_in     = 1;
    var $s_email           = 1;
    var $u_email           = 1;
    var $opt_in_msg        = MAILINGLISTS_DEFAULT_OPT_IN_MSG;
    var $subscribe_msg     = MAILINGLISTS_DEFAULT_SUBSCRIBE_MSG;
    var $unsubscribe_msg   = MAILINGLISTS_DEFAULT_UNSUBSCRIBE_MSG;
    var $created           = 0;
    var $from_name         = NULL;
    var $from_email        = NULL;
    var $subject_prefix    = NULL;


    function MailingLists_List($id=NULL)
    {
        if (empty($id))
        {
            /* Have to set these default values here in the contructor due to the function calls. */
            $this->from_name = Layout::getPageTitle(true);
            $this->from_email = PHPWS_User::getUserSetting('site_contact');
        }
        else
        {
            $this->setId($id);

            $db = new PHPWS_DB('mailinglists_lists');
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

    function setName($name)
    {
        $this->name = PHPWS_Text::parseInput(strip_tags($name));
    }

    function getName()
    {
        return PHPWS_Text::parseOutput($this->name);
    }

    function setDescription($description)
    {
        $this->description = PHPWS_Text::parseInput($description);
    }

    function getDescription($format=true)
    {
        if ($format)
        {
            return PHPWS_Text::parseOutput($this->description);
        }

        return PHPWS_Text::decodeText($this->description);
    }

    function getActive()
    {
        $active = dgettext('mailinglists', 'Active');
        $inactive = dgettext('mailinglists', 'Inactive');

        if (Current_User::allow('mailinglists', 'hide_lists', $this->id))
        {
            $vars['list_id'] = $this->getId();
            $vars['action'] = 'hideList';

            return PHPWS_Text::secureLink(($this->active ? $active : $inactive), 'mailinglists', $vars);
        }

        return ($this->active ? $active : $inactive);
    }

    function setArchiveLink($archive_link)
    {
        $this->archive_link = (int)$archive_link;
    }

    function getArchiveLink()
    {
        return $this->archive_link;
    }

    function setDoubleOptIn($double_opt_in)
    {
        $this->double_opt_in = (int)$double_opt_in;
    }

    function getDoubleOptIn()
    {
        return $this->double_opt_in;
    }

    function setSubscribeEmail($s_email)
    {
        $this->s_email = (int)$s_email;
    }

    function getSubscribeEmail()
    {
        return $this->s_email;
    }

    function setUnsubscribeEmail($u_email)
    {
        $this->u_email = (int)$u_email;
    }

    function getUnsubscribeEmail()
    {
        return $this->u_email;
    }

    function setOptInMsg($opt_in_msg)
    {
        $this->opt_in_msg = PHPWS_Text::parseInput(strip_tags($opt_in_msg));
    }

    function getOptInMsg($format=TRUE)
    {
        if ($format)
        {
            return PHPWS_Text::parseOutput($this->opt_in_msg);
        }

        return PHPWS_Text::decodeText($this->opt_in_msg);
    }

    function setSubscribeMsg($subscribe_msg)
    {
        $this->subscribe_msg = PHPWS_Text::parseInput(strip_tags($subscribe_msg));
    }

    function getSubscribeMsg($format=true)
    {
        if ($format)
        {
            return PHPWS_Text::parseOutput($this->subscribe_msg);
        }

        return PHPWS_Text::decodeText($this->subscribe_msg);
    }

    function setUnsubscribeMsg($unsubscribe_msg)
    {
        $this->unsubscribe_msg = PHPWS_Text::parseInput(strip_tags($unsubscribe_msg));
    }

    function getUnsubscribeMsg($format=true)
    {
        if ($format)
        {
            return PHPWS_Text::parseOutput($this->unsubscribe_msg);
        }

        return PHPWS_Text::decodeText($this->unsubscribe_msg);
    }

    function setCreated()
    {
        if (!$this->getId())
        {
            $this->created = mktime();
        }
    }

    function getCreated($format=MAILINGLISTS_DATE_FORMAT)
    {
        return strftime($format, PHPWS_Time::getUserTime($this->created));
    }

    function setFromName($from_name)
    {
        if (!empty($from_name))
        {
            $this->from_name = PHPWS_Text::parseInput(strip_tags($from_name));
        }
    }

    function getFromName($format=true)
    {
        /* Do not call parseOutput here!  Breaks the sending of emails. */
        if (($format) && !defined(ALLOW_PROFANITY))
        {
            return PHPWS_Text::profanityFilter(PHPWS_Text::decodeText($this->from_name));
        }

        return PHPWS_Text::decodeText($this->from_name);
    }

    function setFromEmail($from_email)
    {
        PHPWS_Core::initCoreClass('Mail.php');
        if (PHPWS_Mail::checkAddress($from_email))
        {
            $this->from_email = $from_email;
        }
    }

    function getFromEmail()
    {
        return $this->from_email;
    }

    function setSubjectPrefix($subject_prefix)
    {
        $this->subject_prefix = PHPWS_Text::parseInput(strip_tags($subject_prefix));
    }

    function getSubjectPrefix($format=true)
    {
        /* Do not call parseOutput here!  Breaks the sending of emails. */
        if (($format) && !defined(ALLOW_PROFANITY))
        {
            return PHPWS_Text::profanityFilter(PHPWS_Text::decodeText($this->subject_prefix));
        }

        return PHPWS_Text::decodeText($this->subject_prefix);
    }

    function save()
    {
        if (!Current_User::authorized('mailinglists', 'edit_lists'))
        {
            Current_User::disallow();
            return;
        }

        $this->setCreated();

        $db = new PHPWS_DB('mailinglists_lists');
        $result = $db->saveObject($this);
        if (PEAR::isError($result))
        {
            return $result;
        }
    }

    function toggle()
    {
        if (!Current_User::authorized('mailinglists', 'hide_lists'))
        {
            Current_User::disallow();
            return;
        }

        $this->active = ($this->active ? 0 : 1);
        return $this->save();
    }

    function clearSubscribers()
    {
        $db = new PHPWS_DB('mailinglists_subscribers');
        $db->addWhere('list_id', $this->getId());
        PHPWS_Error::logIfError($db->delete());
    }

    function clearEmails()
    {
        $db = new PHPWS_DB('mailinglists_emails');
        $db->addWhere('list_id', $this->getId());
        PHPWS_Error::logIfError($db->delete());
    }

    function clearQueue()
    {
        $db = new PHPWS_DB('mailinglists_queue');
        $db->addWhere('list_id', $this->getId());
        PHPWS_Error::logIfError($db->delete());
    }

    function kill()
    {
        if (!Current_User::authorized('mailinglists', 'delete_lists'))
        {
            Current_User::disallow();
            return;
        }

        $this->clearSubscribers();
        $this->clearEmails();
        $this->clearQueue();

        $db = new PHPWS_DB('mailinglists_lists');
        $db->addWhere('id', $this->id);

        PHPWS_Error::logIfError($db->delete());
    }

    function getNumSubscribers($format=true)
    {
        $retval = ($format ? dgettext('mailinglists', 'N/A') : 0);

        $db = new PHPWS_DB('mailinglists_subscribers');
        $db->addWhere('list_id', $this->getId());
        $result = $db->count();
        if (!PHPWS_Error::logIfError($result))
        {
            $retval = $result;
        }

        if ($format && Current_User::allow('mailinglists', 'subscriber_admin'))
        {
            $vars['list_id'] = $this->getId();
            $vars['action'] = 'subscriberAdmin';
            return PHPWS_Text::secureLink($retval, 'mailinglists', $vars);
        }

        return $retval;
    }

    function getLastSent($format=MAILINGLISTS_DATE_FORMAT)
    {
        $db = new PHPWS_DB('mailinglists_emails');
        $db->addWhere('list_id', $this->getId());
        $db->addWhere('approved', 1);
        $db->addColumn('created');
        $db->addOrder('created desc');
        $result = $db->select('one');
        if (PHPWS_Error::logIfError($result) || empty($result))
        {
            return dgettext('mailinglists', 'N/A');
        }

        return strftime($format, PHPWS_Time::getUserTime($result));
    }

    function getTpl()
    {
        $vars['list_id'] = $this->getId();

        if (Current_User::allow('mailinglists', 'send_emails') &&
            ($this->getNumSubscribers(false) > 0) &&
            ($this->active))
        {
            $vars['action'] = 'emailList';
            $links[] = PHPWS_Text::secureLink(dgettext('mailinglists', 'Send Email'), 'mailinglists', $vars);
        }

        $vars['action'] = 'archiveList';
        $links[] = PHPWS_Text::secureLink(dgettext('mailinglists', 'Archive'), 'mailinglists', $vars);

        if (Current_User::allow('mailinglists', 'edit_lists'))
        {
            $vars['action'] = 'editList';
            $links[] = PHPWS_Text::secureLink(dgettext('mailinglists', 'Edit'), 'mailinglists', $vars);
        }

        if (Current_User::allow('mailinglists', 'delete_lists'))
        {
            $vars['action'] = 'deleteList';
            $confirm_vars['QUESTION'] = dgettext('mailinglists',
                                                 'Are you sure you want to permanently delete this mailing list?');
            $confirm_vars['ADDRESS'] = PHPWS_Text::linkAddress('mailinglists', $vars, TRUE);
            $confirm_vars['LINK'] = dgettext('mailinglists', 'Delete');
            $links[] = javascript('confirm', $confirm_vars);
        }

        $template['ACTION'] = implode(' | ', $links);
        $template['NAME'] = $this->getName();
        $template['SUBSCRIBERS'] = $this->getNumSubscribers();
        $template['CREATED'] = $this->getCreated();
        $template['LAST_SENT'] = $this->getLastSent();
        $template['ACTIVE'] = $this->getActive();

        return $template;
    }

    function getPublicTpl()
    {
        $db = new PHPWS_DB('mailinglists_subscribers');
        $db->addWhere('list_id', $this->getId());
        $db->addWhere('user_id', Current_User::getId());
        $db->addColumn('html');
        $db->addColumn('active');
        $result = $db->select();

        $vars['action'] = 'user';
        $vars['tab'] = 'mailinglists';
        $vars['list_id'] = $this->getId();

        if (!empty($result))
        {
            if ($result[0]['active'])
            {
                if (PHPWS_Settings::get('mailinglists', 'user_send') != 0)
                {
                    $vars['user_op'] = 'emailList';
                    $links[] = PHPWS_Text::moduleLink(dgettext('mailinglists', 'Send Email'), 'users', $vars);
                }

                if ($this->getArchiveLink())
                {
                    $vars['user_op'] = 'archive';
                    $links[] = PHPWS_Text::moduleLink(dgettext('mailinglists', 'Archive'), 'users', $vars);
                }

                $vars['user_op'] = 'toggleFormat';
                if ($result[0]['html'])
                {
                    $format = dgettext('mailinglists', 'HTML');
                    $links[] = PHPWS_Text::moduleLink(dgettext('mailinglists', 'Text Format'), 'users', $vars);
                }
                else
                {
                    $format = dgettext('mailinglists', 'Text');
                    $links[] = PHPWS_Text::moduleLink(dgettext('mailinglists', 'HTML Format'), 'users', $vars);
                }

                $template['STATUS'] = sprintf(dgettext('mailinglists', 'Subscribed (%s)'), $format);
            }
            else
            {
                $template['STATUS'] = dgettext('mailinglists', 'Verifying');
            }

            $vars['user_op'] = 'unsubscribe';
            $confirm_vars['QUESTION'] = dgettext('mailinglists',
                                                 'Are you sure you want to unsubscribe from this mailing list?');
            $confirm_vars['ADDRESS'] = PHPWS_Text::linkAddress('users', $vars);
            $confirm_vars['LINK'] = dgettext('mailinglists', 'Unsubscribe');
            $links[] = javascript('confirm', $confirm_vars);
        }
        else
        {
            $vars['user_op'] = 'subscribe';
            $links[] = PHPWS_Text::moduleLink(dgettext('mailinglists', 'Subscribe'), 'users', $vars);
            $template['STATUS'] = '';
        }

        $template['ACTION'] = implode(' | ', $links);
        $template['NAME'] = $this->getName();
        $template['DESCRIPTION'] = $this->getDescription();
        $template['ACTIVE'] = $this->getActive();

        return $template;
    }
}

?>