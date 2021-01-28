<?php

/**
 * @see       https://github.com/mezzio/mezzio-authorization for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-authorization/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-authorization/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mia\Stripe;

use Mia\Stripe\Factory\PaymentHandlerFactory;
use Mia\Stripe\Factory\StripeHelperFactory;
use Mia\Stripe\Handler\PaymentHandler;

class ConfigProvider
{
    /**
     * Return the configuration array.
     */
    public function __invoke() : array
    {
        return [
            'dependencies'  => $this->getDependencies()
        ];
    }

    /**
     * Returns the container dependencies
     */
    public function getDependencies() : array
    {
        return [
            'factories' => [
                StripeHelper::class => StripeHelperFactory::class,
                PaymentHandler::class => PaymentHandlerFactory::class,
            ],
        ];
    }
}