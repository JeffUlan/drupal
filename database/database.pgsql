--
-- Table structure for access
--

CREATE TABLE access (
  aid SERIAL,
  mask varchar(255) NOT NULL default '',
  type varchar(255) NOT NULL default '',
  status smallint NOT NULL default '0',
  PRIMARY KEY (aid),
  UNIQUE (mask)
);

--
-- Table structure for authmap
--

CREATE TABLE authmap (
  aid SERIAL,
  uid integer NOT NULL default '0',
  authname varchar(128) NOT NULL default '',
  module varchar(128) NOT NULL default '',
  PRIMARY KEY (aid),
  UNIQUE (authname)
);

--
-- Table structure for blocks
--

CREATE TABLE blocks (
  name varchar(64) NOT NULL default '',
  module varchar(64) NOT NULL default '',
  delta smallint NOT NULL default '0',
  status smallint NOT NULL default '0',
  weight smallint NOT NULL default '0',
  region smallint NOT NULL default '0',
  remove smallint NOT NULL default '0',
  path varchar(255) NOT NULL default '',
  custom smallint NOT NULL default '0',
  PRIMARY KEY  (name)
);

--
-- Table structure for book
--

CREATE TABLE book (
  nid integer NOT NULL default '0',
  parent integer NOT NULL default '0',
  weight smallint NOT NULL default '0',
  format smallint default '0',
  log text,
  PRIMARY KEY (nid)
);
CREATE INDEX book_nid_idx ON book(nid);

--
-- Table structure for boxes
--

CREATE TABLE boxes (
  bid SERIAL,
  title varchar(64) NOT NULL default '',
  body text,
  info varchar(128) NOT NULL default '',
  type smallint NOT NULL default '0',
  PRIMARY KEY  (bid),
  UNIQUE (info),
  UNIQUE (title)
);

--
-- Table structure for bundle
--

CREATE TABLE bundle (
  bid SERIAL,
  title varchar(255) NOT NULL default '',
  attributes varchar(255) NOT NULL default '',
  PRIMARY KEY  (bid),
  UNIQUE (title)
);

--
-- Table structure for cache
--

CREATE TABLE cache (
  cid varchar(255) NOT NULL default '',
  data text,
  expire integer NOT NULL default '0',
  PRIMARY KEY  (cid)
);

--
-- Table structure for comments
--

CREATE TABLE comments (
  cid SERIAL,
  pid integer NOT NULL default '0',
  nid integer NOT NULL default '0',
  uid integer NOT NULL default '0',
  subject varchar(64) NOT NULL default '',
  comment text NOT NULL,
  hostname varchar(128) NOT NULL default '',
  timestamp integer NOT NULL default '0',
  link varchar(16) NOT NULL default '',
  PRIMARY KEY  (cid)
);
CREATE INDEX comments_lid_idx ON comments(nid);

--
-- Table structure for directory
--

CREATE TABLE directory (
  link varchar(255) NOT NULL default '',
  name varchar(128) NOT NULL default '',
  mail varchar(128) NOT NULL default '',
  slogan text NOT NULL,
  mission text NOT NULL,
  timestamp integer NOT NULL default '0',
  PRIMARY KEY  (link)
);

--
-- Table structure for feed
--

CREATE TABLE feed (
  fid SERIAL,
  title varchar(255) NOT NULL default '',
  url varchar(255) NOT NULL default '',
  refresh integer default NULL,
  timestamp integer default NULL,
  attributes varchar(255) NOT NULL default '',
  link varchar(255) NOT NULL default '',
  description text NOT NULL,
  PRIMARY KEY  (fid),
  UNIQUE (title),
  UNIQUE (url)
);

--
-- Table structure for history
--

CREATE TABLE history (
  uid integer NOT NULL default '0',
  nid integer NOT NULL default '0',
  timestamp integer NOT NULL default '0',
  PRIMARY KEY  (uid,nid)
);

--
-- Table structure for item
--

CREATE TABLE item (
  iid SERIAL,
  fid integer NOT NULL default '0',
  title varchar(255) NOT NULL default '',
  link varchar(255) NOT NULL default '',
  author varchar(255) NOT NULL default '',
  description text NOT NULL,
  timestamp integer default NULL,
  attributes varchar(255) NOT NULL default '',
  PRIMARY KEY  (iid)
);

--
-- Table structure for layout
--

CREATE TABLE layout (
  uid integer NOT NULL default '0',
  block varchar(64) NOT NULL default ''
);

--
-- Table structure for locales
--

CREATE TABLE locales (
  lid SERIAL,
  location varchar(128) NOT NULL default '',
  string text NOT NULL,
  da text NOT NULL,
  fi text NOT NULL,
  fr text NOT NULL,
  en text NOT NULL,
  es text NOT NULL,
  nl text NOT NULL,
  no text NOT NULL,
  sw text NOT NULL,
  PRIMARY KEY  (lid)
);

--
-- Table structure for moderate
--

CREATE TABLE moderate (
  cid integer NOT NULL default '0',
  nid integer NOT NULL default '0',
  uid integer NOT NULL default '0',
  score integer NOT NULL default '0',
  timestamp integer NOT NULL default '0'
);
CREATE INDEX moderate_cid_idx ON moderate(cid);
CREATE INDEX moderate_nid_idx ON moderate(nid);

--
-- Table structure for modules
--

CREATE TABLE modules (
  name varchar(64) NOT NULL default '',
  PRIMARY KEY  (name)
);

--
-- Table structure for node
--

CREATE TABLE node (
  nid SERIAL,
  type varchar(16) NOT NULL default '',
  title varchar(128) NOT NULL default '',
  score integer NOT NULL default '0',
  votes integer NOT NULL default '0',
  uid integer NOT NULL default '0',
  status integer NOT NULL default '1',
  created integer NOT NULL default '0',
  comment integer NOT NULL default '0',
  promote integer NOT NULL default '0',
  moderate integer NOT NULL default '0',
  users text NOT NULL,
  attributes varchar(255) NOT NULL default '',
  teaser text NOT NULL,
  body text NOT NULL,
  changed integer NOT NULL default '0',
  revisions text NOT NULL,
  static integer NOT NULL default '0',
  PRIMARY KEY  (nid)
);
CREATE INDEX node_type_idx ON node(type);
CREATE INDEX node_title_idx ON node(title,type);
CREATE INDEX node_promote_idx ON node(promote);
CREATE INDEX node_status_idx ON node(status);
CREATE INDEX node_uid_idx ON node(uid);

--
-- Table structure for page
--

CREATE TABLE page (
  nid integer NOT NULL default '0',
  link varchar(128) NOT NULL default '',
  format smallint NOT NULL default '0',
  PRIMARY KEY  (nid)
);
CREATE INDEX page_nid_idx ON page(nid);

--
-- Table structure for permission
--

CREATE TABLE permission (
  rid integer NOT NULL default '0',
  perm text,
  tid integer NOT NULL default '0'
);
CREATE INDEX permission_rid_idx ON permission(rid);

--
-- Table structure for poll
--

CREATE TABLE poll (
  nid integer NOT NULL default '0',
  runtime integer NOT NULL default '0',
  voters text NOT NULL,
  active integer NOT NULL default '0',
  PRIMARY KEY  (nid)
);

--
-- Table structure for poll_choices
--

CREATE TABLE poll_choices (
  chid SERIAL,
  nid integer NOT NULL default '0',
  chtext varchar(128) NOT NULL default '',
  chvotes integer NOT NULL default '0',
  chorder integer NOT NULL default '0',
  PRIMARY KEY  (chid)
);

--
-- Table structure for rating
--

CREATE TABLE rating (
  uid integer NOT NULL default '0',
  current integer NOT NULL default '0',
  previous integer NOT NULL default '0',
  PRIMARY KEY  (uid)
);

--
-- Table structure for referrer
--

CREATE TABLE referrer (
  url varchar(255) NOT NULL default '',
  timestamp integer NOT NULL default '0'
);

--
-- Table structure for role
--

CREATE TABLE role (
  rid SERIAL,
  name varchar(32) NOT NULL default '',
  PRIMARY KEY  (rid),
  UNIQUE (name)
);

--
-- Table structure for search_index
--

CREATE TABLE search_index (
 word varchar(50) NOT NULL,
 lno integer NOT NULL,
 type varchar(16) default NULL,
 count integer default NULL
);
CREATE INDEX search_index_lno_idx ON search_index(lno);
CREATE INDEX search_index_word_idx ON search_index(word);

--
-- Table structure for site
--

CREATE TABLE site (
  sid SERIAL,
  name varchar(128) NOT NULL default '',
  link varchar(255) NOT NULL default '',
  size text NOT NULL,
  timestamp integer NOT NULL default '0',
  feed varchar(255) NOT NULL default '',
  refresh integer NOT NULL default '0',
  threshold integer NOT NULL default '0',
  PRIMARY KEY  (sid),
  UNIQUE (name),
  UNIQUE (link)
);

--
-- Table structure for system
--

CREATE TABLE system (
  filename varchar(255) NOT NULL default '',
  name varchar(255) NOT NULL default '',
  type varchar(255) NOT NULL default '',
  description varchar(255) NOT NULL default '',
  status integer NOT NULL default '0',
  PRIMARY KEY  (filename)
);

--
-- Table structure for sequences
-- This is only used under MySQL, co commented out
--
--
-- CREATE TABLE sequences (
--   name varchar(255) NOT NULL,
--   id integer NOT NULL,
--   PRIMARY KEY (name)
-- );

--
-- Table structure for term_data
--

CREATE TABLE term_data (
  tid SERIAL,
  vid integer NOT NULL default '0',
  name varchar(255) NOT NULL default '',
  description text,
  weight smallint NOT NULL default '0',
  PRIMARY KEY  (tid)
);
CREATE INDEX term_data_vid_idx ON term_data(vid);

--
-- Table structure for term_hierarchy
--

CREATE TABLE term_hierarchy (
  tid integer NOT NULL default '0',
  parent integer NOT NULL default '0'
);
CREATE INDEX term_hierarchy_tid_idx ON term_hierarchy(tid);
CREATE INDEX term_hierarchy_parent_idx ON term_hierarchy(parent);

--
-- Table structure for term_node
--

CREATE TABLE term_node (
  nid integer NOT NULL default '0',
  tid integer NOT NULL default '0'
);
CREATE INDEX term_node_nid_idx ON term_node(nid);
CREATE INDEX term_node_tid_idx ON term_node(tid);

--
-- Table structure for term_relation
--

CREATE TABLE term_relation (
  tid1 integer NOT NULL default '0',
  tid2 integer NOT NULL default '0'
);
CREATE INDEX term_relation_tid1_idx ON term_relation(tid1);
CREATE INDEX term_relation_tid2_idx ON term_relation(tid2);

--
-- Table structure for term_synonym
--

CREATE TABLE term_synonym (
  tid integer NOT NULL default '0',
  name varchar(255) NOT NULL default ''
);
CREATE INDEX term_synonym_tid_idx ON term_synonym(tid);
CREATE INDEX term_synonym_name_idx ON term_synonym(name);

--
-- Table structure for users
--

CREATE TABLE users (
  uid SERIAL,
  name varchar(60) NOT NULL default '',
  pass varchar(32) NOT NULL default '',
  mail varchar(64) default '',
  homepage varchar(128) NOT NULL default '',
  mode smallint NOT NULL default '0',
  sort smallint default '0',
  threshold smallint default '0',
  theme varchar(255) NOT NULL default '',
  signature varchar(255) NOT NULL default '',
  timestamp integer NOT NULL default '0',
  hostname varchar(128) NOT NULL default '',
  status smallint NOT NULL default '0',
  timezone varchar(8) default NULL,
  rating decimal(8,2) default NULL,
  language char(2) NOT NULL default '',
  sid varchar(32) NOT NULL default '',
  init varchar(64) default '',
  session text,
  data text,
  rid integer NOT NULL default '0',
  PRIMARY KEY  (uid),
  UNIQUE (name)
);

--
-- Table structure for variable
--

CREATE TABLE variable (
  name varchar(32) NOT NULL default '',
  value text NOT NULL,
  PRIMARY KEY  (name)
);

--
-- Table structure for vocabulary
--

CREATE TABLE vocabulary (
  vid SERIAL,
  name varchar(255) NOT NULL default '',
  description text,
  relations smallint NOT NULL default '0',
  hierarchy smallint NOT NULL default '0',
  multiple smallint NOT NULL default '0',
  required smallint NOT NULL default '0',
  types text,
  weight smallint NOT NULL default '0',
  PRIMARY KEY  (vid)
);

--
-- Table structure for watchdog
--

CREATE TABLE watchdog (
  wid SERIAL,
  uid integer NOT NULL default '0',
  type varchar(16) NOT NULL default '',
  message varchar(255) NOT NULL default '',
  location varchar(128) NOT NULL default '',
  hostname varchar(128) NOT NULL default '',
  timestamp integer NOT NULL default '0',
  PRIMARY KEY  (wid)
);

--
-- Insert some default values
--

INSERT INTO variable(name,value) VALUES('update_start', '2002-05-15');
INSERT INTO system VALUES ('archive.module','archive','module','',1);
INSERT INTO system VALUES ('block.module','block','module','',1);
INSERT INTO system VALUES ('blog.module','blog','module','',1);
INSERT INTO system VALUES ('book.module','book','module','',1);
INSERT INTO system VALUES ('cloud.module','cloud','module','',1);
INSERT INTO system VALUES ('comment.module','comment','module','',1);
INSERT INTO system VALUES ('forum.module','forum','module','',1);
INSERT INTO system VALUES ('help.module','help','module','',1);
INSERT INTO system VALUES ('import.module','import','module','',1);
INSERT INTO system VALUES ('node.module','node','module','',1);
INSERT INTO system VALUES ('page.module','page','module','',1);
INSERT INTO system VALUES ('poll.module','poll','module','',1);
INSERT INTO system VALUES ('queue.module','queue','module','',1);
INSERT INTO system VALUES ('rating.module','rating','module','',1);
INSERT INTO system VALUES ('search.module','search','module','',1);
INSERT INTO system VALUES ('statistics.module','statistics','module','',1);
INSERT INTO system VALUES ('story.module','story','module','',1);
INSERT INTO system VALUES ('taxonomy.module','taxonomy','module','',1);
INSERT INTO system VALUES ('themes/example/example.theme','example','theme','Internet explorer, Netscape, Opera, Lynx',1);
INSERT INTO system VALUES ('themes/goofy/goofy.theme','goofy','theme','Internetexplorer, Netscape, Opera',1);
INSERT INTO system VALUES ('themes/marvin/marvin.theme','marvin','theme','Internet explorer, Netscape, Opera',1);
INSERT INTO system VALUES ('themes/unconed/unconed.theme','unconed','theme','Internet explorer, Netscape, Opera',1);
INSERT INTO system VALUES ('tracker.module','tracker','module','',1);

DELETE FROM variable WHERE name='theme_default';
INSERT INTO variable(value,name) VALUES('marvin', 'theme_default');

DELETE FROM blocks WHERE name='User information';
INSERT INTO blocks(name,module,delta,status) VALUES('User information', 'user', '0', '1');

DELETE FROM blocks WHERE name='Log in';
INSERT INTO blocks(name,module,delta,status) VALUES('Log in', 'user', '1', '1');

