<?php

namespace Mia\Stripe\Handler;

use Mia\Stripe\StripeHelper;

/**
 * Description of PayHandler
 *
 * @author matiascamiletti
 */
abstract class PaymentHandler extends \Mia\Auth\Request\MiaAuthRequestHandler
{
    /**
     * @var StripeHelper
     */
    private $service;

    public function __construct(StripeHelper $stripe) {
        $this->service = $stripe;
    }
    /**
     * 
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface 
    {
        // Obtener usuario
        $user = $this->getUser($request);
        // Obtener Payment method ID
        $paymentMethodId = $this->getParam($request, 'payment_method_id', null);
        $paymentIntentId = $this->getParam($request, 'payment_intent_id', null);
        // Procesar parametros
        if($paymentMethodId != null){
            // Creamos Intent
            $intent = $this->service->createIntent($paymentMethodId, $this->getAmount($request) * 100);
            return new \Mia\Core\Diactoros\MiaJsonResponse($this->processIntent($request, $intent));
        } else if ($paymentIntentId != null) {
            $intent = $this->service->retrieve($paymentIntentId);
            $intent->confirm();
            
            return new \Mia\Core\Diactoros\MiaJsonResponse($this->processIntent($request, $intent));
        }
        // Si llego aca hubo un error
        return new \Mia\Core\Diactoros\MiaJsonErrorResponse(123, 'Problem with stripe');
    }

    abstract protected function getAmount(\Psr\Http\Message\ServerRequestInterface $request): float;

    abstract protected function paySucess(\Psr\Http\Message\ServerRequestInterface $request, \Stripe\PaymentIntent $intent);

    protected function processIntent(\Psr\Http\Message\ServerRequestInterface $request, \Stripe\PaymentIntent $intent)
    {
        switch ($intent->status) {
            case "requires_action":
            case "requires_source_action":
              // Card requires authentication
              return [
                'requiresAction'=> true,
                'paymentIntentId'=> $intent->id,
                'clientSecret'=> $intent->client_secret
              ];
            case "requires_payment_method":
            case "requires_source":
              // Card was not properly authenticated, suggest a new payment method
              return [
                'error' => "Your card was denied, please provide a new payment method"
              ];
            case "succeeded":
              // Payment is complete, authentication not required
              $this->paySucess($request ,$intent);
              // To cancel the payment after capture you will need to issue a Refund (https://stripe.com/docs/api/refunds)
              return ['clientSecret' => $intent->client_secret];
          }
    }
}
