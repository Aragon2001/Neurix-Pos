<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Stripe_payments — modelo de pagos con Stripe SDK v13.
 * SDK v13 usa instancia StripeClient en vez de métodos estáticos (v7).
 */
class Stripe_payments extends CI_Model
{
    protected $private_key;
    public    $message = '';
    public    $code;
    public    $error   = false;

    /** @var \Stripe\StripeClient */
    protected $stripe;

    public function __construct()
    {
        parent::__construct();
        $this->private_key = $this->Settings->stripe_secret_key;
        $this->stripe = new \Stripe\StripeClient($this->private_key);
    }

    public function init(array $config = [])
    {
        if (isset($config['private_key'])) {
            $this->private_key = $config['private_key'];
            $this->stripe      = new \Stripe\StripeClient($this->private_key);
        }
    }

    public function get_balance()
    {
        try {
            $bal = $this->stripe->balance->retrieve();
            return [
                'mode'               => $bal->livemode ? 'Live' : 'Test',
                'pending_amount'     => $bal->pending[0]->amount   / 100,
                'pending_currency'   => strtoupper($bal->pending[0]->currency),
                'available_amount'   => $bal->available[0]->amount / 100,
                'available_currency' => strtoupper($bal->available[0]->currency),
            ];
        } catch (\Exception $e) {
            return $this->_error($e);
        }
    }

    public function insert($token, $description, $amount, $currency)
    {
        try {
            $charge = $this->stripe->charges->create([
                'amount'      => (int)$amount,
                'currency'    => $currency,
                'source'      => $token,
                'description' => $description,
            ]);
            return $charge;
        } catch (\Exception $e) {
            return $this->_error($e);
        }
    }

    public function charge($token, $description, $amount, $currency)
    {
        return $this->insert($token, $description, $amount, $currency);
    }

    public function get_transaction($transaction_id)
    {
        try {
            return $this->stripe->charges->retrieve($transaction_id);
        } catch (\Exception $e) {
            $this->_error($e);
            return false;
        }
    }

    public function get_all_transactions($limit = 100, $offset = 0)
    {
        try {
            $ch  = $this->stripe->charges->all(['limit' => min($limit, 100)]);
            $raw = [];
            foreach ($ch->data as $record) {
                $raw[] = $this->charge_to_array($record);
            }
            return ['error' => false, 'data' => $raw];
        } catch (\Exception $e) {
            $this->_error($e);
            return false;
        }
    }

    public function refund($transaction_id, $amount = 'all')
    {
        try {
            $params = ['charge' => $transaction_id];
            if ($amount !== 'all') {
                $params['amount'] = (int)$amount;
            }
            return $this->stripe->refunds->create($params);
        } catch (\Exception $e) {
            $this->_error($e);
            return false;
        }
    }

    public function insert_many(array $data)
    {
        return array_map(fn($r) => $this->insert($r['token'], $r['description'], $r['amount'], $r['currency']), $data);
    }

    public function count_all_transactions()
    {
        $charges = $this->get_all_transactions();
        return is_array($charges) ? count($charges['data'] ?? []) : 0;
    }

    public function get_limit($limit, $offset = 0)
    {
        return $this->get_all_transactions($limit, $offset);
    }

    public function charge_to_array($charge)
    {
        $card = $charge->payment_method_details->card ?? null;
        return [
            'id'              => $charge->id,
            'invoice'         => $charge->invoice,
            'card'            => $card ? $this->card_to_array($charge) : [],
            'livemode'        => $charge->livemode,
            'amount'          => $charge->amount,
            'failure_message' => $charge->failure_message,
            'currency'        => $charge->currency,
            'paid'            => $charge->paid,
            'description'     => $charge->description,
            'object'          => $charge->object,
            'refunded'        => $charge->refunded,
            'created'         => date('Y-m-d H:i:s', $charge->created),
            'customer'        => $charge->customer,
            'amount_refunded' => $charge->amount_refunded,
        ];
    }

    public function card_to_array($charge)
    {
        $card = $charge->payment_method_details->card ?? (object)[];
        return [
            'last4'     => $card->last4     ?? '',
            'exp_month' => $card->exp_month ?? '',
            'exp_year'  => $card->exp_year  ?? '',
            'country'   => $card->country   ?? '',
            'brand'     => $card->brand     ?? '',
        ];
    }

    private function _error(\Exception $e)
    {
        $this->error   = true;
        $this->message = $e->getMessage();
        $this->code    = $e->getCode();
        log_message('error', '[Stripe] ' . $e->getMessage());
        return ['error' => true, 'code' => $this->code, 'message' => $this->message];
    }
}
