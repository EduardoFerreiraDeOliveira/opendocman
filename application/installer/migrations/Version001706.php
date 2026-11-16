<?php

require_once __DIR__ . '/MigrationInterface.php';

class Version001706 implements MigrationInterface
{
    public function getVersion(): string
    {
        return '1.7.6';
    }

    public function getDescription(): string
    {
        return 'Add properties: property table, property_perms table, data.property_id';
    }

    public function up(PDO $pdo, string $prefix): void
    {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS `{$prefix}property` (
                id int(11) unsigned NOT NULL auto_increment,
                reference varchar(50) NOT NULL default '',
                address varchar(255) NOT NULL default '',
                city varchar(100) default NULL,
                type varchar(30) NOT NULL default 'apartment',
                price decimal(12,2) default NULL,
                status varchar(20) NOT NULL default 'for_sale',
                owner_name varchar(255) default NULL,
                description text default NULL,
                created datetime NOT NULL,
                created_by int(11) unsigned default NULL,
                PRIMARY KEY (id),
                UNIQUE KEY reference (reference),
                KEY city (city),
                KEY status (status)
            ) ENGINE = MYISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS `{$prefix}property_perms` (
                prop_id int(11) unsigned NOT NULL,
                dept_id int(11) unsigned NOT NULL default '0',
                user_id int(11) unsigned NOT NULL default '0',
                rights tinyint(4) NOT NULL default '0',
                PRIMARY KEY (prop_id, dept_id, user_id),
                KEY prop_id (prop_id),
                KEY dept_id (dept_id),
                KEY user_id (user_id)
            ) ENGINE = MYISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        // 0 means "no property", the same convention as data.category.
        $pdo->exec(
            "ALTER TABLE `{$prefix}data`
                ADD COLUMN property_id int(11) unsigned NOT NULL default '0',
                ADD KEY property_id (property_id)"
        );
    }

    public function down(PDO $pdo, string $prefix): void
    {
        $pdo->exec("ALTER TABLE `{$prefix}data` DROP KEY property_id, DROP COLUMN property_id");
        $pdo->exec("DROP TABLE IF EXISTS `{$prefix}property_perms`");
        $pdo->exec("DROP TABLE IF EXISTS `{$prefix}property`");
    }
}
