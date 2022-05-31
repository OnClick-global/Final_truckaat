<?php

namespace App\Http\Controllers;

use App\Models\BusinessSetting;
use App\Models\Order;
use App\Models\RegisterCoupon;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function payment(Request $request)
    {
        if ($request->has('callback')) {
            Order::where(['id' => $request->order_id])->update(['callback' => $request['callback']]);
        }
        session()->put('resturant_id', $request['resturant_id']);
        session()->put('code', $request->code);

        $resturant = Restaurant::findOrFail($request['resturant_id']);

//        show price after coupone
        $annual_subscription = BusinessSetting::where('key', 'Annual_subscription')->first()->value;
        if ($request->code) {
            $exists_coupon = RegisterCoupon::where('code', $request->code)->where('status', 1)->where('limit', '>', 0)->first();
            if ($exists_coupon) {
                $data['done'] = true;

                if ($exists_coupon->discount_type == 'percent') {
                    $discount_persentage = $exists_coupon->discount / 100;
                    $discount = $discount_persentage * $annual_subscription;
                    $finat_price = $annual_subscription - $discount;
//                $data['old_price'] = $annual_subscription;
//                $data['discount'] = $discount;
//                $data['new_price'] = $finat_price;
                    session()->put('new_price', $finat_price);
                } else {
                    $finat_price = $annual_subscription - $exists_coupon->discount;
//                $data['old_price'] = $annual_subscription;
//                $data['discount'] = $exists_coupon->discount;
                    $new_price = ($finat_price < 0) ? 0 : $finat_price;
                    session()->put('new_price', $new_price);
                }
            } else {
                return response()->json(['errors' => ['code' => 'order-payment', 'message' => 'you should choose valid coupon']], 403);
            }

        } else {
            session()->put('new_price', $annual_subscription);
        }
        if (isset($resturant)) {
            $data = [
                'name' => $resturant->vendor->f_name,
                'email' => $resturant->vendor->email,
                'phone' => $resturant->vendor->phone,
            ];
            session()->put('data', $data);
            return view('payment-view');
        }

        return response()->json(['errors' => ['code' => 'order-payment', 'message' => 'Data not found']], 403);
    }

    public function success()
    {
//        $order = Order::where(['id' => session('order_id'), 'user_id' => session('customer_id')])->first();
//        if ($order->callback != null) {
//            return redirect($order->callback . '&status=success');
//        }
        return response()->json(['message' => 'Payment succeeded'], 200);
    }

    public function fail()
    {
//        $order = Order::where(['id' => session('order_id'), 'user_id' => session('customer_id')])->first();
//        if ($order->callback != null) {
//            return redirect($order->callback . '&status=fail');
//        }
        return response()->json(['message' => 'Payment failed'], 403);
    }

    public function DoPayment($id, $user_id)
    {
        $order = User_fund::find($id);
        $user = User::find($user_id);
        return view('payment.paymentMethods', compact('order', 'user'));
    }

    public function payway(Request $request, $payway = 'visa', $code, $resturant_id, $new_price)
    {
        $order = User_fund::find($resturant_id);
        if ($order->payment == 'not paid') {
            if ($payway == 'visa') {
                $paymob = new PayMobController;
                return $paymob->checkingOut(env('PAYMOB_VISA_ID'), env('PAYMOB_VISA_IFRAME_ID'), $order->id, $resturant_id, $request->phone);
            } elseif ($payway == 'wallet') {
                $paymob = new PayMobController;
                return $paymob->checkingOut(env('PAYMOB_WALLET_ID'), 'wallet', $order->id, $resturant_id, $request->phone);
            }
        } else {
            return redirect('payment-fail');
        }
    }

    public function show_phone_page($payway = 'visa', $id, $user_id)
    {
        return view('payment.phone_page', compact('payway', 'id', 'user_id'));
    }
}
