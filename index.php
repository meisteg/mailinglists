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
 * @version $Id: index.php,v 1.25 2005/09/12 00:36:08 blindman1344 Exp $
 * @author  Greg Meiste <blindman1344 [at] users dot sourceforge dot net>
 */

if (!isset($GLOBALS['core'])){
  header('location:../../');
  exit();
}

/* Check to see if Mailinglist session is set and set it if it's not. */
if(!isset($_SESSION['SES_MAILINGLISTS_MANAGER'])) {
  $_SESSION['SES_MAILINGLISTS_MANAGER'] = new PHPWS_mailinglists;
}

if(isset($_REQUEST['op'])) {
$noPermission = $_SESSION['translate']->it('Incorrect permissions to view.');

switch($_REQUEST['op']) {
  case 'admin':
  $_SESSION['SES_MAILINGLISTS_MANAGER']->showMenu();
  break;

  case 'config':
  $_SESSION['SES_MAILINGLISTS_MANAGER']->showConfig();
  break;

  case 'user':
  if(isset($_SESSION['OBJ_user']->username)) {
    $_SESSION['SES_MAILINGLISTS_MANAGER']->showUserMenu();
  } else {
    $_SESSION['SES_MAILINGLISTS_MANAGER']->showAnonMenu();
  }
  break;

  case 'savedEmails':
  $_SESSION['SES_MAILINGLISTS_MANAGER']->showSavedEmails();
  break;

  case 'addList':
  if($_SESSION['OBJ_user']->allow_access('mailinglists', 'create_lists')) {
    $_SESSION['SES_MAILINGLISTS_MANAGER']->edit();
  }
  else {
    $GLOBALS['CNT_mailinglists_menu']['content'] = $noPermission;
  }
  break;

  case 'doAddList':
  {
    $message = "You have received this email because you have subscribed to the \"[LISTNAME]\" mailing list.  There is one more step before your subscription is complete.  You need to confirm your email address to us before you will begin to receive emails.  To do so, please go to the following URL:\n\n";
    $message .= "[URL]\n\nIf you have gotten this in error, please ignore this email.  You will not receive future emails from us.";

    $sMessage = "Your subscription to the \"[LISTNAME]\" mailing list is now complete.  You will begin to receive all messages we send out to this list.\n\n";
    $sMessage .= 'To unsubscribe, just return to our website and login to your subscription menu.';

    $uMessage = "Your subscription to the \"[LISTNAME]\" mailing list has been terminated.  You will no longer receive messages we send out to this list.\n\n";
    $uMessage .= 'To subscribe again, just return to our website and login to your subscription menu.';

    $prefix = '[' . $listName . ']';

    $GLOBALS['core']->sqlInsert(array('name'=>$listName,'status'=>'off','description'=>$description,'archive'=>$archive,'archiveLink'=>$archiveLink,'doubleOptIn'=>$doubleOptIn,'sEmail'=>$sEmail,'uEmail'=>$uEmail,'optInMessage'=>$message,'subscribeMessage'=>$sMessage,'unsubscribeMessage'=>$uMessage,'dateCreated'=>date("Y-m-d"),'lastSentBy'=>'N/A','fromName'=>$fromName,'fromEmail'=>$fromEmail,'subjectPrefix'=>$prefix), 'mod_mailinglists_lists');
    $_SESSION['SES_MAILINGLISTS_MANAGER']->showMenu($_SESSION['translate']->it('List Added'));
  }
  break;

  case 'chgConfig':
  if(isset($_POST['convert'])){
    $_SESSION['SES_MAILINGLISTS_MANAGER']->showConfig($_SESSION['translate']->it('HTML Converted'), $personal, $footer, $footerHtmlMessage);
  }
  else {
    $GLOBALS['core']->sqlUpdate(array('personal'=>$personal, 'footer'=>$footer, 'footerMessage'=>$footerMessage, 'footerHtmlMessage'=>$footerHtmlMessage, 'userSend'=>$userSend, 'anonSubscribe'=>$anonSubscribe, 'subjectPrefix'=>$subjectPrefix), 'mod_mailinglists_conf');
    $_SESSION['SES_MAILINGLISTS_MANAGER']->showConfig($_SESSION['translate']->it('Settings Updated'));
  }
  break;

  case 'listShowHide':
  if($list != NULL) {
    $result = $GLOBALS['core']->sqlSelect('mod_mailinglists_lists', 'id', $list[0]);
    if($result[0]['status'] == 'on') {
      $GLOBALS['core']->sqlUpdate(array('status'=>'off'), 'mod_mailinglists_lists', 'id', $list[0]);
    }
    else {
      $GLOBALS['core']->sqlUpdate(array('status'=>'on'), 'mod_mailinglists_lists', 'id', $list[0]);
    }
    $_SESSION['SES_MAILINGLISTS_MANAGER']->showMenu($_SESSION['translate']->it('List Status Changed'));
  }
  else
    $_SESSION['SES_MAILINGLISTS_MANAGER']->showMenu($_SESSION['translate']->it('Please select a mailing list.'));
  break;

  case 'subscribe':
  $_SESSION['SES_MAILINGLISTS_MANAGER']->subscribeUser($user, $list);
  $_SESSION['SES_MAILINGLISTS_MANAGER']->showUserMenu();
  break;

  case 'unsubscribe':
  $result = $GLOBALS['core']->sqlSelect('mod_mailinglists_subscribers', array('userID'=>$user, 'listID'=>$list, 'active'=>'1'));
  $GLOBALS['core']->sqlDelete('mod_mailinglists_subscribers', array('userID'=>$user,'listID'=>$list));
  if($result != NULL) {
    $_SESSION['SES_MAILINGLISTS_MANAGER']->sendUnsubscribeEmail($user, $list);
  }
  $_SESSION['SES_MAILINGLISTS_MANAGER']->showUserMenu();
  break;

  case 'deleteList':
  if($list != NULL)
    $_SESSION['SES_MAILINGLISTS_MANAGER']->showDeleteList($list[0]);
  else
    $_SESSION['SES_MAILINGLISTS_MANAGER']->showMenu($_SESSION['translate']->it('Please select a mailing list.'));
  break;

  case 'doDeleteList':
  $_SESSION['SES_MAILINGLISTS_MANAGER']->doDeleteList($list);
  $_SESSION['SES_MAILINGLISTS_MANAGER']->showMenu($_SESSION['translate']->it('List Deleted'));
  break;

  case 'editList':
  if($_SESSION['OBJ_user']->allow_access('mailinglists', 'edit_lists')) {
    if($list != NULL)
      $_SESSION['SES_MAILINGLISTS_MANAGER']->edit($list[0]);
    else
      $_SESSION['SES_MAILINGLISTS_MANAGER']->showMenu($_SESSION['translate']->it('Please select a mailing list.'));
  }
  else {
    $_SESSION['SES_MAILINGLISTS_MANAGER']->showMenu($noPermission);
  }
  break;

  case 'doEditList':
  $GLOBALS['core']->sqlUpdate(array('name'=>$listName,'description'=>$description,'archive'=>$archive,'archiveLink'=>$archiveLink,'doubleOptIn'=>$doubleOptIn,'sEmail'=>$sEmail,'uEmail'=>$uEmail,'optInMessage'=>$optInMessage,'subscribeMessage'=>$subscribeMessage,'unsubscribeMessage'=>$unsubscribeMessage,'fromName'=>$fromName,'fromEmail'=>$fromEmail,'subjectPrefix'=>$subjectPrefix), 'mod_mailinglists_lists', 'id', $list);
  $_SESSION['SES_MAILINGLISTS_MANAGER']->showMenu($_SESSION['translate']->it('List Updated'));
  break;

  case 'sendEmail':
  if($list != NULL) {
    $result = $GLOBALS['core']->sqlSelect('mod_mailinglists_lists', array('id'=>$_REQUEST['list'][0]));
    if($mladmin != NULL)
    {
      $_SESSION['SES_MAILINGLISTS_MANAGER']->showEmailForm($list[0], NULL, NULL, NULL, 0);
    }
    else
    {
      $_SESSION['SES_MAILINGLISTS_MANAGER']->showEmailForm($list[0]);
    }
  }
  else
    $_SESSION['SES_MAILINGLISTS_MANAGER']->showMenu($_SESSION['translate']->it('Please select a mailing list.'));
  break;

  case 'loadEmail':
  $_SESSION['SES_MAILINGLISTS_MANAGER']->showLoadEmail($list);
  break;

  case 'doLoadEmail':
  $_SESSION['SES_MAILINGLISTS_MANAGER']->showEmailForm($list,$email);
  break;

  case 'addEmail':
  $_SESSION['SES_MAILINGLISTS_MANAGER']->showEditEmail();
  break;

  case 'doAddEmail':
  if(isset($_POST['convert'])){
    $_SESSION['SES_MAILINGLISTS_MANAGER']->showEditEmail(NULL, $emailSubject, $htmlMessage);
  }
  else {
    $GLOBALS['core']->sqlInsert(array('name'=>$emailSubject, 'message'=>$message, 'htmlMessage'=>$htmlMessage), 'mod_mailinglists_saves');
    $_SESSION['SES_MAILINGLISTS_MANAGER']->showSavedEmails($_SESSION['translate']->it('Email Added Successfully'));
  }
  break;

  case 'deleteEmail':
  $_SESSION['SES_MAILINGLISTS_MANAGER']->showDeleteEmail($email);
  break;

  case 'addAllUsers':
  $_SESSION['SES_MAILINGLISTS_MANAGER']->showAddAllUsers($list[0]);
  break;

  case 'doAddAllUsers':
  $_SESSION['SES_MAILINGLISTS_MANAGER']->doAddAllUsers($list);
  $_SESSION['SES_MAILINGLISTS_MANAGER']->showMenu($_SESSION['translate']->it('All users added'));
  break;

  case 'doDeleteEmail':
  $GLOBALS['core']->sqlDelete('mod_mailinglists_saves', array('id'=>$email));
  $_SESSION['SES_MAILINGLISTS_MANAGER']->showSavedEmails($_SESSION['translate']->it('Email Deleted'));
  break;

  case 'editEmail':
  $_SESSION['SES_MAILINGLISTS_MANAGER']->showEditEmail($email);
  break;

  case 'doEditEmail':
  if(isset($_POST['convert'])){
    $_SESSION['SES_MAILINGLISTS_MANAGER']->showEditEmail($email, $emailSubject, $htmlMessage);
  }
  else {
    $GLOBALS['core']->sqlUpdate(array('name'=>$emailSubject,'message'=>$message,'htmlMessage'=>$htmlMessage), 'mod_mailinglists_saves', 'id', $email);
    $_SESSION['SES_MAILINGLISTS_MANAGER']->showSavedEmails($_SESSION['translate']->it('Email Edited Successfully'));
  }
  break;

  case 'doSendEmail':
  $result = $GLOBALS['core']->sqlSelect('mod_mailinglists_conf');
  if(isset($_POST['convert'])){
    $_SESSION['SES_MAILINGLISTS_MANAGER']->showEmailForm($list, NULL, $emailSubject, $htmlMessage, $mladmin);
  }
  else if(isset($_POST['preview'])){
    $_SESSION['SES_MAILINGLISTS_MANAGER']->showEmailForm($list, NULL, NULL, NULL, $mladmin);
  }
  else if(!strlen($emailSubject)) {
    $_SESSION['SES_MAILINGLISTS_MANAGER']->showEmailForm($list, NULL, NULL, NULL, $mladmin);
  }
  else if(($mladmin) || ($result[0]['userSend'] == 2)){
    $_SESSION['SES_MAILINGLISTS_MANAGER']->doSendEmail($list);
  }
  else {
    $newID = $GLOBALS['core']->sqlInsert(array('listID'=>$list, 'subject'=>$emailSubject, 'message'=>$message, 'htmlMessage'=>$htmlMessage, 'sentBy'=>$_SESSION['OBJ_user']->username), 'mod_mailinglists_limbo', 0, 1);

    $result = $GLOBALS['core']->sqlSelect('mod_mailinglists_lists', 'id', $list);
    require_once (PHPWS_SOURCE_DIR . 'mod/approval/class/Approval.php');
    $emailInfo = '<b>' . $_SESSION['translate']->it('Subject') . ':</b> ' . $emailSubject . '<br />';
    $emailInfo .= '<b>' . $_SESSION['translate']->it('Sent By') . ':</b> ' . $_SESSION['OBJ_user']->username . '<br />';
    $emailInfo .= '<b>' . $_SESSION['translate']->it('List Name') . ':</b> ' . $result[0]['name'];
    PHPWS_Approval::add($newID, $emailInfo, 'mailinglists');

    $GLOBALS['CNT_mailinglists_menu']['content'] = $_SESSION['translate']->it('The email was successfully submitted to the database.');
  }
  break;

  case 'archives':
  $_SESSION['SES_MAILINGLISTS_MANAGER']->showArchives($_REQUEST['list']);
  break;

  case 'archivedEmail':
  $_SESSION['SES_MAILINGLISTS_MANAGER']->showArchivedEmail($_REQUEST['email'], $_REQUEST['list']);
  break;

  case 'user_options':
  $GLOBALS['core']->sqlUpdate(array('htmlEmail'=>$htmlEmail), 'mod_mailinglists_useroptions', 'userID', $userID);
  $_SESSION['SES_MAILINGLISTS_MANAGER']->showUserMenu($_SESSION['translate']->it('User options updated.'));
  break;

  case 'confirm':
  {
    $title = $_SESSION['translate']->it('Mailing List Subscription Confirmation');

    $result = $GLOBALS['core']->sqlSelect('mod_mailinglists_subscribers', array('userID'=>$_REQUEST['user'], 'listID'=>$_REQUEST['list'], 'activeKey'=>$_REQUEST['key']));
    if($result != NULL) {
      if($result[0]['active']) {
        $content = $_SESSION['translate']->it('Your subscription has already been activated. You may delete the confirmation email.');
      }
      else {
        $GLOBALS['core']->sqlUpdate(array('active'=>1), 'mod_mailinglists_subscribers', array('userID'=>$_REQUEST['user'], 'listID'=>$_REQUEST['list'], 'activeKey'=>$_REQUEST['key']));
        $content = $_SESSION['translate']->it('Thank you! Your subscription has been activated.');
        $_SESSION['SES_MAILINGLISTS_MANAGER']->sendSubscribeEmail($_REQUEST['user'],$_REQUEST['list']);
      }
    }
    else {
      $content = $_SESSION['translate']->it('ERROR!') . '<br /><br />';
      $content .= $_SESSION['translate']->it('The information you supplied was not found in our database.');
      $content .= '<br />' . $_SESSION['translate']->it('Please contact us if you believe this is an error.');
    }

    $GLOBALS['CNT_mailinglists_menu']['title'] = $title;
    $GLOBALS['CNT_mailinglists_menu']['content'] = $content;
  }
  break;

  case 'remove':
  {
    $title = $_SESSION['translate']->it('Mailing List Removal');

    $result = $GLOBALS['core']->sqlSelect('mod_mailinglists_subscribers', array('userID'=>$_REQUEST['user'], 'listID'=>$_REQUEST['list'], 'activeKey'=>$_REQUEST['key']));
    if($result != NULL) {
      $GLOBALS['core']->sqlDelete('mod_mailinglists_subscribers', array('userID'=>$_REQUEST['user'], 'listID'=>$_REQUEST['list'], 'activeKey'=>$_REQUEST['key']));
      $content = $_SESSION['translate']->it('Your subscription has been cancelled.');
      $_SESSION['SES_MAILINGLISTS_MANAGER']->sendUnsubscribeEmail($_REQUEST['user'],$_REQUEST['list']);
    }
    else {
      $content = $_SESSION['translate']->it('ERROR!') . '<br /><br />';
      $content .= $_SESSION['translate']->it('The information you supplied was not found in our database.');
      $content .= '<br />' . $_SESSION['translate']->it('Please contact us if you believe this is an error.');
    }

    $GLOBALS['CNT_mailinglists_menu']['title'] = $title;
    $GLOBALS['CNT_mailinglists_menu']['content'] = $content;
  }
  break;

  case 'subscriberAdmin':
  if($_SESSION['OBJ_user']->allow_access('mailinglists', 'subscriber_admin')) {

  if(isset($_REQUEST['subscribe']))
  {
    $result = $GLOBALS['core']->sqlSelect('mod_users', array('username'=>$_REQUEST['subscriber']));
    if($result != NULL) {
      if(!($GLOBALS['core']->sqlSelect('mod_mailinglists_subscribers', array('userID'=>$result[0]['user_id'], 'listID'=>$_REQUEST['list'])))) {
        $_SESSION['SES_MAILINGLISTS_MANAGER']->subscribeUser($result[0]['user_id'], $_REQUEST['list']);

        $optionCheck = $GLOBALS['core']->sqlSelect('mod_mailinglists_useroptions', 'userID', $result[0]['user_id']);
        if($optionCheck == NULL) {
          $GLOBALS['core']->sqlInsert(array('userID'=>$result[0]['user_id'], 'htmlEmail'=>1), 'mod_mailinglists_useroptions');
        }

        $_SESSION['SES_MAILINGLISTS_MANAGER']->subscriberAdmin($_REQUEST['list'],$_SESSION['translate']->it('List Updated'));
      }
      else {
        $_SESSION['SES_MAILINGLISTS_MANAGER']->subscriberAdmin($_REQUEST['list'],$_SESSION['translate']->it('User already subscribed to list'));
      }
    }
    else {
      $_SESSION['SES_MAILINGLISTS_MANAGER']->subscriberAdmin($_REQUEST['list'],$_SESSION['translate']->it('User not found'));
    }
  }
  else if(isset($_REQUEST['unsubscribe']))
  {
    $result = $GLOBALS['core']->sqlSelect('mod_users', array('username'=>$_REQUEST['subscriber']));
    if($result != NULL) {
      if($GLOBALS['core']->sqlSelect('mod_mailinglists_subscribers', array('userID'=>$result[0]['user_id'], 'listID'=>$_REQUEST['list']))) {
        $GLOBALS['core']->sqlDelete('mod_mailinglists_subscribers', array('userID'=>$result[0]['user_id'], 'listID'=>$_REQUEST['list']));
        $_SESSION['SES_MAILINGLISTS_MANAGER']->subscriberAdmin($_REQUEST['list'],$_SESSION['translate']->it('List Updated'));
      }
      else {
        $_SESSION['SES_MAILINGLISTS_MANAGER']->subscriberAdmin($_REQUEST['list'],$_SESSION['translate']->it('User was not subscribed to list'));
      }
    }
    else {
      $_SESSION['SES_MAILINGLISTS_MANAGER']->subscriberAdmin($_REQUEST['list'],$_SESSION['translate']->it('User not found'));
    }
  }
  else if(isset($_REQUEST['anon_remove']))
  {
    if($GLOBALS['core']->sqlSelect('mod_mailinglists_anon_subscribers', array('email'=>$_REQUEST['email'], 'listID'=>$_REQUEST['list']))) {
      $GLOBALS['core']->sqlDelete('mod_mailinglists_anon_subscribers', array('email'=>$_REQUEST['email'], 'listID'=>$_REQUEST['list']));
      $_SESSION['SES_MAILINGLISTS_MANAGER']->subscriberAdmin($_REQUEST['list'],$_SESSION['translate']->it('List Updated'));
    }
    else {
      $_SESSION['SES_MAILINGLISTS_MANAGER']->subscriberAdmin($_REQUEST['list'],$_SESSION['translate']->it('User was not subscribed to list'));
    }
  }
  else if(isset($_REQUEST['anon_add']))
  {
      $_REQUEST['emailAddress'] = $_REQUEST['email'];
      $_SESSION['SES_MAILINGLISTS_MANAGER']->subscribeAnon(1);
  }
  else if($list != NULL) {
    if (is_array($_REQUEST['list'])) {
      $_SESSION['SES_MAILINGLISTS_MANAGER']->subscriberAdmin($_REQUEST['list'][0]);
    }
    else {
      $_SESSION['SES_MAILINGLISTS_MANAGER']->subscriberAdmin($_REQUEST['list']);
    }
  }
  else {
    $_SESSION['SES_MAILINGLISTS_MANAGER']->showMenu($_SESSION['translate']->it('Please select a mailing list.'));
  }

  }
  else {
    $GLOBALS['CNT_mailinglists_menu']['content'] = $noPermission;
  }
  break;

  case 'doAnonSubscribe':
  $_SESSION['SES_MAILINGLISTS_MANAGER']->subscribeAnon();
  break;

  case 'confirmAnon':
  {
    $title = $_SESSION['translate']->it('Mailing List Subscription Confirmation');

    $result = $GLOBALS['core']->sqlSelect('mod_mailinglists_anon_subscribers', array('listID'=>$_REQUEST['list'], 'activeKey'=>$_REQUEST['key']));
    if($result != NULL) {
      if($result[0]['active']) {
        $content = $_SESSION['translate']->it('Your subscription has already been activated. You may delete the confirmation email.');
      }
      else {
        $GLOBALS['core']->sqlUpdate(array('active'=>1), 'mod_mailinglists_anon_subscribers', array('listID'=>$_REQUEST['list'], 'activeKey'=>$_REQUEST['key']));
        $content = $_SESSION['translate']->it('Thank you! Your subscription has been activated.');
        $_SESSION['SES_MAILINGLISTS_MANAGER']->sendSubscribeEmail($result[0]['email'],$_REQUEST['list'],1);
      }
    }
    else {
      $content = $_SESSION['translate']->it('ERROR!') . '<br /><br />';
      $content .= $_SESSION['translate']->it('The information you supplied was not found in our database.');
      $content .= '<br />' . $_SESSION['translate']->it('Please contact us if you believe this is an error.');
    }

    $GLOBALS['CNT_mailinglists_menu']['title'] = $title;
    $GLOBALS['CNT_mailinglists_menu']['content'] = $content;
  }
  break;

  case 'removeAnon':
  {
    $title = $_SESSION['translate']->it('Mailing List Removal');

    $result = $GLOBALS['core']->sqlSelect('mod_mailinglists_anon_subscribers', array('id'=>$_REQUEST['user'], 'listID'=>$_REQUEST['list'], 'activeKey'=>$_REQUEST['key']));
    if($result != NULL) {
      $GLOBALS['core']->sqlDelete('mod_mailinglists_anon_subscribers', array('id'=>$_REQUEST['user'], 'listID'=>$_REQUEST['list'], 'activeKey'=>$_REQUEST['key']));
      $content = $_SESSION['translate']->it('Your subscription has been cancelled.');
      $_SESSION['SES_MAILINGLISTS_MANAGER']->sendUnsubscribeEmail($result[0]['email'],$_REQUEST['list'],1);
    }
    else {
      $content = $_SESSION['translate']->it('ERROR!') . '<br /><br />';
      $content .= $_SESSION['translate']->it('The information you supplied was not found in our database.');
      $content .= '<br />' . $_SESSION['translate']->it('Please contact us if you believe this is an error.');
    }

    $GLOBALS['CNT_mailinglists_menu']['title'] = $title;
    $GLOBALS['CNT_mailinglists_menu']['content'] = $content;
  }
  break;

  case 'showImport':
  $_SESSION['SES_MAILINGLISTS_MANAGER']->showImport($list);
  break;

  case 'doImport':
  $_SESSION['SES_MAILINGLISTS_MANAGER']->showEmailForm($list,NULL,NULL,NULL,1,array('module'=>$_REQUEST['mod'],'id'=>$_REQUEST['id']));
  break;
}
}

?>