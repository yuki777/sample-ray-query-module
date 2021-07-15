<?php

declare(strict_types=1);

namespace MyVendor\Weekday\Resource\Page;

use BEAR\Resource\ResourceObject;
use Ray\IdentityValueModule\NowInterface;
use Ray\Di\Di\Inject;
use Ray\Di\Di\Named;

class Index extends ResourceObject
{
    /** @var array{greeting: string} */
    public $body;

    /** @var callable */
    private $selectOne;

    /**
     * @Inject
     * @Named("selectOne=selectOne")
     */
    public function __construct(callable $selectOne)
    {
        $this->selectOne = $selectOne;
    }

    /** @return static */
    public function onGet(string $name = 'BEAR.Sunday')
    {
        $this->body = [
            'greeting' => 'Hello ' . $name,
            'one' => ($this->selectOne)([]),
        ];

        return $this;
    }
}
