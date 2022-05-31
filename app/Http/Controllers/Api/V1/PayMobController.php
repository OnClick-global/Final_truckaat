<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use BaklySystems\PayMob\Facades\PayMob;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Fhistory;

class PayMobController extends Controller
{

    /**
     * Display checkout page.
     *
     * @param int $orderId
     * @return Response
     */
    public function checkingOut($integration_id, $iframe_id, $orderId, $user_id, $phone)
    {
        $order = config('paymob.order.model', 'App\Order')::find($orderId);
        # code... get order user.
        $auth = PayMob::authPaymob(); // login PayMob servers
        $user = User::find($user_id);
        if (property_exists($auth, 'detail')) { // login to PayMob attempt failed.
            # code... redirect to previous page with a message.
        }
        if (!is_null($order->paymob_id)) {
            $paymobOrder = $order->paymob_id;
        } else {
            $paymobOrders = PayMob::makeOrderPaymob( // make order on PayMob
                $auth->token,
                $auth->profile->id,
                $order->fund_amount * 100,
                $order->id . '_' . time()
            );
            $paymobOrder = $paymobOrders->id;
        }
        $order->update(['paymob_id' => $paymobOrder]); // save paymob order id for later usage.
        $payment_key = PayMob::getPaymentKeyPaymob( // get payment key
            $integration_id,
            $auth->token,
            $order->fund_amount * 100,
            $paymobOrder,
            // For billing data
            // For billing data
            ($user->email == null) ? 'test@test.com' : $user->email, // optional
            'Roiless', // optional
            $user->name, // optional
            $user->phone, // optional
            'cairo', // optional
            'egypt' // optional
        );
        if ($iframe_id == 'wallet') {
            $data = [
                "source" => ["identifier" => $phone, "subtype" => "WALLET"],
                "payment_token" => $payment_key->token,
            ];
            $request = HttpPost("acceptance/payments/pay", $data);
            if ($request) {
                if (isset($request->redirect_url)) {
                    return redirect($request->redirect_url);
                }
            }

        } else {
            return redirect('https://accept.paymob.com/api/acceptance/iframes/' . $iframe_id . '?payment_token=' . $payment_key->token);
        }
    }

    /**
     * Make payment on PayMob for API (mobile clients).
     * For PCI DSS Complaint Clients Only.
     *
     * @param \Illuminate\Http\Reuqest $request
     * @return Response
     */
    public function payAPI(Request $request)
    {
        $this->validate($request, [
            'orderId' => 'required|integer',
            'card_number' => 'required|numeric|digits:16',
            'card_holdername' => 'required|string|max:255',
            'card_expiry_mm' => 'required|integer|max:12',
            'card_expiry_yy' => 'required|integer',
            'card_cvn' => 'required|integer|digits:3',
        ]);

        $user = auth()->user();
        $order = config('paymob.order.model', 'App\Order')::findOrFail($request->orderId);
        $payment = PayMob::makePayment( // make transaction on Paymob servers.
            $payment_key_token,
            $request->card_number,
            $request->card_holdername,
            $request->card_expiry_mm,
            $request->card_expiry_yy,
            $request->card_cvn,
            $order->paymob_order_id,
            $user->firstname,
            $user->lastname,
            $user->email,
            $user->phone
        );

        # code...
    }

    /**
     * Transaction succeeded.
     *
     * @param object $order
     * @return void
     */
    protected function succeeded($order)
    {
        # code...
    }

    /**
     * Transaction voided.
     *
     * @param object $order
     * @return void
     */
    protected function voided($order)
    {
        # code...
    }

    /**
     * Transaction refunded.
     *
     * @param object $order
     * @return void
     */
    protected function refunded($order)
    {
        # code...
    }

    /**
     * Transaction failed.
     *
     * @param object $order
     * @return void
     */
    protected function failed($order)
    {
        # code...
    }

    /**
     * Processed callback from PayMob servers.
     * Save the route for this method in PayMob dashboard >> processed callback route.
     *
     * @param \Illumiante\Http\Request $request
     * @return  Response
     */
    public function processedCallback(Request $request)
    {
        $orderId = explode('_', $request['merchant_order_id']);
        $orderId = $orderId[0];
        $order = config('paymob.order.model', 'App\Order')::find($orderId);
        // Statuses.
        $isSuccess = filter_var($request['success'], FILTER_VALIDATE_BOOLEAN);
        $isVoided = filter_var($request['is_voided'], FILTER_VALIDATE_BOOLEAN);
        $isRefunded = filter_var($request['is_refunded'], FILTER_VALIDATE_BOOLEAN);
        //return dd($request->all());

        if ($isSuccess && !$isVoided && !$isRefunded) { // transcation succeeded.
            $order->user_status = 'payed_success';
            $order->payment = 'paid';
            $order->save();
            $history_data['user_fund_id'] = $order->id;
            $history_data['type'] = 'user';
            $history_data['show_in'] = 'web';
            $history_data['status'] = 'pending';
            $history_data['user_id'] = $order->user_id;
            $history_data['note_ar'] = 'تم دفع الرسوم';
            $history_data['note_en'] = 'Fund Cost Has Been Paid';
            Fhistory::create($history_data);
            $history_app_data['user_fund_id'] = $order->id;
            $history_app_data['type'] = 'user';
            $history_app_data['show_in'] = 'app';
            $history_app_data['status'] = 'pending';
            $history_app_data['user_id'] = $order->user_id;
            $history_app_data['note_ar'] = 'تم دفع الرسوم';
            $history_app_data['note_en'] = 'Fund Cost Has Been Paid';
            Fhistory::create($history_app_data);
            return redirect('/payment/success');
        } elseif ($isSuccess && $isVoided) { // transaction voided.
            return redirect('/payment/fail');
        } elseif ($isSuccess && $isRefunded) { // transaction refunded.
            return redirect('/payment/fail');
        } elseif (!$isSuccess) { // transaction failed.
            return redirect('/payment/fail');
        } else {
            return redirect('/payment/fail');
        }
        //return redirect('/payment/fail');
        //return response()->json(['success' => true], 200);
    }

    /**
     * Display invoice page (PayMob response callback).
     * Save the route for this method to PayMob dashboard >> response callback route.
     *
     * @param \Illuminate\Http\Request $request
     * @return Response
     */
    public function invoice(Request $request)
    {
        # code...
    }

}
