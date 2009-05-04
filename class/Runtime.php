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

class MailingLists_Runtime
{
    function showBlock()
    {
        $key = Key::getCurrent();
        if (!empty($key) && $key->isHomeKey() && PHPWS_Settings::get('mailinglists', 'show_block'))
        {
            if (Current_User::isLogged())
            {
                MailingLists_Runtime::showUserBlock();
            }
            else
            {
                MailingLists_Runtime::showAnonBlock();
            }
        }
    }

    function showUserBlock()
    {
        $db = new PHPWS_DB('mailinglists_lists');
        $db->addColumn('name');
        $db->addWhere('active', 1);
        $db->addWhere('mailinglists_subscribers.user_id', Current_User::getId());
        $db->addWhere('mailinglists_subscribers.list_id', 'mailinglists_lists.id');
        $result = $db->select('col');

        if (!PHPWS_Error::logIfError($result))
        {
            if (!empty($result))
            {
                foreach ($result as $list_name)
                {
                    $template['listrows'][]['LIST_NAME'] = PHPWS_Text::parseOutput($list_name);
                }
            }
            else
            {
                $template['EMPTY_MESSAGE'] = dgettext('mailinglists', 'You are not subscribed to any mailing lists.');
            }
        }

        $vars['action'] = 'user';
        $vars['tab'] = 'mailinglists';
        $template['MENU_LINK'] = PHPWS_Text::moduleLink(dgettext('mailinglists', 'Manage Subscriptions'), 'users', $vars);
        $template['TITLE'] = dgettext('mailinglists', 'Your Mailing Lists');
        Layout::add(PHPWS_Template::process($template, 'mailinglists', 'blocks/user.tpl'), 'mailinglists', 'sidebox');
    }

    function showAnonBlock()
    {
        if (PHPWS_Settings::get('mailinglists', 'anon_subscribe'))
        {
            $db = new PHPWS_DB('mailinglists_lists');
            $db->addColumn('id');
            $db->addColumn('name');
            $db->addWhere('active', 1);
            $result = $db->select();
            if (!PHPWS_Error::logIfError($result) && !empty($result))
            {
                $form = new PHPWS_Form;
                $form->addHidden('module', 'mailinglists');
                $form->addHidden('user_op', 'aSubscribe');

                if (sizeof($result) > 1)
                {
                    foreach ($result as $list)
                    {
                        $available_lists[$list['id']] = PHPWS_Text::parseOutput($list['name']);
                    }
                    $form->addSelect('list_id', $available_lists);
                    $form->setLabel('list_id', dgettext('mailinglists', 'Choose a List'));
                }
                else
                {
                    $form->addHidden('list_id', $result[0]['id']);
                }

                $form->addText('email');
                $form->setLabel('email', dgettext('mailinglists', 'Email Address'));
                $form->setSize('email', 20, 100);

                $form->addCheck('html');
                $form->setMatch('html', 1);
                $form->setLabel('html', dgettext('mailinglists', 'HTML'));

                $form->addSubmit('submit', dgettext('mailinglists', 'Subscribe'));

                $template = $form->getTemplate();
                $template['TITLE'] = dgettext('mailinglists', 'Subscribe');
                Layout::add(PHPWS_Template::process($template, 'mailinglists', 'blocks/anon.tpl'),
                            'mailinglists', 'sidebox');
            }
        }
    }

    function calcLimit()
    {
        $this_hour = floor(mktime()/3600);
        if (PHPWS_Settings::get('mailinglists', 'this_hour') != $this_hour)
        {
            PHPWS_Settings::set('mailinglists', 'this_hour', $this_hour);
            PHPWS_Settings::set('mailinglists', 'sent_this_hour', 0);

            PHPWS_Error::logIfError(PHPWS_Settings::save('mailinglists'));
        }

        /* First check if module has hit "per hour" limit. */
        if (PHPWS_Settings::get('mailinglists', 'max_per_hour') > 0)
        {
            $limit = PHPWS_Settings::get('mailinglists', 'max_per_hour');
            if ($limit > PHPWS_Settings::get('mailinglists', 'sent_this_hour'))
            {
                $limit -= PHPWS_Settings::get('mailinglists', 'sent_this_hour');
            }
            else
            {
                $limit = 0;
            }
        }

        /* Next check the "at once" setting. */
        if (PHPWS_Settings::get('mailinglists', 'max_at_once') > 0)
        {
            if (isset($limit))
            {
                $limit = min($limit, PHPWS_Settings::get('mailinglists', 'max_at_once'));
            }
            else
            {
                $limit = PHPWS_Settings::get('mailinglists', 'max_at_once');
            }
        }

        return (isset($limit) ? $limit : 100000);
    }

    function sendEmail()
    {
        /* Lock tables to prevent multiple emails sent to same subscriber. */
        $db = new PHPWS_DB('mailinglists_queue');
        $db->addTable(array('mailinglists_subscribers', 'mailinglists_lists',
                            'mailinglists_emails', 'users', 'mod_settings'));
        $db->setLock('mailinglists_queue',       'write');
        $db->setLock('mailinglists_subscribers', 'write');
        $db->setLock('mailinglists_lists',       'write');
        $db->setLock('mailinglists_emails',      'write');
        $db->setLock('users',                    'write');
        $db->setLock('mod_settings',             'write');
        $db->lockTables();

        /* Read from email queue. */
        $db_queue = new PHPWS_DB('mailinglists_queue');
        $db_queue->addWhere('mailinglists_queue.email_id', 'mailinglists_emails.id');
        $db_queue->addWhere('mailinglists_emails.approved', 1);
        $db_queue->addOrder('id asc');
        $db_queue->setLimit(MailingLists_Runtime::calcLimit());

        $result = $db_queue->select();
        if (!PHPWS_Error::logIfError($result) && (sizeof($result) > 0))
        {
            PHPWS_Core::initModClass('mailinglists', 'List.php');
            PHPWS_Core::initModClass('mailinglists', 'Email.php');
            PHPWS_Core::initModClass('mailinglists', 'Subscriber.php');

            $num_sent = 0;

            foreach ($result as $row)
            {
                $list = new MailingLists_List($row['list_id']);
                $email = new MailingLists_Email($row['email_id']);

                $active_key = 0;
                $send_to = $row['email_address'];
                if (empty($send_to))
                {
                    $subscriber = new MailingLists_Subscriber($row['subscriber_id']);
                    $active_key = $subscriber->getActiveKey();
                    $send_to = $subscriber->getEmail();
                    if (empty($send_to))
                    {
                        $db_users = new PHPWS_DB('users');
                        $db_users->addWhere('id', $subscriber->getUserId());
                        $db_users->addColumn('email');
                        $user_email = $db_users->select('one');
                        if (!PHPWS_Error::logIfError($user_email))
                        {
                            $send_to = $user_email;
                        }
                    }
                }

                if (!PHPWS_Error::logIfError($email->send($list, $send_to, isset($subscriber) ? $subscriber : NULL)))
                {
                    $num_sent++;
                }

                /* Remove from queue. */
                $db_queue->reset();
                $db_queue->addWhere($row);
                PHPWS_Error::logIfError($db_queue->delete());
            }

            /* Update count for the hour. */
            PHPWS_Settings::set('mailinglists', 'sent_this_hour',
                                PHPWS_Settings::get('mailinglists', 'sent_this_hour') + $num_sent);
            PHPWS_Error::logIfError(PHPWS_Settings::save('mailinglists'));
        }

        /* Finished, so unlock tables. */
        $db->unlockTables();
    }

    function cleanup()
    {
        /* Purge subscribers who haven't activated in over 2 days. */
        $clean_time = (mktime() - (86400*2));

        $db = new PHPWS_DB('mailinglists_subscribers');
        $db->addWhere('subscribed', $clean_time, '<');
        $db->addWhere('active', 0);
        PHPWS_Error::logIfError($db->delete());
    }
}

?>