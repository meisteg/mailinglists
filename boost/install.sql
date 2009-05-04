-- Mailing Lists - phpWebSite Module
--
-- See docs/credits.txt for copyright information
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

CREATE TABLE mailinglists_lists (
  id INT NOT NULL,
  name VARCHAR(60) NOT NULL,
  description TEXT NULL,
  active SMALLINT NOT NULL DEFAULT '1',
  archive_link SMALLINT NOT NULL DEFAULT '1',
  double_opt_in SMALLINT NOT NULL DEFAULT '1',
  s_email SMALLINT NOT NULL DEFAULT '1',
  u_email SMALLINT NOT NULL DEFAULT '1',
  opt_in_msg TEXT NOT NULL,
  subscribe_msg TEXT NOT NULL,
  unsubscribe_msg TEXT NOT NULL,
  created INT NOT NULL DEFAULT '0',
  from_name VARCHAR(255) NOT NULL,
  from_email VARCHAR(255) NOT NULL,
  subject_prefix VARCHAR(65) NOT NULL,
  PRIMARY KEY (id)
);

CREATE TABLE mailinglists_subscribers (
  id INT NOT NULL,
  user_id INT NOT NULL DEFAULT '0',
  email VARCHAR(100) NULL,
  list_id INT NOT NULL DEFAULT '0',
  html SMALLINT NOT NULL DEFAULT '1',
  active SMALLINT NOT NULL DEFAULT '1',
  active_key INT NOT NULL,
  subscribed INT NOT NULL,
  PRIMARY KEY (id)
);

CREATE TABLE mailinglists_emails (
  id INT NOT NULL,
  list_id INT NOT NULL DEFAULT '0',
  subject VARCHAR(60) NOT NULL,
  msg_text TEXT NOT NULL,
  msg_html TEXT NULL,
  file_id INT NOT NULL,
  created INT NOT NULL DEFAULT '0',
  user_id INT NOT NULL DEFAULT '0',
  approved INT NOT NULL DEFAULT '0',
  PRIMARY KEY (id)
);

CREATE TABLE mailinglists_queue (
  id INT NOT NULL,
  list_id INT NOT NULL DEFAULT '0',
  subscriber_id INT NOT NULL DEFAULT '0',
  email_id INT NOT NULL DEFAULT '0',
  email_address VARCHAR(100) NULL,
  PRIMARY KEY (id)
);
