<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PHPExtensionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(User $user): void
    {
        $extensions = [
            "40-vld",
            "amqp",
            "apcu",
            "bcmath",
            "brotli",
            "bz2",
            "calendar",
            "core",
            "ctype",
            "curl",
            "date",
            "dba",
            "dbase",
            "dom",
            "eio",
            "elastic_apm",
            "enchant",
            "exif",
            "ffi",
            "fileinfo",
            "filter",
            "ftp",
            "gd",
            "gearman",
            "geoip",
            "geos",
            "gettext",
            "gmagick",
            "gmp",
            "gnupg",
            "grpc",
            "hash",
            "http",
            "iconv",
            "igbinary",
            "imagick",
            "imap",
            "inotify",
            "intl",
            "ioncube_loader",
            "jsmin",
            "json",
            "ldap",
            "libxml",
            "luasandbox",
            "lzf",
            "mailparse",
            "mbstring",
            "mcrypt",
            "mongodb",
            "msgpack",
            "mysqli",
            "mysqlnd",
            "nd_mysqli",
            "nd_pdo_mysql",
            "newrelic",
            "oauth",
            "odbc",
            "opcache",
            "openssl",
            "pcntl",
            "pcre",
            "pdf",
            "pdo",
            "pdo_dblib",
            "pdo_firebird",
            "pdo_mysql",
            "pdo_odbc",
            "pdo_pgsql",
            "pdo_sqlite",
            "pdo_sqlsrv",
            "pgsql",
            "phalcon5",
            "phar",
            "phpiredis",
            "posix",
            "protobuf",
            "pspell",
            "psr",
            "random",
            "raphf",
            "rar",
            "readline",
            "redis",
            "reflection",
            "rrd",
            "scoutapm",
            "session",
            "shmop",
            "simplexml",
            "snmp",
            "snuffleupagus",
            "soap",
            "sockets",
            "sodium",
            "solr",
            "sourceguardian",
            "spl",
            "sqlite3",
            "sqlsrv",
            "ssh2",
            "standard",
            "swoole",
            "sysvmsg",
            "sysvsem",
            "sysvshm",
            "tideways_xhprof",
            "tidy",
            "timezonedb",
            "tokenizer",
            "trader",
            "uploadprogress",
            "uuid",
            "xdebug",
            "xml",
            "xmlreader",
            "xmlrpc",
            "xmlwriter",
            "xsl",
            "yaml",
            "yaz",
            "zip",
            "zlib",
            "zmq",
        ];
        foreach ($extensions as $extension) {
            // //eğer linux ise paketi yükle
            // if (PHP_OS_FAMILY === 'Linux') {
            //     shell_exec("sudo apt-get install php-$extension");
            // }
            \App\Models\PHPExtension::create([
                'name' => $extension,
                'is_enabled' => false,
                'user_id' => $user->id,
            ]);
        }
    }
}