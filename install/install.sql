CREATE TABLE `mod_sources` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `domain` varchar(255) NOT NULL,
    `tags` varchar(500) DEFAULT NULL,
    `region` varchar(255) DEFAULT NULL,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `mod_sources_tags` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `type` varchar(255) DEFAULT NULL,
    `tag` varchar(255) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `tag` (`tag`),
    KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `mod_sources_pages` (
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
    CONSTRAINT `fk1_mod_sources_pages` FOREIGN KEY (`source_id`) REFERENCES `mod_sources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `mod_sources_pages_tags` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `page_id` int unsigned DEFAULT NULL,
    `tag_id` int unsigned DEFAULT NULL,
    `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `page_id` (`page_id`),
    KEY `tag_id` (`tag_id`),
    CONSTRAINT `fk1_mod_sources_pages_tags` FOREIGN KEY (`page_id`) REFERENCES `mod_sources_pages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk2_mod_sources_pages_tags` FOREIGN KEY (`tag_id`) REFERENCES `mod_sources_tags` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `mod_sources_pages_contents` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `page_id` int unsigned DEFAULT NULL,
    `content` longtext,
    `hash` varchar(128) NOT NULL,
    `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `page_id` (`page_id`),
    FULLTEXT KEY `ft1` (`content`),
    CONSTRAINT `fk1_mod_sources_pages_contents` FOREIGN KEY (`page_id`) REFERENCES `mod_sources_pages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `mod_sources_pages_media` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `page_id` int unsigned NOT NULL,
    `url` varchar(500) DEFAULT NULL,
    `type` varchar(255) DEFAULT NULL,
    `description` varchar(255) DEFAULT NULL,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `page_id` (`page_id`),
    CONSTRAINT `fk1_mod_sources_pages_images` FOREIGN KEY (`page_id`) REFERENCES `mod_sources_pages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `mod_sources_pages_references` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `page_id` int unsigned NOT NULL,
    `domain` varchar(255) DEFAULT NULL,
    `url` varchar(500) DEFAULT NULL,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `page_id` (`page_id`),
    CONSTRAINT `fk1_mod_sources_pages_references` FOREIGN KEY (`page_id`) REFERENCES `mod_sources_pages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `mod_sources_contents_raw` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `source_id` int unsigned NOT NULL,
    `domain` varchar(255) DEFAULT NULL,
    `url` varchar(255) NOT NULL,
    `status` enum('pending','process','complete','error')NOT NULL DEFAULT 'pending',
    `content_type` enum('html','json','text') DEFAULT 'html',
    `section_name` varchar(255) DEFAULT NULL,
    `content` longtext CHARACTER SET utf8 NOT NULL,
    `options` json DEFAULT NULL,
    `note` varchar(255) DEFAULT NULL,
    `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `url` (`url`),
    KEY `status` (`status`),
    KEY `source_id` (`source_id`),
    KEY `domain` (`domain`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `mod_sources_chats` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `messenger_type` enum('tg') NOT NULL,
  `type` enum('channel','group') NOT NULL,
  `peer_name` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `geolocation` varchar(255) DEFAULT NULL,
  `subscribers_count` int unsigned DEFAULT NULL,
  `date_messenger_created` datetime DEFAULT NULL,
  `last_message_id` int unsigned DEFAULT NULL,
  `date_last_message` timestamp NULL DEFAULT NULL,
  `tgstat` json DEFAULT NULL,
  `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `title` (`title`),
  KEY `peer_name` (`peer_name`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4;

CREATE TABLE `mod_sources_chats_files` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `content` longblob,
  `refid` int unsigned NOT NULL,
  `filename` varchar(255) NOT NULL,
  `filesize` int NOT NULL,
  `hash` varchar(128) NOT NULL,
  `type` varchar(20) DEFAULT NULL,
  `fieldid` varchar(255) DEFAULT NULL,
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
    `messenger_id` int unsigned NOT NULL,
    `category_id` int unsigned NOT NULL,
    PRIMARY KEY (`id`),
    KEY `messenger_id` (`messenger_id`),
    KEY `category_id` (`category_id`),
    CONSTRAINT `fk1_mod_sources_chats_categories_link` FOREIGN KEY (`messenger_id`) REFERENCES `mod_sources_chats` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk2_mod_sources_chats_categories_link` FOREIGN KEY (`category_id`) REFERENCES `mod_sources_chats_categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `mod_sources_chats_messages` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `messenger_id` int unsigned NOT NULL,
    `type` enum ('msg', 'file') NOT NULL DEFAULT 'msg',
    `viewed_count` int unsigned DEFAULT NULL,
    `repost_count` int unsigned DEFAULT NULL,
    `comments_count` int unsigned DEFAULT NULL,
    `content` text NULL,
    `meta_data` JSON NULL DEFAULT NULL,
    `date_messenger_edit` timestamp NULL DEFAULT NULL,
    `date_messenger_created` timestamp NULL DEFAULT NULL,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `messenger_id` (`messenger_id`),
    CONSTRAINT `fk1_mod_sources_chats_messages` FOREIGN KEY (`messenger_id`) REFERENCES `mod_sources_chats` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `mod_sources_chats_messages_files` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `content` longblob,
    `refid` int unsigned NOT NULL,
    `filename` varchar(255) NOT NULL,
    `filesize` int NOT NULL,
    `hash` varchar(128) NOT NULL,
    `type` varchar(20) DEFAULT NULL,
    `fieldid` varchar(255) DEFAULT NULL,
    `thumb` longblob,
    PRIMARY KEY (`id`),
    KEY `refid` (`refid`),
    CONSTRAINT `fk1_mod_sources_chats_messages_files` FOREIGN KEY (`refid`) REFERENCES `mod_sources_chats_messages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `mod_sources_chats_messages_reactions` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `message_id` int unsigned NOT NULL,
    `emoticon` varchar(10) NOT NULL,
    `count` int unsigned NOT NULL,
    PRIMARY KEY (`id`),
    KEY `message_id` (`message_id`),
    CONSTRAINT `fk1_mod_sources_chats_messages_reactions` FOREIGN KEY (`message_id`) REFERENCES `mod_sources_chats_messages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `mod_sources_chats_subscribers` (
   `id` int unsigned NOT NULL AUTO_INCREMENT,
   `messenger_id` int unsigned NOT NULL,
   `date_day` date NOT NULL,
   `quantity` int unsigned NOT NULL,
   `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
   `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
   PRIMARY KEY (`id`),
   KEY `messenger_id` (`messenger_id`),
   KEY `date_day` (`date_day`),
   CONSTRAINT `fk1_mod_sources_chats_subscribers` FOREIGN KEY (`messenger_id`) REFERENCES `mod_sources_chats` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE `mod_sources_chats_content` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `type` varchar(100) NOT NULL,
    `content` mediumtext NOT NULL,
    `meta_data` json NULL DEFAULT NULL,
    `is_parsed_sw` enum ('Y', 'N') NOT NULL DEFAULT 'N',
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `type` (`type`),
    KEY `is_parsed_sw` (`is_parsed_sw`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;
