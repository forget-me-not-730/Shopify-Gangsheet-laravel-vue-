<?php

namespace App\Http\Controllers\Merchant;

use App\Events\CreditAdded;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WebhookLog;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use ProtoneMedia\LaravelQueryBuilderInertiaJs\InertiaTable;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Stripe\Webhook;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
use App\Mail\ChargeUpEmail;
use Illuminate\Support\Facades\Mail;

class PaymentController extends Controller
{
    function index(Request $request)
    {
        $user = $request->user();
        $globalSearch = AllowedFilter::callback('global', function ($query, $value) {
            $query->where(function ($query) use ($value) {
                Collection::wrap($value)->each(function ($value) use ($query) {
                    $query
                        ->orWhere('transaction_id', 'LIKE', "%{$value}%")
                        ->orWhere('amount', 'LIKE', "%{$value}%")
                        ->orWhere('status', 'LIKE', "%{$value}%")
                        ->orWhere('created_at', 'LIKE', "%{$value}%");
                });
            });
        });

        $perPage = $request->input('perPage') ?? 10;

        $transactions = QueryBuilder::for(Transaction::class)
            ->allowedFields(['id', 'transaction_id', 'amount', 'status', 'created_at'])
            ->allowedSorts(['id', 'transaction_id', 'amount', 'status', 'created_at'])
            ->allowedFilters(['id', 'transaction_id', 'amount', 'status', 'created_at', $globalSearch])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return inertia('Merchant/Payments', [
            'transactions' => $transactions,
        ])->table(function (InertiaTable $table) {
            $table
                ->withGlobalSearch()
                ->defaultSort('-id')
                ->perPageOptions([10, 25, 50, 100])
                ->column(key: 'id', label: "ID", sortable: true, searchable: true)
                ->column(key: 'transaction_id', label: "Transaction ID", sortable: true, searchable: true)
                ->column(key: 'amount', label: "Amount", sortable: true, searchable: true)
                ->column(key: 'status', label: "Status")
                ->column(key: 'created_at', label: "Created At", sortable: true, searchable: true);
        });
    }

    function addCredits(Request $request)
    {
        $data = $this->validate($request, [
            'amount' => 'required|numeric|min_digits:1',
            'auto_charge_enabled' => 'nullable|boolean',
            'auto_min_credits' => 'required',
        ]);

        $user = $request->user();
        $user->setMetaData([
            'auto_charge_enabled' => $data['auto_charge_enabled'],
            'auto_charge_amount' => $data['amount'],
            'auto_min_credits' => $data['auto_min_credits']
        ]);

        $url = $user->getStripeCheckoutUrl(
            $data['amount'],
            route('merchant.payment.success'),
            route('merchant.payment.index')
        );

        return Inertia::location($url);
    }

    public function paymentSuccess(Request $request)
    {
        $user = $request->user();

        $sessionId = $request->query('session_id');

        if (!$sessionId) {
            return redirect()->route('merchant.payment.index')->with('error', 'No session ID found.');
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        $session = Session::retrieve($sessionId);
        $customerEmail = $session->customer_details->email;
        $customerName = $session->customer_details->name;
        $customer = Customer::create([
            'email' => $customerEmail,
            'name' => $customerName,
        ]);
        $paymentIntent = PaymentIntent::retrieve($session->payment_intent);
        $paymentMethodId = $paymentIntent->payment_method;

        $paymentMethod = PaymentMethod::retrieve($paymentMethodId);
        $paymentMethod->attach(['customer' => $customer->id]);

        $user->setMetaData([
            'stripe_payment_method' => $paymentMethodId,
            'stripe_customer_id' => $customer->id
        ]);

        return redirect()->route('merchant.payment.index')->with('message', 'Payment succeeded!');
    }

    public function getCredits(Request $request)
    {
        $user = $request->user();
        $latestTransaction = Transaction::where('user_id', $user->id)->latest()->first();
        $response = ['credits' => $user->credits];

        if ($latestTransaction) {
            $response['transaction_amount'] = $latestTransaction->amount;
        }

        return response()->json($response);
    }

    public function getCreditsNotification(Request $request)
    {
        $user = $request->user();
        $notifcation = $user->getMetaData('creditNotification', null);
        return response()->json(['message' => $notifcation]);
    }

    function webhook(Request $request)
    {
        $signature = $request->header('stripe-signature');
        $event = Webhook::constructEvent($request->getContent(), $signature, config('services.stripe.webhook_secret'));

        try {
            $object = $event->data->object;

            $webHookLog = WebhookLog::create([
                'type' => $event->type,
                'data' => $object
            ]);

            switch ($event->type) {
                case 'checkout.session.completed':
                    $transaction_id = $object->payment_intent;
                    $amount = $object->amount_subtotal / 100;
                    $user_id = $object->metadata['user_id'];
                    break;
                case 'payment_intent.succeeded':
                    $transaction_id = $object->id;
                    $amount = $object->amount_received / 100;
                    $user_id = $object->metadata['user_id'];
                    break;
                default:
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Webhook received'
                    ]);
            }

            if (empty($transaction_id)) {
                throw new \Exception('Transaction ID not found');
            }

            if ($amount <= 0) {
                throw new \Exception('Invalid amount');
            }

            if (empty($user_id)) {
                throw new \Exception('User ID not found');
            }

            if (Transaction::where('transaction_id', $transaction_id)->exists()) {
                throw new \Exception('Transaction already exists');
            }

            $transaction = Transaction::create([
                'user_id' => $user_id,
                'amount' => $amount,
                'status' => 'paid',
                'transaction_id' => $transaction_id
            ]);

            $webHookLog->handler_id = $transaction->id;
            $webHookLog->save();

            $user = User::findOrFail($user_id);
            $user->increment('credits', $amount);

            event(new CreditAdded($transaction));

            $user->removeMetaData('creditNotification');
            Mail::to($user->email)->send(new ChargeUpEmail($user, $amount));

        } catch (\Exception $e) {
            report($e);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Webhook handled'
        ]);
    }
}
