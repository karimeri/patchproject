<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Docker;

use Magento\MagentoCloud\App\ContainerInterface;
use Magento\MagentoCloud\Docker\Compose;

/**
 * Factory class for Docker builder.
 *
 * @codeCoverageIgnore
 */
class ComposeFactory
{
    const COMPOSE_DEVELOPER = 'developer';
    const COMPOSE_PRODUCTION = 'production';
    const COMPOSE_FUNCTIONAL = 'functional';

    /**
     * @var array
     */
    private static $map = [
        self::COMPOSE_DEVELOPER => Compose\DeveloperCompose::class,
        self::COMPOSE_PRODUCTION => Compose\ProductionCompose::class,
        /** Internal CI configurations. */
        self::COMPOSE_FUNCTIONAL => Compose\FunctionalCompose::class
    ];

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $strategy
     * @return ComposeInterface
     * @throws ConfigurationMismatchException
     */
    public function create(string $strategy): ComposeInterface
    {
        if (!array_key_exists($strategy, self::$map)) {
            throw new ConfigurationMismatchException(
                sprintf('Wrong strategy "%s" passed', $strategy)
            );
        }

        return $this->container->create(
            self::$map[$strategy]
        );
    }
}
