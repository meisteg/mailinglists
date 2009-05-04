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

$use_permissions = TRUE;

$permissions['edit_lists']       = dgettext('mailinglists', 'Edit Lists');
$permissions['delete_lists']     = dgettext('mailinglists', 'Delete Lists');
$permissions['hide_lists']       = dgettext('mailinglists', 'Hide Lists');

$permissions['send_emails']      = dgettext('mailinglists', 'Send Emails');
$permissions['saved_emails']     = dgettext('mailinglists', 'Saved Emails Admin');

$permissions['change_settings']  = dgettext('mailinglists', 'Change Settings');
$permissions['subscriber_admin'] = dgettext('mailinglists', 'Subscriber Admin');

$item_permissions = FALSE;

?>