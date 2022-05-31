<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Banner;
use App\Models\Restaurant;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use App\CentralLogics\Helpers;

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
    function index()
    {
    
        $banners = Banner::latest()->paginate(config('default_pagination'));
        return view('admin-views.banner.index', compact('banners'));
    }

    public function store(Request $request)
    {
//        dd($request->all());
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'image' => 'required',
            'banner_type' => 'required',
            'ads_type' => 'required',
            'zone_id' => 'required',
            'restaurant_id' => 'required_if:ads_type,restaurant_wise',
            'item_id' => 'required_if:banner_type,item_wise',
            'type_count' => 'required_if:ads_type,clicks,views',
            'start_date' => 'required_if:ads_type,duration',
            'end_date' => 'required_if:ads_type,duration',
        ], [
            'ads_type' => 'ads type is required!',
            'start_date' => 'start date is required!',
            'end_date' => 'end date is required!',
            'type_count' => 'count is required!',
            'title.required' => 'Title is required!',
            'zone_id.required' => 'Zone is required!',
            'restaurant_id.required_if' => "Restaurant is required when banner type is restaurant wise",
            'item_id.required_if' => "Food is required when banner type is food wise",
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }
        $banner = new Banner;
        $banner->title = $request->title;
        $banner->type = $request->banner_type;
        $banner->zone_id = $request->zone_id;
        $banner->image = Helpers::upload('banner/', 'png', $request->file('image'));
        $banner->data = ($request->banner_type == 'restaurant_wise') ? $request->restaurant_id : $request->item_id;
        $banner->ads_type = $request->ads_type;
        $banner->start_date = $request->start_date;
        $banner->end_date = $request->end_date;
        $banner->type_count = $request->type_count;
        $banner->original_count = $request->type_count;
        $banner->save();
        return response()->json([], 200);
    }

    public function edit(Banner $banner)
    {
        return view('admin-views.banner.edit', compact('banner'));
    }

    // public function view(Banner $banner)
    // {
    //     $restaurant_ids = json_decode($banner->restaurant_ids);
    //     $restaurants = Restaurant::whereIn('id', $restaurant_ids)->paginate(10);
    //     return view('admin-views.banner.view', compact('banner', 'restaurants', 'restaurant_ids'));
    // }

    public function status(Request $request)
    {
        $banner = Banner::find($request->id);
        $banner->status = $request->status;
        $banner->save();
        Toastr::success(trans('messages.banner_status_updated'));
        return back();
    }

    public function update(Request $request, Banner $banner)
    {
        $request->validate([
            'title' => 'required',
            'banner_type' => 'required',
            'zone_id' => 'required',
            'restaurant_id' => 'required_if:banner_type,restaurant_wise',
            'item_id' => 'required_if:banner_type,item_wise',
            'type_count' => 'required_if:ads_type,clicks,views',
            'start_date' => 'required_if:ads_type,duration',
            'end_date' => 'required_if:ads_type,duration',
        ], [
            'title.required' => 'Title is required!',
            'zone_id.required' => 'Zone is required!',
            'restaurant_id.required_if' => "Restaurant is required when banner type is restaurant wise",
            'item_id.required_if' => "Food is required when banner type is food wise",
        ]);

        $banner = Banner::findOrFail($banner->id);
        $banner->title = $request->title;
        $banner->type = $request->banner_type;
        $banner->zone_id = $request->zone_id;
        $banner->image = $request->has('image') ? Helpers::update('banner/', $banner->image, 'png', $request->file('image')) : $banner->image;
        $banner->data = $request->banner_type == 'restaurant_wise' ? $request->restaurant_id : $request->item_id;
        $banner->ads_type = $request->ads_type;
        if ($request->has('start_date', 'end_date')) {
            $banner->start_date = $request->start_date;
            $banner->end_date = $request->end_date;
        }
        if ($request->has('type_count')) {

            $banner->type_count = $request->type_count;
        }
        $banner->save();
        Toastr::success(trans('messages.banner_updated_successfully'));
        return redirect('admin/banner/add-new');
    }

    public function delete(Banner $banner)
    {
        if (Storage::disk('public')->exists('banner/' . $banner['image'])) {
            Storage::disk('public')->delete('banner/' . $banner['image']);
        }
        $banner->delete();
        Toastr::success(trans('messages.banner_beleted_successfully'));
        return back();
    }

    public function search(Request $request)
    {
        $key = explode(' ', $request['search']);
        $banners = Banner::where(function ($q) use ($key) {
            foreach ($key as $value) {
                $q->orWhere('title', 'like', "%{$value}%");
            }
        })->limit(50)->get();
        return response()->json([
            'view' => view('admin-views.banner.partials._table', compact('banners'))->render(),
            'count' => $banners->count()
        ]);
    }
}
