alter table minibb_forums add column topics_count int(10) not null default 0;
alter table minibb_forums add column posts_count int(10) not null default 0;

alter table minibb_topics add column posts_count int(10) not null default 0;
alter table minibb_topics add column sticky int(1) not null default 0;
alter table minibb_topics add key (sticky);
alter table minibb_topics add key (topic_last_post_id);

alter table minibb_send_mails add key (topic_id);
alter table minibb_send_mails add key (user_id);

alter table minibb_users add language varchar(3) not null default '';
alter table minibb_users change user_icq user_icq varchar(50) not null default '';
alter table minibb_users add activity int(1) not null default '1';
alter table minibb_users add user_custom1 varchar(255) not null default '';
alter table minibb_users add user_custom2 varchar(255) not null default '';
alter table minibb_users add user_custom3 varchar(255) not null default '';