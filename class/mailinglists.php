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
 * @version $Id: mailinglists.php,v 1.39 2005/09/12 03:25:32 blindman1344 Exp $
 * @author  Greg Meiste <blindman1344 [at] users dot sourceforge dot net>
 */

require_once(PHPWS_SOURCE_DIR . 'core/WizardBag.php');
require_once(PHPWS_SOURCE_DIR . 'core/Template.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');
require_once(PHPWS_SOURCE_DIR . 'mod/help/class/CLS_help.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Pager.php');

/* PEAR mail classes */
require_once('Mail.php');
require_once('Mail/mime.php');

class PHPWS_mailinglists {

  /**
 * Admin menu for Mailing Lists class
 *
 * @author Greg Meiste <blindman1344 [at] users dot sourceforge dot net>
 * @access public
 */
  function showMenu($msg=NULL) {
    if($_SESSION['OBJ_user']->allow_access('mailinglists')) {
    $content = $this->adminNavBar('listML');

    if($msg != NULL) {
      $content .= '<br /><b>' . $msg . '</b><br />';
    }

    $content .= '<form action="index.php" method="post">';
    $content .= PHPWS_Form::formHidden('module', 'mailinglists') . '<br />';

    $tags['ID_LABEL'] = $_SESSION['translate']->it('ID');
    $tags['NAME_LABEL'] = $_SESSION['translate']->it('List Name');
    $tags['CREATED_LABEL'] = $_SESSION['translate']->it('Created');
    $tags['LASTEMAIL_LABEL'] = $_SESSION['translate']->it('Last Email');
    $tags['SENTBY_LABEL'] = $_SESSION['translate']->it('Sent By');
    $tags['STATUS_LABEL'] = $_SESSION['translate']->it('Status');
    $tags['LIST_ITEMS'] = '';
    
    $result = $GLOBALS['core']->sqlSelect('mod_mailinglists_lists', NULL, NULL, 'name');
    if($result != NULL) {
      foreach ($result as $row) {
        $id = $row['id'];
        $tags_row['LISTNAME'] = $row['name'];
        $status = $row['status'];
        $tags_row['CREATED'] = $row['dateCreated'];
        $lastSent = $row['lastSent'];
        $tags_row['SENTBY'] = $row['lastSentBy'];

        $countResult = $GLOBALS['core']->sqlSelect('mod_mailinglists_subscribers', 'listID', $id);
	$userCount = 0;
        if($countResult != NULL) {
          foreach ($countResult as $row) {
	    $userCount++;
	  }
	}
	
	$countResult2 = $GLOBALS['core']->sqlSelect('mod_mailinglists_anon_subscribers', 'listID', $id);
        if($countResult2 != NULL) {
          foreach ($countResult2 as $row) {
	    $userCount++;
	  }
	}

	if($status == 'off')
	  $statusText = $_SESSION['translate']->it('Hidden');
	else
	  $statusText = $_SESSION['translate']->it('Visible');

        if($lastSent == '0000-00-00 00:00:00') {
          $lastSent = $_SESSION['translate']->it('N/A');
        }

        PHPWS_WizardBag::toggle($row_class, ' class="bg_light"');
        $tags_row['ROW_CLASS'] = $row_class;
        $tags_row['SELECT'] = PHPWS_Form::formCheckBox('list[]', $id);

        $tags_row['USERCOUNT'] = $userCount;
        $tags_row['LASTEMAIL'] = $lastSent;
        $tags_row['STATUS'] = $statusText;
        $tags_row['ID'] = $id;

	$tags['LIST_ITEMS'] .= PHPWS_Template::processTemplate($tags_row, 'mailinglists', 'manager/row.tpl');
      }

      if($_SESSION['OBJ_user']->allow_access('mailinglists', 'send_emails')) {
        $actions['sendEmail'] = $_SESSION['translate']->it('Send Email');
      }
      if($_SESSION['OBJ_user']->allow_access('mailinglists', 'subscriber_admin')) {
        $actions['subscriberAdmin'] = $_SESSION['translate']->it('Subscriber Admin');
      }
      if($_SESSION['OBJ_user']->allow_access('mailinglists', 'edit_lists')) {
        $actions['editList'] = $_SESSION['translate']->it('Edit');
      }
      if($_SESSION['OBJ_user']->allow_access('mailinglists', 'change_visibility')) {
        $actions['listShowHide'] = $_SESSION['translate']->it('Show/Hide');
      }
      if($_SESSION['OBJ_user']->allow_access('mailinglists', 'delete_lists')) {
        $actions['deleteList'] = $_SESSION['translate']->it('Delete');
      }
      if($_SESSION['OBJ_user']->allow_access('mailinglists', 'subscriber_admin')) {
        $actions['addAllUsers'] = $_SESSION['translate']->it('Add All Users');
      }

      if($actions != NULL) {
        $tags['ACTION_SELECT'] = PHPWS_Form::formSelect('op', $actions);
        $tags['ACTION_BUTTON'] = PHPWS_Form::formSubmit($_SESSION['translate']->it('Go'));
      }

    } else {
      $tags['NO_LISTS'] = $_SESSION['translate']->it('No mailing lists at this time.');
    }

    $content .= PHPWS_Template::processTemplate($tags, 'mailinglists', 'manager/list.tpl');
    $content .= '</form>';

    $GLOBALS['CNT_mailinglists_menu']['title'] = $_SESSION['translate']->it('Mailing Lists Administration');
    $GLOBALS['CNT_mailinglists_menu']['content'] = $content;
    }
  }// END FUNC showMenu


  /**
 * Shows form to add/edit a Mailing List
 *
 * @author Greg Meiste <blindman1344 [at] users dot sourceforge dot net>
 * @access public
 */
  function edit($list=NULL) {
    if(isset($list)) {
      $content = $this->adminNavBar();
      $hiddenOp = 'doEditList';
      $extraHidden = PHPWS_Form::formHidden('list', $list);

      $result = $GLOBALS['core']->sqlSelect('mod_mailinglists_lists', 'id', $list);
      foreach ($result as $row) {
        $name = $row['name'];
        $description = $row['description'];
        $archive = $row['archive'];
        $archiveLink = $row['archiveLink'];
        $doubleOptIn = $row['doubleOptIn'];
        $sEmail = $row['sEmail'];
        $uEmail = $row['uEmail'];
        $optInMessage = $row['optInMessage'];
        $subscribeMessage = $row['subscribeMessage'];
        $unsubscribeMessage = $row['unsubscribeMessage'];
        $fromName = $row['fromName'];
        $fromEmail = $row['fromEmail'];
        $subjectPrefix = $row['subjectPrefix'];
      }

      $tags['ARCHIVE_LABEL'] = '<a href="index.php?module=mailinglists&amp;op=archives&amp;list=';
      $tags['ARCHIVE_LABEL'] .= $list . '">' . $_SESSION['translate']->it('Archive') . '</a>';

      $tags['SUBMIT_BUTTON'] = PHPWS_Form::formSubmit($_SESSION['translate']->it('Update List'), 'do_edit');

      $tags['OPTINMESSAGE_HELP'] = $_SESSION['OBJ_help']->show_link('mailinglists', 'optInMessage');
      $tags['OPTINMESSAGE_LABEL'] = $_SESSION['translate']->it('Double Opt-in Confirmation Email');
      $tags['OPTINMESSAGE'] = PHPWS_Form::formTextArea('optInMessage', $optInMessage, 10, 60);
      
      $tags['SMESSAGE_HELP'] = $_SESSION['OBJ_help']->show_link('mailinglists', 'sMessage');
      $tags['SMESSAGE_LABEL'] = $_SESSION['translate']->it('Subscribe Email');
      $tags['SMESSAGE'] = PHPWS_Form::formTextArea('subscribeMessage', $subscribeMessage, 10, 60);
      
      $tags['UMESSAGE_HELP'] = $_SESSION['OBJ_help']->show_link('mailinglists', 'uMessage');
      $tags['UMESSAGE_LABEL'] = $_SESSION['translate']->it('Unsubscribe Email');
      $tags['UMESSAGE'] = PHPWS_Form::formTextArea('unsubscribeMessage', $unsubscribeMessage, 10, 60);
      
      $tags['SUBJECTPREFIX_HELP'] = $_SESSION['OBJ_help']->show_link('mailinglists', 'subjectPrefix');
      $tags['SUBJECTPREFIX_LABEL'] = $_SESSION['translate']->it('Subject Prefix');
      $tags['SUBJECTPREFIX'] = PHPWS_Form::formTextField('subjectPrefix', $subjectPrefix, 40, 65);
    }
    else {
      $content = $this->adminNavBar('addML');
      $hiddenOp = 'doAddList';
      $extraHidden = NULL;
      $name = NULL;
      $description = NULL;
      $fromName = $_SESSION['OBJ_layout']->page_title;
      extract(PHPWS_User::getSettings());
      $fromEmail = $user_contact;
      $archive = 1;
      $archiveLink = 1;
      $doubleOptIn = 1;
      $sEmail = 1;
      $uEmail = 1;
      $tags['ARCHIVE_LABEL'] = $_SESSION['translate']->it('Archive');
      $tags['SUBMIT_BUTTON'] = PHPWS_Form::formSubmit($_SESSION['translate']->it('Add List'), 'do_add');
    }
    $content .= '<br />';

    $content .= '<form action="index.php" method="post">';
    $content .= PHPWS_Form::formHidden('module', 'mailinglists');
    $content .= PHPWS_Form::formHidden('op', $hiddenOp) . $extraHidden;

    $tags['LISTNAME_HELP'] = $_SESSION['OBJ_help']->show_link('mailinglists', 'listname');
    $tags['LISTNAME_LABEL'] = $_SESSION['translate']->it('Mailing List Name');
    $tags['LISTNAME'] = PHPWS_Form::formTextField('listName', $name, 40, 55);

    $tags['DESCRIPTION_HELP'] = $_SESSION['OBJ_help']->show_link('mailinglists', 'description');
    $tags['DESCRIPTION_LABEL'] = $_SESSION['translate']->it('Description');
    $tags['DESCRIPTION'] = PHPWS_Form::formTextField('description', $description, 60, 255);

    $tags['FROMNAME_HELP'] = $_SESSION['OBJ_help']->show_link('mailinglists', 'fromName');
    $tags['FROMNAME_LABEL'] = $_SESSION['translate']->it('Email From Name');
    $tags['FROMNAME'] = PHPWS_Form::formTextField('fromName', $fromName, 40, 255);

    $tags['FROMADDRESS_HELP'] = $_SESSION['OBJ_help']->show_link('mailinglists', 'fromAddress');
    $tags['FROMADDRESS_LABEL'] = $_SESSION['translate']->it('Email From Address');
    $tags['FROMADDRESS'] = PHPWS_Form::formTextField('fromEmail', $fromEmail, 40, 255);

    $tags['ARCHIVE_HELP'] = $_SESSION['OBJ_help']->show_link('mailinglists', 'archive');
    $tags['ARCHIVE_YES'] = PHPWS_Form::formRadio('archive', 1, $archive, NULL, $_SESSION['translate']->it('Yes'));
    $tags['ARCHIVE_NO'] = PHPWS_Form::formRadio('archive', 0, $archive, NULL, $_SESSION['translate']->it('No'));

    $tags['ARCHLINK_HELP'] = $_SESSION['OBJ_help']->show_link('mailinglists', 'archiveLink');
    $tags['ARCHLINK_LABEL'] = $_SESSION['translate']->it('Public Archive Link?');
    $tags['ARCHLINK_YES'] = PHPWS_Form::formRadio('archiveLink', 1, $archiveLink, NULL, $_SESSION['translate']->it('Yes'));
    $tags['ARCHLINK_NO'] = PHPWS_Form::formRadio('archiveLink', 0, $archiveLink, NULL, $_SESSION['translate']->it('No'));

    $tags['DOUBLEOPT_HELP'] = $_SESSION['OBJ_help']->show_link('mailinglists', 'doubleOpt');
    $tags['DOUBLEOPT_LABEL'] = $_SESSION['translate']->it('Double Opt-In?');
    $tags['DOUBLEOPT_YES'] = PHPWS_Form::formRadio('doubleOptIn', 1, $doubleOptIn, NULL, $_SESSION['translate']->it('Yes'));
    $tags['DOUBLEOPT_NO'] = PHPWS_Form::formRadio('doubleOptIn', 0, $doubleOptIn, NULL, $_SESSION['translate']->it('No'));

    $tags['SEMAIL_HELP'] = $_SESSION['OBJ_help']->show_link('mailinglists', 'sEmail');
    $tags['SEMAIL_LABEL'] = $_SESSION['translate']->it('Subscribe Email');
    $tags['SEMAIL_YES'] = PHPWS_Form::formRadio('sEmail', 1, $sEmail, NULL, $_SESSION['translate']->it('Yes'));
    $tags['SEMAIL_NO'] = PHPWS_Form::formRadio('sEmail', 0, $sEmail, NULL, $_SESSION['translate']->it('No'));

    $tags['UEMAIL_HELP'] = $_SESSION['OBJ_help']->show_link('mailinglists', 'uEmail');
    $tags['UEMAIL_LABEL'] = $_SESSION['translate']->it('Unsubscribe Email');
    $tags['UEMAIL_YES'] = PHPWS_Form::formRadio('uEmail', 1, $uEmail, NULL, $_SESSION['translate']->it('Yes'));
    $tags['UEMAIL_NO'] = PHPWS_Form::formRadio('uEmail', 0, $uEmail, NULL, $_SESSION['translate']->it('No'));

    $content .= PHPWS_Template::processTemplate($tags, 'mailinglists', 'edit.tpl');
    $content .= '</form>';

    $GLOBALS['CNT_mailinglists_menu']['title'] = $_SESSION['translate']->it('Mailing Lists Administration');
    $GLOBALS['CNT_mailinglists_menu']['content'] = $content;
  }// END FUNC edit


  /**
 * Saved Emails for Mailing Lists
 *
 * @author Greg Meiste <blindman1344 [at] users dot sourceforge dot net>
 * @access public
 */
  function showSavedEmails($msg=NULL) {
    if($_SESSION['OBJ_user']->allow_access('mailinglists', 'saved_emails')) {

      $content = $this->adminNavBar('savedE');

      $tags['MESSAGE'] = $msg;

      $tags['TEXT'] = '<a href="index.php?module=mailinglists&amp;op=addEmail">';
      $tags['TEXT'] .= $_SESSION['translate']->it('Add New Email') . '</a>';

      $tags['EMAILNAME_LABEL'] = $_SESSION['translate']->it('Email Name');
      $tags['MESSAGE_LABEL'] = $_SESSION['translate']->it('Message');
      $tags['ACTION_LABEL'] = $_SESSION['translate']->it('Action');
      $tags['LIST_ITEMS'] = '';

      $result = $GLOBALS['core']->sqlSelect('mod_mailinglists_saves', NULL, NULL, 'name');
      if($result != NULL) {
        foreach ($result as $row) {
          $id = $row['id'];
          $name = $row['name'];
          $message = $row['message'];

	  $dots = '';
	  if(strlen($message) > 100)
	    $dots = '...';
	  $message = substr($message,0,100);

          PHPWS_WizardBag::toggle($row_class, ' class="bg_light"');
          $tags_row['ROW_CLASS'] = $row_class;

          $tags_row['NAME'] = '<a href="index.php?module=mailinglists&amp;op=editEmail&amp;email=';
          $tags_row['NAME'] .= $id . '">' . $name . '</a>';
          $tags_row['MESSAGE'] = nl2br($message) . $dots;
          $tags_row['ACTION'] = '<a href="index.php?module=mailinglists&amp;op=deleteEmail&amp;email=';
          $tags_row['ACTION'] .= $id . '">' . $_SESSION['translate']->it('Delete') . '</a>';

	  $tags['LIST_ITEMS'] .= PHPWS_Template::processTemplate($tags_row, 'mailinglists', 'savedemails/listrow.tpl');
        }
      }
      else {
        $tags['LIST_ITEMS'] = '<tr><td colspan="3">';
        $tags['LIST_ITEMS'] .= $_SESSION['translate']->it("There aren't any saved emails in the database.") . '</td></tr>';
      }

      $content .= PHPWS_Template::processTemplate($tags, 'mailinglists', 'savedemails/list.tpl');

      $GLOBALS['CNT_mailinglists_menu']['title'] = $_SESSION['translate']->it('Mailing Lists Administration');
      $GLOBALS['CNT_mailinglists_menu']['content'] = $content;
    }
  }// END FUNC showSavedEmails

  
 /**
 * Displays the user block that contains their Mailing Lists information
 *
 * @author Greg Meiste <blindman1344 [at] users dot sourceforge dot net>
 * @access public
 */
  function showBlock() {
    $theLists = $GLOBALS['core']->sqlSelect('mod_mailinglists_lists', 'status', 'on');
    if($theLists != NULL) {
      $result = $GLOBALS['core']->sqlSelect('mod_mailinglists_conf');

      foreach ($result as $row) {
        $status = $row['personal'];
      }

      if($status == 'on')
      {
        $title = $_SESSION['translate']->it('Your Mailing Lists');

        $result = $GLOBALS['core']->sqlSelect('mod_mailinglists_subscribers', 'userID', $_SESSION['OBJ_user']->user_id);
        $count = 0;
        $output = '';
        if($result != NULL) {
          foreach ($result as $row) {
            $count++;
            $listID = $row['listID'];
            $result = $GLOBALS['core']->sqlSelect('mod_mailinglists_lists', 'id', $listID);
            foreach ($result as $row) {
              $theListName = $row['name'];
            }
            $tags['LISTNAME'] = $theListName;
            $output .= PHPWS_Template::processTemplate($tags, 'mailinglists', 'personalbox_listname.tpl');
          }
        }

        if($count > 0) {
          $blocktags['LISTS'] = $output;
        }
        else {
          $blocktags['NOLISTS'] = $_SESSION['translate']->it('You are not subscribed to any mailing lists.');
        }

        $blocktags['MENU_LINK'] = '<a href="index.php?module=mailinglists&amp;op=user">';
        $blocktags['MENU_LINK'] .= $_SESSION['translate']->it('Subscription Menu') . '</a>';

        $GLOBALS['CNT_mailinglists']['title'] = $title;
        $GLOBALS['CNT_mailinglists']['content'] = PHPWS_Template::processTemplate($blocktags, 'mailinglists', 'personalbox.tpl');
      }
    }
  }// END FUNC showBlock


  /**
 * Displays the user menu so they can change subscription status.
 *
 * @author Greg Meiste <blindman1344 [at] users dot sourceforge dot net>
 * @access public
 */
  function showUserMenu($msg = NULL) {
    $title = $_SESSION['translate']->it('Mailing Lists Subscription Menu');

    if(PHPWS_Text::isValidInput($_SESSION['OBJ_user']->email, 'email')) {

    $tags['MESSAGE'] = $msg;

    $tags['EMAIL_LINE'] = $_SESSION['translate']->it('The email address where the mail will be sent');
    $tags['EMAIL_ADDY'] = $_SESSION['OBJ_user']->email;
    $tags['EMAIL_UPDATE'] = $_SESSION['translate']->it('If this is not correct, please update it by updating your info in the Control Panel.');

    $subLists = array();
    $i = 0;

    $tags['CURRENTLY'] = $_SESSION['translate']->it('You are currently subscribed to these lists');
    $tags2['COL1_HEADER'] = $_SESSION['translate']->it('List Name');
    $tags2['COL2_HEADER'] = $_SESSION['translate']->it('Description');
    $tags2['COL3_HEADER'] = $_SESSION['translate']->it('Action');
    $tags2['CUR_ROWS'] = '';

    $userResult = $GLOBALS['core']->sqlSelect('mod_mailinglists_subscribers', 'userID', $_SESSION['OBJ_user']->user_id);
    $confResult = $GLOBALS['core']->sqlSelect('mod_mailinglists_conf');
    $warnings = 0;

    if($userResult != NULL) {
      foreach ($userResult as $list) {
        $result = $GLOBALS['core']->sqlSelect('mod_mailinglists_lists', 'id', $list['listID']);
        foreach ($result as $row) {
          $id = $row['id'];
          $cur_row['NAME'] = $row['name'];
          $cur_row['DESCRIPTION'] = $row['description'];
          $archiveLink = $row['archiveLink'];

          PHPWS_WizardBag::toggle($row_class, ' class="bg_light"');
          $cur_row['ROW_CLASS'] = $row_class;

          if(!$list['active']) {
            $cur_row['WARNING_IMAGE'] = '<img src="http://' . PHPWS_SOURCE_HTTP . 'mod/mailinglists/img/warning.png" alt="';
            $cur_row['WARNING_IMAGE'] .= $_SESSION['translate']->it('Warning') . '" border="0" />';
            $warnings++;
	    $cur_row['SENDEMAIL'] = NULL;
          }
	  else {
	    $cur_row['WARNING_IMAGE'] = NULL;
	    if($confResult[0]['userSend'])
	    {
	      $cur_row['SENDEMAIL'] = '<a href="index.php?module=mailinglists&amp;op=sendEmail&amp;mladmin=0&amp;list=' . $id . '">';
	      $cur_row['SENDEMAIL'] .= $_SESSION['translate']->it('Send Email') . '</a>';
	    }
	  }

          $cur_row['UNSUBSCRIBE'] = '<a href="index.php?module=mailinglists&amp;op=unsubscribe&amp;user=';
	  $cur_row['UNSUBSCRIBE'] .= $_SESSION['OBJ_user']->user_id . '&amp;list=' . $id . '">';
	  $cur_row['UNSUBSCRIBE'] .= $_SESSION['translate']->it('Unsubscribe') . '</a>';

          if($archiveLink) {
            $cur_row['ARCHIVE'] = '<a href="index.php?module=mailinglists&amp;op=archives&amp;list=';
	    $cur_row['ARCHIVE'] .= $id . '">' . $_SESSION['translate']->it('Archive') . '</a>';
          }
	  else {
	    $cur_row['ARCHIVE'] = NULL;
	  }

          $tags2['CUR_ROWS'] .= PHPWS_Template::processTemplate($cur_row, 'mailinglists', 'cur_tablerow.tpl');
	  $i++;
	  array_push($subLists, $row['id']);
        }
      }
    } else {
      $tags2['NO_CUR_ROWS'] = $_SESSION['translate']->it('You are not subscribed to any mailing lists.');
    }

    if($warnings) {
      $tags2['WARNING'] = $_SESSION['translate']->it('Lists with the exclamation point need to be confirmed yet. Please check your email.');
    }

    $tags['CUR_TABLE'] = PHPWS_Template::processTemplate($tags2, 'mailinglists', 'cur_table.tpl');

    $tags['AVAILABLE'] = $_SESSION['translate']->it('Lists available to subscribe to');
    $tags3['COL1_HEADER'] = $_SESSION['translate']->it('List Name');
    $tags3['COL2_HEADER'] = $_SESSION['translate']->it('Description');
    $tags3['COL3_HEADER'] = $_SESSION['translate']->it('Action');
    $tags3['AVAIL_ROWS'] = '';

    $result = $GLOBALS['core']->sqlSelect('mod_mailinglists_lists', 'status', 'on', 'name');
    if($result != NULL) {
      foreach ($result as $row) {
        $id = $row['id'];

	$ok = 'yes';
	$j = 0;
	while($j < $i) {
	  if($subLists[$j] == $id)
	    $ok = 'no';
          $j++;
	}

	if($ok == 'yes') {
          PHPWS_WizardBag::toggle($row_class2, ' class="bg_light"');
          $avail_row['ROW_CLASS'] = $row_class2;

          $avail_row['NAME'] = $row['name'];
          $avail_row['DESCRIPTION'] = $row['description'];
          $avail_row['SUBSCRIBE'] = '<a href="index.php?module=mailinglists&amp;op=subscribe&amp;user=';
	  $avail_row['SUBSCRIBE'] .= $_SESSION['OBJ_user']->user_id . '&amp;list=' . $id . '">';
	  $avail_row['SUBSCRIBE'] .= $_SESSION['translate']->it('Subscribe') . '</a>';
          $tags3['AVAIL_ROWS'] .= PHPWS_Template::processTemplate($avail_row, 'mailinglists', 'avail_tablerow.tpl');
	}
      }
      if($tags3['AVAIL_ROWS'] == '')
	$tags3['NO_MORE_LISTS'] = $_SESSION['translate']->it("There aren't any more mailing lists available at this time.");
    } else {
      $tags3['NOT_ANY_LISTS'] = $_SESSION['translate']->it("There aren't any mailing lists available at this time.");
    }

    $tags['AVAIL_TABLE'] = PHPWS_Template::processTemplate($tags3, 'mailinglists', 'avail_table.tpl');
    $tags['USER_OPTIONS'] = $this->getUserOptions($_SESSION['OBJ_user']->user_id);
    $content = PHPWS_Template::processTemplate($tags, 'mailinglists', 'subscription_menu.tpl');
    }

    else {
      $content = '<span class="errortext">' . $_SESSION['translate']->it('ERROR!') . '<br /><br />';
      $content .= $_SESSION['translate']->it('The email address for you stored in the database is malformed.  Please click the button below to correct.') . '</span>';

      $content .= '<form action="index.php" method="post">';
      $content .= '<input type="hidden" name="module" value="users" />';
      $content .= '<input type="hidden" name="norm_user_op" value="user_options" />';
      $content .= '<input type="submit" name="user_option[update_info]" value="';
      $content .= $_SESSION['translate']->it('Update My Information') . '" />';
      $content .= '</form>';
    }

    $GLOBALS['CNT_mailinglists_menu']['title'] = $title;
    $GLOBALS['CNT_mailinglists_menu']['content'] = $content;
  }// END FUNC showUserMenu


  /**
 * Deletes all Subscribers from a list and then removes the mailing list
 *
 * @author Greg Meiste <blindman1344 [at] users dot sourceforge dot net>
 * @param  int     $list   The list that is to be deleted.
 * @access public
 */
  function doDeleteList($list) {
    $core = $GLOBALS['core'];
    $core->sqlDelete('mod_mailinglists_subscribers', array('listID'=>$list));
    $core->sqlDelete('mod_mailinglists_anon_subscribers', array('listID'=>$list));
    $core->sqlDelete('mod_mailinglists_archives', array('listID'=>$list));
    $core->sqlDelete('mod_mailinglists_lists', array('id'=>$list));
  }// END FUNC doDeleteList


  /**
 * Asks the admin if they REALLY want to delete a list.
 *
 * @author Greg Meiste <blindman1344 [at] users dot sourceforge dot net>
 * @param  int     $list   List ID that is about to be deleted.
 * @access public
 */
  function showDeleteList($list) {
    if($_SESSION['OBJ_user']->allow_access('mailinglists', 'delete_lists')) {
      $tags['CAP_TEXT'] = $_SESSION['translate']->it('ARE YOU SURE YOU WANT TO DELETE THIS MAILING LIST?');
      $tags['REG_TEXT'] = $_SESSION['translate']->it('The subscribers to this list will be automatically unsubscribed.  Archived emails (if any) will be deleted.');

      $tags['YES_LINK'] = '<a href="index.php?module=mailinglists&amp;op=doDeleteList&amp;list=' . $list . '">';
      $tags['YES_LINK'] .= $_SESSION['translate']->it('Yes') . '</a>';
      $tags['NO_LINK'] = '<a href="index.php?module=mailinglists&amp;op=admin">';
      $tags['NO_LINK'] .= $_SESSION['translate']->it('No') . '</a>';

      $GLOBALS['CNT_mailinglists_menu']['title'] = $_SESSION['translate']->it('ARE YOU SURE?');
      $GLOBALS['CNT_mailinglists_menu']['content'] = PHPWS_Template::processTemplate($tags, 'mailinglists', 'delete.tpl');
    }
  }// END FUNC showDeleteList
  
  
  /**
 * Asks the admin if they REALLY want to add all users to the selected list.
 *
 * @author Greg Meiste <blindman1344 [at] users dot sourceforge dot net>
 * @param  int     $list   List ID that is about to have all users added to it.
 * @access public
 */
  function showAddAllUsers($list) {
    if($_SESSION['OBJ_user']->allow_access('mailinglists', 'edit_lists')) {
      $content = $_SESSION['translate']->it('All of the website users are about to be added to this list.  Is this what you want to do?');

      $content .= '<br /><br /><a href="index.php?module=mailinglists&amp;op=doAddAllUsers&amp;list=' . $list . '">';
      $content .= $_SESSION['translate']->it('Yes') . '</a> | ';
      $content .= '<a href="index.php?module=mailinglists&amp;op=admin">';
      $content .= $_SESSION['translate']->it('No') . '</a>';

      $GLOBALS['CNT_mailinglists_menu']['title'] = $_SESSION['translate']->it('ARE YOU SURE?');
      $GLOBALS['CNT_mailinglists_menu']['content'] = $content;
    }
  }// END FUNC showAddAllUsers


  /**
 * Adds all of the users of the website to a list
 *
 * @author Greg Meiste <blindman1344 [at] users dot sourceforge dot net>
 * @param  int     $list   The list that is to be deleted.
 * @access public
 */
  function doAddAllUsers($list) {
    $numUsers = $GLOBALS['core']->sqlMaxValue('mod_users', 'user_id');

    for($i=1; $i<=$numUsers; $i++) {
      if($GLOBALS['core']->sqlSelect('mod_users', array('user_id'=>$i)) != NULL) {
        $userResult = $GLOBALS['core']->sqlSelect('mod_users', array('user_id'=>$i));
        if(PHPWS_User::isUserApproved($userResult[0]['username'])) {
          $result = $GLOBALS['core']->sqlSelect('mod_mailinglists_subscribers', array('userID'=>$i,'listID'=>$list));
          if($result == NULL) {
            $this->subscribeUser($i, $list);
          }

          $result = $GLOBALS['core']->sqlSelect('mod_mailinglists_useroptions', array('userID'=>$i));
          if($result == NULL) {
            $GLOBALS['core']->sqlInsert(array('userID'=>$i, 'htmlEmail'=>1), 'mod_mailinglists_useroptions');
          }
        }
      }
    }
  }// END FUNC doAddAllUsers


  /**
 * Shows form to send out an email to a certain list.
 *
 * @author Greg Meiste <blindman1344 [at] users dot sourceforge dot net>
 * @param  int     $list   List ID that is about to receive an email
 * @param  int     $email  ID of the saved email that should be used, if needed
 * @access public
 */
  function showEmailForm($list, $email=NULL, $name=NULL, $htmlMessage=NULL, $mladmin=1, $import=NULL) {
    $result = $GLOBALS['core']->sqlSelect('mod_mailinglists_conf');
    if(($_SESSION['OBJ_user']->allow_access('mailinglists', 'send_emails')) || ($result[0]['userSend'])) {

    $title = $_SESSION['translate']->it('Mailing Lists Administration') . ': ';
    $title .= $_SESSION['translate']->it('Send Email');
    
    if($mladmin) {
      $menutags['HREF'] = './index.php?module=mailinglists&amp;op=admin';
      $menutags['TITLE'] = $_SESSION['translate']->it('Back to Main Menu');
      $tags['MENU'] = PHPWS_Template::processTemplate($menutags, 'mailinglists', 'tab/inactive.tpl');

      $menutags['HREF'] = './index.php?module=mailinglists&amp;op=loadEmail&amp;list=' . $list;
      $menutags['TITLE'] = $_SESSION['translate']->it('Load Saved Message');
      $menutags['HELP'] = $_SESSION['OBJ_help']->show_link('mailinglists', 'loadSaved');
      $tags['MENU'] .= PHPWS_Template::processTemplate($menutags, 'mailinglists', 'tab/inactive.tpl');
      
      $menutags['HREF'] = './index.php?module=mailinglists&amp;op=showImport&amp;list=' . $list;
      $menutags['TITLE'] = $_SESSION['translate']->it('Import Message From Module');
      $menutags['HELP'] = $_SESSION['OBJ_help']->show_link('mailinglists', 'moduleImport');
      $tags['MENU'] .= PHPWS_Template::processTemplate($menutags, 'mailinglists', 'tab/inactive.tpl');
    }

    $result = $GLOBALS['core']->sqlSelect('mod_mailinglists_lists', 'id', $list);
    foreach ($result as $row) {
      $tags['LISTNAME'] = $row['name'];
    }
    $tags['EMAILING'] = $_SESSION['translate']->it('Emailing');
    
    $countResult = $GLOBALS['core']->sqlSelect('mod_mailinglists_subscribers', array('listID'=>$list, 'active'=>1));
    $numberTotal = sizeof($countResult);
    $countResult = $GLOBALS['core']->sqlSelect('mod_mailinglists_anon_subscribers', array('listID'=>$list, 'active'=>1));
    $numberTotal += sizeof($countResult);
    if(!$numberTotal) {
      $tags['MSG'] = $_SESSION['translate']->it('There currently are not any active subscribers to this list.');
    }

    if($email != NULL) {
      $result = $GLOBALS['core']->sqlSelect('mod_mailinglists_saves', 'id', $email);
      foreach ($result as $row) {
        $name = $row['name'];
        $message = $row['message'];
        $htmlMessage = $row['htmlMessage'];
      }
    }
    else if($import != NULL) {
      if($import['module'] == 'article')
      {
        require_once(PHPWS_SOURCE_DIR.'mod/article/class/ArticleManager.php');
	$_SESSION['SES_ART_master'] = new PHPWS_ArticleManager;
        $temp = new PHPWS_Article($import['id']);
	$result = $GLOBALS['core']->sqlSelect('mod_article', 'id', $import['id']);
	$name = $result[0]['title'];
	$htmlMessage = str_replace("\n", '', $temp->view(TRUE, TRUE));
	$message = strip_tags($temp->view(TRUE, TRUE));
      }
      if($import['module'] == 'announce')
      {
        require_once(PHPWS_SOURCE_DIR.'mod/announce/class/Announcement.php');
	$ann = new PHPWS_Announcement($import['id']);
	$name = $ann->_subject;
	$htmlMessage = $ann->_summary . "\n\n" . $ann->_body;
	$message = strip_tags($ann->_summary . "\n\n" . $ann->_body);
      }
      if($import['module'] == 'calendar')
      {
	$result = $GLOBALS['core']->sqlSelect('mod_calendar_events', 'id', $import['id']);
	$name = $result[0]['title'];
	require_once(PHPWS_SOURCE_DIR . '/mod/calendar/class/Calendar.php');
        require_once(PHPWS_SOURCE_DIR . '/mod/calendar/class/Event.php');
	require_once(PHPWS_SOURCE_DIR . '/mod/calendar/class/Display.php');
        $htmlMessage = str_replace("\n", '', PHPWS_Calendar_Display::viewEvent($import['id']));
	$message = strip_tags(PHPWS_Calendar_Display::viewEvent($import['id']));
      }
      $tags['MSG'] = $_SESSION['translate']->it('You should check the formatting on the imported message before sending.');
      $htmlMessage = str_replace(array('src="./','href="./','href="index','src="images','href="files'),
                                 array('src="http://'.PHPWS_HOME_HTTP,
                                       'href="http://'.PHPWS_HOME_HTTP,
                                       'href="http://'.PHPWS_HOME_HTTP.'index',
                                       'src="http://'.PHPWS_HOME_HTTP.'images',
                                       'href="http://'.PHPWS_HOME_HTTP.'files'),
                                 $htmlMessage);
    }
    else if($_POST['doSendEmail']) {
      $htmlMessage = stripslashes($_REQUEST['htmlMessage']);
      $message = stripslashes($_REQUEST['message']);
      $tags['MSG'] = $_SESSION['translate']->it('Please provide an Email Subject');
    }
    else if($_POST['preview']) {
      $name = stripslashes($_REQUEST['emailSubject']);
      $htmlMessage = stripslashes($_REQUEST['htmlMessage']);
      $message = stripslashes($_REQUEST['message']);
      $tags['PREVIEW'] = nl2br($htmlMessage);
    }
    else if($_POST['convert']) {
      $htmlMessage = stripslashes($htmlMessage);
      $message = strip_tags($htmlMessage);
    }
    
    $tags['FORM_BEGIN'] = '<form action="index.php" method="post" name="mailinglists_sendemail">';
    $tags['FORM_BEGIN'] .= PHPWS_Form::formHidden('module', 'mailinglists');
    $tags['FORM_BEGIN'] .= PHPWS_Form::formHidden('list', $list);
    $tags['FORM_BEGIN'] .= PHPWS_Form::formHidden('op', 'doSendEmail');
    $tags['FORM_BEGIN'] .= PHPWS_Form::formHidden('mladmin', $mladmin);

    $tags['SUBJECT_LABEL'] = $_SESSION['translate']->it('Email Subject');
    $tags['SUBJECT'] = PHPWS_Form::formTextField('emailSubject', $name, 40, 40);

    $tags['HTML_LABEL'] = $_SESSION['translate']->it('HTML Message');

    if($_SESSION['OBJ_user']->js_on) {
      $tags['HTML'] = PHPWS_WizardBag::js_insert('wysiwyg', 'mailinglists_sendemail', 'htmlMessage');
    }

    $tags['HTML'] .= PHPWS_Form::formTextArea('htmlMessage', $htmlMessage, 10, 60);

    $tags['CONVERT_BUTTON'] = PHPWS_Form::formSubmit($_SESSION['translate']->it('Convert HTML to Plain Text'), 'convert');
    $tags['CONVERT_HELP'] = $_SESSION['OBJ_help']->show_link('mailinglists', 'convert');

    $tags['PLAIN_LABEL'] = $_SESSION['translate']->it('Plain Text Message');
    $tags['PLAIN'] = PHPWS_Form::formTextArea('message', $message, 10, 60);

    $tags['PREVIEW_BUTTON'] = PHPWS_Form::formSubmit($_SESSION['translate']->it('Preview HTML Email'), 'preview');
    $tags['SEND_BUTTON'] = PHPWS_Form::formSubmit($_SESSION['translate']->it('Send Email - Do Not Click Twice'), 'doSendEmail');
    $tags['FORM_END'] = '</form>';

    $GLOBALS['CNT_mailinglists_menu']['title'] = $title;
    $GLOBALS['CNT_mailinglists_menu']['content'] = PHPWS_Template::processTemplate($tags, 'mailinglists', 'sendemail.tpl');
    }
  }// END FUNC showEmailForm


  /**
 * Lists the saved emails that can be selected to send out.
 *
 * @author Greg Meiste <blindman1344 [at] users dot sourceforge dot net>
 * @param  int     $list   List ID that should have an email loaded with selected message
 * @access public
 */
  function showLoadEmail($list) {
    if($_SESSION['OBJ_user']->allow_access('mailinglists')) {

    $title = $_SESSION['translate']->it('Mailing Lists Administration') . ': ';
    $title .= $_SESSION['translate']->it('Load Email');
    $menutags['HREF'] = './index.php?module=mailinglists&amp;op=admin';
    $menutags['TITLE'] = $_SESSION['translate']->it('Back to Main Menu');
    $tags['MENU'] = PHPWS_Template::processTemplate($menutags, 'mailinglists', 'tab/inactive.tpl');
    $menutags['HREF'] = './index.php?module=mailinglists&amp;op=sendEmail&amp;list=' . $list;
    $menutags['TITLE'] = $_SESSION['translate']->it('Back to Send Email');
    $tags['MENU'] .= PHPWS_Template::processTemplate($menutags, 'mailinglists', 'tab/inactive.tpl');

    $tags['TEXT'] = $_SESSION['translate']->it('Click on a email name to load it.');
    $tags['EMAILNAME_LABEL'] = $_SESSION['translate']->it('Email Name');
    $tags['MESSAGE_LABEL'] = $_SESSION['translate']->it('Message');
    $tags['LIST_ITEMS'] = '';

    $result = $GLOBALS['core']->sqlSelect('mod_mailinglists_saves', NULL, NULL, 'name');
    if($result != NULL) {
      foreach ($result as $row) {
        $id = $row['id'];
        $name = $row['name'];
        $message = $row['message'];

	$dots = '';
	if(strlen($message) > 100)
	  $dots = '...';
	$message = substr($message,0,100);

        PHPWS_WizardBag::toggle($row_class, ' class="bg_light"');
        $tags_row['ROW_CLASS'] = $row_class;

	$tags_row['NAME'] = '<a href="index.php?module=mailinglists&amp;op=doLoadEmail&amp;list=';
	$tags_row['NAME'] .= $list . '&amp;email=' . $id . '">' . $name . '</a>';
        $tags_row['MESSAGE'] = nl2br($message) . $dots;
        $tags['LIST_ITEMS'] .= PHPWS_Template::processTemplate($tags_row, 'mailinglists', 'savedemails/listrow.tpl');
      }
    } else {
      $tags['LIST_ITEMS'] .= '<tr><td colspan="2">' . $_SESSION['translate']->it("There aren't any saved emails in the database.") . '</td></tr>';
    }

    $content .= PHPWS_Template::processTemplate($tags, 'mailinglists', 'savedemails/list.tpl');
    $GLOBALS['CNT_mailinglists_menu']['title'] = $title;
    $GLOBALS['CNT_mailinglists_menu']['content'] = $content;
    }
  }// END FUNC showLoadEmail


  /**
 * Shows form to add/edit an Email to the database
 *
 * @author Greg Meiste <blindman1344 [at] users dot sourceforge dot net>
 * @access public
 */
  function showEditEmail($email=NULL, $subject=NULL, $htmlMessage=NULL) {
    if($_SESSION['OBJ_user']->allow_access('mailinglists', 'saved_emails')) {

    $title = $_SESSION['translate']->it('Mailing Lists Administration');
    $content = $this->adminNavBar();
    $content .= '<br />';

    $tags['SAVE_BUTTON'] = PHPWS_Form::formSubmit($_SESSION['translate']->it('Save Email'), 'doSaveEmail');
    $op = 'doAddEmail';

    if($email != NULL) {
      if(($subject == NULL) && ($htmlMessage == NULL)) {
        $result = $GLOBALS['core']->sqlSelect('mod_mailinglists_saves', 'id', $email);
        foreach ($result as $row) {
          $subject = $row['name'];
          $message = $row['message'];
          $htmlMessage = $row['htmlMessage'];
        }
      }
      else {
        $subject = stripslashes($subject);
        $htmlMessage = stripslashes($htmlMessage);
        $message = strip_tags($htmlMessage);
      }
      $tags['SAVE_BUTTON'] = PHPWS_Form::formSubmit($_SESSION['translate']->it('Edit Email'), 'doEditEmail');
      $op = 'doEditEmail';
      $extraHidden = PHPWS_Form::formHidden('email', $email);
    }
    else if(($subject != NULL) && ($htmlMessage != NULL)) {
      $subject = stripslashes($subject);
      $htmlMessage = stripslashes($htmlMessage);
      $message = strip_tags($htmlMessage);
    }
    else {
      $message = NULL;
    }

    $content .= '<form name="mailinglists_editemail" action="index.php" method="post">';
    $content .= PHPWS_Form::formHidden('module', 'mailinglists');
    $content .= PHPWS_Form::formHidden('op', $op);
    if(isset($extraHidden)) {
      $content .= $extraHidden;
    }
    $tags['SUBJECT_LABEL'] = $_SESSION['translate']->it('Email Subject');
    $tags['SUBJECT'] = PHPWS_Form::formTextField('emailSubject', $subject, 40, 40);
    $tags['HTML_LABEL'] = $_SESSION['translate']->it('HTML Message');

    if($_SESSION['OBJ_user']->js_on) {
      $tags['HTML'] = PHPWS_WizardBag::js_insert('wysiwyg', 'mailinglists_editemail', 'htmlMessage');
    }

    $tags['HTML'] .= PHPWS_Form::formTextArea('htmlMessage', $htmlMessage, 10, 60);
    $tags['CONVERT_BUTTON'] = PHPWS_Form::formSubmit($_SESSION['translate']->it('Convert HTML to Plain Text'), 'convert');
    $tags['CONVERT_HELP'] = $_SESSION['OBJ_help']->show_link('mailinglists', 'convert');
    $tags['PLAIN_LABEL'] = $_SESSION['translate']->it('Plain Text Message');

    $tags['PLAIN'] = PHPWS_Form::formTextArea('message', $message, 10, 60);
    $content .= PHPWS_Template::processTemplate($tags, 'mailinglists', 'savedemails/edit.tpl') . '</form>';

    $GLOBALS['CNT_mailinglists_menu']['title'] = $title;
    $GLOBALS['CNT_mailinglists_menu']['content'] = $content;
    }
  }// END FUNC showEditEmail


  /**
 * Asks the admin if they REALLY want to delete a saved email.
 *
 * @author Greg Meiste <blindman1344 [at] users dot sourceforge dot net>
 * @param  int     $email   Email ID that is about to be deleted.
 * @access public
 */
  function showDeleteEmail($email) {
    if($_SESSION['OBJ_user']->allow_access('mailinglists', 'saved_emails')) {
      $tags['CAP_TEXT'] = $_SESSION['translate']->it('ARE YOU SURE YOU WANT TO DELETE THIS EMAIL?');
      $tags['REG_TEXT'] = $_SESSION['translate']->it('Once deleted, you will no longer be able to load this saved email.');

      $tags['YES_LINK'] = '<a href="index.php?module=mailinglists&amp;op=doDeleteEmail&amp;email=' . $email . '">';
      $tags['YES_LINK'] .= $_SESSION['translate']->it('Yes') . '</a>';
      $tags['NO_LINK'] = '<a href="index.php?module=mailinglists&amp;op=savedEmails">';
      $tags['NO_LINK'] .= $_SESSION['translate']->it('No') . '</a>';

      $GLOBALS['CNT_mailinglists_menu']['title'] = $_SESSION['translate']->it('ARE YOU SURE?');
      $GLOBALS['CNT_mailinglists_menu']['content'] = PHPWS_Template::processTemplate($tags, 'mailinglists', 'delete.tpl');
    }
  }// END FUNC showDeleteEmail


  /**
 * Sends the email out to entire mailing list
 *
 * @author Greg Meiste <blindman1344 [at] users dot sourceforge dot net>
 * @param  int     $list   The list ID that gets blessed with this email.
 * @access public
 */
  function doSendEmail($list, $sender=NULL, $displayResult=TRUE) {
    if($sender == NULL) {
      $sender = $_SESSION['OBJ_user']->username;
    }
    
    if($_POST['message'] == NULL) {
      $_POST['message'] = strip_tags($_POST['htmlMessage']);
    }
    if($_POST['htmlMessage'] == NULL) {
      $_POST['htmlMessage'] = $_POST['message'];
    }

    $conf = $GLOBALS['core']->sqlSelect('mod_mailinglists_conf');
    if($conf[0]['footer']) {
      $message = stripslashes($_POST['message']) . "\n\n" . $conf[0]['footerMessage'];
      $htmlMessage = stripslashes($_POST['htmlMessage']) . "\n\n" . $conf[0]['footerHtmlMessage'];
    }
    else {
      $message = stripslashes($_POST['message']);
      $htmlMessage = stripslashes($_POST['htmlMessage']);
    }

    $result = $GLOBALS['core']->sqlSelect('mod_mailinglists_lists', 'id', $list);
    if($result != NULL) {
      $theDateSent = date('Y-m-d H:i:s');

      if($result[0]['archive']) {
        $GLOBALS['core']->sqlInsert(array('listID'=>$list, 'subject'=>$_POST['emailSubject'], 'message'=>$htmlMessage, 'dateSent'=>$theDateSent, 'sentBy'=>$sender), 'mod_mailinglists_archives');
      }

      $GLOBALS['core']->sqlUpdate(array('lastSent'=>$theDateSent, 'lastSentBy'=>$sender), 'mod_mailinglists_lists', 'id', $list);

      $fromName = $result[0]['fromName'];
      $fromEmail = $result[0]['fromEmail'];
    }
    
    if($conf[0]['subjectPrefix']) {
      $theSubject = $result[0]['subjectPrefix'] . ' ' . stripslashes($_POST['emailSubject']);
    }
    else {
      $theSubject = stripslashes($_POST['emailSubject']);
    }

    $numberSent = 0;
    $result = $GLOBALS['core']->sqlSelect('mod_mailinglists_subscribers', array('listID'=>$list, 'active'=>1));
    $numberTotal = sizeof($result);
    if($result != NULL) {
      foreach ($result as $user) {
        $userResult = $GLOBALS['core']->sqlSelect('mod_users', 'user_id', $user['userID']);
        $typeResult = $GLOBALS['core']->sqlSelect('mod_mailinglists_useroptions', 'userID', $user['userID']);
        if($userResult != NULL) {
          $url = 'http://' . PHPWS_HOME_HTTP . 'index.php?module=mailinglists&op=remove&key=';
          $url .= $user['activeKey'] . '&list=' . $list . '&user=' . $user['userID'];

          if($typeResult[0]['htmlEmail']) {
            $crlf = "\n";
            $hdrs = array(
                         'From'    => $fromName . ' <' . $fromEmail . '>',
                         'Subject' => $theSubject
                         );

            $mime = new Mail_mime($crlf);

            $mime->setTXTBody(str_replace('[URL]', $url, $message));
            $mime->setHTMLBody('<html><body>' . nl2br(str_replace('[URL]', $url, $htmlMessage)) . '</body></html>');

            $body = $mime->get();
            $hdrs = $mime->headers($hdrs);

            $mail =& Mail::factory('mail');
	    if($mail->send($userResult[0]['email'], $hdrs, $body))
	      $numberSent++;
          }
          else {
	    $from = 'From: ' . $fromName . ' <' . $fromEmail . ">\n";
	    if(mail($userResult[0]['email'], $theSubject, str_replace('[URL]', $url, $message), $from))
	      $numberSent++;
          }
        }
      }
    }
    
    $result = $GLOBALS['core']->sqlSelect('mod_mailinglists_anon_subscribers', array('listID'=>$list, 'active'=>1));
    $numberTotal += sizeof($result);
    if($result != NULL) {
      foreach ($result as $user) {
        $url = 'http://' . PHPWS_HOME_HTTP . 'index.php?module=mailinglists&op=removeAnon&key=';
        $url .= $user['activeKey'] . '&list=' . $list . '&user=' . $user['id'];

        if($user['html']) {
          $crlf = "\n";
          $hdrs = array(
                       'From'    => $fromName . ' <' . $fromEmail . '>',
                       'Subject' => $theSubject
                       );

          $mime = new Mail_mime($crlf);

          $mime->setTXTBody(str_replace('[URL]', $url, $message));
          $mime->setHTMLBody('<html><body>' . nl2br(str_replace('[URL]', $url, $htmlMessage)) . '</body></html>');

          $body = $mime->get();
          $hdrs = $mime->headers($hdrs);

          $mail =& Mail::factory('mail');
	  if($mail->send($user['email'], $hdrs, $body))
	    $numberSent++;
        }
        else {
	  $from = 'From: ' . $fromName . ' <' . $fromEmail . ">\n";
          if(mail($user['email'], $theSubject, str_replace('[URL]', $url, $message), $from))
	    $numberSent++;
        }
      }
    }

    if($displayResult) {
      $title = $_SESSION['translate']->it('Mailing Lists Administration') . ': ';
      if($numberSent == $numberTotal) {
        $title .= $_SESSION['translate']->it('Email Sent');
        $content = '<b>' . $theSubject . '</b><br /><br />' . stripslashes(nl2br($htmlMessage));
      }
      else if($numberSent == 0) {
        $title .= $_SESSION['translate']->it('A PROBLEM OCCURRED!');
        $content = $_SESSION['translate']->it('Email is not correctly set up on this server.');
      }
      else {
        $title .= $_SESSION['translate']->it('A PROBLEM OCCURRED!');
        $content = $_SESSION['translate']->it('Not all emails were successfully sent. Check the subscriber list.');
	$content .= '<br />' . $_SESSION['translate']->it('Total subscribers') . ': ' . $numberTotal;
	$content .= '<br />' . $_SESSION['translate']->it('Total emails sent') . ': ' . $numberSent;
      }

      if($_REQUEST['mladmin']) {
        $content .= '<br /><br /><a href="index.php?module=mailinglists&amp;op=admin">';
        $content .= $_SESSION['translate']->it('Back to Main Menu') . '</a>';
      }
      $GLOBALS['CNT_mailinglists_menu']['title'] = $title;
      $GLOBALS['CNT_mailinglists_menu']['content'] = $content;
    }
  }// END FUNC doSendEmail


  /**
 * Displays the archived emails for a mailing list
 *
 * @author Greg Meiste <blindman1344 [at] users dot sourceforge dot net>
 * @param  int     $list   List ID of the mailing list
 * @access public
 */
  function showArchives($list) {
    $listResult = $GLOBALS['core']->sqlSelect('mod_mailinglists_lists', 'id', $list);
    $userResult = $GLOBALS['core']->sqlSelect('mod_mailinglists_subscribers', array('userID'=>$_SESSION['OBJ_user']->user_id, 'listID'=>$list));

    if (($listResult[0]['status'] == 'on') || ($userResult != NULL)) {
      $tags['INSTRUCTIONS'] = $_SESSION['translate']->it('Click on the email subject to view the message.');

      $result = $GLOBALS['core']->sqlSelect('mod_mailinglists_archives', 'listID', $list, array('id desc'));
      if($result != NULL) {
        $tags['LINKS'] = '';
        foreach ($result as $email) {
          $link['DATE'] = $email['dateSent'];
          $link['LINK'] = '<a href="index.php?module=mailinglists&amp;op=archivedEmail&amp;list=';
          $link['LINK'] .= $list . '&amp;email=' . $email['id'] . '">' . $email['subject'] . '</a>';
          $tags['LINKS'] .= PHPWS_Template::processTemplate($link, 'mailinglists', 'archive/link.tpl');
        }
      }
      else {
        $tags['NONE'] = $_SESSION['translate']->it('No archived emails at this time.');
      }

      $GLOBALS['CNT_mailinglists_menu']['title'] = $_SESSION['translate']->it('Mailing List Archive');
      $GLOBALS['CNT_mailinglists_menu']['content'] = PHPWS_Template::processTemplate($tags, 'mailinglists', 'archive/list.tpl');
    }
  }// END FUNC showArchives


  /**
 * Displays a single archived email
 *
 * @author Greg Meiste <blindman1344 [at] users dot sourceforge dot net>
 * @param  int     $email   Email ID of the email getting displayed
 * @param  int     $list    List ID that the email belongs to
 * @access public
 */
  function showArchivedEmail($email, $list) {
    $title = $_SESSION['translate']->it('Mailing List Archive') . ': ';
    $title .= $_SESSION['translate']->it('View Email');

    $result = $GLOBALS['core']->sqlSelect('mod_mailinglists_archives', id, $email);
    if($result != NULL) {
      foreach ($result as $email) {
        $content = '<b>' . $email['subject'] . '</b><br /><br />' . stripslashes(nl2br($email['message']));
      }
    }

    $content .= '<br /><hr /><a href="index.php?module=mailinglists&amp;op=archives&amp;list=';
    $content .= $list . '">' . $_SESSION['translate']->it('Back to Archive List') . '</a>';

    $GLOBALS['CNT_mailinglists_menu']['title'] = $title;
    $GLOBALS['CNT_mailinglists_menu']['content'] = $content;
  }// END FUNC showArchivedEmail


  /**
 * Adds a user to a mailing list
 *
 * @author Greg Meiste <blindman1344 [at] users dot sourceforge dot net>
 * @param  int     $user   userID of the user to be added
 * @param  int     $list   listID of the list to accept the user
 * @access public
 */
  function subscribeUser($user, $list) {
    $result = $GLOBALS['core']->sqlSelect('mod_mailinglists_lists', 'id', $list);
    if($result != NULL) {
      if($result[0]['doubleOptIn']) {
        $fromName = $result[0]['fromName'];
        $fromEmail = $result[0]['fromEmail'];
        $key = rand(1,999999999);

        $GLOBALS['core']->sqlInsert(array('userID'=>$user,'listID'=>$list,'active'=>0,'activeKey'=>$key,'dateSubscribed'=>date('Y-m-d')), 'mod_mailinglists_subscribers', 0, 0, 0, 0);

        $headers = 'From: ' . $fromName . ' <' . $fromEmail . ">\r\n";

        $url = 'http://' . PHPWS_HOME_HTTP . 'index.php?module=mailinglists&op=confirm&key=';
        $url .= $key . '&list=' . $list . '&user=' . $user;
        $message = str_replace(array('[LISTNAME]','[URL]'), array($result[0]['name'], $url), $result[0]['optInMessage']);

        $userResult = $GLOBALS['core']->sqlSelect('mod_users', user_id, $user);
        if($userResult != NULL) {
          $emailSubject = $_SESSION['translate']->it('Confirmation Email') . ': ' . $result[0]['name'];
          mail($userResult[0]['email'], $emailSubject, $message, $headers);
        }
      }
      else {
        $key = rand(1,999999999);
        $GLOBALS['core']->sqlInsert(array('userID'=>$user,'listID'=>$list,'active'=>1,'activeKey'=>$key,'dateSubscribed'=>date('Y-m-d')), 'mod_mailinglists_subscribers', 0, 0, 0, 0);
        $this->sendSubscribeEmail($user,$list);
      }
    }
  }// END FUNC subscribeUser


  /**
 * Sends the subscribe email to a user if option is set
 *
 * @author Greg Meiste <blindman1344 [at] users dot sourceforge dot net>
 * @param  int     $user   userID of the user to receive email
 * @param  int     $list   listID of the list that user is now subscribed to
 * @access public
 */
  function sendSubscribeEmail($user, $list, $anon=NULL) {
    $result = $GLOBALS['core']->sqlSelect('mod_mailinglists_lists', 'id', $list);
    if($result != NULL) {
      if($result[0]['sEmail'] == 1) {
        $fromName = $result[0]['fromName'];
        $fromEmail = $result[0]['fromEmail'];

        $message = str_replace('[LISTNAME]', $result[0]['name'], $result[0]['subscribeMessage']);
        $headers = 'From: ' . $fromName . ' <' . $fromEmail . ">\r\n";
        $emailSubject = $_SESSION['translate']->it('Welcome to') . ' ' . $result[0]['name'];

        if($anon == NULL)
        {
          $userResult = $GLOBALS['core']->sqlSelect('mod_users', user_id, $user);
          if($userResult != NULL) {
            mail($userResult[0]['email'], $emailSubject, $message, $headers);
          }
	}
	else
	{
	  mail($user, $emailSubject, $message, $headers);
	}
      }
    }
  }// END FUNC sendSubscribeEmail


  /**
 * Sends the unsubscribe email to a user if option is set
 *
 * @author Greg Meiste <blindman1344 [at] users dot sourceforge dot net>
 * @param  int     $user   userID of the user to receive email
 * @param  int     $list   listID of the list that user is now subscribed to
 * @access public
 */
  function sendUnsubscribeEmail($user, $list, $anon=NULL) {
    $result = $GLOBALS['core']->sqlSelect('mod_mailinglists_lists', 'id', $list);
    if($result != NULL) {
      if($result[0]['uEmail'] == 1) {
        $fromName = $result[0]['fromName'];
        $fromEmail = $result[0]['fromEmail'];

        $message = str_replace('[LISTNAME]', $result[0]['name'], $result[0]['unsubscribeMessage']);
        $headers = 'From: ' . $fromName . ' <' . $fromEmail . ">\r\n";
        $emailSubject = $_SESSION['translate']->it('Removed from') . ' ' . $result[0]['name'];

        if($anon == NULL)
        {
          $userResult = $GLOBALS['core']->sqlSelect('mod_users', user_id, $user);
          if($userResult != NULL) {
            mail($userResult[0]['email'], $emailSubject, $message, $headers);
          }
	}
	else
	{
	  mail($user, $emailSubject, $message, $headers);
	}
      }
    }
  }// END FUNC sendUnsubscribeEmail


  /**
 * The Mailing List config menu
 *
 * @author Greg Meiste <blindman1344 [at] users dot sourceforge dot net>
 * @access public
 */
  function showConfig($msg = NULL, $personal=NULL, $footer=NULL, $footerHtmlMessage=NULL) {
    if($_SESSION['OBJ_user']->allow_access('mailinglists', 'change_settings')) {

    $tags['MENU'] = $this->adminNavBar('settings');
    $tags['MSG'] = $msg;

    if((isset($personal)) && (isset($footer)) && (isset($footerHtmlMessage))) {
      $footerHtmlMessage = stripslashes($footerHtmlMessage);
      $footerMessage = strip_tags($footerHtmlMessage);
      $userSend = $_REQUEST['userSend'];
      $anonSubscribe = $_REQUEST['anonSubscribe'];
    }
    else {
      $result = $GLOBALS['core']->sqlSelect('mod_mailinglists_conf');
      foreach ($result as $row) {
        $personal = $row['personal'];
        $footer = $row['footer'];
        $footerMessage = $row['footerMessage'];
        $footerHtmlMessage = $row['footerHtmlMessage'];
	$userSend = $row['userSend'];
	$anonSubscribe = $row['anonSubscribe'];
	$subjectPrefix = $row['subjectPrefix'];
      }
    }

    $tags['FORM'] = '<form action="index.php" method="post" name="mailinglists_config">';
    $tags['FORM'] .= PHPWS_Form::formHidden('module', 'mailinglists');
    $tags['FORM'] .= PHPWS_Form::formHidden('op', 'chgConfig');

    $tags['BOX_HELP'] = $_SESSION['OBJ_help']->show_link('mailinglists', 'box');
    $tags['BOX_LABEL'] = $_SESSION['translate']->it("Show 'Your Mailing Lists' box on homepage");

    $tags['BOX_YES'] = PHPWS_Form::formRadio('personal', 'on', $personal, NULL, $_SESSION['translate']->it('Yes'));
    $tags['BOX_NO'] = PHPWS_Form::formRadio('personal', 'off', $personal, NULL, $_SESSION['translate']->it('No'));

    $tags['FOOTER_HELP'] = $_SESSION['OBJ_help']->show_link('mailinglists', 'footer');
    $tags['FOOTER_LABEL'] = $_SESSION['translate']->it('Attach footer to all emails');
    $tags['FOOTER_YES'] = PHPWS_Form::formRadio('footer', 1, $footer, NULL, $_SESSION['translate']->it('Yes')) . ' ';
    $tags['FOOTER_NO'] = PHPWS_Form::formRadio('footer', 0, $footer, NULL, $_SESSION['translate']->it('No'));
    
    $tags['USERSEND_HELP'] = $_SESSION['OBJ_help']->show_link('mailinglists', 'userSend');
    $tags['USERSEND_LABEL'] = $_SESSION['translate']->it('Allow registered website users send messages');
    $tags['USERSEND_YES_NA'] = PHPWS_Form::formRadio('userSend', 2, $userSend, NULL, $_SESSION['translate']->it('Yes, without approval')) . ' ';
    $tags['USERSEND_YES_WA'] = PHPWS_Form::formRadio('userSend', 1, $userSend, NULL, $_SESSION['translate']->it('Yes, approval required')) . ' ';
    $tags['USERSEND_NO'] = PHPWS_Form::formRadio('userSend', 0, $userSend, NULL, $_SESSION['translate']->it('No'));
    
    $tags['ANONSUBSCRIBE_HELP'] = $_SESSION['OBJ_help']->show_link('mailinglists', 'anonSubscribe');
    $tags['ANONSUBSCRIBE_LABEL'] = $_SESSION['translate']->it('Allow anonymous users to subscribe to lists');
    $tags['ANONSUBSCRIBE_YES'] = PHPWS_Form::formRadio('anonSubscribe', 1, $anonSubscribe, NULL, $_SESSION['translate']->it('Yes')) . ' ';
    $tags['ANONSUBSCRIBE_NO'] = PHPWS_Form::formRadio('anonSubscribe', 0, $anonSubscribe, NULL, $_SESSION['translate']->it('No'));
    
    $tags['SUBJECTPREFIX_HELP'] = $_SESSION['OBJ_help']->show_link('mailinglists', 'subPrefix');
    $tags['SUBJECTPREFIX_LABEL'] = $_SESSION['translate']->it('Attach a subject prefix to list messages');
    $tags['SUBJECTPREFIX_YES'] = PHPWS_Form::formRadio('subjectPrefix', 1, $subjectPrefix, NULL, $_SESSION['translate']->it('Yes')) . ' ';
    $tags['SUBJECTPREFIX_NO'] = PHPWS_Form::formRadio('subjectPrefix', 0, $subjectPrefix, NULL, $_SESSION['translate']->it('No'));

    $tags['FOOTHTML_HELP'] = $_SESSION['OBJ_help']->show_link('mailinglists', 'footHTML');
    $tags['FOOTHTML_LABEL'] = $_SESSION['translate']->it('Footer Message (HTML)');
    if($_SESSION['OBJ_user']->js_on) {
      $tags['FOOTHTML'] = PHPWS_WizardBag::js_insert('wysiwyg', 'mailinglists_config', 'footerHtmlMessage');
    }
    $tags['FOOTHTML'] .= PHPWS_Form::formTextArea('footerHtmlMessage', $footerHtmlMessage, 6, 60);

    $tags['CONVERT_HELP'] = $_SESSION['OBJ_help']->show_link('mailinglists', 'convert');
    $tags['CONVERT_BUTTON'] = PHPWS_Form::formSubmit($_SESSION['translate']->it('Convert HTML to Plain Text'), 'convert');

    $tags['FOOTPLAIN_HELP'] = $_SESSION['OBJ_help']->show_link('mailinglists', 'footPlain');
    $tags['FOOTPLAIN_LABEL'] = $_SESSION['translate']->it('Footer Message (Plain Text)');
    $tags['FOOTPLAIN'] = PHPWS_Form::formTextArea('footerMessage', $footerMessage, 6, 60);

    $tags['SUBMIT_BUTTON'] = PHPWS_Form::formSubmit($_SESSION['translate']->it('Update Settings'), 'do_chgconf');
    $tags['END_FORM'] = '</form>';

    $GLOBALS['CNT_mailinglists_menu']['title'] = $_SESSION['translate']->it('Mailing Lists Administration');
    $GLOBALS['CNT_mailinglists_menu']['content'] = PHPWS_Template::processTemplate($tags, 'mailinglists', 'settings.tpl');
    }
  }// END FUNC showConfig


  /**
 * Builds the admin navigation and returns the nav bar as a string ready
 * for display.
 *
 * @author Greg Meiste <blindman1344 [at] users dot sourceforge dot net>
 * @param   string     $tab      The name of the tab to make active
 * @return  string     $retVal   The admin navigation ready to be displayed
 * @access public
 */
  function adminNavBar($tab=NULL) {
    $retVal = '<table width="100%"><tr>';

    if($_SESSION['OBJ_user']->allow_access('mailinglists', 'create_lists')) {
      $tags1['TITLE'] = $_SESSION['translate']->it('Add Mailing List');
      $tags1['HREF'] = 'index.php?module=mailinglists&amp;op=addList';
      if($tab == 'addML') {
        $retVal .= PHPWS_Template::processTemplate($tags1, 'mailinglists', 'tab/active.tpl');
      }
      else {
        $retVal .= PHPWS_Template::processTemplate($tags1, 'mailinglists', 'tab/inactive.tpl');
      }
    }

    $tags2['TITLE'] = $_SESSION['translate']->it('List Mailing Lists');
    $tags2['HREF'] = 'index.php?module=mailinglists&amp;op=admin';
    if($tab == 'listML') {
      $retVal .= PHPWS_Template::processTemplate($tags2, 'mailinglists', 'tab/active.tpl');
    }
    else {
      $retVal .= PHPWS_Template::processTemplate($tags2, 'mailinglists', 'tab/inactive.tpl');
    }

    if($_SESSION['OBJ_user']->allow_access('mailinglists', 'saved_emails')) {
      $tags3['TITLE'] = $_SESSION['translate']->it('Saved Emails');
      $tags3['HREF'] = 'index.php?module=mailinglists&amp;op=savedEmails';
      $tags3['HELP'] = $_SESSION['OBJ_help']->show_link('mailinglists', 'savedEmails');
      if($tab == 'savedE') {
        $retVal .= PHPWS_Template::processTemplate($tags3, 'mailinglists', 'tab/active.tpl');
      }
      else {
        $retVal .= PHPWS_Template::processTemplate($tags3, 'mailinglists', 'tab/inactive.tpl');
      }
    }

    if($_SESSION['OBJ_user']->allow_access('mailinglists', 'change_settings')) {
      $tags4['TITLE'] = $_SESSION['translate']->it('Settings');
      $tags4['HREF'] = 'index.php?module=mailinglists&amp;op=config';
      if($tab == 'settings') {
        $retVal .= PHPWS_Template::processTemplate($tags4, 'mailinglists', 'tab/active.tpl');
      }
      else {
        $retVal .= PHPWS_Template::processTemplate($tags4, 'mailinglists', 'tab/inactive.tpl');
      }
    }

    $retVal .= '</tr></table>';
    return $retVal;

  }// END FUNC adminNavBar


  /**
 * Checks what the user options are.  If the user isn't found in the options
 * database table, default options are set.  Returns a string ready for display.
 *
 * @author Greg Meiste <blindman1344 [at] users dot sourceforge dot net>
 * @param   int        $userID   The name of the tab to make active
 * @return  string     $retVal   The admin navigation ready to be displayed
 * @access public
 */
  function getUserOptions($userID) {
    $result = $GLOBALS['core']->sqlSelect('mod_mailinglists_useroptions', 'userID', $userID);
    if($result != NULL) {
      foreach ($result as $row) {
        $htmlEmail = $row['htmlEmail'];
      }
    }
    else {
      $htmlEmail = 1;
      $GLOBALS['core']->sqlInsert(array('userID'=>$userID, 'htmlEmail'=>$htmlEmail), 'mod_mailinglists_useroptions');
    }

    $retVal = '<table width="100%"><tr><td align="center">';
    $retVal .= '<form action="index.php" method="post">';
    $retVal .= PHPWS_Form::formHidden('module', 'mailinglists');
    $retVal .= PHPWS_Form::formHidden('op', 'user_options');
    $retVal .= PHPWS_Form::formHidden('userID', $userID);

    if($htmlEmail) {
      $actions['1'] = $_SESSION['translate']->it('HTML');
      $actions['0'] = $_SESSION['translate']->it('Plain Text');
    }
    else {
      $actions['0'] = $_SESSION['translate']->it('Plain Text');
      $actions['1'] = $_SESSION['translate']->it('HTML');
    }

    $tags['FORMAT_SELECT'] = PHPWS_Form::formSelect('htmlEmail', $actions);
    $tags['ACTION_BUTTON'] = PHPWS_Form::formSubmit($_SESSION['translate']->it('Set'));
    $tags['FORMAT_TEXT'] = $_SESSION['translate']->it('Format to receive emails');
    $tags['HELP'] = $_SESSION['OBJ_help']->show_link('mailinglists', 'emailFormat');

    $retVal .= PHPWS_Template::processTemplate($tags, 'mailinglists', 'user_options.tpl');
    $retVal .= '</form>';
    $retVal .= '</td></tr></table>';

    return $retVal;

  }// END FUNC getUserOptions
  
  
 /**
 * Shows subscriber admin for a mailing list
 *
 * @author Greg Meiste <blindman1344 [at] users dot sourceforge dot net>
 * @access public
 */
  function subscriberAdmin($list, $msg=NULL) {
    $title = $_SESSION['translate']->it('Mailing Lists Administration');
    $content = $this->adminNavBar();
    
    if($msg != NULL) {
      $content .= '<br /><b>' . $msg . '</b><br />';
    }
    
    $content .= '<br />';

    if($_SESSION['OBJ_user']->allow_access('mailinglists', 'subscriber_admin')) {
      $result = $GLOBALS['core']->sqlSelect('mod_mailinglists_lists', 'id', $list);
      $tags['LISTNAME'] = $_SESSION['translate']->it('Subscriber Admin') . ': ' . $result[0]['name'];
      
      $tags['FORM'] = '<form name="mailinglists_subscriberAdmin" action="index.php" method="post">';
      $tags['FORM'] .= PHPWS_Form::formHidden('module', 'mailinglists');
      $tags['FORM'] .= PHPWS_Form::formHidden('op', 'subscriberAdmin');
      $tags['FORM'] .= PHPWS_Form::formHidden('list', $list);
      
      $tags['MEMBER_LABEL'] = $_SESSION['translate']->it('Web Site Members');
      $tags['USER_HELP'] = $_SESSION['OBJ_help']->show_link('mailinglists', 'subscriberAdmin');
      $tags['USER'] = PHPWS_Form::formTextField('subscriber', $_SESSION['translate']->it('Username'), 20, 20);
      $tags['ADD_BUTTON'] = PHPWS_Form::formSubmit($_SESSION['translate']->it('Subscribe'), 'subscribe');
      $tags['REMOVE_BUTTON'] = PHPWS_Form::formSubmit($_SESSION['translate']->it('Unsubscribe'), 'unsubscribe');
      
      $tags['ANON_LABEL'] = $_SESSION['translate']->it('Other Users');
      $tags['ANON_HELP'] = $_SESSION['OBJ_help']->show_link('mailinglists', 'anonAdmin');
      $tags['ANON_EMAIL'] = PHPWS_Form::formTextField('email', $_SESSION['translate']->it('Email Address'), 20, 50);
      $tags['ANON_ADD'] = PHPWS_Form::formSubmit($_SESSION['translate']->it('Subscribe'), 'anon_add');
      $tags['ANON_REMOVE'] = PHPWS_Form::formSubmit($_SESSION['translate']->it('Unsubscribe'), 'anon_remove');

      $tags['ENDFORM'] = '</form>';
      
      if(!isset($PAGER_section)) {
        unset($_SESSION['Pager']);
      }

      $sub = PHPWS_TBL_PREFIX . 'mod_mailinglists_subscribers';
      $user = PHPWS_TBL_PREFIX . 'mod_users';
      $sql = "SELECT $sub.userID, $sub.active, $sub.dateSubscribed, $user.username, $user.email FROM $sub, $user WHERE $sub.listID=$list AND $user.user_id=$sub.userID ORDER BY $user.username";
      $result = $GLOBALS['core']->getAll($sql);

      $tags['LISTINGTITLE'] = $_SESSION['translate']->it('Subscribed Users');
    
      $data = array();
      if($result != NULL) {
        if(!isset($_SESSION['Pager'])) {
	  foreach ($result as $row) {
	    PHPWS_WizardBag::toggle($row_class, ' class="bg_light"');
            $tags_row['ROW_CLASS'] = $row_class;
	    $tags_row['ACTION'] = "<a href=\"./index.php?module=mailinglists&amp;op=subscriberAdmin&amp;list=$list&amp;subscriber=" . $row['username'] . '&amp;unsubscribe=1">';
	    $tags_row['ACTION'] .= $_SESSION['translate']->it('Unsubscribe') . '</a>';
	    $tags_row['USER'] = $row['username'];
	    $tags_row['EMAIL'] = $row['email'];
	    $tags_row['DATE'] = $row['dateSubscribed'];
	      
	    if($row['active']) {
	      $tags_row['ACTIVE'] = $_SESSION['translate']->it('Yes');
	    }
	    else {
	      $tags_row['ACTIVE'] = $_SESSION['translate']->it('No');
	    }
	    $data[] = PHPWS_Template::processTemplate($tags_row, 'mailinglists', 'subscriberAdmin/row.tpl');
          }
	}
      }

      $result = $GLOBALS['core']->sqlSelect('mod_mailinglists_anon_subscribers', 'listID', $list, 'email');

      if($result != NULL) {
        if(!isset($_SESSION['Pager'])) {
	  foreach ($result as $row) {
	    PHPWS_WizardBag::toggle($row_class, ' class="bg_light"');
            $tags_row['ROW_CLASS'] = $row_class;
	    $tags_row['ACTION'] = "<a href=\"./index.php?module=mailinglists&amp;op=subscriberAdmin&amp;list=$list&amp;email=" . $row['email'] . '&amp;anon_remove=1">';
	    $tags_row['ACTION'] .= $_SESSION['translate']->it('Unsubscribe') . '</a>';
	    $tags_row['USER'] = '<i>' . $_SESSION['translate']->it('Anonymous') . '</i>';
	    $tags_row['EMAIL'] = $row['email'];
	    $tags_row['DATE'] = $row['dateSubscribed'];
	      
	    if($row['active']) {
	      $tags_row['ACTIVE'] = $_SESSION['translate']->it('Yes');
	    }
	    else {
	      $tags_row['ACTIVE'] = $_SESSION['translate']->it('No');
	    }
	    $data[] = PHPWS_Template::processTemplate($tags_row, 'mailinglists', 'subscriberAdmin/row.tpl');
          }
	}
      }

      if(sizeof($data) && !isset($_SESSION['Pager'])) {
        $_SESSION['Pager'] = new PHPWS_Pager;
        $_SESSION['Pager']->setData($data);
        $_SESSION['Pager']->setLinkBack("./index.php?module=mailinglists&amp;op=subscriberAdmin&amp;list=$list&amp;listing=anon");
      }
      else {
	$tags['NONE'] = $_SESSION['translate']->it('None');
      }

      if($_SESSION['Pager']) {
        $_SESSION['Pager']->pageData();

	$tags['USER_LABEL'] = $_SESSION['translate']->it('Username');
	$tags['EMAIL_LABEL'] = $_SESSION['translate']->it('User Email Address');
	$tags['DATE_LABEL'] = $_SESSION['translate']->it('Date Subscribed');
        $tags['ACTIVE_LABEL'] = $_SESSION['translate']->it('Activated');
	$tags['ACTION_LABEL'] = $_SESSION['translate']->it('Action');
        $tags['LISTING'] = $_SESSION['Pager']->getData();
        $tags['SECTIONLINKS'] = $_SESSION['Pager']->getBackLink() . ' ' . $_SESSION['Pager']->getSectionLinks() . ' ' . $_SESSION['Pager']->getForwardLink();
        $tags['SECTIONINFO'] = $_SESSION['Pager']->getSectionInfo();
        $tags['LIMITLINKS'] = $_SESSION['Pager']->getLimitLinks();
      }

      $content .= PHPWS_Template::processTemplate($tags, 'mailinglists', 'subscriberAdmin/user.tpl');
      $GLOBALS['CNT_mailinglists_menu']['title'] = $title;
      $GLOBALS['CNT_mailinglists_menu']['content'] = $content;
    }
  }// END FUNC subscriberAdmin


/**
 * Displays the menu that allows anonymous users to subscribe/unsubscribe to mailing lists.
 *
 * @author Shaun Murray <shaun [at] aegisdesign dot co dot uk>
 * @access public
 */
  function showAnonMenu() {
    $result = $GLOBALS['core']->sqlSelect('mod_mailinglists_conf');

    if($result[0]['anonSubscribe'])
    {
      $title = $_SESSION['translate']->it('Mailing Lists');

      $theLists = $GLOBALS['core']->sqlSelect('mod_mailinglists_lists', 'status', 'on');
      if($theLists != NULL) {
        $content = '<form action="index.php" method="post">';
        $content .= PHPWS_Form::formHidden('module', 'mailinglists');
        $content .= PHPWS_Form::formHidden('op', 'doAnonSubscribe');

        $blocktags['INSTRUCTIONS'] = $_SESSION['translate']->it('Choose a list');
        $blocktags['EMAIL_LABEL'] = $_SESSION['translate']->it('Email Address');
        $blocktags['EMAIL'] = PHPWS_Form::formTextField('emailAddress', NULL, 18, 50);
        $blocktags['SUBSCRIBE'] = PHPWS_Form::formSubmit($_SESSION['translate']->it('Subscribe'), 'subscribe');
        $blocktags['HTML_LABEL'] = $_SESSION['translate']->it('HTML');
        $blocktags['TEXT_LABEL'] = $_SESSION['translate']->it('Text');
        $blocktags['HTML'] = PHPWS_Form::formRadio('format', 1, 1);
        $blocktags['TEXT'] = PHPWS_Form::formRadio('format', 0, 1);

        $listArray = array();
        foreach ($theLists as $row) {
          $listArray[$row['id']] = $row['name'];
        }
        $blocktags['LISTS'] = PHPWS_Form::formSelect('list', $listArray);
        $content .= PHPWS_Template::processTemplate($blocktags, 'mailinglists', 'anon_box.tpl');
        $content .= '</form>';

        $GLOBALS['CNT_mailinglists_menu']['title'] = $title;
        $GLOBALS['CNT_mailinglists_menu']['content'] = $content;
      }
    }
  }// END FUNC showAnonMenu


/**
 * Displays the block that allows anonymous users to subscribe/unsubscribe to mailing lists.
 *
 * Changed to only display the block if there are mailing lists - singletrack
 *
 * @author Greg Meiste <blindman1344 [at] users dot sourceforge dot net>
 * @access public
 */
  function showAnonBlock() {
    $result = $GLOBALS['core']->sqlSelect('mod_mailinglists_conf');

    if($result[0]['anonSubscribe'])
    {
      $title = $_SESSION['translate']->it('Mailing Lists');

      $theLists = $GLOBALS['core']->sqlSelect('mod_mailinglists_lists', 'status', 'on');
      if($theLists != NULL) {
        $content = '<form action="index.php" method="post">';
        $content .= PHPWS_Form::formHidden('module', 'mailinglists');
        $content .= PHPWS_Form::formHidden('op', 'doAnonSubscribe');

        $blocktags['INSTRUCTIONS'] = $_SESSION['translate']->it('Choose a list');
        $blocktags['EMAIL_LABEL'] = $_SESSION['translate']->it('Email Address');
        $blocktags['EMAIL'] = PHPWS_Form::formTextField('emailAddress', NULL, 18, 50);
        $blocktags['SUBSCRIBE'] = PHPWS_Form::formSubmit($_SESSION['translate']->it('Subscribe'), 'subscribe');
        $blocktags['HTML_LABEL'] = $_SESSION['translate']->it('HTML');
        $blocktags['TEXT_LABEL'] = $_SESSION['translate']->it('Text');
        $blocktags['HTML'] = PHPWS_Form::formRadio('format', 1, 1);
        $blocktags['TEXT'] = PHPWS_Form::formRadio('format', 0, 1);

        $listArray = array();
        foreach ($theLists as $row) {
          $listArray[$row['id']] = $row['name'];
        }
        $blocktags['LISTS'] = PHPWS_Form::formSelect('list', $listArray);
        $content .= PHPWS_Template::processTemplate($blocktags, 'mailinglists', 'anon_box.tpl');
        $content .= '</form>';

        $GLOBALS['CNT_mailinglists']['title'] = $title;
        $GLOBALS['CNT_mailinglists']['content'] = $content;
      }
    }
  }// END FUNC showAnonBlock
  
  
  /**
 * Adds an anonymous user to a mailing list
 *
 * @author Greg Meiste <blindman1344 [at] users dot sourceforge dot net>
 * @param  bool     $admin   Is the user an admin or not?
 * @access public
 */
  function subscribeAnon($admin = NULL) {
    $title = $_SESSION['translate']->it('Mailing Lists');
    
    $result = $GLOBALS['core']->sqlSelect('mod_mailinglists_lists', 'id', $_REQUEST['list']);
    
    if((PHPWS_Text::isValidInput($_REQUEST['emailAddress'], 'email')) && ($result != NULL)) {
      $userResult = $GLOBALS['core']->sqlSelect('mod_users', 'email', $_REQUEST['emailAddress']);
      $userResult2 = $GLOBALS['core']->sqlSelect('mod_mailinglists_anon_subscribers', array('email'=>$_REQUEST['emailAddress'], 'listID'=>$_REQUEST['list']));
      if(($userResult == NULL) && ($userResult2 == NULL)) {
        $key = rand(1,999999999);

        if($admin) {
          $GLOBALS['core']->sqlInsert(array('email'=>$_REQUEST['emailAddress'],'html'=>1,'listID'=>$_REQUEST['list'],'active'=>1,'activeKey'=>$key,'dateSubscribed'=>date('Y-m-d')), 'mod_mailinglists_anon_subscribers');
          $content = $_SESSION['translate']->it('List Updated');
        }
        else {
          $GLOBALS['core']->sqlInsert(array('email'=>$_REQUEST['emailAddress'],'html'=>$_REQUEST['format'],'listID'=>$_REQUEST['list'],'active'=>0,'activeKey'=>$key,'dateSubscribed'=>date('Y-m-d')), 'mod_mailinglists_anon_subscribers');
        
          $fromName = $result[0]['fromName'];
          $fromEmail = $result[0]['fromEmail'];
          $headers = 'From: ' . $fromName . ' <' . $fromEmail . ">\r\n";

          $url = 'http://' . PHPWS_HOME_HTTP . 'index.php?module=mailinglists&op=confirmAnon&key=';
          $url .= $key . '&list=' . $_REQUEST['list'];
          $message = str_replace(array('[LISTNAME]','[URL]'), array($result[0]['name'], $url), $result[0]['optInMessage']);

          $emailSubject = $_SESSION['translate']->it('Confirmation Email') . ': ' . $result[0]['name'];
          mail($_REQUEST['emailAddress'], $emailSubject, $message, $headers);
      
          $content = $_SESSION['translate']->it('Please check your email to confirm your subscription.');
        }
      }
      else {
        $content = $_SESSION['translate']->it('Error! The email address you submitted is already used.');
      }
    }
    else {
      $content = $_SESSION['translate']->it('Error! The email address you submitted is malformed. Please click the back button and try again.');
    }
    
    if($admin) {
      $this->subscriberAdmin($_REQUEST['list'], $content);
    }
    else {
      $GLOBALS['CNT_mailinglists_menu']['title'] = $title;
      $GLOBALS['CNT_mailinglists_menu']['content'] = $content;
    }
  }// END FUNC subscribeAnon
  
  
  /**
 * Echos a single email
 *
 * @author Greg Meiste <blindman1344 [at] users dot sourceforge dot net>
 * @param  int     $id   Email ID of the email getting displayed
 * @access public
 */
  function viewLimbo($id) {
    $result = $GLOBALS['core']->sqlSelect('mod_mailinglists_limbo', id, $id);
    if($result != NULL) {
      foreach ($result as $email) {
        echo $_SESSION['translate']->it('Sent By') . ': ' . $email['sentBy'] . '<br /><br />';
        echo '<b>' . $email['subject'] . '</b><br /><br />' . stripslashes(nl2br($email['htmlMessage']));
      }
    }
  }// END FUNC viewLimbo
  
  
  /**
 * Rejects an email from being sent
 *
 * @author Greg Meiste <blindman1344 [at] users dot sourceforge dot net>
 * @param  int     $id   Email ID of the email
 * @access public
 */
  function refuse($id) {
    $GLOBALS['core']->sqlDelete('mod_mailinglists_limbo', id, $id);
  }// END FUNC refuse
  
  
  /**
 * Calls send function after email approved.
 *
 * @author Greg Meiste <blindman1344 [at] users dot sourceforge dot net>
 * @param  int     $id   Email ID of the email
 * @access public
 */
  function approve($id) {
    $result = $GLOBALS['core']->sqlSelect('mod_mailinglists_limbo', id, $id);
    $_POST['message'] = $result[0]['message'];
    $_POST['htmlMessage'] = $result[0]['htmlMessage'];
    $_POST['emailSubject'] = $result[0]['subject'];
    PHPWS_mailinglists::doSendEmail($result[0]['listID'], $result[0]['sentBy'], 0);
  }// END FUNC approve
  
  
 /**
 * Lists the items that can be imported
 *
 * @author Greg Meiste <blindman1344 [at] users dot sourceforge dot net>
 * @param  int     $list   List ID that should have an email loaded with selected import
 * @access public
 */
  function showImport($list) {
    if($_SESSION['OBJ_user']->allow_access('mailinglists')) {

      $menutags['HREF'] = './index.php?module=mailinglists&amp;op=admin';
      $menutags['TITLE'] = $_SESSION['translate']->it('Back to Main Menu');
      $tags['MENU'] = PHPWS_Template::processTemplate($menutags, 'mailinglists', 'tab/inactive.tpl');
      $menutags['HREF'] = './index.php?module=mailinglists&amp;op=sendEmail&amp;list=' . $list;
      $menutags['TITLE'] = $_SESSION['translate']->it('Back to Send Email');
      $tags['MENU'] .= PHPWS_Template::processTemplate($menutags, 'mailinglists', 'tab/inactive.tpl');

      $tags['TEXT'] = $_SESSION['translate']->it('Select an item to import');
      
      if($GLOBALS['core']->moduleExists('article')) {
        $tags['ARTICLE_LABEL'] = $_SESSION['translate']->it('Articles');
        $tags['ARTICLE_TITLE'] = $_SESSION['translate']->it('Title');
        $tags['ARTICLE_DATE'] = $_SESSION['translate']->it('Date');
        $tags['ARTICLES'] = '';

        $result = $GLOBALS['core']->sqlSelect('mod_article', 'approved', 0, 'id desc', '!=', NULL, 5);
        if($result != NULL) {
          foreach ($result as $row) {
            PHPWS_WizardBag::toggle($row_class, ' class="bg_light"');
            $tags_row['ROW_CLASS'] = $row_class;

	    $tags_row['TITLE'] = '<a href="index.php?module=mailinglists&amp;op=doImport&amp;list=';
	    $tags_row['TITLE'] .= $list . '&amp;mod=article&amp;id=' . $row['id'] . '">' . $row['title'] . '</a>';
            $tags_row['DATE'] = $row['created_date'];
            $tags['ARTICLES'] .= PHPWS_Template::processTemplate($tags_row, 'mailinglists', 'import/listrow.tpl');
          }
        }
	else {
          $tags['ARTICLES'] .= '<tr><td colspan="2">' . $_SESSION['translate']->it("There aren't any articles in the database.") . '</td></tr>';
        }
      }
      if($GLOBALS['core']->moduleExists('announce')) {
        $tags['ANNOUNCE_LABEL'] = $_SESSION['translate']->it('Announcements');
        $tags['ANNOUNCEMENT_TITLE'] = $_SESSION['translate']->it('Title');
        $tags['ANNOUNCEMENT_DATE'] = $_SESSION['translate']->it('Date');
        $tags['ANNOUNCEMENTS'] = '';

        $result = $GLOBALS['core']->sqlSelect('mod_announce', 'active', 1, 'id desc', NULL, NULL, 5);
        if($result != NULL) {
          foreach ($result as $row) {
            PHPWS_WizardBag::toggle($row_class, ' class="bg_light"');
            $tags_row['ROW_CLASS'] = $row_class;

	    $tags_row['TITLE'] = '<a href="index.php?module=mailinglists&amp;op=doImport&amp;list=';
	    $tags_row['TITLE'] .= $list . '&amp;mod=announce&amp;id=' . $row['id'] . '">' . $row['subject'] . '</a>';
            $tags_row['DATE'] = $row['dateCreated'];
            $tags['ANNOUNCEMENTS'] .= PHPWS_Template::processTemplate($tags_row, 'mailinglists', 'import/listrow.tpl');
          }
        }
	else {
          $tags['ANNOUNCEMENTS'] .= '<tr><td colspan="2">' . $_SESSION['translate']->it("There aren't any anouncements in the database.") . '</td></tr>';
        }
      }
      if($GLOBALS['core']->moduleExists('calendar')) {
        $tags['CALENDAR_LABEL'] = $_SESSION['translate']->it('Calendar Events');
        $tags['CALENDAR_TITLE'] = $_SESSION['translate']->it('Title');
        $tags['CALENDAR_DATE'] = $_SESSION['translate']->it('Date');
        $tags['EVENTS'] = '';

        $result = $GLOBALS['core']->sqlSelect('mod_calendar_events', 'active', 1, 'id desc', NULL, NULL, 5);
        if($result != NULL) {
          foreach ($result as $row) {
            PHPWS_WizardBag::toggle($row_class, ' class="bg_light"');
            $tags_row['ROW_CLASS'] = $row_class;

	    $tags_row['TITLE'] = '<a href="index.php?module=mailinglists&amp;op=doImport&amp;list=';
	    $tags_row['TITLE'] .= $list . '&amp;mod=calendar&amp;id=' . $row['id'] . '">' . $row['title'] . '</a>';
	    
	    if ($row['startTime'] == 9999){
              $hour = 12;
              $minute = 0;
            }
	    else {
              $hour = floor($row['startTime'] / 100);
              $minute = $row['startTime'] % 100;
            }
            $year = substr($row['startDate'], 0, 4);
            $month = substr($row['startDate'], 4, 2);
            $day = substr($row['startDate'], 6, 2);
            $tags_row['DATE'] = date('Y-m-d H:i:s', mktime($hour, $minute, 0, $month, $day, $year));

            $tags['EVENTS'] .= PHPWS_Template::processTemplate($tags_row, 'mailinglists', 'import/listrow.tpl');
          }
        }
	else {
          $tags['EVENTS'] .= '<tr><td colspan="2">' . $_SESSION['translate']->it("There aren't any events in the database.") . '</td></tr>';
        }
      }

      $GLOBALS['CNT_mailinglists_menu']['title'] = $_SESSION['translate']->it('Mailing Lists Administration');
      $GLOBALS['CNT_mailinglists_menu']['content'] = PHPWS_Template::processTemplate($tags, 'mailinglists', 'import/list.tpl');
    }
  }// END FUNC showImport

}// END CLASS PHPWS_mailinglists

?>