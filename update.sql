-- INSERT INTO `cm_config` (`name`,`title`,`type`,`sort`,`group` ,`value` ,`extra` ,`describe` ,`status`, `create_time`, `update_time`)
-- VALUES ('usdt_rate', 'usdt充值费率' , 1, 0, 0, '6.8', '', '', 1, '1666212478', '1666212478');

ALTER TABLE `cm_ewm_order` ADD `extra` TEXT NOT NULL COMMENT '附加信息';
INSERT INTO `cm_pay_code` VALUES ('53', '', 'usdt钱包', 'usdtTrc', 'usdt钱包', '1', '1678084941', '1678085087', '');

ALTER TABLE `cm_orders_notify`
    MODIFY COLUMN `sign_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'sign_date' AFTER `times`,
    MODIFY COLUMN `sign_md5` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'sign_md5' AFTER `sign_data`;
ALTER TABLE `cm_orders_notify` ADD `content` text NOT NULL COMMENT '原始返回内容';

