<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

$access_table = mcs_table('staff_access');
$nonce_table  = mcs_table('used_nonce');

$CI->db->query("CREATE TABLE IF NOT EXISTS `$access_table` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `clientid` INT NOT NULL,
    `email` VARCHAR(191) NOT NULL,
    `company_slug` VARCHAR(191) NOT NULL,
    `staff_id` INT NOT NULL,
    `origin_slug` VARCHAR(191) NOT NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_email_company` (`email`, `company_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$CI->db->query("CREATE TABLE IF NOT EXISTS `$nonce_table` (
    `nonce` VARCHAR(64) NOT NULL,
    `used_at` DATETIME NOT NULL,
    PRIMARY KEY (`nonce`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
