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
 * @version $Id: approval.php,v 1.3 2005/09/12 00:36:10 blindman1344 Exp $
 * @author  Greg Meiste <blindman1344 [at] users dot sourceforge dot net>
 */

if ($_SESSION['OBJ_user']->allow_access('mailinglists', 'send_emails')) {
  require_once(PHPWS_SOURCE_DIR . 'mod/mailinglists/class/mailinglists.php');

  if ($approvalChoice == 'yes'){
    PHPWS_mailinglists::approve($id);
  } else if ($approvalChoice == 'no') {
    PHPWS_mailinglists::refuse($id);
  } else if ($approvalChoice == 'view') {
    PHPWS_mailinglists::viewLimbo($id);
  }
}

?>