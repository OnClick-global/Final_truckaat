<?php

namespace App\Http\Controllers;

use App\CentralLogics\Helpers;
use App\Models\Order;
use App\Models\RegisterCoupon;
use App\Models\Restaurant;
use App\Models\Vendor;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class MyfatoorahController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth');
    }

//restaurant register payment
    public function paywith(Request $request)
    {
        $resturant = Restaurant::findOrFail(session('resturant_id'));
        $price = session('new_price');
        $root_url = $request->root();
        $path = 'https://api.myfatoorah.com/v2/SendPayment';
        $token ='bearer 4OS8zrCmq9CsqWiXkpjiZ7SVH-b8Ral3_Ty1j5JL6AwBMiK75ku0LDa4ZAf0hRIJsCK5qJQyJlkMrc1m5UzugnzVCFNCfrXr5xmwzT4ERhgXZqTe-aM9_zrAL7_lvMkFDqR5sstMOsqKW4Q0qKUQSX2nDiDpuK9dhAjWxE2cTazDANb7f5ZK3lAs3Fb9oODP7hW24huAZZ3u4kB36I5MslrSKL5zGdS15yG2E8tw02PKXI7VSL2c1o0jLsNtiiRcK5aIuQJAD6nd-9g3mjhc2T35LnFf5uonSz7RKbpDVwwVlCrPvEn7-986Pn3QotYlK-JJzyhmyYUIgEQnXIUmJDa2NVuxHFAFh80IUEjP0BsrqNacdD3nxW4bgKrxzdKHdtidbTFj2ITwT4I_kY9NNhdgWZFDnK5pWnOaBFMGNaPcXWOSJCWhU1yuADRy6qcbXCXTvKRM1A6YbzZBODrmz5nywepK2zEQhbSFsP_U2KOI-2IrZ3qMR9ZLk3WyoM9dCGXd0KXj4Vz72T4PS9p8Z9xcLcOpH2fq3S0yFW3GqRxNJKW5O25G58M7ZrsLxOo6ZyPPxDWypwd_wVaV1y-VatnkcvYlmLlQAZtedJ4pu1192T-ADkyPb7ITTPT5IpGNTMfRykxw8Ex0xXCaqFJln7X6f-XbYe5H2KvOw2IaCwOD5pQWeIsBbfWY2BBtTq2bnXvOxA';
        $headers = array(
            'Authorization:' . $token,
            'Content-Type:application/json'
        );
        if (session('code')) {
            $code = session('code');
        } else {
            $code = '0';
        }
        $call_back_url = $root_url . "/myfatoorah-oncomplate?resturant_id=" . $resturant->id . '&code=' . $code;
        $error_url = $root_url . "/payment-fail";
        $fields = array(
            "CustomerName" => $resturant->vendor->f_name,
            "NotificationOption" => "LNK",
            "InvoiceValue" => $price,
            "CallBackUrl" => $call_back_url,
            "ErrorUrl" => $error_url,
            "Language" => "AR",
            "CustomerEmail" => $resturant->vendor->email
        );
        $payload = json_encode($fields);
        $curl_session = curl_init();
        curl_setopt($curl_session, CURLOPT_URL, $path);
        curl_setopt($curl_session, CURLOPT_POST, true);
        curl_setopt($curl_session, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_session, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl_session, CURLOPT_IPRESOLVE, CURLOPT_IPRESOLVE);
        curl_setopt($curl_session, CURLOPT_POSTFIELDS, $payload);
        $result = curl_exec($curl_session);
        curl_close($curl_session);
        $result = json_decode($result);
        if ($result) {
            return redirect()->to($result->Data->InvoiceURL);
        } else {
            print_r($request["errors"]);
        }
    }

    public function oncomplate(Request $request)
    {
        if ($request->code) {
            $exists_coupon = RegisterCoupon::where('code', $request->code)->where('status', 1)->where('limit', '>', 0)->first();
            if ($exists_coupon) {
                $exists_coupon->total_uses = $exists_coupon->total_uses + 1;
                $exists_coupon->limit = $exists_coupon->limit - 1;
                $exists_coupon->save();
            }
        }
        $data['payment_status'] = 'paid';
        $data['status'] = 1;
        $updated_after_payment = Restaurant::where('id', $request->resturant_id)
            ->update($data);
        $resturant = Restaurant::where('id', $request->resturant_id)->first();
        $vendor = Vendor::findOrFail($resturant->vendor_id);
        $vendor->status = 1;
        $vendor->save();
        if ($updated_after_payment) {
            return \redirect()->route('payment-success');
        } else {
            return \redirect()->route('payment-fail');
        }
    }

//main app cart payment
    public function paywith_cart(Request $request)
    {
        $order = Order::findOrFail(session('order_id'));
        $price = $order->order_amount;
        $root_url = $request->root();
        $path = 'https://api.myfatoorah.com/v2/SendPayment';
        $token = 'bearer 4OS8zrCmq9CsqWiXkpjiZ7SVH-b8Ral3_Ty1j5JL6AwBMiK75ku0LDa4ZAf0hRIJsCK5qJQyJlkMrc1m5UzugnzVCFNCfrXr5xmwzT4ERhgXZqTe-aM9_zrAL7_lvMkFDqR5sstMOsqKW4Q0qKUQSX2nDiDpuK9dhAjWxE2cTazDANb7f5ZK3lAs3Fb9oODP7hW24huAZZ3u4kB36I5MslrSKL5zGdS15yG2E8tw02PKXI7VSL2c1o0jLsNtiiRcK5aIuQJAD6nd-9g3mjhc2T35LnFf5uonSz7RKbpDVwwVlCrPvEn7-986Pn3QotYlK-JJzyhmyYUIgEQnXIUmJDa2NVuxHFAFh80IUEjP0BsrqNacdD3nxW4bgKrxzdKHdtidbTFj2ITwT4I_kY9NNhdgWZFDnK5pWnOaBFMGNaPcXWOSJCWhU1yuADRy6qcbXCXTvKRM1A6YbzZBODrmz5nywepK2zEQhbSFsP_U2KOI-2IrZ3qMR9ZLk3WyoM9dCGXd0KXj4Vz72T4PS9p8Z9xcLcOpH2fq3S0yFW3GqRxNJKW5O25G58M7ZrsLxOo6ZyPPxDWypwd_wVaV1y-VatnkcvYlmLlQAZtedJ4pu1192T-ADkyPb7ITTPT5IpGNTMfRykxw8Ex0xXCaqFJln7X6f-XbYe5H2KvOw2IaCwOD5pQWeIsBbfWY2BBtTq2bnXvOxA';
        $headers = array(
            'Authorization:' . $token,
            'Content-Type:application/json'
        );
        $call_back_url = $root_url . "/myfatoorah-cart-oncomplete?order_id=" . $order->id . '&customer_id=' . session('customer_id');
        $error_url = $root_url . "/payment-fail";
        $fields = array(
            "CustomerName" => $order->customer->f_name,
            "NotificationOption" => "LNK",
            "InvoiceValue" => $price,
            "CallBackUrl" => $call_back_url,
            "ErrorUrl" => $error_url,
            "Language" => "AR",
            "CustomerEmail" => $order->customer->email
        );
        $payload = json_encode($fields);
        $curl_session = curl_init();
        curl_setopt($curl_session, CURLOPT_URL, $path);
        curl_setopt($curl_session, CURLOPT_POST, true);
        curl_setopt($curl_session, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_session, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl_session, CURLOPT_IPRESOLVE, CURLOPT_IPRESOLVE);
        curl_setopt($curl_session, CURLOPT_POSTFIELDS, $payload);
        $result = curl_exec($curl_session);
        curl_close($curl_session);
        $result = json_decode($result);
        if ($result) {
            return redirect()->to($result->Data->InvoiceURL);
        } else {
            print_r($request["errors"]);
        }
    }

    public function oncomplete_cart(Request $request)
    {
        $data['order_status'] = 'pending';
        $data['payment_status'] = 'paid';
        $updated_after_payment = Order::where('id', $request->order_id)->update($data);
        if ($updated_after_payment) {
            return \redirect()->route('payment-success');
        } else {
            return \redirect()->route('payment-fail');
        }
    }

    public function error(Request $request)
    {
        return dd($request);
    }
//     public function getPaymentStatus(Request $request)
//     {
//         if($request->status == "paid"){
//             DB::table('orders')
//                 ->where('transaction_reference', $request->id)
//                 ->update(['order_status' => 'confirmed', 'payment_status' => 'paid', 'transaction_reference' => $request->id]);
//             $order = Order::where('transaction_reference', $request->id)->first();
//             if ($order->callback != null) {
//                 return redirect($order->callback . '/success');
//             }else{
//                 return \redirect()->route('payment-success');
//             }
//         }
//         $order = Order::where('transaction_reference', $payment_id)->first();
//         if ($order->callback != null) {
//             return redirect($order->callback . '/fail');
//         }else{
//             return \redirect()->route('payment-fail');
//         }
//     }
//     public function oncomplate(Request $request,Order $order)
//     {
//         DB::table('orders')
//         ->where('id', $order->id)
//         ->update([
//             'transaction_reference' => $request->id,
//             'payment_method' => 'paypal',
//             'order_status' => 'failed',
//             'updated_at' => now()
//         ]);
//     }
}
