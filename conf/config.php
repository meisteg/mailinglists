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

/* Date format to use within the Mailing Lists module. */
define('MAILINGLISTS_DATE_FORMAT', '%m/%d/%Y %r');

/* Default values for new Mailing Lists. */
define('MAILINGLISTS_DEFAULT_OPT_IN_MSG', "You have received this email because you have subscribed to the \"[LISTNAME]\" mailing list.  There is one more step before your subscription is complete.  You need to confirm your email address to us before you will begin to receive emails.  To do so, please go to the following URL:\n\n[URL]\n\nIf you have gotten this in error, please ignore this email.  You will not receive future emails from us.");
define('MAILINGLISTS_DEFAULT_SUBSCRIBE_MSG', "Your subscription to the \"[LISTNAME]\" mailing list is now complete.  You will begin to receive all messages we send out to this list.\n\nTo unsubscribe, just return to our website and login to your subscription menu.");
define('MAILINGLISTS_DEFAULT_UNSUBSCRIBE_MSG', "Your subscription to the \"[LISTNAME]\" mailing list has been terminated.  You will no longer receive messages we send out to this list.\n\nTo subscribe again, just return to our website and login to your subscription menu.");

/* Number of lines for an email body textarea. */
define('MAILINGLISTS_TEXTAREA_ROWS', 10);

?>