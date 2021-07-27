<?php

namespace Mia\Stripe\Factory;

use Mia\Stripe\StripeHelper;
use Psr\Container\ContainerInterface;

class StripeInitFactory
{
    public function __invoke(ContainerInterface $container, $requestName)
    {
        // Get service
        $service = $container->get(StripeHelper::class);
        // Generate class
        return new $requestName($service);
    }
}