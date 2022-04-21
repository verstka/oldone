<?php

namespace Components;

use Aura\Sql\ExtendedPdo;
use PDO;

// https://open.spotify.com/track/4wEPeUrP6m3RLKhhqyNBEX?si=3a5c8f36c3cf4d2e

class Sqlite
{
    static private $connection;

    public static function getConnection()
    {
        if (empty(static::$connection)) {

//            $profiler = new \Aura\Sql\Profiler();
//            $profiler->setActive(true);

            $is_need_init = !file_exists(static::getFilePath());

            static::$connection = new ExtendedPdo(
                sprintf('sqlite:%s', static::getFilePath()),
                static::getUser(),
                static::getPassword(),
                static::getSettings(), // driver attributes/options as key-value pairs
                []  // queries to execute after connection
            );
//            static::$connection->setProfiler($profiler);

            static::$connection->connect();
        }

        if ($is_need_init) {
            foreach (static::getInitialMigration() as $query) {
                static::$connection->query($query);
            }
        }

        return static::$connection;
    }

    private static function getFilePath()
    {
        return path(getenv('sqlite_file') ?? '/db/db_file.sqlite3');
    }

    private static function getUser()
    {
        return path(getenv('sqlite_user') ?? 'username');
    }

    private static function getPassword()
    {
        return path(getenv('sqlite_pass') ?? 'password');
    }

    private static function getSettings()
    {
        return [
            PDO::ATTR_EMULATE_PREPARES => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ];
    }

    private static function getInitialMigration()
    {
        return [
            "CREATE TABLE IF NOT EXISTS t_materials (
                name TEXT PRIMARY KEY,
                cdate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                udate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                desktop_html TEXT,
                mobile_html TEXT
            );",
            "INSERT INTO t_materials (name) VALUES ('index');",
            "INSERT INTO t_materials (name) VALUES ('about');",
            "INSERT INTO t_materials (name) VALUES ('test');"
        ];
    }
}