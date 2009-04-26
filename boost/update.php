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
 * @version $Id: update.php,v 1.19 2005/09/12 00:36:09 blindman1344 Exp $
 * @author  Greg Meiste <blindman1344 [at] users dot sourceforge dot net>
 */

if (!$_SESSION['OBJ_user']->isDeity()) {
  header('location:index.php');
  exit();
}

// Need to do core version check
if (version_compare($GLOBALS['core']->version, '0.9.3-4') < 0) {
    $content .= 'This module requires a phpWebSite core version of 0.9.3-4 or greater to install.<br />';
    $content .= 'You are currently using phpWebSite core version ' . $GLOBALS['core']->version . '.<br />';
    return;
}

// Load help information
require_once(PHPWS_SOURCE_DIR . 'mod/help/class/CLS_help.php');
CLS_help::uninstall_help('mailinglists');
CLS_help::setup_help('mailinglists');

// Update Language
require_once(PHPWS_SOURCE_DIR . 'mod/language/class/Language.php');
PHPWS_Language::uninstallLanguages('mailinglists');
PHPWS_Language::installLanguages('mailinglists');

$status = 1;

if($currentVersion < 0.20) {
  $content .= 'Adding three new columns to the "mod_mailinglists_subscribers" table.<br />';
  $sql = "ALTER TABLE mod_mailinglists_subscribers ADD (active smallint NOT NULL DEFAULT '1', activeKey int, dateSubscribed date NOT NULL)";
  $GLOBALS['core']->query($sql, TRUE);
  $content .= 'Columns added successfully!<br />';
  $content .= 'Updating current subscribers...<br />';

  $sql = 'SELECT userID,listID FROM mod_mailinglists_subscribers';
  $result = $GLOBALS['core']->getAll($sql, TRUE);
  if(sizeof($result) > 0) {
    $i = 0;
    foreach($result as $row) {
      $key = rand(1,999999999);
      $sql = 'UPDATE mod_mailinglists_subscribers SET activeKey=' . $key . ',dateSubscribed="' . date("Y-m-d") . '" WHERE userID=' . $row['userID'] . ' AND listID=' . $row['listID'];
      $GLOBALS['core']->query($sql, TRUE);
      $i++;
    }

    $content .= $i . ' subscribers were updated!<br />';
  } else {
    $content .= 'No subscribers were found. Skipping this step.<br />';
  }

  $content .= 'Adding columns "footer" and "footerMessage" to "mod_mailinglists_conf".';
  $sql = "ALTER TABLE mod_mailinglists_conf ADD (footer smallint NOT NULL DEFAULT '0', footerMessage text NOT NULL)";
  $GLOBALS['core']->query($sql, TRUE);
  $content .= '<br />Columns added successfully!<br />';

  $sql = 'UPDATE mod_mailinglists_conf SET footerMessage="To unsubscribe, login to your subscription menu on our website."';
  $GLOBALS['core']->query($sql, TRUE);

  $content .= 'Configuration settings updated!<br />';

  $sql = 'CREATE TABLE mod_mailinglists_archives (
	  id int PRIMARY KEY,
	  listID int NOT NULL,
	  subject varchar(60) NOT NULL,
	  message text NOT NULL,
	  dateSent datetime NOT NULL
	 )';
  $GLOBALS['core']->query($sql, TRUE);
  $content .= 'Archives table created!<br />';

  $content .= 'Adding 9 new columns to the "mod_mailinglists_lists" table.<br />';
  $sql = "ALTER TABLE mod_mailinglists_lists ADD 
         (archive smallint NOT NULL DEFAULT '1',
	  archiveLink smallint NOT NULL DEFAULT '1',
	  doubleOptIn smallint NOT NULL DEFAULT '1',
	  sEmail smallint NOT NULL DEFAULT '1',
	  uEmail smallint NOT NULL DEFAULT '1',
	  optInMessage text NOT NULL,
	  subscribeMessage text NOT NULL,
	  unsubscribeMessage text NOT NULL,
	  dateCreated date NOT NULL)";

  $GLOBALS['core']->query($sql, TRUE);
  $content .= 'Columns added successfully!<br />';
  $content .= 'Setting new list options for all lists.<br />';

  $sql = 'SELECT id FROM mod_mailinglists_lists';
  $result = $GLOBALS['core']->getAll($sql, TRUE);
  if(sizeof($result) > 0) {
    $i = 0;

    $message = "You have received this email because you have subscribed to the \\\"[LISTNAME]\\\" mailing list.  There is one more step before your subscription is complete.  You need to confirm your email address to us before you will begin to receive emails.  To do so, please go to the following URL:\n\n";
    $message .= "[URL]\n\nIf you have gotten this in error, please ignore this email.  You will not receive future emails from us.";

    $sMessage = "Your subscription to the \\\"[LISTNAME]\\\" mailing list is now complete.  You will begin to receive all messages we send out to this list.\n\n";
    $sMessage .= 'To unsubscribe, just return to our website and login to your subscription menu.';

    $uMessage = "Your subscription to the \\\"[LISTNAME]\\\" mailing list has been terminated.  You will no longer receive messages we send out to this list.\n\n";
    $uMessage .= 'To subscribe again, just return to our website and login to your subscription menu.';

    foreach($result as $row) {
      $sql = 'UPDATE mod_mailinglists_lists SET optInMessage="' . $message . '", subscribeMessage="' . $sMessage . '", unsubscribeMessage="' . $uMessage . '", dateCreated="' . date("Y-m-d") . '" WHERE id=' . $row['id'];
      $GLOBALS['core']->query($sql, TRUE);
      $i++;
    }

    $content .= $i . ' mailing lists were updated!<br />';
  } else {
    $content .= 'No mailing lists were found. Skipping this step.<br />';
  }
}


if($currentVersion < 0.21) {
  $content .= 'Fixing subscribers that are not stored correctly.<br />';
  $sql = 'SELECT userID,listID FROM mod_mailinglists_subscribers WHERE dateSubscribed="0000-00-00"';
  $result = $GLOBALS['core']->getAll($sql, TRUE);
  if(sizeof($result) > 0) {
    $i = 0;
    foreach($result as $row) {
      $key = rand(1,999999999);
      $sql = 'UPDATE mod_mailinglists_subscribers SET activeKey=' . $key . ',dateSubscribed="' . date("Y-m-d") . '" WHERE userID=' . $row['userID'] . ' AND listID=' . $row['listID'];
      $GLOBALS['core']->query($sql, TRUE);
      $i++;
    }

    $content .= $i . ' subscribers were fixed!<br />';
  } else {
    $content .= 'No subscribers needed to be fixed.<br />';
  }
}


if($currentVersion < 0.30) {
  $sql = "CREATE TABLE mod_mailinglists_useroptions (
          id int PRIMARY KEY,
          userID int NOT NULL,
          htmlEmail smallint NOT NULL DEFAULT '1'
          )";
  $GLOBALS['core']->query($sql, TRUE);

  $numUsers = $GLOBALS['core']->sqlMaxValue(mod_users, user_id);

  for($i=1; $i<=$numUsers; $i++) {
    $GLOBALS['core']->sqlInsert(array('userID'=>$i,'htmlEmail'=>1), 'mod_mailinglists_useroptions');
  }

  $sql = "ALTER TABLE mod_mailinglists_archives ADD (sentBy varchar(20) NOT NULL DEFAULT 'Unknown')";
  $GLOBALS['core']->query($sql, TRUE);

  $sql = 'ALTER TABLE mod_mailinglists_saves ADD (htmlMessage text NOT NULL)';
  $GLOBALS['core']->query($sql, TRUE);

  $sql = 'UPDATE mod_mailinglists_saves SET htmlMessage=message';
  $GLOBALS['core']->query($sql, TRUE);

  $sql = 'ALTER TABLE mod_mailinglists_conf ADD (footerHtmlMessage text NOT NULL)';
  $GLOBALS['core']->query($sql, TRUE);

  $sql = 'UPDATE mod_mailinglists_conf SET footerHtmlMessage=footerMessage';
  $GLOBALS['core']->query($sql, TRUE);

  $sql = "ALTER TABLE mod_mailinglists_lists
          ADD (lastSent datetime NOT NULL,
               lastSentBy varchar(20) NOT NULL DEFAULT 'Unknown',
               fromName varchar(255) NOT NULL,
               fromEmail varchar(255) NOT NULL)";
  $GLOBALS['core']->query($sql, TRUE);

  extract(PHPWS_User::getSettings());
  $sql = 'UPDATE mod_mailinglists_lists SET fromName="' . $_SESSION['OBJ_layout']->page_title . '", fromEmail="' . $user_contact . '"';
  $GLOBALS['core']->query($sql, TRUE);

  $content .= 'Mailing Lists Updates for Version 0.3.0<br />';
  $content .= '------------------------------------------<br />';
  $content .= '- "From" mail header can now be set<br />';
  $content .= '- Fixed some xhtml compliance errors<br />';
  $content .= '- Website output now templated for easy customization<br />';
  $content .= '- Module rights support added<br />';
  $content .= '- Language support added<br />';
  $content .= '- HTML support added!!!<br />';
  $content .= '- Interface improvements<br />';
  $content .= '- Now keeps track of the date and who sent last email<br />';
  $content .= '- Archives now stores who sent each email<br />';
  $content .= '- Other minor tweaks<br />';
}


if($currentVersion < 0.31) {
  $content .= 'Mailing Lists Updates for Version 0.3.1<br />';
  $content .= '------------------------------------------<br />';
  $content .= '- Script now properly checks to see if all emails sent<br />';
  $content .= '- FAQ doc file added<br />';
  $content .= '- Subscription Menu now properly displays subscribed lists.<br />';
  $content .= '- Unsubscribe will no longer send emails to unactive users.<br />';
}

if($currentVersion < 0.32) {
  $content .= 'Mailing Lists Updates for Version 0.3.2<br />';
  $content .= '------------------------------------------<br />';
  $content .= '- Admin can now add all the website users to a mailing list.<br />';
  $content .= '- Footers can now have an unsubscribe link.<br />';
}

if($currentVersion < 0.33) {
  $content .= 'Mailing Lists Updates for Version 0.3.3<br />';
  $content .= '------------------------------------------<br />';
  $content .= '- Now compatible with phpWebSite 0.9.3-2<br />';
  $content .= '- Minor bug fixed with saved emails.<br />';
}

if(in_array($currentVersion, array('0.10', '0.11', '0.20', '0.21', '0.22', '0.30', '0.31', '0.32', '0.33'))) {
    $currentVersion = '0.3.3';
}

// Begin using version_compare()

if(version_compare($currentVersion, '0.4.0') < 0) {
  $sql = "ALTER TABLE mod_mailinglists_conf ADD (userSend smallint NOT NULL DEFAULT '0', anonSubscribe smallint NOT NULL DEFAULT '0')";
  $GLOBALS['core']->query($sql, TRUE);
  
  $sql = "CREATE TABLE mod_mailinglists_anon_subscribers (
          id int PRIMARY KEY,
          email varchar(50) NOT NULL,
          html int NOT NULL DEFAULT '1',
          listID int NOT NULL,
          active smallint NOT NULL DEFAULT '1',
          activeKey int,
          dateSubscribed date NOT NULL
         )";
  $GLOBALS['core']->query($sql, TRUE);
  
  $sql = "CREATE TABLE mod_mailinglists_limbo (
          id int PRIMARY KEY,
          listID int NOT NULL,
          subject varchar(60) NOT NULL,
          message text NOT NULL,
          htmlMessage text NOT NULL,
          sentBy varchar(20) NOT NULL DEFAULT 'Unknown'
         )";
  $GLOBALS['core']->query($sql, TRUE);

  $content .= 'Mailing Lists Updates for Version 0.4.0<br />';
  $content .= '------------------------------------------<br />';
  $content .= '- Subscriber Admin has been added.<br />';
  $content .= '- Help module utilized.<br />';
  $content .= '- Anonymous visitors can now subscribe to mailing lists.<br />';
  $content .= '- Registered subscribed users can now send messages to the list.<br />';
}

if(version_compare($currentVersion, '0.4.1') < 0) {
  $sql = 'ALTER TABLE mod_mailinglists_lists ADD (subjectPrefix varchar(65) NOT NULL)';
  $GLOBALS['core']->query($sql, TRUE);

  $sql = 'SELECT id,name FROM mod_mailinglists_lists';
  $result = $GLOBALS['core']->getAll($sql, TRUE);
  if(sizeof($result) > 0) {
    foreach($result as $row) {
      $sql = 'UPDATE mod_mailinglists_lists SET subjectPrefix="[' . $row['name'] . ']" WHERE id=' . $row['id'];
      $GLOBALS['core']->query($sql, TRUE);
    }
  }
  
  $sql = "ALTER TABLE mod_mailinglists_conf ADD (subjectPrefix smallint NOT NULL DEFAULT '1')";
  $GLOBALS['core']->query($sql, TRUE);

  $content .= '<br />Mailing Lists Updates for Version 0.4.1<br />';
  $content .= '------------------------------------------<br />';
  $content .= '- Subject prefixes now possible.<br />';
  $content .= '- Subscriber Admin bug fixes.<br />';
  $content .= '- Fixed update script to support table prefixes.<br />';
}

if(version_compare($currentVersion, '0.5.0') < 0) {
  $sql = 'UPDATE mod_mailinglists_archives SET subject="(No Subject)" WHERE subject=""';
  $GLOBALS['core']->query($sql, TRUE);

  $content .= '<br />Mailing Lists Updates for Version 0.5.0<br />';
  $content .= '------------------------------------------<br />';
  $content .= '- Interface improvements<br />';
  $content .= '- Improved Subscriber Admin<br />';
  $content .= '- Messages can now be imported from modules<br />';
  $content .= '- Fixed bug with HTML emails on certain servers<br />';
  $content .= '- Email subjects now required<br />';
}

if(version_compare($currentVersion, '0.5.1') < 0) {
  $content .= '<br />Mailing Lists Updates for Version 0.5.1<br />';
  $content .= '------------------------------------------<br />';
  $content .= '- Add All Users no longer adds unapproved users.<br />';
  $content .= '- Fixed the relative link problem.<br />';
  $content .= '- One anonymous user can now subscribe to multiple lists.<br />';
  $content .= '- Email links in HTML emails no longer "mungled"<br />';
  $content .= '- Better sanity checks on email send form<br />';
  $content .= '- HTML email preview feature added<br />';
  $content .= '- Modified how announcements were imported for compatibility with 0.9.3-3<br />';
}

if(version_compare($currentVersion, '0.5.2') < 0) {
  $content .= '<br />Mailing Lists Updates for Version 0.5.2<br />';
  $content .= '------------------------------------------<br />';
  $content .= '- Now compatible with phpWebSite 0.10.0<br />';
  $content .= '- Properly handles lists with ID > 9<br />';
}

?>