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

function mailinglists_update(&$content, $currentVersion)
{
    switch ($currentVersion)
    {
        case version_compare($currentVersion, '1.1.0', '<'):
            $content[] = '- Purge deleted site users.';

        case version_compare($currentVersion, '1.2.0', '<'):
            $db = new PHPWS_DB('mailinglists_emails');

            /* Add new column to email table */
            if (PHPWS_Error::logIfError($db->addTableColumn('file_id', 'INT NOT NULL', 'msg_html')))
            {
                $content[] = '- Unable to create table column file_id in mailinglists_emails table.';
                return false;
            }

            /* Update the templates */
            $files = array('templates/email/edit.tpl',
                           'templates/email/send_html.tpl',
                           'templates/email/send_text.tpl',
                           'templates/email/view.tpl',
                           'templates/list/edit.tpl');
            mailinglists_update_files($files, $content);

            $content[] = '- Purge subscribers who haven\'t activated in over 2 days.';
            $content[] = '- File Cabinet added to email forms for HTML messages.';
            $content[] = '- Emails are now templated.';
            $content[] = '- Added workaround for issue where emails could be sent with relative URLs.';
            $content[] = '- Now call cacheQueries on all DBPagers to retain pager settings.';
            $content[] = '- Support Smart Tags in HTML emails.';

        case version_compare($currentVersion, '1.2.1', '<'):
            $content[] = '- Fixed issue sending emails with an apostrophe in "from" or "subject"';
            $content[] = '- Added more error checks when sending emails to help when debugging issues.';
    }

    return true;
}

function mailinglists_update_files($files, &$content)
{
    if (PHPWS_Boost::updateFiles($files, 'mailinglists'))
    {
        $content[] = '- Updated the following files:';
    }
    else
    {
        $content[] = '- Unable to update the following files:';
    }

    foreach ($files as $file)
    {
        $content[] = '--- ' . $file;
    }
}

?>