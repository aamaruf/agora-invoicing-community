<?php

use App\Model\Common\Country;
use App\Model\Common\Setting;
use App\Model\Payment\Currency;
use App\Model\Payment\PlanPrice;
use Illuminate\Database\Seeder;

class PlanPriceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->mapCountriesToCurrency();
    }

    private function mapCountriesToCurrency()
    {
        $nonDefaultCurrencies = PlanPrice::where(
            'currency', "!=", (new Setting())->first()->default_currency
        )->get(['id', 'currency']);
        if ($nonDefaultCurrencies) {
            foreach ($nonDefaultCurrencies as $currency) {
                $currencyId = Currency::where('code',$currency->currency)->first()->id;
                $countryId = Country::where('currency_id',$currencyId)->first()->country_id;
                $currency->update(['country_id'=>$countryId]);
            }
        }
    }

}
