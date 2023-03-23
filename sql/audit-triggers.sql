
CREATE TABLE `audit_recency` SELECT * from `recency` WHERE 1=0;

ALTER TABLE `audit_recency` 
   MODIFY COLUMN `recency_id` int(11) NOT NULL, 
   ENGINE = MyISAM, 
   ADD `action` VARCHAR(8) DEFAULT 'insert' FIRST, 
   ADD `revision` INT(6) NOT NULL AUTO_INCREMENT AFTER `action`,
   ADD `dt_datetime` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `revision`,
   ADD PRIMARY KEY (`recency_id`, `revision`);

DROP TRIGGER IF EXISTS recency_data__ai;
DROP TRIGGER IF EXISTS recency_data__au;
DROP TRIGGER IF EXISTS recency_data__bd;

CREATE TRIGGER recency_data__ai AFTER INSERT ON `recency` FOR EACH ROW
    INSERT INTO `audit_recency` SELECT 'insert', NULL, NOW(), d.* 
    FROM `recency` AS d WHERE d.recency_id = NEW.recency_id;

CREATE TRIGGER recency_data__au AFTER UPDATE ON `recency` FOR EACH ROW
    INSERT INTO `audit_recency` SELECT 'update', NULL, NOW(), d.*
    FROM `recency` AS d WHERE d.recency_id = NEW.recency_id;

CREATE TRIGGER recency_data__bd BEFORE DELETE ON `recency` FOR EACH ROW
    INSERT INTO `audit_recency` SELECT 'delete', NULL, NOW(), d.* 
    FROM `recency` AS d WHERE d.recency_id = OLD.recency_id;