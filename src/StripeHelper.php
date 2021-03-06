<?php

namespace Mia\Stripe;

use Stripe\Exception\OAuth\InvalidGrantException;

class StripeHelper
{
    /**
     * 
     * @var string
     */
    protected $apiKey = '';
    /**
     * 
     * @var string
     */
    protected $publishableKey = '';
    /**
     * @var string
     */
    protected $webhookSecret = '';
    /**
     * 
     * @param string $access_token
     */
    public function __construct($apiKey, $publishableKey)
    {
        $this->apiKey = $apiKey;
        $this->publishableKey = $publishableKey;
        $this->initStripe();   
    }

    protected function initStripe()
    {
        \Stripe\Stripe::setApiKey($this->apiKey);
    }

    public function createExpressAccount()
    {
        return \Stripe\Account::create([
            'country' => 'CA',
            'type' => 'express',
        ]);
    }

    public function createAccountLink($accountId, $refreshUrl, $redirectUrl)
    {
        return \Stripe\AccountLink::create([
            'account' => $accountId,
            'refresh_url' => $refreshUrl,
            'return_url' => $redirectUrl,
            'type' => 'account_onboarding',
            // type =  account_update
          ]);
    }

    public function editCard($customerId, $cardId, $month, $year)
    {
        $stripe = new \Stripe\StripeClient($this->apiKey);
        return $stripe->paymentMethods->update($cardId, array(
            'card' => array(
                'exp_month' => $month,
                'exp_year' => $year
            )
        ))->toArray();
    }

    public function deleteCard($customerId, $cardId)
    {
        $stripe = new \Stripe\StripeClient($this->apiKey);
        $stripe->paymentMethods->detach($cardId);
    }

    public function getPaymentMethods($customerId)
    {
        $stripe = new \Stripe\StripeClient($this->apiKey);
        return $stripe->paymentMethods->all([
          'customer' => $customerId,
          'type' => 'card',
        ]);
    }

    public function getEphemeralKey($customerId, $apiVersion)
    {
        return \Stripe\EphemeralKey::create(
            ['customer' => $customerId],
            ['stripe_version' => $apiVersion]
          );
    }
    
    public function createCustomer($name, $email, $phone)
    {
        return \Stripe\Customer::create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone
        ]);
    }

    public function connect($code)
    {
        try {
            $stripeResponse = \Stripe\OAuth::token([
                'grant_type' => 'authorization_code',
                'code' => $code,
            ]);
        } catch (InvalidGrantException $e) {
            //return $response->withStatus(400)->withJson(array('error' => 'Invalid authorization code: ' . $code));
            return -2;
        } catch (\Exception $e) {
            //return $response->withStatus(500)->withJson(array('error' => 'An unknown error occurred.'));
            return -1;
        }
          
        return $stripeResponse->stripe_user_id;
    }

    public function createIntent($paymentMethodId, $amount, $isMobile = true)
    {
        return \Stripe\PaymentIntent::create([
            'amount' => $amount,
            'currency' => 'usd',
            "payment_method" => $paymentMethodId,
            "confirmation_method" => "manual",
            "confirm" => true,
            // If a mobile client passes `useStripeSdk`, set `use_stripe_sdk=true`
            // to take advantage of new authentication features in mobile SDKs
            "use_stripe_sdk" => $isMobile,
            // Verify your integration in this guide by including this parameter
            'metadata' => ['integration_check' => 'accept_a_payment'],
        ]);
    }

    public function createIntentWithAccount($paymentMethodId, $amount, $isMobile = true, $stripeAccout = '')
    {
        return \Stripe\PaymentIntent::create([
            'amount' => $amount,
            'currency' => 'usd',
            "payment_method" => $paymentMethodId,
            "confirmation_method" => "manual",
            "confirm" => true,
            // If a mobile client passes `useStripeSdk`, set `use_stripe_sdk=true`
            // to take advantage of new authentication features in mobile SDKs
            "use_stripe_sdk" => $isMobile,
            // Verify your integration in this guide by including this parameter
            'metadata' => ['integration_check' => 'accept_a_payment'],
        ], ['stripe_account' => $stripeAccout]);
    }

    public function createCheckoutSubscription($priceId, $successUrl, $cancelUrl)
    {
        return \Stripe\Checkout\Session::create([
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'payment_method_types' => ['card'],
            'mode' => 'subscription',
            'line_items' => [[
              'price' => $priceId,
              // For metered billing, do not pass quantity
              'quantity' => 1,
            ]],
        ]);
    }

    public function getCheckoutSession($sessionId)
    {
        return \Stripe\Checkout\Session::retrieve($sessionId);
    }

    public function createBillingPortal($stripeCustomerId, $returnUrl)
    {
        return \Stripe\BillingPortal\Session::create([
            'customer' => $stripeCustomerId,
            'return_url' => $returnUrl,
        ]);
    }
    
    public function retrieve($paymentIntentId)
    {
        return \Stripe\PaymentIntent::retrieve($paymentIntentId);
    }

    public function pay($amount, $customerId, $paymentMethodId)
    {
        try {
            return \Stripe\PaymentIntent::create([
                'amount' => $amount,
                'currency' => 'usd',
                'customer' => $customerId,
                'payment_method' => $paymentMethodId,
                'off_session' => true,
                'confirm' => true,
                'application_fee_amount' => 1
            ]);
        } catch (\Stripe\Exception\CardException $e) {
            //$log = new \Mobileia\Expressive\Database\Model\MIALog();
            //$log->data = $e->getError()->toArray();
            //$log->save();
            // Error code will be authentication_required if authentication is needed
            //echo 'Error code is:' . $e->getError()->code;
            //$payment_intent_id = $e->getError()->payment_intent->id;
            //$payment_intent = \Stripe\PaymentIntent::retrieve($payment_intent_id);
            
            return false;
        } catch (\Exception $e) {
            //$log = new \Mobileia\Expressive\Database\Model\MIALog();
            //$log->data = $e->getMessage();
            //$log->save();
            // Error code will be authentication_required if authentication is needed
            //echo 'Error code is:' . $e->getError()->code;
            //$payment_intent_id = $e->getError()->payment_intent->id;
            //$payment_intent = \Stripe\PaymentIntent::retrieve($payment_intent_id);
            
            return false;
        }
        
        return true;
    }

    public function setWebhookSecret($secret)
    {
        $this->webhookSecret = $secret;
    }

    public function getWebhookSecret()
    {
        return $this->webhookSecret;
    }
}