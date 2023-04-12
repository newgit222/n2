INSERT INTO `cm_config` (`name`,`title`,`type`,`sort`,`group` ,`value` ,`extra` ,`describe` ,`status`, `create_time`, `update_time`, `admin_id`)
VALUES ('usdt_rate', 'usdt充值费率' , 1, 0, 0, '6.8', '', '', 1, '1666212478', '1666212478', 1);

ALTER TABLE `cm_ewm_order` ADD `extra` TEXT NOT NULL COMMENT '附加信息';