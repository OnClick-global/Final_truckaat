<?php

namespace App\Http\Controllers\Api\V1;

use App\CentralLogics\Helpers;
use App\CentralLogics\CouponLogic;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\RegisterCoupon;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RegisterCouponController extends Controller
{
    public function list()
    {
        try {
            $coupon = RegisterCoupon::active()->get();
            return response()->json($coupon, 200);
        } catch (\Exception $e) {
            return response()->json(['errors' => $e], 403);
        }
    }

    public function apply(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'code' => 'required',
        ]);

        if ($validator->errors()->count() > 0) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        try {

            $coupon = RegisterCoupon::active()->where(['code' => $request['code']])->first();

            if (isset($coupon)) {
                $staus = CouponLogic::is_reg_valide($coupon);

                switch ($staus) {
                    case 407:
                        return response()->json([
                            'errors' => [
                                ['code' => 'coupon', 'message' => trans('messages.coupon_expire')]
                            ]
                        ], 407);

                    case 100:
                        return response()->json([

                            'message' => trans('messages.coupon_available'),
                            'code' => $coupon['code'],
                            'type' => $coupon['discount_type'],

                        ], 200);

                    default:
                        return response()->json([
                            'errors' => [
                                ['code' => 'coupon', 'message' => trans('messages.not_found')]
                            ]
                        ], 404);
                }
            } else {
                return response()->json([
                    'errors' => [
                        ['code' => 'coupon', 'message' => trans('messages.not_found')]
                    ]
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['errors' => $e], 403);
        }
    }
}
