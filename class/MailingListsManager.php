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
 * @version $Id: MailingListsManager.php,v 1.3 2008/08/23 17:03:16 blindman1344 Exp $
 * @author  Greg Meiste <blindman1344 [at] users dot sourceforge dot net>
 */

PHPWS_Core::initModClass('mailinglists', 'List.php');
PHPWS_Core::initModClass('mailinglists', 'Email.php');
PHPWS_Core::initModClass('mailinglists', 'Subscriber.php');

class MailingLists_Manager
{
    function action()
    {
        if (!Current_User::allow('mailinglists'))
        {
            Current_User::disallow();
            return;
        }

        $panel = & MailingLists_Manager::cpanel();
        if (isset($_REQUEST['action']))
        {
            $action = $_REQUEST['action'];
        }
        else
        {
            $tab = $panel->getCurrentTab();
            if (empty($tab))
            {
                $action = 'manageLists';
            }
            else
            {
                $action = &$tab;
            }
        }

        $panel->setContent(MailingLists_Manager::route($action, $panel));
        Layout::add(PHPWS_ControlPanel::display($panel->display()));
    }

    function &cpanel()
    {
        PHPWS_Core::initModClass('controlpanel', 'Panel.php');
        PHPWS_Core::initModClass('version', 'Version.php');

        $linkBase = 'index.php?module=mailinglists';

        $tabs['manageLists'] = array ('title'=>dgettext('mailinglists', 'Manage Mailing Lists'), 'link'=> $linkBase);
        if (Current_User::allow('mailinglists', 'edit_lists'))
        {
            $tabs['newList'] = array ('title'=>dgettext('mailinglists', 'New Mailing List'), 'link'=> $linkBase);
        }

        if (Current_User::allow('mailinglists', 'saved_emails'))
        {
            $tabs['manageSavedEmails'] = array ('title'=>dgettext('mailinglists', 'Manage Saved Emails'),
                                                'link'=> $linkBase);
            $tabs['newSavedEmail'] = array ('title'=>dgettext('mailinglists', 'New Saved Email'),
                                            'link'=> $linkBase);
        }

        if (Current_User::allow('mailinglists', 'change_settings'))
        {
            $tabs['editSettings'] = array ('title'=>dgettext('mailinglists', 'Settings'), 'link'=> $linkBase);
        }

        $db = new PHPWS_DB('mailinglists_emails');
        $db->addWhere('list_id', 0, '!=');
        $db->addWhere('approved', 0);
        $unapproved = $db->count();
        if (!PHPWS_Error::logIfError($unapproved) &&
            ((PHPWS_Settings::get('mailinglists', 'user_send') == 1) || ($unapproved > 0)) &&
            Current_User::allow('mailinglists', 'send_emails'))
        {
            $tabs['approval'] = array ('title'=>sprintf(dgettext('mailinglists', 'Approval (%s)'), $unapproved),
                                       'link'=> $linkBase);
        }

        $panel = new PHPWS_Panel('mailinglists');
        $panel->enableSecure();
        $panel->quickSetTabs($tabs);

        $panel->setModule('mailinglists');
        return $panel;
    }

    function route($action, &$panel)
    {
        $title   = NULL;
        $content = NULL;
        $message = MailingLists_Manager::getMessage();

        $list = new MailingLists_List(isset($_REQUEST['list_id']) ? $_REQUEST['list_id'] : NULL);
        $email = new MailingLists_Email(isset($_REQUEST['email_id']) ? $_REQUEST['email_id'] : NULL);

        switch ($action)
        {
            case 'newList':
                $title = dgettext('mailinglists', 'New Mailing List');
                $content = MailingLists_Manager::editList($list);
                break;

            case 'deleteList':
                $list->kill();
                MailingLists_Manager::sendMessage(dgettext('mailinglists', 'List deleted'), 'manageLists');
                break;

            case 'editList':
                $title = dgettext('mailinglists', 'Edit Mailing List');
                $content = MailingLists_Manager::editList($list);
                break;

            case 'hideList':
                if (PHPWS_Error::logIfError($list->toggle()))
                {
                    MailingLists_Manager::sendMessage(dgettext('mailinglists', 'List activation could not be changed'),
                                                      'manageLists');
                }
                MailingLists_Manager::sendMessage(dgettext('mailinglists', 'List activation changed'), 'manageLists');
                break;

            case 'postList':
                $result = MailingLists_Manager::postList($list);
                if (is_array($result))
                {
                    $title = dgettext('mailinglists', 'Edit Mailing List');
                    $content = MailingLists_Manager::editList($list, $result);
                }
                else
                {
                    if (PHPWS_Error::logIfError($list->save()))
                    {
                        MailingLists_Manager::sendMessage(dgettext('mailinglists', 'List could not be saved'),
                                                          'manageLists');
                    }

                    MailingLists_Manager::sendMessage(dgettext('mailinglists', 'List saved'), 'manageLists');
                }
                break;

            case 'emailList':
                $title = sprintf(dgettext('mailinglists', 'Send Email to %s'), $list->getName());
                MailingLists_Manager::emailImport($email);
                $content = MailingLists_Manager::emailList($email, $list);
                break;

            case 'postEmailList':
                $result = MailingLists_Manager::postEmailList($email, $list);
                if (is_array($result))
                {
                    $title = sprintf(dgettext('mailinglists', 'Send Email to %s'), $list->getName());
                    $content = MailingLists_Manager::emailList($email, $list, $result);
                }
                else
                {
                    if (PHPWS_Error::logIfError($email->save()) || PHPWS_Error::logIfError($email->addQueue()))
                    {
                        MailingLists_Manager::sendMessage(dgettext('mailinglists', 'Email could not be sent'),
                                                          'manageLists');
                    }

                    MailingLists_Manager::sendMessage(dgettext('mailinglists', 'Email sent'), 'manageLists');
                }
                break;

            case 'archiveList':
                $title = sprintf(dgettext('mailinglists', 'Archive for %s'), $list->getName());
                $content = MailingLists_Manager::archiveList($list);
                break;

            case 'manageLists':
                /* Need to set tab in case we got here from another action. */
                $panel->setCurrentTab('manageLists');
                $title = dgettext('mailinglists', 'Manage Mailing Lists');
                $content = MailingLists_Manager::listLists();
                break;

            case 'subscriberAdmin':
                $title = sprintf(dgettext('mailinglists', 'Subscriber Admin for %s'), $list->getName());
                $content = MailingLists_Manager::subscriberAdmin($list);
                break;

            case 'postSubscriberAdmin':
                if (!isset($_POST['subscribers']) ||
                    (PHPWS_Error::logIfError(MailingLists_Manager::postSubscriberAdmin($list))))
                {
                    MailingLists_Manager::sendMessage(dgettext('mailinglists', 'Error updating subscriber list'),
                                                      array('list_id'=>$list->getId(), 'action'=>'subscriberAdmin'));
                }

                MailingLists_Manager::sendMessage(dgettext('mailinglists', 'Subscriber list updated'),
                                                  array('list_id'=>$list->getId(), 'action'=>'subscriberAdmin'));
                break;

            case 'addAllSubscriberAdmin':
                if (PHPWS_Error::logIfError(MailingLists_Manager::addAllSubscriberAdmin($list)))
                {
                    MailingLists_Manager::sendMessage(dgettext('mailinglists', 'Error updating subscriber list'),
                                                      array('list_id'=>$list->getId(), 'action'=>'subscriberAdmin'));
                }

                MailingLists_Manager::sendMessage(dgettext('mailinglists', 'Subscriber list updated'),
                                                  array('list_id'=>$list->getId(), 'action'=>'subscriberAdmin'));
                break;

            case 'unsubscribeSubscriberAdmin':
                $subscriber = new MailingLists_Subscriber($_REQUEST['subscriber_id']);
                $subscriber->kill();
                MailingLists_Manager::sendMessage(dgettext('mailinglists', 'Subscriber unsubscribed'),
                                                  array('list_id'=>$list->getId(), 'action'=>'subscriberAdmin'));
                break;

            case 'newSavedEmail':
                $title = dgettext('mailinglists', 'New Saved Email');
                $content = MailingLists_Manager::editSavedEmail($email);
                break;

            case 'deleteSavedEmail':
                $email->kill();
                MailingLists_Manager::sendMessage(dgettext('mailinglists', 'Email deleted'), 'manageSavedEmails');
                break;

            case 'editSavedEmail':
                $title = dgettext('mailinglists', 'Edit Saved Email');
                $content = MailingLists_Manager::editSavedEmail($email);
                break;

            case 'postSavedEmail':
                $result = MailingLists_Manager::postSavedEmail($email);
                if (is_array($result))
                {
                    $title = dgettext('mailinglists', 'Edit Saved Email');
                    $content = MailingLists_Manager::editSavedEmail($email, $result);
                }
                else
                {
                    if (PHPWS_Error::logIfError($email->save()))
                    {
                        MailingLists_Manager::sendMessage(dgettext('mailinglists', 'Email could not be saved'),
                                                          'manageSavedEmails');
                    }

                    MailingLists_Manager::sendMessage(dgettext('mailinglists', 'Email saved'), 'manageSavedEmails');
                }
                break;

            case 'importSavedEmail':
                $title = dgettext('mailinglists', 'Send Email: Import Saved Email');
                $content = MailingLists_Manager::listSavedEmails('getImportSavedTpl');
                break;

            case 'importFromModule':
                $title = dgettext('mailinglists', 'Send Email: Import From Module');
                $content = MailingLists_Manager::displayImportFromModule();
                break;

            case 'manageSavedEmails':
                /* Need to set tab in case we got here from another action. */
                $panel->setCurrentTab('manageSavedEmails');
                $title = dgettext('mailinglists', 'Manage Saved Emails');
                $content = MailingLists_Manager::listSavedEmails();
                break;

            case 'viewEmail':
                $title = dgettext('mailinglists', 'View Email');
                $content = $email->view();
                break;

            case 'editSettings':
                $title = dgettext('mailinglists', 'Settings');
                $content = MailingLists_Manager::editSettings();
                break;

            case 'postSettings':
                MailingLists_Manager::postSettings();
                break;

            case 'approval':
                $title = dgettext('mailinglists', 'Approval');
                $content = MailingLists_Manager::approval();
                break;

            case 'approveEmail':
                $email->setApproved(1);
                if (PHPWS_Error::logIfError($email->save()))
                {
                    MailingLists_Manager::sendMessage(dgettext('mailinglists', 'Email could not be approved'), 'approval');
                }

                MailingLists_Manager::sendMessage(dgettext('mailinglists', 'Email approved'), 'approval');
                break;

            case 'rejectEmail':
                $email->kill();
                MailingLists_Manager::sendMessage(dgettext('mailinglists', 'Email rejected'), 'approval');
                break;
        }

        $template['TITLE'] = &$title;
        if (isset($message))
        {
            $template['MESSAGE'] = &$message;
        }
        $template['CONTENT'] = &$content;

        return PHPWS_Template::process($template, 'mailinglists', 'admin.tpl');
    }

    function sendMessage($message, $command)
    {
        $_SESSION['mailinglists_message'] = $message;
        if (is_array($command))
        {
            PHPWS_Core::reroute(PHPWS_Text::linkAddress('mailinglists', $command, true));
        }
        else
        {
            PHPWS_Core::reroute(PHPWS_Text::linkAddress('mailinglists', array('action'=>$command), true));
        }
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

    function editList(&$list, $errors=NULL)
    {
        if (!Current_User::allow('mailinglists', 'edit_lists'))
        {
            Current_User::disallow();
            return;
        }

        $form = new PHPWS_Form;
        $form->addHidden('module', 'mailinglists');
        $form->addHidden('action', 'postList');

        $form->addText('name', $list->getName());
        $form->setLabel('name', dgettext('mailinglists', 'Name'));
        $form->setSize('name', 40, 60);

        $form->addTextArea('description', $list->getDescription(false));
        $form->setRows('description', '3');
        $form->setWidth('description', '80%');
        $form->setLabel('description', dgettext('mailinglists', 'Description'));

        $form->addText('from_name', $list->getFromName(false));
        $form->setLabel('from_name', dgettext('mailinglists', 'Email From Name'));
        $form->setSize('from_name', 40, 255);

        $form->addText('from_email', $list->getFromEmail(false));
        $form->setLabel('from_email', dgettext('mailinglists', 'Email From Address'));
        $form->setSize('from_email', 40, 255);

        $form->addCheck('archive_link');
        $form->setMatch('archive_link', $list->getArchiveLink());
        $form->setLabel('archive_link', dgettext('mailinglists', 'Public Archive Link'));

        $form->addCheck('double_opt_in');
        $form->setMatch('double_opt_in', $list->getDoubleOptIn());
        $form->setLabel('double_opt_in', dgettext('mailinglists', 'Double Opt-In'));

        $form->addCheck('s_email');
        $form->setMatch('s_email', $list->getSubscribeEmail());
        $form->setLabel('s_email', dgettext('mailinglists', 'Subscribe Email'));

        $form->addCheck('u_email');
        $form->setMatch('u_email', $list->getUnsubscribeEmail());
        $form->setLabel('u_email', dgettext('mailinglists', 'Unsubscribe Email'));

        $form->addTextArea('opt_in_msg', $list->getOptInMsg(false));
        $form->setRows('opt_in_msg', MAILINGLISTS_TEXTAREA_ROWS);
        $form->setWidth('opt_in_msg', '80%');
        $form->setLabel('opt_in_msg', dgettext('mailinglists', 'Double Opt-In Confirmation Email'));

        $form->addTextArea('subscribe_msg', $list->getSubscribeMsg(false));
        $form->setRows('subscribe_msg', MAILINGLISTS_TEXTAREA_ROWS);
        $form->setWidth('subscribe_msg', '80%');
        $form->setLabel('subscribe_msg', dgettext('mailinglists', 'Subscribe Email'));

        $form->addTextArea('unsubscribe_msg', $list->getUnsubscribeMsg(false));
        $form->setRows('unsubscribe_msg', MAILINGLISTS_TEXTAREA_ROWS);
        $form->setWidth('unsubscribe_msg', '80%');
        $form->setLabel('unsubscribe_msg', dgettext('mailinglists', 'Unsubscribe Email'));

        if (empty($list->id))
        {
            $form->addSubmit('submit', dgettext('mailinglists', 'Save New List'));
        }
        else
        {
            $form->addText('subject_prefix', $list->getSubjectPrefix(false));
            $form->setLabel('subject_prefix', dgettext('mailinglists', 'Subject Prefix'));
            $form->setSize('subject_prefix', 40, 65);

            $form->addHidden('list_id', $list->getId());
            $form->addSubmit('submit', dgettext('mailinglists', 'Update List'));
        }

        $template = $form->getTemplate();
        if (isset($errors['name']))
        {
            $template['NAME_ERROR'] = $errors['name'];
        }
        if (isset($errors['from_name']))
        {
            $template['FROM_NAME_ERROR'] = $errors['from_name'];
        }
        if (isset($errors['from_email']))
        {
            $template['FROM_EMAIL_ERROR'] = $errors['from_email'];
        }

        return PHPWS_Template::process($template, 'mailinglists', 'list/edit.tpl');
    }

    function postList(&$list)
    {
        if (empty($_POST['name']))
        {
            $errors['name'] = dgettext('mailinglists', 'You must have a name.');
        }
        if (empty($_POST['from_name']))
        {
            $errors['from_name'] = dgettext('mailinglists', 'You must have an email from name.');
        }
        if (empty($_POST['from_email']))
        {
            $errors['from_email'] = dgettext('mailinglists', 'You must have an email from address.');
        }

        $list->setName($_POST['name']);
        if (!empty($_POST['description']))
        {
            $list->setDescription($_POST['description']);
        }
        $list->setArchiveLink((int)isset($_POST['archive_link']));
        $list->setDoubleOptIn((int)isset($_POST['double_opt_in']));
        $list->setSubscribeEmail((int)isset($_POST['s_email']));
        $list->setUnsubscribeEmail((int)isset($_POST['u_email']));
        $list->setOptInMsg($_POST['opt_in_msg']);
        $list->setSubscribeMsg($_POST['subscribe_msg']);
        $list->setUnsubscribeMsg($_POST['unsubscribe_msg']);
        $list->setFromName($_POST['from_name']);
        $list->setFromEmail($_POST['from_email']);
        if ($list->getId())
        {
            $list->setSubjectPrefix($_POST['subject_prefix']);
        }
        else
        {
            $list->setSubjectPrefix('[' . $_POST['name'] . ']');
        }

        return (isset($errors) ? $errors : true);
    }

    function emailList(&$email, &$list, $errors=NULL)
    {
        if (!Current_User::allow('mailinglists', 'send_emails'))
        {
            Current_User::disallow();
            return;
        }

        PHPWS_Core::initModClass('filecabinet', 'Cabinet.php');

        $form = new PHPWS_Form;
        $form->addHidden('module', 'mailinglists');
        $form->addHidden('action', 'postEmailList');
        $form->addHidden('list_id', $list->getId());

        $form->addText('subject', $email->getSubject(false));
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

        $vars['list_id'] = $list->getId();
        $vars['action'] = 'importSavedEmail';
        $links[] = PHPWS_Text::secureLink(dgettext('mailinglists', 'Import Saved Email'), 'mailinglists', $vars);

        $vars['action'] = 'importFromModule';
        $links[] = PHPWS_Text::secureLink(dgettext('mailinglists', 'Import From Module'), 'mailinglists', $vars);

        $template['OPTIONS'] = implode(' | ', $links);
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
        $email->setApproved(1);

        return (isset($errors) ? $errors : true);
    }

    function archiveList(&$list)
    {
        PHPWS_Core::initCoreClass('DBPager.php');

        $pageTags['SUBJECT'] = dgettext('mailinglists', 'Subject');
        $pageTags['CREATED'] = dgettext('mailinglists', 'Sent');
        $pageTags['ACTION']  = dgettext('mailinglists', 'Action');
        $pager = new DBPager('mailinglists_emails', 'MailingLists_Email');
        $pager->setModule('mailinglists');
        $pager->setTemplate('email/list.tpl');
        $pager->addToggle(PHPWS_LIST_TOGGLE_CLASS);
        $pager->addPageTags($pageTags);
        $pager->addRowTags('getArchivedTpl');
        $pager->setSearch('subject', 'msg_html', 'msg_text');
        $pager->setDefaultOrder('created', 'desc');
        $pager->setEmptyMessage(dgettext('mailinglists', 'No archived emails.'));
        $pager->addWhere('list_id', $list->getId());
        $pager->addWhere('approved', 1);
        $pager->cacheQueries();

        return $pager->get();
    }

    function listLists()
    {
        PHPWS_Core::initCoreClass('DBPager.php');

        $pageTags['NAME']        = dgettext('mailinglists', 'Name');
        $pageTags['SUBSCRIBERS'] = dgettext('mailinglists', 'Subscribers');
        $pageTags['CREATED']     = dgettext('mailinglists', 'Created');
        $pageTags['LAST_SENT']   = dgettext('mailinglists', 'Last Email');
        $pageTags['ACTIVE']      = dgettext('mailinglists', 'Active');
        $pageTags['ACTION']      = dgettext('mailinglists', 'Action');
        $pager = new DBPager('mailinglists_lists', 'MailingLists_List');
        $pager->setModule('mailinglists');
        $pager->setTemplate('list/list.tpl');
        $pager->addToggle(PHPWS_LIST_TOGGLE_CLASS);
        $pager->addPageTags($pageTags);
        $pager->addRowTags('getTpl');
        $pager->setSearch('name', 'description');
        $pager->setDefaultOrder('name', 'asc');
        $pager->setEmptyMessage(dgettext('mailinglists', 'No mailing lists found.'));
        $pager->cacheQueries();

        return $pager->get();
    }

    function subscriberAdmin(&$list)
    {
        if (!Current_User::allow('mailinglists', 'subscriber_admin'))
        {
            Current_User::disallow();
            return;
        }

        PHPWS_Core::initCoreClass('DBPager.php');

        $form = new PHPWS_Form;
        $form->addHidden('module', 'mailinglists');
        $form->addHidden('action', 'postSubscriberAdmin');
        $form->addHidden('list_id', $list->getId());

        $form->addTextArea('subscribers');
        $form->setRows('subscribers', '2');
        $form->setWidth('subscribers', '40%');

        $form->addSubmit('subscribe', dgettext('mailinglists', 'Subscribe'));
        $form->addSubmit('unsubscribe', dgettext('mailinglists', 'Unsubscribe'));

        $pageTags = $form->getTemplate();

        $vars['list_id'] = $list->getId();
        $vars['action'] = 'addAllSubscriberAdmin';
        $confirm_vars['QUESTION'] = dgettext('mailinglists',
                                             'Are you sure you want to subscribe all site users to this list?');
        $confirm_vars['ADDRESS'] = PHPWS_Text::linkAddress('mailinglists', $vars, true);
        $confirm_vars['LINK'] = dgettext('mailinglists', 'Add all users');

        $pageTags['ADD_ALL']    = javascript('confirm', $confirm_vars);
        $pageTags['USER']       = dgettext('mailinglists', 'User');
        $pageTags['SUBSCRIBED'] = dgettext('mailinglists', 'Subscribed');
        $pageTags['ACTIVE']     = dgettext('mailinglists', 'Active');
        $pageTags['HTML']       = dgettext('mailinglists', 'HTML');
        $pageTags['ACTION']     = dgettext('mailinglists', 'Action');
        $pager = new DBPager('mailinglists_subscribers', 'MailingLists_Subscriber');
        $pager->setModule('mailinglists');
        $pager->setTemplate('subscribers/list.tpl');
        $pager->addToggle(PHPWS_LIST_TOGGLE_CLASS);
        $pager->addPageTags($pageTags);
        $pager->addRowTags('getTpl');
        $pager->setSearch('email');
        $pager->setDefaultOrder('id', 'asc');
        $pager->setEmptyMessage(dgettext('mailinglists', 'No subscribers.'));
        $pager->addWhere('list_id', $list->getId());
        $pager->cacheQueries();

        return $pager->get();
    }

    function postSubscriberAdmin(&$list)
    {
        if (!Current_User::allow('mailinglists', 'subscriber_admin'))
        {
            Current_User::disallow();
            return;
        }

        PHPWS_Core::initCoreClass('Mail.php');

        $subscribers = preg_split("/[\s,;]+/", PHPWS_Text::parseInput($_POST['subscribers']));

        $db_users = new PHPWS_DB('users');
        $db_subscribers = new PHPWS_DB('mailinglists_subscribers');

        if (isset($_POST['subscribe']))
        {
            foreach ($subscribers as $new_sub)
            {
                $db_subscribers->reset();
                $db_subscribers->addWhere('list_id', $list->getId());

                $sub_obj = new MailingLists_Subscriber();
                $sub_obj->setListId($list->getId());
                $sub_obj->setActive(1);
                $sub_obj->setHtml(1);

                /* Check if email address.  If true, then this is not a username. */
                if (PHPWS_Mail::checkAddress($new_sub))
                {
                    $db_subscribers->addWhere('email', $new_sub);
                    $sub_obj->setEmail($new_sub);
                }
                /* Not and email address.  Check if active username. */
                else
                {
                    /* Query users table. */
                    $db_users->reset();
                    $db_users->addWhere('username', $new_sub);
                    $db_users->addWhere('active', 1);
                    $db_users->addColumn('id');
                    $user_id = $db_users->select('col');

                    if (PEAR::isError($user_id))
                    {
                        return $user_id;
                    }

                    /* If empty, this isn't an active username either.  Forget and move on. */
                    if (empty($user_id))
                    {
                        continue;
                    }

                    $db_subscribers->addWhere('user_id', $user_id[0]);
                    $sub_obj->setUserId($user_id[0]);
                }

                $count = $db_subscribers->count();
                if (PEAR::isError($count))
                {
                    return $count;
                }

                /* Zero count means not already subscribed to list. */
                if ($count == 0)
                {
                    /* Subscribe to list. */
                    $saved = $sub_obj->save();
                    if (PEAR::isError($saved))
                    {
                        return $saved;
                    }
                }
            }
        }
        else if (isset($_POST['unsubscribe']))
        {
            foreach ($subscribers as $leaving_sub)
            {
                $db_subscribers->reset();
                $db_subscribers->addWhere('list_id', $list->getId());
                $db_subscribers->addColumn('id');

                /* Check if email address.  If true, then this is not a username. */
                if (PHPWS_Mail::checkAddress($leaving_sub))
                {
                    $db_subscribers->addWhere('email', $leaving_sub);
                }
                /* Not and email address.  Check if username. */
                else
                {
                    /* Query users table. */
                    $db_users->reset();
                    $db_users->addWhere('username', $leaving_sub);
                    $db_users->addColumn('id');
                    $user_id = $db_users->select('col');

                    if (PEAR::isError($user_id))
                    {
                        return $user_id;
                    }

                    /* If empty, this isn't a username either.  Forget and move on. */
                    if (empty($user_id))
                    {
                        continue;
                    }

                    $db_subscribers->addWhere('user_id', $user_id[0]);
                }

                $sub_id = $db_subscribers->select('col');
                if (PEAR::isError($sub_id))
                {
                    return $sub_id;
                }

                /* If result is not empty, actually subscribed to list. */
                if (!empty($sub_id))
                {
                    /* Unsubscribe from the list. */
                    $sub_obj = new MailingLists_Subscriber($sub_id[0]);
                    $sub_obj->kill();
                }
            }
        }
    }

    function addAllSubscriberAdmin(&$list)
    {
        if (!Current_User::allow('mailinglists', 'subscriber_admin'))
        {
            Current_User::disallow();
            return;
        }

        $db_subscribers = new PHPWS_DB('mailinglists_subscribers');
        $db_users = new PHPWS_DB('users');

        /* Query users table. */
        $db_users->addWhere('active', 1);
        $db_users->addColumn('id');
        $users = $db_users->select('col');

        if (PEAR::isError($users))
        {
            return $users;
        }

        foreach ($users as $id)
        {
            $db_subscribers->reset();
            $db_subscribers->addWhere('list_id', $list->getId());
            $db_subscribers->addWhere('user_id', $id);
            $count = $db_subscribers->count();
            if (PEAR::isError($count))
            {
                return $count;
            }

            /* Zero count means not already subscribed to list. */
            if ($count == 0)
            {
                /* Subscribe to list. */
                $sub_obj = new MailingLists_Subscriber();
                $sub_obj->setListId($list->getId());
                $sub_obj->setActive(1);
                $sub_obj->setHtml(1);
                $sub_obj->setUserId($id);
                $saved = $sub_obj->save();
                if (PEAR::isError($saved))
                {
                    return $saved;
                }
            }
        }
    }

    function editSavedEmail(&$email, $errors=NULL)
    {
        if (!Current_User::allow('mailinglists', 'saved_emails') || ($email->getListId() != 0))
        {
            Current_User::disallow();
            return;
        }

        PHPWS_Core::initModClass('filecabinet', 'Cabinet.php');

        $form = new PHPWS_Form;
        $form->addHidden('module', 'mailinglists');
        $form->addHidden('action', 'postSavedEmail');

        $form->addText('subject', $email->getSubject(false));
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

        if (empty($email->id))
        {
            $form->addSubmit('submit', dgettext('mailinglists', 'Save New Email'));
        }
        else
        {
            $form->addHidden('email_id', $email->getId());
            $form->addSubmit('submit', dgettext('mailinglists', 'Update Email'));
        }

        $template = $form->getTemplate();
        $template['HTML_EMAIL_LEGEND'] = dgettext('mailinglists', 'Message (HTML)');
        $template['TEXT_EMAIL_LEGEND'] = dgettext('mailinglists', 'Message (Plain Text)');

        if (isset($errors['subject']))
        {
            $template['SUBJECT_ERROR'] = $errors['subject'];
        }

        return PHPWS_Template::process($template, 'mailinglists', 'email/edit.tpl');
    }

    function postSavedEmail(&$email)
    {
        if (empty($_POST['subject']))
        {
            $errors['subject'] = dgettext('mailinglists', 'You must have a subject.');
        }

        $email->setSubject($_POST['subject']);
        $email->setMsgText($_POST['msg_text']);
        $email->setMsgHtml($_POST['msg_html']);
        $email->setFileId($_POST['file_id']);
        $email->setUserId(Current_User::getId());
        $email->setApproved(0);

        return (isset($errors) ? $errors : true);
    }

    function listSavedEmails($rowTags='getSavedTpl')
    {
        PHPWS_Core::initCoreClass('DBPager.php');

        $pageTags['SUBJECT'] = dgettext('mailinglists', 'Subject');
        $pageTags['CREATED'] = dgettext('mailinglists', 'Created');
        $pageTags['ACTION']  = dgettext('mailinglists', 'Action');
        $pager = new DBPager('mailinglists_emails', 'MailingLists_Email');
        $pager->setModule('mailinglists');
        $pager->setTemplate('email/list.tpl');
        $pager->addToggle(PHPWS_LIST_TOGGLE_CLASS);
        $pager->addPageTags($pageTags);
        $pager->addRowTags($rowTags);
        $pager->setSearch('subject', 'msg_html', 'msg_text');
        $pager->setDefaultOrder('subject', 'asc');
        $pager->setEmptyMessage(dgettext('mailinglists', 'No saved emails found.'));
        $pager->addWhere('list_id', 0);
        $pager->addWhere('approved', 0);
        $pager->cacheQueries();

        return $pager->get();
    }

    function getBlogTpl($row)
    {
        $template['TITLE']   = PHPWS_Text::parseOutput($row['title']);
        $template['CREATED'] = strftime(MAILINGLISTS_DATE_FORMAT, PHPWS_Time::getUserTime($row['create_date']));

        $vars['list_id'] = PHPWS_Text::parseInput($_REQUEST['list_id']);
        $vars['action'] = 'emailList';
        $vars['blog_id'] = $row['id'];
        $template['ACTION'] = PHPWS_Text::secureLink(dgettext('mailinglists', 'Import'), 'mailinglists', $vars);

        return $template;
    }

    function displayImportFromModule()
    {
        PHPWS_Core::initCoreClass('DBPager.php');

        $pageTags['TITLE']   = dgettext('mailinglists', 'Title');
        $pageTags['CREATED'] = dgettext('mailinglists', 'Created');
        $pageTags['ACTION']  = dgettext('mailinglists', 'Action');

        if (PHPWS_Core::moduleExists('blog'))
        {
            $pageTags['MODULE_NAME'] = dgettext('mailinglists', 'Blog');

            $blog_pager = new DBPager('blog_entries');
            $blog_pager->setModule('mailinglists');
            $blog_pager->setTemplate('import/list.tpl');
            $blog_pager->addToggle(PHPWS_LIST_TOGGLE_CLASS);
            $blog_pager->addPageTags($pageTags);
            $blog_pager->addRowFunction(array('MailingLists_Manager', 'getBlogTpl'));
            $blog_pager->setDefaultOrder('id', 'desc');
            $blog_pager->setEmptyMessage(dgettext('mailinglists', 'No blog entries found.'));
            $blog_pager->setDefaultLimit(5);
            $blog_pager->addWhere('approved', 1);

            $content = $blog_pager->get();
        }

        if (!isset($content))
        {
            $content = dgettext('mailinglists', 'No supported modules installed.');
        }

        return $content;
    }

    function emailImport(&$email)
    {
        if (isset($_REQUEST['blog_id']))
        {
            PHPWS_Core::initModClass('blog', 'Blog.php');
            $blog = new Blog($_REQUEST['blog_id']);

            $email->setSubject($blog->title);
            $email->setFileId($blog->image_id);

            /* Not calling the setMsgHtml function because the summary is already parsed and encoded. */
            $email->msg_html = $blog->getSummary();

            /* Need to decode (twice due to core and/or blog bug) so the HTML tags can be stripped. */
            $email->setMsgText(PHPWS_Text::decodeText(PHPWS_Text::decodeText($blog->getSummary())));
        }
    }

    function editSettings()
    {
        if (!Current_User::allow('mailinglists', 'change_settings'))
        {
            Current_User::disallow();
            return;
        }

        $form = new PHPWS_Form;
        $form->addHidden('module', 'mailinglists');
        $form->addHidden('action', 'postSettings');

        $form->addCheck('show_block');
        $form->setMatch('show_block', PHPWS_Settings::get('mailinglists', 'show_block'));
        $form->setLabel('show_block', dgettext('mailinglists', 'Show on homepage'));

        $form->addCheck('footer');
        $form->setMatch('footer', PHPWS_Settings::get('mailinglists', 'footer'));
        $form->setLabel('footer', dgettext('mailinglists', 'Attach footer to all emails'));

        $form->addCheck('anon_subscribe');
        $form->setMatch('anon_subscribe', PHPWS_Settings::get('mailinglists', 'anon_subscribe'));
        $form->setLabel('anon_subscribe', dgettext('mailinglists', 'Allow anonymous users to subscribe to lists'));

        $form->addCheck('subject_prefix');
        $form->setMatch('subject_prefix', PHPWS_Settings::get('mailinglists', 'subject_prefix'));
        $form->setLabel('subject_prefix', dgettext('mailinglists', 'Attach a subject prefix to list messages'));

        $user_send_labels = array(dgettext('mailinglists', 'No'),
                                  dgettext('mailinglists', 'Yes: Approval Required'),
                                  dgettext('mailinglists', 'Yes: Without Approval'));
        $user_send_ids = array(0, 1, 2);
        $form->addRadio('user_send', $user_send_ids);
        $form->setLabel('user_send', $user_send_labels);
        $form->setMatch('user_send', PHPWS_Settings::get('mailinglists', 'user_send'));
        $form->addTplTag('USER_SEND_LABEL', dgettext('mailinglists', 'Allow registered website users send messages'));

        $form->addText('max_per_hour', PHPWS_Settings::get('mailinglists', 'max_per_hour'));
        $form->setLabel('max_per_hour', dgettext('mailinglists',
                                                 'Maximum number of emails to send per hour (0 for unlimited)'));
        $form->setSize('max_per_hour', 5, 5);

        $form->addText('max_at_once', PHPWS_Settings::get('mailinglists', 'max_at_once'));
        $form->setLabel('max_at_once', dgettext('mailinglists',
                                                'Maximum number of emails to send at once (0 for unlimited)'));
        $form->setSize('max_at_once', 5, 5);

        $form->addTextArea('footer_html_msg', PHPWS_Text::decodeText(PHPWS_Settings::get('mailinglists', 'footer_html_msg')));
        $form->setRows('footer_html_msg', '3');
        $form->setWidth('footer_html_msg', '80%');
        $form->setLabel('footer_html_msg', dgettext('mailinglists', 'Footer Message (HTML)'));
        $form->useEditor('footer_html_msg');

        $form->addTextArea('footer_text_msg', PHPWS_Text::decodeText(PHPWS_Settings::get('mailinglists', 'footer_text_msg')));
        $form->setRows('footer_text_msg', '3');
        $form->setWidth('footer_text_msg', '80%');
        $form->setLabel('footer_text_msg', dgettext('mailinglists', 'Footer Message (Plain Text)'));

        $form->addSubmit('submit', dgettext('mailinglists', 'Update Settings'));

        return PHPWS_Template::process($form->getTemplate(), 'mailinglists', 'settings.tpl');
    }

    function postSettings()
    {
        if (!Current_User::allow('mailinglists', 'change_settings'))
        {
            Current_User::disallow();
            return;
        }

        PHPWS_Settings::set('mailinglists', 'show_block', (int)isset($_POST['show_block']));
        PHPWS_Settings::set('mailinglists', 'footer', (int)isset($_POST['footer']));
        PHPWS_Settings::set('mailinglists', 'anon_subscribe', (int)isset($_POST['anon_subscribe']));
        PHPWS_Settings::set('mailinglists', 'subject_prefix', (int)isset($_POST['subject_prefix']));

        if (isset($_POST['user_send']))
        {
            PHPWS_Settings::set('mailinglists', 'user_send', PHPWS_Text::parseInput($_POST['user_send']));
        }

        if (isset($_POST['max_per_hour']))
        {
            PHPWS_Settings::set('mailinglists', 'max_per_hour', PHPWS_Text::parseInput($_POST['max_per_hour']));
        }

        if (isset($_POST['max_at_once']))
        {
            PHPWS_Settings::set('mailinglists', 'max_at_once', PHPWS_Text::parseInput($_POST['max_at_once']));
        }

        if (isset($_POST['footer_html_msg']))
        {
            PHPWS_Settings::set('mailinglists', 'footer_html_msg', PHPWS_Text::parseInput($_POST['footer_html_msg']));
        }

        if (isset($_POST['footer_text_msg']))
        {
            PHPWS_Settings::set('mailinglists', 'footer_text_msg',
                                PHPWS_Text::parseInput(strip_tags($_POST['footer_text_msg'])));
        }

        if (PHPWS_Error::logIfError(PHPWS_Settings::save('mailinglists')))
        {
            MailingLists_Manager::sendMessage(dgettext('mailinglists', 'There was an error saving the settings.'),
                                              'editSettings');
        }

        MailingLists_Manager::sendMessage(dgettext('mailinglists', 'Your settings have been successfully saved.'),
                                          'editSettings');
    }

    function approval()
    {
        PHPWS_Core::initCoreClass('DBPager.php');

        $pageTags['SUBJECT'] = dgettext('mailinglists', 'Subject');
        $pageTags['CREATED'] = dgettext('mailinglists', 'Created');
        $pageTags['ACTION']  = dgettext('mailinglists', 'Action');
        $pager = new DBPager('mailinglists_emails', 'MailingLists_Email');
        $pager->setModule('mailinglists');
        $pager->setTemplate('email/list.tpl');
        $pager->addToggle(PHPWS_LIST_TOGGLE_CLASS);
        $pager->addPageTags($pageTags);
        $pager->addRowTags('getApprovalTpl');
        $pager->setSearch('subject', 'msg_html', 'msg_text');
        $pager->setDefaultOrder('created', 'asc');
        $pager->setEmptyMessage(dgettext('mailinglists', 'No emails waiting for approval.'));
        $pager->addWhere('list_id', 0, '!=');
        $pager->addWhere('approved', 0);
        $pager->cacheQueries();

        return $pager->get();
    }
}

?>