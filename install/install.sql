CREATE TABLE `mod_sources_sites` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `domain` varchar(255) NOT NULL,
    `tags` varchar(500) DEFAULT NULL,
    `region` varchar(255) DEFAULT NULL,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `mod_sources_sites_tags` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `type` varchar(255) DEFAULT NULL,
    `tag` varchar(255) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `tag` (`tag`),
    KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `mod_sources_sites_pages` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `source_id` int unsigned NOT NULL,
    `title` varchar(1000) DEFAULT NULL,
    `url` varchar(500) DEFAULT NULL,
    `image` varchar(500) DEFAULT NULL,
    `source_domain` varchar(128) DEFAULT NULL,
    `source_url` varchar(500) DEFAULT NULL,
    `source_author` varchar(255) DEFAULT NULL,
    `count_views` int unsigned DEFAULT NULL,
    `date_publish` timestamp NULL DEFAULT NULL,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `source_id` (`source_id`),
    FULLTEXT KEY `ft1` (`title`),
    CONSTRAINT `fk1_mod_sources_sites_pages` FOREIGN KEY (`source_id`) REFERENCES `mod_sources_sites` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `mod_sources_sites_pages_tags` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `page_id` int unsigned DEFAULT NULL,
    `tag_id` int unsigned DEFAULT NULL,
    `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `page_id` (`page_id`),
    KEY `tag_id` (`tag_id`),
    CONSTRAINT `fk1_mod_sources_sites_pages_tags` FOREIGN KEY (`page_id`) REFERENCES `mod_sources_sites_pages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk2_mod_sources_sites_pages_tags` FOREIGN KEY (`tag_id`) REFERENCES `mod_sources_sites_tags` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `mod_sources_sites_pages_contents` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `page_id` int unsigned DEFAULT NULL,
    `content` longtext,
    `hash` varchar(128) NOT NULL,
    `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `page_id` (`page_id`),
    FULLTEXT KEY `ft1` (`content`),
    CONSTRAINT `fk1_mod_sources_sites_pages_contents` FOREIGN KEY (`page_id`) REFERENCES `mod_sources_sites_pages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `mod_sources_sites_pages_media` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `page_id` int unsigned NOT NULL,
    `url` varchar(500) DEFAULT NULL,
    `type` varchar(255) DEFAULT NULL,
    `description` varchar(255) DEFAULT NULL,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `page_id` (`page_id`),
    CONSTRAINT `fk1_mod_sources_sites_pages_media` FOREIGN KEY (`page_id`) REFERENCES `mod_sources_sites_pages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `mod_sources_sites_pages_references` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `page_id` int unsigned NOT NULL,
    `domain` varchar(255) DEFAULT NULL,
    `url` varchar(500) DEFAULT NULL,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `page_id` (`page_id`),
    CONSTRAINT `fk1_mod_sources_sites_pages_references` FOREIGN KEY (`page_id`) REFERENCES `mod_sources_sites_pages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `mod_sources_sites_contents_raw` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `source_id` int unsigned NOT NULL,
    `domain` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
    `url` varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `status` enum('pending','process','complete','error') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
    `content_type` enum('html','json','text') COLLATE utf8mb4_general_ci DEFAULT 'html',
    `file_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
    `file_size` int DEFAULT NULL,
    `section_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
    `options` json DEFAULT NULL,
    `note` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
    `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `url` (`url`),
    KEY `status` (`status`),
    KEY `source_id` (`source_id`),
    KEY `domain` (`domain`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

###
### Chats
###

CREATE TABLE `mod_sources_chats` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `messenger_type` enum('tg') NOT NULL,
    `type` enum('channel','group') DEFAULT NULL,
    `peer_name` varchar(255) DEFAULT NULL,
    `peer_id` varchar(100) DEFAULT NULL,
    `title` varchar(255) DEFAULT NULL,
    `description` varchar(500) DEFAULT NULL,
    `geolocation` varchar(255) DEFAULT NULL,
    `subscribers_count` int unsigned DEFAULT NULL,
    `date_state_info` datetime DEFAULT NULL,
    `old_message_id` int unsigned DEFAULT NULL,
    `new_message_id` int unsigned DEFAULT NULL,
    `date_old_message` timestamp NULL DEFAULT NULL,
    `date_new_message` timestamp NULL DEFAULT NULL,
    `date_history_load` date DEFAULT NULL,
    `tgstat` json DEFAULT NULL,
    `is_connect_sw` enum('Y','N') NOT NULL DEFAULT 'N',
    `is_extreme_sw` enum('N','Y') NOT NULL DEFAULT 'N',
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `title` (`title`),
    KEY `peer_name` (`peer_name`),
    KEY `type` (`type`),
    KEY `peer_id` (`peer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `mod_sources_chats_accounts` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `account_key` varchar(255) NOT NULL,
    `inactive_methods` json NULL DEFAULT NULL,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `account_key` (`account_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `mod_sources_chats_accounts_subscribes` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `account_id` int unsigned NOT NULL,
    `chat_id` int unsigned NOT NULL,
    `is_subscribe_sw` enum('Y','N') NOT NULL DEFAULT 'N',
    `is_subscribe_need_sw` enum('Y','N') DEFAULT 'N',
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `account_id_chat_id` (`account_id`,`chat_id`),
    KEY `account_id` (`account_id`),
    KEY `chat_id` (`chat_id`),
    CONSTRAINT `fk1_mod_sources_chats_accounts_subscribes` FOREIGN KEY (`account_id`) REFERENCES `mod_sources_chats_accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk2_mod_sources_chats_accounts_subscribes` FOREIGN KEY (`chat_id`) REFERENCES `mod_sources_chats` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `mod_sources_chats_files` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `content` longblob,
  `refid` int unsigned NOT NULL,
  `filename` varchar(255) NOT NULL,
  `filesize` int NOT NULL,
  `hash` varchar(128) NOT NULL,
  `type` varchar(20) DEFAULT NULL,
  `fieldid` varchar(255) DEFAULT NULL,
  `meta_data` json DEFAULT NULL,
  `thumb` longblob,
  PRIMARY KEY (`id`),
  KEY `refid` (`refid`),
  CONSTRAINT `fk1_mod_sources_chats_files` FOREIGN KEY (`refid`) REFERENCES `mod_sources_chats` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `mod_sources_chats_categories` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `mod_sources_chats_categories_link` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `chat_id` int unsigned NOT NULL,
    `category_id` int unsigned NOT NULL,
    PRIMARY KEY (`id`),
    KEY `messenger_id` (`chat_id`),
    KEY `category_id` (`category_id`),
    CONSTRAINT `fk1_mod_sources_chats_categories_link` FOREIGN KEY (`chat_id`) REFERENCES `mod_sources_chats` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk2_mod_sources_chats_categories_link` FOREIGN KEY (`category_id`) REFERENCES `mod_sources_chats_categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `mod_sources_chats_users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('user','bot') NOT NULL DEFAULT 'user',
  `messenger_id` varchar(100) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `phone_number` varchar(100) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `messenger_id` (`messenger_id`),
  KEY `phone_number` (`phone_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `mod_sources_chats_users_links` (
   `id` int unsigned NOT NULL AUTO_INCREMENT,
   `chat_id` int unsigned NOT NULL,
   `user_id` int unsigned NOT NULL,
   PRIMARY KEY (`id`),
   KEY `chat_id` (`chat_id`),
   KEY `user_id` (`user_id`),
   CONSTRAINT `fk1_mod_sources_chats_users_links` FOREIGN KEY (`chat_id`) REFERENCES `mod_sources_chats` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
   CONSTRAINT `fk2_mod_sources_chats_users_links` FOREIGN KEY (`user_id`) REFERENCES `mod_sources_chats_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `mod_sources_chats_reactions` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `emoticon` varchar(10) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `emoticon` (`emoticon`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `mod_sources_chats_links` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `host` varchar(100) NOT NULL,
    `url` varchar(10000) NOT NULL,
    `type` enum('document','audio','photo','video','tg_channel','tg_message') DEFAULT NULL,
    `hash` varchar(100) DEFAULT NULL,
    `title` varchar(1500) DEFAULT NULL,
    `description` text,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `host` (`host`),
    KEY `hash` (`hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `mod_sources_chats_hashtags` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `hashtag` varchar(255) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `host` (`hashtag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `mod_sources_chats_messages` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `chat_id` int unsigned NOT NULL,
    `user_id` int unsigned DEFAULT NULL,
    `media_type` enum('document','photo','video','audio','webpage','other') DEFAULT NULL,
    `messenger_id` int unsigned NOT NULL,
    `viewed_count` int unsigned DEFAULT NULL,
    `repost_count` int unsigned DEFAULT NULL,
    `comments_count` int unsigned DEFAULT NULL,
    `reply_to_id` int unsigned DEFAULT NULL,
    `fwd_chat_id` varchar(100) DEFAULT NULL,
    `fwd_message_id` varchar(100) DEFAULT NULL,
    `group_value` varchar(100) DEFAULT NULL,
    `content` text,
    `meta_data` json DEFAULT NULL,
    `date_messenger_edit` timestamp NULL DEFAULT NULL,
    `date_messenger_created` timestamp NULL DEFAULT NULL,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `messenger_id` (`messenger_id`),
    KEY `chat_id` (`chat_id`),
    KEY `group_value` (`group_value`),
    KEY `media_type` (`media_type`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `fk1_mod_sources_chats_messages` FOREIGN KEY (`chat_id`) REFERENCES `mod_sources_chats` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk2_mod_sources_chats_messages` FOREIGN KEY (`user_id`) REFERENCES `mod_sources_chats_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `mod_sources_chats_messages_files` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `content` longblob,
    `refid` int unsigned NOT NULL,
    `filename` varchar(255) DEFAULT NULL,
    `filesize` int DEFAULT NULL,
    `hash` varchar(128) NOT NULL,
    `type` varchar(20) DEFAULT NULL,
    `fieldid` varchar(255) DEFAULT NULL,
    `media_type` varchar(255) DEFAULT NULL,
    `meta_data` json DEFAULT NULL,
    `is_load_sw` enum('Y','N') NOT NULL DEFAULT 'N',
    `thumb` longblob,
    PRIMARY KEY (`id`),
    KEY `refid` (`refid`),
    KEY `is_load_sw` (`is_load_sw`),
    KEY `media_type` (`media_type`),
    CONSTRAINT `fk1_mod_sources_chats_messages_files` FOREIGN KEY (`refid`) REFERENCES `mod_sources_chats_messages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `mod_sources_chats_messages_reactions` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `message_id` int unsigned NOT NULL,
    `reaction_id` int unsigned NOT NULL,
    `count` int unsigned NOT NULL,
    PRIMARY KEY (`id`),
    KEY `message_id` (`message_id`),
    KEY `reaction_id` (`reaction_id`),
    CONSTRAINT `fk1_mod_sources_chats_messages_reactions` FOREIGN KEY (`message_id`) REFERENCES `mod_sources_chats_messages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk2_mod_sources_chats_messages_reactions` FOREIGN KEY (`reaction_id`) REFERENCES `mod_sources_chats_reactions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `mod_sources_chats_messages_replies` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `message_id` int unsigned NOT NULL,
    `user_id` int unsigned NOT NULL,
    PRIMARY KEY (`id`),
    KEY `message_id` (`message_id`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `fk1_mod_sources_chats_messages_replies` FOREIGN KEY (`message_id`) REFERENCES `mod_sources_chats_messages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk2_mod_sources_chats_messages_replies` FOREIGN KEY (`user_id`) REFERENCES `mod_sources_chats_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `mod_sources_chats_messages_links` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `message_id` int unsigned NOT NULL,
    `link_id` int unsigned NOT NULL,
    `offset` int unsigned DEFAULT NULL,
    `length` int unsigned DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `message_id` (`message_id`),
    KEY `link_id` (`link_id`),
    CONSTRAINT `fk1_mod_sources_chats_messages_links` FOREIGN KEY (`message_id`) REFERENCES `mod_sources_chats_messages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk2_mod_sources_chats_messages_links` FOREIGN KEY (`link_id`) REFERENCES `mod_sources_chats_links` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `mod_sources_chats_messages_hashtags` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `message_id` int unsigned NOT NULL,
    `hashtag_id` int unsigned NOT NULL,
    PRIMARY KEY (`id`),
    KEY `message_id` (`message_id`),
    KEY `hashtag_id` (`hashtag_id`),
    CONSTRAINT `fk1_mod_sources_chats_messages_hashtags` FOREIGN KEY (`message_id`) REFERENCES `mod_sources_chats_messages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk2_mod_sources_chats_messages_hashtags` FOREIGN KEY (`hashtag_id`) REFERENCES `mod_sources_chats_hashtags` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `mod_sources_chats_subscribers` (
   `id` int unsigned NOT NULL AUTO_INCREMENT,
   `chat_id` int unsigned NOT NULL,
   `date_day` date NOT NULL,
   `quantity` int unsigned NOT NULL,
   PRIMARY KEY (`id`),
   KEY `date_day` (`date_day`),
   KEY `chat_id` (`chat_id`),
   CONSTRAINT `fk1_mod_sources_chats_subscribers` FOREIGN KEY (`chat_id`) REFERENCES `mod_sources_chats` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `mod_sources_chats_content` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `type` varchar(100) NOT NULL,
    `file_name` varchar(255) DEFAULT NULL,
    `file_size` int DEFAULT NULL,
    `meta_data` json DEFAULT NULL,
    `hash` varchar(100) DEFAULT NULL,
    `is_parsed_sw` enum('Y','N') NOT NULL DEFAULT 'N',
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `type` (`type`),
    KEY `is_parsed_sw` (`is_parsed_sw`),
    KEY `hash` (`hash`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

###
### Video
###

CREATE TABLE `mod_sources_videos` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `type` enum('yt','rt') NOT NULL,
    `channel_id` varchar(255) DEFAULT NULL,
    `name` varchar(255) DEFAULT NULL,
    `title` varchar(255) DEFAULT NULL,
    `description` text,
    `subscribers_count` int unsigned DEFAULT NULL,
    `geolocation` varchar(100) DEFAULT NULL,
    `default_lang` varchar(10) DEFAULT NULL,
    `meta_data` json DEFAULT NULL,
    `date_state_info` date DEFAULT NULL,
    `date_update_videos` date DEFAULT NULL,
    `date_platform_created` date DEFAULT NULL,
    `date_load_last_clip` timestamp NULL DEFAULT NULL,
    `date_load_old_clip` timestamp NULL DEFAULT NULL,
    `is_connect_sw` enum('Y','N') NOT NULL DEFAULT 'N',
    `is_load_info_sw` enum('Y','N') DEFAULT 'N',
    `is_load_history_sw` enum('Y','N') NOT NULL DEFAULT 'N',
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_modify` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `type` (`type`),
    KEY `channel_id` (`channel_id`),
    KEY `title` (`title`),
    KEY `is_connect_sw` (`is_connect_sw`),
    KEY `name` (`name`),
    KEY `is_load_info_sw` (`is_load_info_sw`),
    KEY `is_load_history_sw` (`is_load_history_sw`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `mod_sources_videos_accounts` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `account_key` varchar(255) NOT NULL,
    `inactive_methods` json DEFAULT NULL,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `account_key` (`account_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `mod_sources_videos_stats` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `channel_id` int unsigned NOT NULL,
    `date_day` date NOT NULL,
    `subscribers_count` int unsigned DEFAULT NULL,
    `view_count` varchar(40) DEFAULT NULL,
    `video_count` int unsigned DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `date_day_channel_id` (`channel_id`,`date_day`),
    KEY `channel_id` (`channel_id`),
    KEY `date_day` (`date_day`),
    CONSTRAINT `fk1_mod_sources_videos_subscribers` FOREIGN KEY (`channel_id`) REFERENCES `mod_sources_videos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `mod_sources_videos_links` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `host` varchar(100) NOT NULL,
    `url` varchar(10000) NOT NULL,
    `hash` varchar(100) DEFAULT NULL,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `host` (`host`),
    KEY `hash` (`hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `mod_sources_videos_users` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(200) DEFAULT NULL,
    `type` varchar(20) NOT NULL,
    `platform_id` varchar(200) DEFAULT NULL,
    `profile_url` varchar(500) DEFAULT NULL,
    `profile_avatar_url` varchar(500) DEFAULT NULL,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_modify` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `name` (`name`),
    KEY `platform_id` (`platform_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `mod_sources_videos_hashtags` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `hashtag` varchar(255) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `host` (`hashtag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `mod_sources_videos_channels_links` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `channel_id` int unsigned NOT NULL,
    `link_id` int unsigned NOT NULL,
    PRIMARY KEY (`id`),
    KEY `channel_id` (`channel_id`),
    KEY `link_id` (`link_id`),
    CONSTRAINT `fk1_mod_sources_videos_channels_links` FOREIGN KEY (`channel_id`) REFERENCES `mod_sources_videos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk2_mod_sources_videos_channels_links` FOREIGN KEY (`link_id`) REFERENCES `mod_sources_videos_links` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `mod_sources_videos_channels_hashtags` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `channel_id` int unsigned NOT NULL,
    `hashtag_id` int unsigned NOT NULL,
    PRIMARY KEY (`id`),
    KEY `channel_id` (`channel_id`),
    KEY `hashtag_id` (`hashtag_id`),
    CONSTRAINT `fk1_mod_sources_videos_channels_hashtags` FOREIGN KEY (`channel_id`) REFERENCES `mod_sources_videos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk2_mod_sources_videos_channels_hashtags` FOREIGN KEY (`hashtag_id`) REFERENCES `mod_sources_videos_hashtags` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `mod_sources_videos_clips` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `channel_id` int unsigned DEFAULT NULL,
    `platform_id` varchar(255) NOT NULL,
    `url` varchar(255) DEFAULT NULL,
    `type` varchar(50) DEFAULT NULL,
    `title` varchar(255) DEFAULT NULL,
    `description` text,
    `duration` int unsigned DEFAULT NULL,
    `viewed_count` int unsigned DEFAULT NULL,
    `comments_count` int unsigned DEFAULT NULL,
    `likes_count` int unsigned DEFAULT NULL,
    `dislike_count` int unsigned DEFAULT NULL,
    `default_lang` varchar(10) DEFAULT NULL,
    `date_platform_created` timestamp NULL DEFAULT NULL,
    `comments_page_token` varchar(255) DEFAULT NULL,
    `is_load_comments_sw` enum('Y','N') NOT NULL DEFAULT 'N',
    `is_load_info_sw` enum('Y','N') NOT NULL DEFAULT 'N',
    `is_load_subtitles_sw` enum('Y','N') NOT NULL DEFAULT 'N',
    `date_load_deferred` timestamp NULL DEFAULT NULL,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_modify` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `channel_id` (`channel_id`),
    KEY `platform_id` (`platform_id`),
    KEY `title` (`title`),
    KEY `is_load_comments_sw` (`is_load_comments_sw`),
    KEY `viewed_count` (`viewed_count` DESC),
    KEY `type` (`type`),
    KEY `date_platform_created` (`date_platform_created` DESC),
    KEY `is_load_info_sw` (`is_load_info_sw`),
    CONSTRAINT `fk1_mod_sources_videos_clips` FOREIGN KEY (`channel_id`) REFERENCES `mod_sources_videos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `mod_sources_videos_clips_files` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `content` longblob,
    `refid` int unsigned NOT NULL,
    `filename` varchar(255) DEFAULT NULL,
    `filesize` int DEFAULT NULL,
    `hash` varchar(128) NOT NULL,
    `type` varchar(20) DEFAULT NULL,
    `fieldid` varchar(255) DEFAULT NULL,
    `meta_data` json DEFAULT NULL,
    `thumb` longblob,
    `is_load_sw` enum('Y','N') NOT NULL DEFAULT 'Y',
    PRIMARY KEY (`id`),
    KEY `refid` (`refid`),
    KEY `is_load_sw` (`is_load_sw`),
    CONSTRAINT `fk1_mod_sources_videos_clips_files` FOREIGN KEY (`refid`) REFERENCES `mod_sources_videos_clips` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `mod_sources_videos_clips_comments` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `clip_id` int unsigned NOT NULL,
    `user_id` int unsigned DEFAULT NULL,
    `platform_id` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
    `reply_to_id` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
    `likes_count` int unsigned DEFAULT NULL,
    `dislike_count` int unsigned DEFAULT NULL,
    `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
    `date_platform_created` timestamp NULL DEFAULT NULL,
    `date_platform_modify` timestamp NULL DEFAULT NULL,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_modify` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `clip_id` (`clip_id`),
    KEY `platform_id` (`platform_id`),
    KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `mod_sources_videos_clips_subtitles` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `clip_id` int unsigned NOT NULL,
    `lang` varchar(255) DEFAULT NULL,
    `content_time` longtext,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `clip_id` (`clip_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `mod_sources_videos_clips_hashtags` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `clip_id` int unsigned NOT NULL,
    `hashtag_id` int unsigned NOT NULL,
    PRIMARY KEY (`id`),
    KEY `clip_id` (`clip_id`),
    KEY `hashtag_id` (`hashtag_id`),
    CONSTRAINT `fk1_mod_sources_videos_clips_hashtags` FOREIGN KEY (`clip_id`) REFERENCES `mod_sources_videos_clips` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk2_mod_sources_videos_clips_hashtags` FOREIGN KEY (`hashtag_id`) REFERENCES `mod_sources_videos_hashtags` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `mod_sources_videos_clips_links` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `clip_id` int unsigned NOT NULL,
    `link_id` int unsigned NOT NULL,
    PRIMARY KEY (`id`),
    KEY `clip_id` (`clip_id`),
    KEY `link_id` (`link_id`),
    CONSTRAINT `fk1_mod_sources_videos_clips_links` FOREIGN KEY (`clip_id`) REFERENCES `mod_sources_videos_clips` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk2_mod_sources_videos_clips_links` FOREIGN KEY (`link_id`) REFERENCES `mod_sources_videos_links` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `mod_sources_videos_raw` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `type` varchar(100) NOT NULL,
    `file_name` varchar(255) DEFAULT NULL,
    `file_size` int DEFAULT NULL,
    `meta_data` json DEFAULT NULL,
    `hash` varchar(100) DEFAULT NULL,
    `is_parsed_sw` enum('Y','N') NOT NULL DEFAULT 'N',
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_modify` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `type` (`type`),
    KEY `is_parsed_sw` (`is_parsed_sw`),
    KEY `hash` (`hash`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;