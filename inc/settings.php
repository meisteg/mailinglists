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

$settings = array('show_block' => 1,
                  'footer' => 1,
                  'anon_subscribe' => 0,
                  'subject_prefix' => 1,
                  'user_send' => 0,
                  'max_per_hour' => 0,
                  'max_at_once' => 50,
                  'footer_text_msg' => "To unsubscribe from this list:\n[URL]",
                  'footer_html_msg' => '<p><em>To unsubscribe from this list, <a href="[URL]">click here</a>.</em></p>',
                  'this_hour' => 0,
                  'sent_this_hour' => 0);

?>