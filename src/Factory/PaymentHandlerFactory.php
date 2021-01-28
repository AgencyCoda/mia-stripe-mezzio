<?php

declare(strict_types=1);

namespace Mia\Stripe\Factory;

use Mia\Stripe\Handler\PaymentHandler;
use Mia\Stripe\StripeHelper;
use Psr\Container\ContainerInterface;

/**
 * Description of PaymentHandlerFactory
 *
 * @author matiascamiletti
 */
class PaymentHandlerFactory 
{
    public function __invoke(ContainerInterface $container) : PaymentHandler
    {
        // Creamos servicio
        $service   = $container->get(StripeHelper::class);
        // Generamos el handler
        return new PaymentHandler($service);
    }
}