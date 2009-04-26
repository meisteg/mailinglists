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
 * @version $Id: help.php,v 1.9 2005/09/12 00:36:10 blindman1344 Exp $
 * @author  Greg Meiste <blindman1344@NOSPAM.users.sourceforge.net>
 */

 $listname = 'Mailing List Name';
 $listname_content = 'This should be a good descriptive name of this mailing list.';

 $description = 'Mailing List Description';
 $description_content = 'The desciption should tell a subscriber what kind of content will be sent to this mailing list.';

 $fromName = 'Email From Name';
 $fromName_content = 'This sets the name in the "from" field of admin emails.  WARNING: Make sure that you do not use special characters here or else your emails might not send properly!';

 $fromAddress = 'Email From Address';
 $fromAddress_content = 'This sets the email address that admin emails will be sent from.';

 $archive = 'Archive';
 $archive_content = 'Selecting yes will save all emails sent to the list in the database.  This is good for those who want a record of what was sent.';

 $archiveLink = 'Public Archive Link';
 $archiveLink_content = 'Selecting yes will make a link to the archives visible to the public.  This option does not do much good without the archive feature turned on.';

 $doubleOpt = 'Double Opt-In';
 $doubleOpt_content = 'Selecting yes will force a user to opt-in twice before a subscription to a list is complete.  A confirmation email is sent to the user with a link that must be visited to activate their subscription.';

 $sEmail = 'Subscribe Email';
 $sEmail_content = 'Selecting yes will cause a "welcome email" to be sent to all new subscribers to this list.';

 $uEmail = 'Unsubscribe Email';
 $uEmail_content = 'Selecting yes will cause a "goodbye email" to be sent to a subscriber who removes him/herself from this list.';

 $optInMessage = 'Double Opt-In Confirmation Email';
 $optInMessage_content = 'This is the text that will be sent in the confirmation email sent to users (provided that double opt-in is activated).  Supported tags:<br /><br />[LISTNAME]<br />[URL]';

 $sMessage = 'Subscribe Email';
 $sMessage_content = 'This is the text that will be sent in the "welcome email" sent to users (provided that subscribe email is activated).  Supported tags:<br /><br />[LISTNAME]';

 $uMessage = 'Unsubscribe Email';
 $uMessage_content = 'This is the text that will be sent in the "goodbye email" sent to users (provided that unsubscribe email is activated).  Supported tags:<br /><br />[LISTNAME]';

 $box = 'Mailing List Box on Homepage';
 $box_content = 'Selecting yes will display a "Your Mailing Lists" box on the homepage when a user logs in.  It displays what lists the user is subscribed to and a direct link to the subscription page.';

 $footer = 'Email Footer';
 $footer_content = 'Selecting yes will attach a footer to all emails sent using this module.  The text of the footer can be set a little farther down on this page.';

 $footHTML = 'HTML Footer Message';
 $footHTML_content = 'If the footer option is selected, this is the text that is attached to HTML emails.  This is a good spot to put information on how to unsubscribe from a mailing list.  The [URL] tag is available to link a user to a page that automatically unsubscribes them.';

 $convert = 'Convert HTML to Plain Text';
 $convert_content = 'Clicking this button will cause the text in the above HTML text area to be copied to the plain text area.  All the HTML tags will be stripped from the text during the copy.';

 $footPlain = 'Plain Text Footer Message';
 $footPlain_content = 'If the footer option is selected, this is the text that is attached to plain text emails.  This is a good spot to put information on how to unsubscribe from a mailing list.  The [URL] tag is available to link a user to a page that automatically unsubscribes them.';

 $savedEmails = 'Saved Emails';
 $savedEmails_content = 'This is where you can save "boiler plate" emails.  This is helpful if your emails all have a basic format.  Emails saved here can be loaded from the "Send Email" interface.';

 $emailFormat = 'Email Format';
 $emailFormat_content = 'This option selects what format you want to receive emails.  HTML emails allow for text formatting, but some businesses and email clients do not allow HTML email to be received or viewed correctly.  Select the format that works best for you.  If you are unsure what to choose, leave this set to HTML.';

 $loadSaved = 'Load Saved Message';
 $loadSaved_content = 'This is where you can load "boiler plate" emails that have been previously saved.  The saved text will be copied into the text fields below.  Emails can be saved by clicking on "Saved Emails" link on the main admin page.';

 $subscriberAdmin = 'Subscriber Admin';
 $subscriberAdmin_content = 'This form can be used by an admin to manually subscribe/unsubscribe users to the selected mailing list.  Type in a registered username in the text box and click the appropriate button.';

 $anonAdmin = 'Anonymous Subscriber Admin';
 $anonAdmin_content = 'This form can be used by an admin to manually add/remove anonymous users from the selected mailing list.  Type in a registered email address in the text box and click the appropriate button.';

 $userSend = 'Registered Users Can Send Emails';
 $userSend_content = 'This option sets whether or not a normal user can send an email to mailing lists.  There are two options for yes: with or without approval.  Yes with approval forces the email to be approved by an admin before it is actually sent to the list.';

 $anonSubscribe = 'Mailing List Name';
 $anonSubscribe_content = 'Selecting yes will allow anonymous users to subscribe to visible mailing lists.  The subscribe box will automatically appear on the homepage.';

 $subjectPrefix = 'Subject Prefix';
 $subjectPrefix_content = 'Prefix for subject line of list postings.<br /><br />This text will be prepended to subject lines of messages posted to the list, to distinguish mailing list messages in in mailbox summaries. Brevity is premium here, it is ok to shorten long mailing list names to something more concise, as long as it still identifies the mailing list.';

 $subPrefix = 'Subject Prefix';
 $subPrefix_content = 'Selecting yes will attach prefixes to the subject line of list postings. This will help distinguish mailing list messages in in mailbox summaries.';

 $moduleImport = 'Import Message From Module';
 $moduleImport_content = 'This is where you can load an email with content from another module.  The content will be copied into the text fields below.  Be sure to check the formatting before clicking send!';

?>