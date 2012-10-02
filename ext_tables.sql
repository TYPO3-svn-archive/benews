#
# Table structure for table 'tx_benews_readarticles'
#
CREATE TABLE tx_benews_readarticles (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	news_table  text,
	news_uid    int(11) DEFAULT '0' NOT NULL,
	be_user_uid int(11) DEFAULT '0' NOT NULL,
	PRIMARY KEY (uid),
	KEY parent (pid),
);
