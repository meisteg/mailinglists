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

class MailingLists_Email
{
    var $id         = 0;
    var $list_id    = 0;
    var $subject    = NULL;
    var $msg_text   = NULL;
    var $msg_html   = NULL;
    var $file_id    = 0;
    var $created    = 0;
    var $user_id    = 0;
    var $approved   = 0;


    function MailingLists_Email($id=NULL)
    {
        if (!empty($id))
        {
            $this->setId($id);

            $db = new PHPWS_DB('mailinglists_emails');
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

    function setListId($list_id)
    {
        $this->list_id = (int)$list_id;
    }

    function getListId()
    {
        return $this->list_id;
    }

    function setSubject($subject)
    {
        $this->subject = PHPWS_Text::parseInput(strip_tags($subject));
    }

    function getSubject($format=true)
    {
        if ($format)
        {
            return PHPWS_Text::parseOutput($this->subject);
        }

        return PHPWS_Text::decodeText($this->subject);
    }

    function setMsgText($msg_text)
    {
        $this->msg_text = PHPWS_Text::parseInput(strip_tags($msg_text));
    }

    function getMsgText($format=true)
    {
        /*
         * NOTE: This function can't use the parseOutput function because new lines are changed to <br> tags.
         * In addition, some characters (like the apostrophe) are re-encoded.
         */
        $retval = PHPWS_Text::decodeText($this->msg_text);

        if ($format && !(ALLOW_PROFANITY))
        {
            $retval = PHPWS_Text::profanityFilter($retval);
        }

        return $retval;
    }

    function setMsgHtml($msg_html)
    {
        if (!empty($msg_html))
        {
            $this->msg_html = PHPWS_Text::parseInput($msg_html);
        }
    }

    function getMsgHtml($format=true)
    {
        if ($format)
        {
            /* Support Smart Tags */
            $text = PHPWS_Text::parseTag($this->msg_html);
            $text = PHPWS_Text::parseOutput($text);

            /* Need to add full URL since the text is going into emails. */
            return $this->makeAbsolute($text);
        }

        return PHPWS_Text::decodeText($this->msg_html);
    }

    function setFileId($file_id)
    {
        $this->file_id = (int)$file_id;
    }

    function getFileId()
    {
        return $this->file_id;
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

    function setUserId($user_id)
    {
        $this->user_id = (int)$user_id;
    }

    function getUserId()
    {
        return $this->user_id;
    }

    function setApproved($approved)
    {
        $this->approved = (int)$approved;
    }

    function getApproved()
    {
        return $this->approved;
    }

    function makeAbsolute($text)
    {
        $address = PHPWS_Core::getHomeHttp();

        $src[] = '@(src|href)="\./@';
        $rpl[] = "\\1=\"$address";
        $src[] = '@(src|href)="(images|index.php|filecabinet)@';
        $rpl[] = "\\1=\"$address\\2";

        return preg_replace($src, $rpl, $text);
    }

    function getFile()
    {
        if ($this->getFileId())
        {
            PHPWS_Core::initModClass('filecabinet', 'Cabinet.php');

            $file = Cabinet::getFile($this->getFileId());
            return $this->makeAbsolute($file->getTag());
        }

        return NULL;
    }

    function save()
    {
        if (($this->getListId() != 0) &&
            (!Current_User::authorized('mailinglists', 'send_emails') &&
             (PHPWS_Settings::get('mailinglists', 'user_send') == 0)))
        {
            Current_User::disallow();
            return;
        }

        $this->setCreated();

        $db = new PHPWS_DB('mailinglists_emails');
        return $db->saveObject($this);
    }

    function addQueue()
    {
        if ((!Current_User::allow('mailinglists', 'send_emails') &&
             (PHPWS_Settings::get('mailinglists', 'user_send') == 0)) ||
            !Current_User::isLogged())
        {
            Current_User::disallow();
            return;
        }

        if (($this->getId() == 0) || ($this->getListId() == 0))
        {
            return PHPWS_Error::get(MAILINGLISTS_OBJ_INCOMPLETE, 'mailinglists', 'MailingLists_Email::addQueue');
        }

        $db = new PHPWS_DB('mailinglists_subscribers');
        $db->addWhere('list_id', $this->getListId());
        $db->addWhere('active', 1);
        $db->addColumn('id');
        $result = $db->select('col');
        if (PEAR::isError($result))
        {
            return $result;
        }

        if (!empty($result))
        {
            $db_queue = new PHPWS_DB('mailinglists_queue');
            $values['list_id'] = $this->getListId();
            $values['email_id'] = $this->getId();

            foreach ($result as $sub_id)
            {
                $values['subscriber_id'] = $sub_id;

                $db_queue->reset();
                $db_queue->addValue($values);
                $result_queue = $db_queue->insert();
                if (PEAR::isError($result_queue))
                {
                    return $result_queue;
                }
            }
        }
    }

    function clearQueue()
    {
        $db = new PHPWS_DB('mailinglists_queue');
        $db->addWhere('email_id', $this->getId());
        PHPWS_Error::logIfError($db->delete());
    }

    function kill()
    {
        if ((($this->getListId() == 0) && !Current_User::authorized('mailinglists', 'saved_emails')) ||
            (($this->getListId() != 0) && ($this->getApproved() == 1)) ||
            (($this->getListId() != 0) && !Current_User::authorized('mailinglists', 'send_emails')))
        {
            Current_User::disallow();
            return;
        }

        $this->clearQueue();

        $db = new PHPWS_DB('mailinglists_emails');
        $db->addWhere('id', $this->getId());

        PHPWS_Error::logIfError($db->delete());
    }

    function view()
    {
        if ((($this->getListId() == 0) && !Current_User::authorized('mailinglists', 'saved_emails')) ||
            (($this->getApproved() == 0) && !Current_User::authorized('mailinglists')) ||
            (!Current_User::isLogged()))
        {
            Current_User::disallow();
            return;
        }

        $template['SUBJECT'] = $this->getSubject();
        $template['MESSAGE'] = $this->getMsgHtml();
        $template['FILE'] = $this->getFile();
        return PHPWS_Template::process($template, 'mailinglists', 'email/view.tpl');
    }

    function send(&$list, $send_to, $subscriber=NULL)
    {
        PHPWS_Core::initCoreClass('Mail.php');

        $mail = new PHPWS_Mail;

        if (!$mail->addSendTo($send_to))
        {
            return PHPWS_Error::get(MAILINGLISTS_MAIL_SEND_TO_FAILED, 'mailinglists', 'MailingLists_Email::send', $send_to);
        }

        $setfrom = '"' . $list->getFromName() . '" <' . $list->getFromEmail()  . '>';
        if (!$mail->setFrom($setfrom))
        {
            return PHPWS_Error::get(MAILINGLISTS_MAIL_SET_FROM_FAILED, 'mailinglists', 'MailingLists_Email::send', $setfrom);
        }

        /* If the email is a list_id of 0, it means this is not a mass mailing, rather a module email. */
        if ($this->getListId() == 0)
        {
            $mail->setSubject($this->getSubject(false));
            $mail->setMessageBody($this->getMsgText());
        }
        /* Subscriber variable is required if not a module email. */
        else if (isset($subscriber))
        {
            if (PHPWS_Settings::get('mailinglists', 'subject_prefix'))
            {
                $mail->setSubject($list->getSubjectPrefix() . ' ' . $this->getSubject(false));
            }
            else
            {
                $mail->setSubject($this->getSubject(false));
            }

            /*
             * Verify both HTML and plain text are set, correcting if necessary. The form guarantees
             * that at least one is set.
             */
            if (empty($this->msg_text))
            {
                $this->setMsgText($this->getMsgHtml(false));
            }
            if (empty($this->msg_html))
            {
                $this->msg_html = $this->msg_text;
            }

            $tpl_text['MESSAGE'] = $this->getMsgText();
            $tpl_html['MESSAGE'] = $this->getMsgHtml();

            if (PHPWS_Settings::get('mailinglists', 'footer'))
            {
                $vars['user_op'] = 'aUnsubscribe';
                $vars['subscriber_id'] = $subscriber->getId();
                $vars['active_key'] = $subscriber->getActiveKey();
                $url = PHPWS_Core::getHomeHttp();
                $url .= PHPWS_Text::linkAddress('mailinglists', $vars, false, false, false);

                $tpl_text['FOOTER'] = str_replace('[URL]', $url,
                                                  PHPWS_Text::decodeText(PHPWS_Settings::get('mailinglists', 'footer_text_msg')));
                $tpl_html['FOOTER'] = str_replace('[URL]', $url,
                                                  PHPWS_Text::parseOutput(PHPWS_Settings::get('mailinglists', 'footer_html_msg')));
            }

            $mail->setMessageBody(PHPWS_Template::process($tpl_text, 'mailinglists', 'email/send_text.tpl'));

            /* Only set the HTML message if the subscriber has requested HTML. */
            if ($subscriber->getHtml(false))
            {
                $tpl_html['FILE'] = $this->getFile();

                $mail->setHTMLBody(PHPWS_Template::process($tpl_html, 'mailinglists', 'email/send_html.tpl'));
            }
        }

        return $mail->send();
    }

    function getTpl($links)
    {
        $template['ACTION'] = implode(' | ', $links);
        $template['SUBJECT'] = $this->getSubject();
        $template['CREATED'] = $this->getCreated();

        return $template;
    }

    function getSavedTpl()
    {
        if (Current_User::allow('mailinglists', 'saved_emails'))
        {
            $vars['email_id'] = $this->getId();

            $vars['action'] = 'editSavedEmail';
            $links[] = PHPWS_Text::secureLink(dgettext('mailinglists', 'Edit'), 'mailinglists', $vars);

            $vars['action'] = 'deleteSavedEmail';
            $confirm_vars['QUESTION'] = dgettext('mailinglists',
                                                 'Are you sure you want to permanently delete this saved email?');
            $confirm_vars['ADDRESS'] = PHPWS_Text::linkAddress('mailinglists', $vars, true);
            $confirm_vars['LINK'] = dgettext('mailinglists', 'Delete');
            $links[] = javascript('confirm', $confirm_vars);
        }

        return $this->getTpl($links);
    }

    function getImportSavedTpl()
    {
        if (isset($_REQUEST['list_id']))
        {
            $vars['list_id'] = PHPWS_Text::parseInput($_REQUEST['list_id']);
            $vars['action'] = 'emailList';
            $vars['email_id'] = $this->getId();
            $links[] = PHPWS_Text::secureLink(dgettext('mailinglists', 'Import'), 'mailinglists', $vars);
        }

        return $this->getTpl($links);
    }

    function getArchivedTpl()
    {
        $vars['email_id'] = $this->getId();
        $vars['action'] = 'viewEmail';
        $links[] = PHPWS_Text::secureLink(dgettext('mailinglists', 'View'), 'mailinglists', $vars);

        return $this->getTpl($links);
    }

    function getPublicArchivedTpl()
    {
        $vars['action'] = 'user';
        $vars['tab'] = 'mailinglists';
        $vars['email_id'] = $this->getId();
        $vars['user_op'] = 'viewEmail';
        $links[] = PHPWS_Text::moduleLink(dgettext('mailinglists', 'View'), 'users', $vars);

        return $this->getTpl($links);
    }

    function getApprovalTpl()
    {
        $vars['email_id'] = $this->getId();

        $vars['action'] = 'viewEmail';
        $links[] = PHPWS_Text::secureLink(dgettext('mailinglists', 'View'), 'mailinglists', $vars);

        $vars['action'] = 'approveEmail';
        $links[] = PHPWS_Text::secureLink(dgettext('mailinglists', 'Approve'), 'mailinglists', $vars);

        $vars['action'] = 'rejectEmail';
        $links[] = PHPWS_Text::secureLink(dgettext('mailinglists', 'Reject'), 'mailinglists', $vars);

        return $this->getTpl($links);
    }
}

?>