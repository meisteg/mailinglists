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
 * @version $Id: MyPage.php,v 1.4 2008/08/23 19:01:13 blindman1344 Exp $
 * @author  Greg Meiste <blindman1344 [at] users dot sourceforge dot net>
 */

PHPWS_Core::initModClass('mailinglists', 'List.php');
PHPWS_Core::initModClass('mailinglists', 'Email.php');
PHPWS_Core::initModClass('mailinglists', 'Subscriber.php');

class MailingLists_MyPage
{
    function show()
    {
        PHPWS_Core::initCoreClass('DBPager.php');

        MailingLists_MyPage::action($template['TITLE'], $template['CONTENT']);
        $template['MESSAGE'] = MailingLists_MyPage::getMessage();

        if (empty($template['CONTENT']))
        {
            $template['TITLE']       = dgettext('mailinglists', 'Manage Mailing List Subscriptions');

            $pageTags['NAME']        = dgettext('mailinglists', 'Name');
            $pageTags['DESCRIPTION'] = dgettext('mailinglists', 'Description');
            $pageTags['STATUS']      = dgettext('mailinglists', 'Status');
            $pageTags['ACTION']      = dgettext('mailinglists', 'Action');
            $pager = new DBPager('mailinglists_lists', 'MailingLists_List');
            $pager->setModule('mailinglists');
            $pager->setTemplate('list/public_list.tpl');
            $pager->addToggle(PHPWS_LIST_TOGGLE_CLASS);
            $pager->addPageTags($pageTags);
            $pager->addRowTags('getPublicTpl');
            $pager->setSearch('name', 'description');
            $pager->setDefaultOrder('name', 'asc');
            $pager->setEmptyMessage(dgettext('mailinglists', 'No lists available at this time.'));
            $pager->addWhere('active', 1);
            $pager->cacheQueries();

            $template['CONTENT'] = $pager->get();
        }

        return PHPWS_Template::process($template, 'mailinglists', 'my_page.tpl');
    }

    function action(&$title, &$content)
    {
        if (isset($_REQUEST['user_op']))
        {
            switch ($_REQUEST['user_op'])
            {
                case 'toggleFormat':
                    $list = new MailingLists_List($_REQUEST['list_id']);
                    MailingLists_MyPage::sendMessage(MailingLists_MyPage::toggleFormat($list),
                                                     dgettext('mailinglists', 'Format changed'),
                                                     dgettext('mailinglists', 'Error occurred while changing format'));
                    break;

                case 'archive':
                    $list = new MailingLists_List($_REQUEST['list_id']);
                    $title = sprintf(dgettext('mailinglists', 'Archive for %s'), $list->getName());
                    $content = MailingLists_MyPage::archive($list);
                    break;

                case 'viewEmail':
                    $email = new MailingLists_Email($_REQUEST['email_id']);
                    $title = dgettext('mailinglists', 'View Email');
                    $content = $email->view();
                    break;

                case 'emailList':
                    $list = new MailingLists_List($_REQUEST['list_id']);
                    $email = new MailingLists_Email();
                    $title = sprintf(dgettext('mailinglists', 'Send Email to %s'), $list->getName());
                    $content = MailingLists_MyPage::emailList($email, $list);
                    break;

                case 'postEmailList':
                    $list = new MailingLists_List($_REQUEST['list_id']);
                    $email = new MailingLists_Email();
                    $result = MailingLists_MyPage::postEmailList($email, $list);
                    if (is_array($result))
                    {
                        $title = sprintf(dgettext('mailinglists', 'Send Email to %s'), $list->getName());
                        $content = MailingLists_MyPage::emailList($email, $list, $result);
                    }
                    else
                    {
                        if (PHPWS_Error::logIfError($email->save()) || PHPWS_Error::logIfError($email->addQueue()))
                        {
                            MailingLists_MyPage::sendMessageOnly(dgettext('mailinglists', 'Email could not be sent'));
                        }
                        else if ($email->getApproved())
                        {
                            MailingLists_MyPage::sendMessageOnly(dgettext('mailinglists', 'Email sent'));
                        }

                        MailingLists_MyPage::sendMessageOnly(dgettext('mailinglists', 'Email ready for approval'));
                    }
                    break;

                case 'subscribe':
                    $list = new MailingLists_List($_REQUEST['list_id']);
                    MailingLists_MyPage::sendMessage(MailingLists_MyPage::subscribe($list),
                                                     dgettext('mailinglists', 'Subscribed to list'),
                                                     dgettext('mailinglists', 'Error occurred while subscribing'));
                    break;

                case 'unsubscribe':
                    $list = new MailingLists_List($_REQUEST['list_id']);
                    MailingLists_MyPage::sendMessage(MailingLists_MyPage::unsubscribe($list),
                                                     dgettext('mailinglists', 'Unsubscribed from list'),
                                                     dgettext('mailinglists', 'Error occurred while unsubscribing'));
                    break;

                case 'aSubscribe':
                    $list = new MailingLists_List($_REQUEST['list_id']);
                    $content = MailingLists_MyPage::anonSubscribe($list);
                    break;

                case 'aUnsubscribe':
                    $subscriber = new MailingLists_Subscriber($_REQUEST['subscriber_id']);
                    $content = MailingLists_MyPage::anonUnsubscribe($subscriber);
                    break;

                case 'confirm':
                    $subscriber = new MailingLists_Subscriber($_REQUEST['subscriber_id']);
                    $content = MailingLists_MyPage::confirm($subscriber);
                    break;
            }
        }
    }

    function sendMessage(&$result, $success_msg, $error_msg)
    {
        $_SESSION['mailinglists_message'] = (PHPWS_Error::logIfError($result) ? $error_msg : $success_msg);
        PHPWS_Core::reroute(PHPWS_Text::linkAddress('users', array('action'=>'user', 'tab'=>'mailinglists'), false));
    }

    function sendMessageOnly($msg)
    {
        $_SESSION['mailinglists_message'] = $msg;
        PHPWS_Core::reroute(PHPWS_Text::linkAddress('users', array('action'=>'user', 'tab'=>'mailinglists'), false));
    }

    function getMessage()
    {
        if (isset($_SESSION['mailinglists_message']))
        {
            $message = $_SESSION['mailinglists_message'];
            unset($_SESSION['mailinglists_message']);
            return $message;
        }

        return NULL;
    }

    function toggleFormat(&$list)
    {
        if (($list->active == 0) || (!Current_User::isLogged()))
        {
            Current_User::disallow();
            return;
        }

        $db = new PHPWS_DB('mailinglists_subscribers');
        $db->addWhere('list_id', $list->getId());
        $db->addWhere('user_id', Current_User::getId());
        $db->addColumn('id');
        $id = $db->select('one');

        if (PEAR::isError($id))
        {
            return $id;
        }

        $subscriber = new MailingLists_Subscriber($id);
        $subscriber->setHtml($subscriber->getHtml(false) ? 0 : 1);
        return $subscriber->save();
    }

    function archive(&$list)
    {
        if (($list->active == 0) || ($list->getArchiveLink() == 0) || (!Current_User::isLogged()))
        {
            Current_User::disallow();
            return;
        }

        PHPWS_Core::initCoreClass('DBPager.php');

        $pageTags['SUBJECT'] = dgettext('mailinglists', 'Subject');
        $pageTags['CREATED'] = dgettext('mailinglists', 'Sent');
        $pageTags['ACTION']  = dgettext('mailinglists', 'Action');
        $pager = new DBPager('mailinglists_emails', 'MailingLists_Email');
        $pager->setModule('mailinglists');
        $pager->setTemplate('email/list.tpl');
        $pager->addToggle(PHPWS_LIST_TOGGLE_CLASS);
        $pager->addPageTags($pageTags);
        $pager->addRowTags('getPublicArchivedTpl');
        $pager->setSearch('subject', 'msg_html', 'msg_text');
        $pager->setDefaultOrder('created', 'desc');
        $pager->setEmptyMessage(dgettext('mailinglists', 'No archived emails.'));
        $pager->addWhere('list_id', $list->getId());
        $pager->addWhere('approved', 1);
        $pager->cacheQueries();

        return $pager->get();
    }

    function emailList(&$email, &$list, $errors=NULL)
    {
        if ((PHPWS_Settings::get('mailinglists', 'user_send') == 0) || !Current_User::isLogged())
        {
            Current_User::disallow();
            return;
        }

        PHPWS_Core::initModClass('filecabinet', 'Cabinet.php');

        $form = new PHPWS_Form;
        $form->addHidden('module', 'users');
        $form->addHidden('action', 'user');
        $form->addHidden('tab', 'mailinglists');
        $form->addHidden('user_op', 'postEmailList');
        $form->addHidden('list_id', $list->getId());

        $form->addText('subject', $email->getSubject());
        $form->setLabel('subject', dgettext('mailinglists', 'Subject'));
        $form->setSize('subject', 40, 60);

        $form->addTextArea('msg_html', $email->getMsgHtml(false));
        $form->setRows('msg_html', MAILINGLISTS_TEXTAREA_ROWS);
        $form->setWidth('msg_html', '80%');
        $form->useEditor('msg_html');

        $manager = Cabinet::fileManager('file_id', $email->getFileId());
        $form->addTplTag('FILE_MANAGER', $manager->get());

        $form->addTextArea('msg_text', $email->getMsgText(false));
        $form->setRows('msg_text', MAILINGLISTS_TEXTAREA_ROWS);
        $form->setWidth('msg_text', '80%');

        $form->addSubmit('submit', dgettext('mailinglists', 'Send Email (Do Not Click Twice)'));

        $template = $form->getTemplate();
        $template['HTML_EMAIL_LEGEND'] = dgettext('mailinglists', 'Message (HTML)');
        $template['TEXT_EMAIL_LEGEND'] = dgettext('mailinglists', 'Message (Plain Text)');

        if (isset($errors['subject']))
        {
            $template['SUBJECT_ERROR'] = $errors['subject'];
        }
        if (isset($errors['body']))
        {
            $template['BODY_ERROR'] = $errors['body'];
        }

        return PHPWS_Template::process($template, 'mailinglists', 'email/edit.tpl');
    }

    function postEmailList(&$email, &$list)
    {
        if (empty($_POST['subject']))
        {
            $errors['subject'] = dgettext('mailinglists', 'You must have a subject.');
        }
        if (empty($_POST['msg_text']) && (strlen($_POST['msg_html']) < 15))
        {
            $errors['body'] = dgettext('mailinglists', 'You must have a message body.');
        }

        $email->setSubject($_POST['subject']);
        $email->setMsgText($_POST['msg_text']);
        $email->setMsgHtml($_POST['msg_html']);
        $email->setFileId($_POST['file_id']);
        $email->setUserId(Current_User::getId());
        $email->setListId($list->getId());
        $email->setApproved((PHPWS_Settings::get('mailinglists', 'user_send') == 2) ? 1 : 0);

        return (isset($errors) ? $errors : true);
    }

    function subscribe(&$list)
    {
        if (!Current_User::isLogged() || ($list->getId() == 0) || !$list->active)
        {
            Current_User::disallow();
            return;
        }

        /* Check if user already subscribed. */
        $db = new PHPWS_DB('mailinglists_subscribers');
        $db->addWhere('list_id', $list->getId());
        $db->addWhere('user_id', Current_User::getId());
        $count = $db->count();

        if (PEAR::isError($count))
        {
            return $count;
        }

        if ($count == 0)
        {
            $subscriber = new MailingLists_Subscriber($id);
            $subscriber->setUserId(Current_User::getId());
            $subscriber->setListId($list->getId());
            $subscriber->setHtml(1);
            $subscriber->setActive($list->getDoubleOptIn() ? 0 : 1);

            $saved = $subscriber->save();
            if (PEAR::isError($saved))
            {
                return $saved;
            }

            if ($list->getDoubleOptIn() || $list->getSubscribeEmail())
            {
                $email = new MailingLists_Email();
                $email->setMsgHtml(NULL);
                $email->setUserId(Current_User::getId());
                $email->setListId(0);  // Settings the true ID would cause this email to appear in archive
                $email->setApproved(1);

                if ($list->getDoubleOptIn())
                {
                    $vars['user_op'] = 'confirm';
                    $vars['subscriber_id'] = $subscriber->getId();
                    $vars['active_key'] = $subscriber->getActiveKey();
                    $url = PHPWS_Core::getHomeHttp();
                    $url .= PHPWS_Text::linkAddress('mailinglists', $vars, false, false, false);

                    $email->setSubject(sprintf(dgettext('mailinglists', 'Confirmation Email: %s'), $list->getName()));
                    $email->setMsgText(str_replace(array('[LISTNAME]', '[URL]'),
                                                   array($list->getName(), $url), $list->getOptInMsg()));
                }
                else
                {
                    $email->setSubject(sprintf(dgettext('mailinglists', 'Welcome to %s'), $list->getName()));
                    $email->setMsgText(str_replace('[LISTNAME]', $list->getName(), $list->getSubscribeMsg()));
                }

                $saved = $email->save();
                if (PEAR::isError($saved))
                {
                    $subscriber->kill();
                    return $saved;
                }

                $db_queue = new PHPWS_DB('mailinglists_queue');
                $values['list_id'] = $list->getId();
                $values['email_id'] = $email->getId();
                $values['subscriber_id'] = $subscriber->getId();
                $db_queue->addValue($values);

                $saved = $db_queue->insert();
                if (PEAR::isError($saved))
                {
                    $subscriber->kill();
                    $email->kill();
                    return $saved;
                }
            }
        }
    }

    function unsubscribe(&$list)
    {
        if (!Current_User::isLogged() || ($list->getId() == 0))
        {
            Current_User::disallow();
            return;
        }

        $db = new PHPWS_DB('mailinglists_subscribers');
        $db->addWhere('list_id', $list->getId());
        $db->addWhere('user_id', Current_User::getId());
        $db->addColumn('id');
        $id = $db->select('one');

        if (PEAR::isError($id))
        {
            return $id;
        }

        $subscriber = new MailingLists_Subscriber($id);
        $subscriber->kill();

        /* NOTE: At this point, the user is unsubscribed, so any errors after this point are not returned. */

        if ($list->getUnsubscribeEmail())
        {
            $email = new MailingLists_Email();
            $email->setSubject(sprintf(dgettext('mailinglists', 'Removed from %s'), $list->getName()));
            $email->setMsgText(str_replace('[LISTNAME]', $list->getName(), $list->getUnsubscribeMsg()));
            $email->setMsgHtml(NULL);
            $email->setUserId(Current_User::getId());
            $email->setListId(0);  // Settings the true ID would cause this email to appear in archive
            $email->setApproved(1);
            if (!PHPWS_Error::logIfError($email->save()))
            {
                $db_queue = new PHPWS_DB('mailinglists_queue');
                $values['list_id'] = $list->getId();
                $values['email_id'] = $email->getId();
                $values['subscriber_id'] = 0;
                $values['email_address'] = Current_User::getEmail();
                $db_queue->addValue($values);
                PHPWS_Error::logIfError($db_queue->insert());
            }
        }
    }

    function anonSubscribe(&$list)
    {
        if (Current_User::isLogged() || ($list->getId() == 0) || !$list->active)
        {
            Current_User::disallow();
            return;
        }

        PHPWS_Core::initCoreClass('Mail.php');
        if (!PHPWS_Mail::checkAddress($_REQUEST['email']))
        {
            return dgettext('mailinglists', 'Error! The email address you submitted is malformed.');
        }

        /* Check if user already subscribed. */
        $db = new PHPWS_DB('mailinglists_subscribers');
        $db->addWhere('list_id', $list->getId());
        $db->addWhere('email', $_REQUEST['email']);
        $count = $db->count();

        if (PHPWS_Error::logIfError($count) || ($count != 0))
        {
            return dgettext('mailinglists', 'Error! The email address you submitted is already in use.');
        }

        $subscriber = new MailingLists_Subscriber($id);
        $subscriber->setEmail($_REQUEST['email']);
        $subscriber->setListId($list->getId());
        $subscriber->setHtml(isset($_REQUEST['html']));
        $subscriber->setActive(0);

        if (!PHPWS_Error::logIfError($subscriber->save()))
        {
            $email = new MailingLists_Email();
            $email->setMsgHtml(NULL);
            $email->setUserId(0);
            $email->setListId(0);  // Setting the true ID would cause this email to appear in archive
            $email->setApproved(1);
            $email->setSubject(sprintf(dgettext('mailinglists', 'Confirmation Email: %s'), $list->getName()));

            $vars['user_op'] = 'confirm';
            $vars['subscriber_id'] = $subscriber->getId();
            $vars['active_key'] = $subscriber->getActiveKey();
            $url = PHPWS_Core::getHomeHttp();
            $url .= PHPWS_Text::linkAddress('mailinglists', $vars, false, false, false);
            $email->setMsgText(str_replace(array('[LISTNAME]', '[URL]'),
                                           array($list->getName(), $url), $list->getOptInMsg()));

            if (!PHPWS_Error::logIfError($email->save()))
            {
                $db_queue = new PHPWS_DB('mailinglists_queue');
                $values['list_id'] = $list->getId();
                $values['email_id'] = $email->getId();
                $values['subscriber_id'] = $subscriber->getId();
                $db_queue->addValue($values);

                if (!PHPWS_Error::logIfError($db_queue->insert()))
                {
                    return dgettext('mailinglists', 'Please check your email to confirm your subscription.');
                }

                $email->kill();
            }

            $subscriber->kill();
        }

        return dgettext('mailinglists', 'Please try again later.');
    }

    function anonUnsubscribe(&$subscriber)
    {
        if (($subscriber->getId() == 0) || !isset($_REQUEST['active_key']))
        {
            Current_User::disallow();
            return;
        }

        if ($subscriber->getActiveKey() == 0)
        {
            return dgettext('mailinglists', 'Your subscription has already been terminated.');
        }

        if ($subscriber->getActiveKey() != (int)$_REQUEST['active_key'])
        {
            return dgettext('mailinglists', 'Error! Invalid key.');
        }

        $list = new MailingLists_List($subscriber->getListId());
        if ($list->getUnsubscribeEmail())
        {
            $email = new MailingLists_Email();
            $email->setMsgHtml(NULL);
            $email->setUserId(0);
            $email->setListId(0);  // Settings the true ID would cause this email to appear in archive
            $email->setApproved(1);
            $email->setSubject(sprintf(dgettext('mailinglists', 'Removed from %s'), $list->getName()));
            $email->setMsgText(str_replace('[LISTNAME]', $list->getName(), $list->getUnsubscribeMsg()));

            if (!PHPWS_Error::logIfError($email->save()))
            {
                $db_queue = new PHPWS_DB('mailinglists_queue');
                $values['list_id'] = $list->getId();
                $values['email_id'] = $email->getId();
                $values['subscriber_id'] = 0;
                $values['email_address'] = $subscriber->getEmail();
                if (empty($values['email_address']))
                {
                    $db_users = new PHPWS_DB('users');
                    $db_users->addWhere('id', $subscriber->getUserId());
                    $db_users->addColumn('email');
                    $user_email = $db_users->select('one');
                    if (!PHPWS_Error::logIfError($user_email))
                    {
                        $values['email_address'] = $user_email;
                    }
                }
                $db_queue->addValue($values);
                PHPWS_Error::logIfError($db_queue->insert());
            }
        }

        $subscriber->kill((int)$_REQUEST['active_key']);
        return dgettext('mailinglists', 'Your subscription has been terminated.');
    }

    function confirm(&$subscriber)
    {
        if (($subscriber->getId() == 0) || !isset($_REQUEST['active_key']))
        {
            Current_User::disallow();
            return;
        }

        if ($subscriber->getActive(false))
        {
            return dgettext('mailinglists', 'Your subscription has already been activated.');
        }

        if ($subscriber->getActiveKey() != $_REQUEST['active_key'])
        {
            return dgettext('mailinglists', 'Error! Invalid key.');
        }

        $subscriber->setActive(1);
        if (PHPWS_Error::logIfError($subscriber->save()))
        {
            return dgettext('mailinglists', 'Please try again later.');
        }

        $list = new MailingLists_List($subscriber->getListId());
        if ($list->getSubscribeEmail())
        {
            $email = new MailingLists_Email();
            $email->setMsgHtml(NULL);
            $email->setUserId($subscriber->getUserId());
            $email->setListId(0);  // Settings the true ID would cause this email to appear in archive
            $email->setApproved(1);
            $email->setSubject(sprintf(dgettext('mailinglists', 'Welcome to %s'), $list->getName()));
            $email->setMsgText(str_replace('[LISTNAME]', $list->getName(), $list->getSubscribeMsg()));

            if (!PHPWS_Error::logIfError($email->save()))
            {
                $db_queue = new PHPWS_DB('mailinglists_queue');
                $values['list_id'] = $list->getId();
                $values['email_id'] = $email->getId();
                $values['subscriber_id'] = $subscriber->getId();
                $db_queue->addValue($values);
                PHPWS_Error::logIfError($db_queue->insert());
            }
        }

        return dgettext('mailinglists', 'Thank you! Your subscription has been activated.');
    }
}

?>