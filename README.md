# MyVendor.Weekday

## Installation

    composer install

## Usage

### Invoke Request

    composer page get /

### Available Commands

    composer serve             // start builtin server
    composer test              // run unit test
    composer tests             // test and quality checks
    composer coverage          // test coverage
    composer cs-fix            // fix the coding standard
    composer doc               // generate API document
    composer run-script --list // list all commands
    
## Links

 * BEAR.Sunday http://bearsunday.github.io/

## Diff
- AppModule.php
```php
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
        $this->install(new SqlQueryModule($this->appMeta->appDir . '/var/db/sql', null, true));
        $this->install(new PackageModule());
    }
}

```

- SqlQueryModule.php
```php
<?php

declare(strict_types=1);

namespace Ray\Query;

use FilesystemIterator;
use Ray\Di\AbstractModule;
use Ray\Query\Annotation\AliasQuery;
use Ray\Query\Annotation\Query;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;
use SplFileInfo;

use function file_get_contents;
use function pathinfo;
use function trim;

class SqlQueryModule extends AbstractModule
{
    /** @var string */
    private $sqlDir;

    public function __construct(string $sqlDir, ?AbstractModule $module = null, $debug = false)
    {
        $this->debug = $debug;
        $this->sqlDir = $sqlDir;
        parent::__construct($module);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        /** @var SplFileInfo $fileInfo */
        foreach ($this->files($this->sqlDir) as $fileInfo) {
            $fullPath = $fileInfo->getPathname();
            $name = pathinfo((string) $fileInfo->getRealPath())['filename'];
            $sqlId = 'sql-' . $name;
            $this->bind(QueryInterface::class)->annotatedWith($name)->toConstructor(
                SqlQueryRowList::class,
                "sql={$sqlId}"
            );
            $this->bindCallableItem($name, $sqlId);
            $this->bindCallableList($name, $sqlId);

            $sql = $this->getFullPathAsComment($fileInfo) . trim((string) file_get_contents($fullPath));
            $this->bind('')->annotatedWith($sqlId)->toInstance($sql);
        }

        $this->bindInterceptor(
            $this->matcher->any(),
            $this->matcher->annotatedWith(Query::class),
            [QueryInterceptor::class]
        );
        // <=0.4.0
        /** @psalm-suppress DeprecatedClass */
        $this->bindInterceptor(
            $this->matcher->any(),
            $this->matcher->annotatedWith(AliasQuery::class),
            [SqlAliasInterceptor::class]
        );
    }

    private function getFullPathAsComment(SplFileInfo $fileInfo): string
    {
        if($this->debug){
           return '/* ' . $fileInfo->getPathname() . ' */ ';
        }

        return '';
    }

    protected function bindCallableItem(string $name, string $sqlId): void
    {
        $this->bind(RowInterface::class)->annotatedWith($name)->toConstructor(
            SqlQueryRow::class,
            "sql={$sqlId}"
        );
    }

    protected function bindCallableList(string $name, string $sqlId): void
    {
        $this->bind()->annotatedWith($name)->toConstructor(
            SqlQueryRowList::class,
            "sql={$sqlId}"
        );
        $this->bind(RowListInterface::class)->annotatedWith($name)->toConstructor(
            SqlQueryRowList::class,
            "sql={$sqlId}"
        );
    }

    /**
     * @psalm-suppress ArgumentTypeCoercion
     */
    private function files(string $dir): RegexIterator
    {
        return new RegexIterator(
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $dir,
                    FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::SKIP_DOTS
                ),
                RecursiveIteratorIterator::LEAVES_ONLY
            ),
            '/^.+\.sql$/',
            RecursiveRegexIterator::MATCH
        );
    }
}

```
