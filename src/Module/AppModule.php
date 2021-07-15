<?php

declare(strict_types=1);

namespace MyVendor\Weekday\Module;

use BEAR\Dotenv\Dotenv;
use BEAR\Package\AbstractAppModule;
use BEAR\Package\PackageModule;
use Ray\AuraSqlModule\AuraSqlModule;
use Ray\Query\SqlQueryModule;

use function dirname;

class AppModule extends AbstractAppModule
{
    protected function configure(): void
    {
        (new Dotenv())->load(dirname(__DIR__, 2));
        $this->install(new AuraSqlModule('sqlite:' . $this->appMeta->appDir . '/var/db/sample.sqlite3'));
        $this->install(new SqlQueryModule($this->appMeta->appDir . '/var/db/sql'));
        $this->install(new PackageModule());
    }
}
