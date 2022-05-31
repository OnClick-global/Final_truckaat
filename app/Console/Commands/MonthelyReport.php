<?php

namespace App\Console\Commands;

use App\Models\MonthlyReport;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Console\Command;

class MonthelyReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Report:Monthly';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make Monthly report';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }


    public function handle()
    {

        $resturants = Restaurant::select('id', 'Profit_Ratio')->get();
        foreach ($resturants as $key => $resturant) {
            $totalAmount = Order::where('restaurant_id', $resturant['id'])->sum('order_amount');
            $monthly_percentage = $resturant['Profit_Ratio'];
            $compuny_amount = $totalAmount * (100 / $monthly_percentage);
            $restaurant_amount = $totalAmount - $compuny_amount;

            $data['restaurant_id'] = $resturant->id ;
            $data['monthly_percentage'] = $monthly_percentage;
            $data['Total_amount'] = $totalAmount ;
            $data['client_amount'] =  $restaurant_amount ;
            $data['Company_amount'] = $compuny_amount ;
            $data['withdraw_status'] = '0';
            MonthlyReport::create($data);
        }
    }
}
