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
--
-- $Id: install.sql,v 1.7 2005/09/01 02:39:29 blindman1344 Exp $

CREATE TABLE mod_mailinglists_subscribers (
  userID int NOT NULL,
  listID int NOT NULL,
  active smallint NOT NULL DEFAULT '1',
  activeKey int,
  dateSubscribed date NOT NULL,
  PRIMARY KEY (userID,listID),
  KEY userID (userID),
  KEY listID (listID)
);

CREATE TABLE mod_mailinglists_lists (
  id int PRIMARY KEY,
  name varchar(60) NOT NULL,
  status varchar(4) NOT NULL,
  description text NOT NULL,
  archive smallint NOT NULL DEFAULT '1',
  archiveLink smallint NOT NULL DEFAULT '1',
  doubleOptIn smallint NOT NULL DEFAULT '1',
  sEmail smallint NOT NULL DEFAULT '1',
  uEmail smallint NOT NULL DEFAULT '1',
  optInMessage text NOT NULL,
  subscribeMessage text NOT NULL,
  unsubscribeMessage text NOT NULL,
  dateCreated date NOT NULL,
  lastSent datetime NOT NULL,
  lastSentBy varchar(20) NOT NULL DEFAULT 'Unknown',
  fromName varchar(255) NOT NULL,
  fromEmail varchar(255) NOT NULL,
  subjectPrefix varchar(65) NOT NULL
);

CREATE TABLE mod_mailinglists_useroptions (
  id int PRIMARY KEY,
  userID int NOT NULL,
  htmlEmail smallint NOT NULL DEFAULT '1'
);

CREATE TABLE mod_mailinglists_conf (
  personal varchar(4) NOT NULL,
  footer smallint NOT NULL DEFAULT '0',
  footerMessage text NOT NULL,
  footerHtmlMessage text NOT NULL,
  userSend smallint NOT NULL DEFAULT '0',
  anonSubscribe smallint NOT NULL DEFAULT '0',
  subjectPrefix smallint NOT NULL DEFAULT '1'
);

INSERT INTO mod_mailinglists_conf VALUES ('on', '0', 'To unsubscribe:\n[URL]', '<i>To unsubscribe, <a href=\"[URL]\">click here</a>.</i>', '0', '0', '1');

CREATE TABLE mod_mailinglists_saves (
  id int PRIMARY KEY,
  name varchar(40) NOT NULL,
  message text NOT NULL,
  htmlMessage text NOT NULL
);

CREATE TABLE mod_mailinglists_archives (
  id int PRIMARY KEY,
  listID int NOT NULL,
  subject varchar(60) NOT NULL,
  message text NOT NULL,
  dateSent datetime NOT NULL,
  sentBy varchar(20) NOT NULL DEFAULT 'Unknown'
);

CREATE TABLE mod_mailinglists_anon_subscribers (
  id int PRIMARY KEY,
  email varchar(50) NOT NULL,
  html int NOT NULL DEFAULT '1',
  listID int NOT NULL,
  active smallint NOT NULL DEFAULT '1',
  activeKey int,
  dateSubscribed date NOT NULL
);

CREATE TABLE mod_mailinglists_limbo (
  id int PRIMARY KEY,
  listID int NOT NULL,
  subject varchar(60) NOT NULL,
  message text NOT NULL,
  htmlMessage text NOT NULL,
  sentBy varchar(20) NOT NULL DEFAULT 'Unknown'
);
