<?php 

declare(strict_types=1);

namespace Mia\Stripe\Factory;

use Mia\Stripe\StripeHelper;
use Psr\Container\ContainerInterface;

class StripeHelperFactory 
{
    public function __invoke(ContainerInterface $container) : StripeHelper
    {
        // Obtenemos configuracion
        $config = $container->get('config')['stripe'];
        // creamos libreria
        return new StripeHelper($config['api_key'], $config['publishable_key']);
    }
}