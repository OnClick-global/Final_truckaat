<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Banner;
use App\Models\Campaign;
use App\CentralLogics\BannerLogic;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function __construct()
    {
        //duration
        $ended_duration_banner = Banner::where('ads_type', 'duration')->where('end_date', '<', Carbon::now())->get();
        if (count($ended_duration_banner) > 0) {
            $data['ad_show'] = 0;
            Banner::where('ads_type', 'duration')->where('end_date', '<', Carbon::now())->update($data);
        }
        //click
        $ended_click_banner = Banner::where('ads_type', 'clicks')->where('type_count' ,0)->get();
        if (count($ended_click_banner) > 0) {
            $data['ad_show'] = 0;
            Banner::where('ads_type', 'clicks')->where('type_count',0)->update($data);
        }
        //view
        $ended_views_banner = Banner::where('ads_type', 'views')->where('type_count' ,0)->get();
        if (count($ended_views_banner) > 0) {
            $data['ad_show'] = 0;
            Banner::where('ads_type', 'views')->where('type_count' ,0)->update($data);
        }
    }
    public function get_banners(Request $request)
    {
        if (!$request->hasHeader('zoneId')) {
            $errors = [];
            array_push($errors, ['code' => 'zoneId', 'message' => trans('messages.zone_id_required')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }
        $zone_id= $request->header('zoneId');
        $banners = BannerLogic::get_banners($zone_id);
        $campaigns = Campaign::whereHas('restaurants', function($query)use($zone_id){
            $query->where('zone_id', $zone_id);
        })->running()->active()->get();
        try {
            return response()->json(['campaigns'=>$campaigns,'banners'=>$banners], 200);
        } catch (\Exception $e) {
            return response()->json([], 200);
        }
    }


    public function useonce(Request $request,$id)
    {
        $banner = Banner::find($id);
        if ($banner->type_count > 0){
            $banner->decrement('type_count', 1);
            return response()->json(['msg'=>'one click count'], 200);
        }else
            return response()->json(['msg'=>'ad is finished'], 200);
    }


}
