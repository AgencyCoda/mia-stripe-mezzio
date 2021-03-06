<?php

namespace Mia\Stripe\Handler;

use Mia\Core\Diactoros\MiaJsonErrorResponse;
use Mia\Stripe\StripeHelper;

/**
 * Description of PayHandler
 *
 * @author matiascamiletti
 */
abstract class WebhookHandler extends \Mia\Auth\Request\MiaAuthRequestHandler
{
    /**
     * @var StripeHelper
     */
    protected $service;

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
        $this->configWebhook($request);

        try {
            $event = \Stripe\Webhook::constructEvent(
                $request->getBody(),
                $request->getHeaderLine('stripe-signature'),
                $this->service->getWebhookSecret()
            );
        } catch (\Exception $e) {
            return new \Laminas\Diactoros\Response\JsonResponse([
                'message' => 'Problem with webhook: ' . $e->getMessage(),
                'headers' => $request->getHeaders(),
                'all_params' => $this->getAllParam($request)
            ]);
            //return new MiaJsonErrorResponse(-3, 'Problem with webhook: ' . $e->getMessage());
        }

        return $this->processEventStripe($event['type'], $event['data']['object']);

        /*switch ($type) {
        case 'checkout.session.completed':
            // Payment is successful and the subscription is created.
            // You should provision the subscription.
            break;
        case 'invoice.paid':
            // Continue to provision the subscription as payments continue to be made.
            // Store the status in your database and check when a user accesses your service.
            // This approach helps you avoid hitting rate limits.
            break;
        case 'invoice.payment_failed':
            // The payment failed or the customer does not have a valid payment method.
            // The subscription becomes past_due. Notify your customer and send them to the
            // customer portal to update their payment information.
            break;
        // ... handle other event types
        default:
            // Unhandled event type
        }*/
    }

    abstract protected function processEventStripe($type, $object): \Psr\Http\Message\ResponseInterface;

    abstract protected function configWebhook(\Psr\Http\Message\ServerRequestInterface $request);
}
