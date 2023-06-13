<?php

namespace App\Http\Controllers\Front;

use App\DefaultPage;
use App\Demo_page;
use App\Http\Controllers\Common\TemplateController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Front\PageRequest;
use App\Model\Common\PricingTemplate;
use App\Model\Front\FrontendPage;
use App\Model\Payment\Plan;
use App\Model\Payment\PlanPrice;
use App\Model\Product\Product;
use App\Model\Product\ProductGroup;
use Illuminate\Http\Request;
use Symfony\Component\Mime\Email;

class PageController extends Controller
{
    public $page;

    public function __construct()
    {
        $this->middleware('auth', ['except' => ['pageTemplates', 'contactUs', 'postDemoReq', 'postContactUs']]);

        $page = new FrontendPage();
        $this->page = $page;
    }

    public function index()
    {
        try {
            $pages_count = count($this->page->all());

            return view('themes.default1.front.page.index', compact('pages_count'));
        } catch (\Exception $ex) {
            return redirect()->back()->with('fails', $ex->getMessage());
        }
    }

    public function getPages()
    {
        return \DataTables::of($this->page->select('id', 'name', 'url', 'created_at'))
                        ->orderColumn('name', '-id $1')
                        ->orderColumn('url', '-id $1')
                        ->orderColumn('created_at', '-id $1')
                        ->addColumn('checkbox', function ($model) {
                            return "<input type='checkbox' class='page_checkbox' 
                            value=".$model->id.' name=select[] id=check>';
                        })
                        ->addColumn('name', function ($model) {
                            return ucfirst($model->name);
                        })
                        ->addColumn('url', function ($model) {
                            return $model->url;
                        })
                        ->addColumn('created_at', function ($model) {
                            return getDateHtml($model->created_at);
                        })

                        ->addColumn('action', function ($model) {
                            return '<a href='.url('pages/'.$model->id.'/edit')
                            ." class='btn btn-sm btn-secondary btn-xs'".tooltip('Edit')."<i class='fa fa-edit'
                                 style='color:white;'> </i></a>";
                        })
                          ->filterColumn('name', function ($query, $keyword) {
                              $sql = 'name like ?';
                              $query->whereRaw($sql, ["%{$keyword}%"]);
                          })
                            ->filterColumn('url', function ($query, $keyword) {
                                $sql = 'url like ?';
                                $query->whereRaw($sql, ["%{$keyword}%"]);
                            })

                          ->rawColumns(['checkbox', 'name', 'url',  'created_at', 'action'])
                        ->make(true);
        // ->searchColumns('name', 'content')
        // ->orderColumns('name')
        // ->make();
    }

    public function create()
    {
        try {
            $parents = $this->page->pluck('name', 'id')->toArray();

            return view('themes.default1.front.page.create', compact('parents'));
        } catch (\Exception $ex) {
            app('log')->error($ex->getMessage());

            return redirect()->back()->with('fails', $ex->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            $page = $this->page->where('id', $id)->first();
            $parents = $this->page->where('id', '!=', $id)->pluck('name', 'id')->toArray();
            $selectedDefault = DefaultPage::value('page_id');
            $date = $this->page->where('id', $id)->pluck('created_at')->first();
            $publishingDate = date('m/d/Y', strtotime($date));
            $selectedParent = $this->page->where('id', $id)->pluck('parent_page_id')->toArray();
            $parentName = $this->page->where('id', $selectedParent)->pluck('name', 'id')->toArray();

            return view('themes.default1.front.page.edit', compact('parents', 'page', 'selectedDefault', 'publishingDate', 'selectedParent',
                'parentName'));
        } catch (\Exception $ex) {
            return redirect()->back()->with('fails', $ex->getMessage());
        }
    }

    public function store(PageRequest $request)
    {
        try {
            $pages_count = count($this->page->all());
            $url = $request->input('url');
            if ($request->input('type') == 'contactus') {
                $url = url('/contact-us');
            }
            $this->page->name = $request->input('name');
            $this->page->publish = $request->input('publish');
            $this->page->slug = $request->input('slug');
            $this->page->url = $url;
            $this->page->parent_page_id = $request->input('parent_page_id');
            $this->page->type = $request->input('type');
            $this->page->content = $request->input('content');
            if ($pages_count <= 2) {
                $this->page->save();

                return redirect()->back()->with('success', trans('message.saved-successfully'));
            } else {
                return redirect()->back()->with('fails', trans('message.limit_exceed'));
            }
        } catch (\Exception $ex) {
            app('log')->error($ex->getMessage());

            return redirect()->back()->with('fails', $ex->getMessage());
        }
    }

    public function update($id, PageRequest $request)
    {
        try {
            if ($request->input('default_page_id') != '') {
                $page = $this->page->where('id', $id)->first();
                $page->fill($request->except('created_at'))->save();
                $date = \DateTime::createFromFormat('m/d/Y', $request->input('created_at'));
                $page->created_at = $date->format('Y-m-d H:i:s');
                $page->save();
                $defaultUrl = $this->page->where('id', $request->input('default_page_id'))->pluck('url')->first();
                DefaultPage::find(1)->update(['page_id' => $request->input('default_page_id'), 'page_url' => $defaultUrl]);
            } else {
                DefaultPage::find(1)->update(['page_id' => 1, 'page_url' => url('my-invoices')]);
            }

            return redirect()->back()->with('success', \Lang::get('message.updated-successfully'));
        } catch (\Exception $ex) {
            return redirect()->back()->with('fails', $ex->getMessage());
        }
    }

    public function getPageUrl($slug)
    {
        $productController = new \App\Http\Controllers\Product\ProductController();
        //  $url = url('/');
        //  $segment = $this->addSegment(['public/pages']);
        $url = url('/');

        $slug = str_slug($slug, '-');
        echo $url.'/pages'.'/'.$slug;
    }

    public function getSlug($slug)
    {
        $slug = str_slug($slug, '-');
        echo $slug;
    }

    public function addSegment($segments = [])
    {
        $segment = '';
        foreach ($segments as $seg) {
            $segment .= '/'.$seg;
        }

        return $segment;
    }

    public function generate(Request $request)
    {
        // dd($request->all());
        if ($request->has('slug')) {
            $slug = $request->input('slug');

            return $this->getSlug($slug);
        }
        if ($request->has('url')) {
            $slug = $request->input('url');

            return $this->getPageUrl($slug);
        }
    }

    public function show($slug)
    {
        try {
            $page = $this->page->where('slug', $slug)->where('publish', 1)->first();
            if ($page && $page->type == 'cart') {
                return $this->cart();
            }

            return view('themes.default1.front.page.show', compact('page'));
        } catch (\Exception $ex) {
            return redirect()->back()->with('fails', $ex->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Response
     */
    public function destroy(Request $request)
    {
        try {
            $ids = $request->input('select');
            $defaultPageId = DefaultPage::pluck('page_id')->first();
            if (! empty($ids)) {
                foreach ($ids as $id) {
                    if ($id != $defaultPageId) {
                        $page = $this->page->where('id', $id)->first();
                        if ($page) {
                            $page->delete();
                        } else {
                            echo "<div class='alert alert-danger alert-dismissable'>
                    <i class='fa fa-ban'></i>
                    <b>"./* @scrutinizer ignore-type */\Lang::get('message.alert').'!</b> '.
                    /* @scrutinizer ignore-type */
                    \Lang::get('message.failed').'
                    <button type=button class=close data-dismiss=alert aria-hidden=true>&times;</button>
                        './* @scrutinizer ignore-type */\Lang::get('message.no-record').'
                </div>';
                            //echo \Lang::get('message.no-record') . '  [id=>' . $id . ']';
                        }
                        echo "<div class='alert alert-success alert-dismissable'>
                    <i class='fa fa-ban'></i>

                    <b>"./* @scrutinizer ignore-type */ \Lang::get('message.alert').'!</b> '.
                    /* @scrutinizer ignore-type */
                    \Lang::get('message.success').'

                    <button type=button class=close data-dismiss=alert aria-hidden=true>&times;</button>
                        './* @scrutinizer ignore-type */\Lang::get('message.deleted-successfully').'
                </div>';
                    } else {
                        echo "<div class='alert alert-danger alert-dismissable'>
                    <i class='fa fa-ban'></i>
                    <b>"./* @scrutinizer ignore-type */\Lang::get('message.alert').'!</b> '.
                    /* @scrutinizer ignore-type */\Lang::get('message.failed').'
                    <button type=button class=close data-dismiss=alert aria-hidden=true>&times;</button>
                        './* @scrutinizer ignore-type */ \Lang::get('message.can-not-delete-default-page').'
                </div>';
                    }
                }
            } else {
                echo "<div class='alert alert-danger alert-dismissable'>
                    <i class='fa fa-ban'></i>
                    <b>"./* @scrutinizer ignore-type */\Lang::get('message.alert').'!</b> '.
                    /* @scrutinizer ignore-type */\Lang::get('message.failed').'
                    <button type=button class=close data-dismiss=alert aria-hidden=true>&times;</button>
                        './* @scrutinizer ignore-type */\Lang::get('message.select-a-row').'
                </div>';
                //echo \Lang::get('message.select-a-row');
            }
        } catch (\Exception $e) {
            echo "<div class='alert alert-danger alert-dismissable'>
                    <i class='fa fa-ban'></i>
                    <b>"./* @scrutinizer ignore-type */\Lang::get('message.alert').'!</b> '.
                    /* @scrutinizer ignore-type */\Lang::get('message.failed').'
                    <button type=button class=close data-dismiss=alert aria-hidden=true>&times;</button>
                        '.$e->getMessage().'
                </div>';
        }
    }

    public function getstrikePriceYear($id)
    {
        $countryCheck = true;
        try {
            $cost = 'Free';
            $plans = Plan::where('product', $id)->get();

            $prices = [];
            if ($plans->count() > 0) {
                foreach ($plans as $plan) {
                    $offerprice = PlanPrice::where('plan_id', $plan->id)->value('offer_price');
                    $planDetails = userCurrencyAndPrice('', $plan);
                    $prices[] = $planDetails['plan']->add_price;
                    $prices[] .= $planDetails['symbol'];
                    $prices[] .= $planDetails['currency'];
                }
                $prices[0] = $prices[0] * 12;
                if (isset($offerprice) && $offerprice != '' && $offerprice != null) {
                    $prices[0] = $prices[0] - ($offerprice / 100 * $prices[0]);
                }
                $format = currencyFormat(min([$prices[0]]), $code = $prices[2]);
                $finalPrice = str_replace($prices[1], '', $format);
                $cost = '<span class="price-unit">'.$prices[1].'</span>'.$finalPrice;
            }

            return $cost;
        } catch (\Exception $ex) {
            return redirect()->back()->with('fails', $ex->getMessage());
        }
    }

        public function transform($type, $data, $trasform = [])
        {
            $config = \Config::get("transform.$type");
            $result = '';
            $array = [];
            foreach ($trasform as $trans) {
                $array[] = $this->checkConfigKey($config, $trans);
            }
            $c = count($array);
            for ($i = 0; $i < $c; $i++) {
                $array1 = $this->keyArray($array[$i]);
                $array2 = $this->valueArray($array[$i]);
                $data = Product::where('name', $array2[0])->value('highlight') ? PricingTemplate::findorFail(2)->data : PricingTemplate::findorFail(1)->data;
                $id = Product::where('name', $array2[0])->value('id');
                $offerprice = $this->getOfferprice($id);
                $description = self::getPriceDescription($id);
                if (! isset($offerprice) || $offerprice == $array2[1] || $offerprice == '' || $offerprice == null) {
                    $data = str_replace('<span class="strike">{{strike-price}}</span><br>', '', $data);
                    $data = str_replace('{{strike-priceyear}}', '', $data);
                } else {
                    $offerprice = $this->getPayingprice($id);
                    $offerpriceYear = $this->getstrikePriceYear($id);
                    $strikePrice = $this->YearlyAmount($id);
                    $data = str_replace('{{price}}', $offerprice, $data);
                    $data = str_replace('{{strike-price}}', $array2[1], $data);
                    $data = str_replace('{{price-year}}', $offerpriceYear, $data);
                    $data = str_replace('{{strike-priceyear}}', $strikePrice, $data);
                }
                $result .= str_replace($array1, $array2, $data);
            }

            return $result;
        }

   public function getPayingprice($id)
   {
       $countryCheck = true;
       try {
           $cost = 'Free';
           $plans = Plan::where('product', $id)->get();

           $prices = [];
           if ($plans->count() > 0) {
               foreach ($plans as $plan) {
                   $offerprice = PlanPrice::where('plan_id', $plan->id)->value('offer_price');
                   $planDetails = userCurrencyAndPrice('', $plan);
                   $price = $planDetails['plan']->add_price;
                   $symbol = $planDetails['symbol'];
                   $currency = $planDetails['currency'];

                   if (isset($offerprice) && $offerprice != '' && $offerprice != null) {
                       $price = $price - ($offerprice / 100 * $price);
                   }

                   $prices[] = $price;
                   $prices[] .= $symbol;
                   $prices[] .= $currency;
               }

               $format = currencyFormat(min([$prices[0]]), $code = $prices[2]);
               $finalPrice = str_replace($prices[1], '', $format);
               $cost = '<span class="price-unit">'.$prices[1].'</span>'.$finalPrice;
           }

           return $cost;
       } catch (\Exception $ex) {
           return redirect()->back()->with('fails', $ex->getMessage());
       }
   }

    /**
     * Get Page Template when Group in Store Dropdown is
     * selected on the basis of Group id.
     *
     * @author Ashutosh Pathak <ashutosh.pathak@ladybirdweb.com>
     *
     * @date   2019-01-10T01:20:52+0530
     *
     * @param  int  $groupid  Group id
     * @param  int  $templateid  Id of the Template
     * @return longtext The Template to be displayed
     */
    public function pageTemplates(int $templateid = null, int $groupid)
    {
        try {
            $productsHightlight = Product::wherehighlight(1)->get();

            // $data = PricingTemplate::findorFail($templateid)->data;
            $headline = ProductGroup::findorFail($groupid)->headline;
            $tagline = ProductGroup::findorFail($groupid)->tagline;
            $productsRelatedToGroup = ProductGroup::find($groupid)->product()->where('hidden', '!=', 1)
            ->orderBy('created_at', 'desc')->get(); //Get ALL the Products Related to the Group
            $trasform = [];
            $templates = $this->getTemplateOne($productsRelatedToGroup, $trasform);
            $products = Product::all();
            foreach ($productsRelatedToGroup as $product) {
                $plan = Product::find($product->id)->plan();
                $description = self::getPriceDescription($product->id);
            }
            $isChecked = request()->cookie('isChecked');

            return view('themes.default1.common.template.shoppingcart', compact('templates', 'headline', 'tagline', 'description'));
        } catch (\Exception $ex) {
            app('log')->error($ex->getMessage());

            return redirect()->back()->with('fails', $ex->getMessage());
        }
    }

    public function contactUs()
    {
        try {
            return view('themes.default1.front.contact');
        } catch (\Exception $ex) {
            return redirect()->back()->with('fails', $ex->getMessage());
        }
    }

    /**
     * Get  Template For Products.
     *
     * @param  $helpdesk_products
     * @param  $data
     * @param  $trasform
     * @return string
     */
    public function getTemplateOne($helpdesk_products, $trasform)
    {
        try {
            $template = '';
            $temp_controller = new TemplateController();
            if (count($helpdesk_products) > 0) {
                foreach ($helpdesk_products as $product) {
                    $price = $temp_controller->leastAmount($product['id']);
                    //Store all the values in $trasform variable for shortcodes to read from
                    $trasform[$product['id']]['price'] = $temp_controller->leastAmount($product['id']);
                    $trasform[$product['id']]['price-year'] = $this->YearlyAmount($product['id']);
                    $trasform[$product['id']]['price-description'] = self::getPriceDescription($product['id']);
                    $trasform[$product['id']]['strike-price'] = $this->getOfferprice($product['id']);
                    $trasform[$product['id']]['strike-priceyear'] = $this->YearlyAmount($product['id']);
                    $trasform[$product['id']]['name'] = $product['name'];
                    $trasform[$product['id']]['feature'] = $product['description'];
                    $trasform[$product['id']]['subscription'] = $temp_controller
                    ->plans($product['shoping_cart_link'], $product['id']);
                    $trasform[$product['id']]['subscription-year'] = $this
                    ->plansYear($product['shoping_cart_link'], $product['id']);
                    if($trasform[$product['id']]['price'] !== "Contact Sales"){
                    $trasform[$product['id']]['url'] = Product::where('name', $product['name'])->value('highlight') ? "<input type='submit'
                     value='Order Now' class='btn btn-primary btn-modern'></form>" : "<input type='submit' 
                   value='Order Now' class='btn btn-dark btn-modern'></form>";
                    }else{
                  $trasform[$product['id']]['url'] = "<a class='btn btn-dark btn-modern' href='https://www.faveohelpdesk.com/contact-us/'>Contact Sales</a>";
                  }
                }
                $data = PricingTemplate::findorFail(1)->data;
                $template = $this->transform('cart', $data, $trasform);
            }

            return $template;
        } catch (\Exception $ex) {
            return redirect()->back()->with('fails', $ex->getMessage());
        }
    }

    public function plansYear($url, $id)
    {
        try {
            $plan = new Plan();
            $plan_form = 'Free'; //No Subscription
            $plans = $plan->where('product', '=', $id)->pluck('name', 'id')->toArray();
            $type = Product::find($id);
            $planid = Plan::where('product', $id)->value('id');
            $price = PlanPrice::where('plan_id', $planid)->value('renew_price');

            $plans = $this->prices($id);
            if ($plans) {
                $plan_form = \Form::select('subscription', ['Plans' => $plans], null);
            }
            $form = \Form::open(['method' => 'get', 'url' => $url]).
            $plan_form.
            \Form::hidden('id', $id);

            return $form;
        } catch (\Exception $ex) {
            return redirect()->back()->with('fails', $ex->getMessage());
        }
    }

        public function getPrice($months, $price, $priceDescription, $value, $cost, $currency, $offer, $product)
        {
            $cost = $cost * 12;
            if (isset($offer) && $offer !== "" && $offer !== null) {
                $cost = $cost - ($offer / 100 * $cost);
            }
            $price1 = currencyFormat($cost, $code = $currency);
            $price[$value->id] = $months.'  '.$price1.' '.$priceDescription;

            return $price;
        }

    public function prices($id)
    {
        try {
            $plans = Plan::where('product', $id)->orderBy('id', 'desc')->get();
            $price = [];
            foreach ($plans as $value) {
                $offer = PlanPrice::where('plan_id', $value->id)->value('offer_price');
                $product = Product::find($value->product);
                $currencyAndSymbol = userCurrencyAndPrice('', $value);
                $currency = $currencyAndSymbol['currency'];
                $symbol = $currencyAndSymbol['symbol'];
                $cost = $currencyAndSymbol['plan']->add_price;
                $priceDescription = 'Per Year';
                $cost = rounding($cost);
                $duration = $value->periods;
                $months = count($duration) > 0 ? $duration->first()->name : '';
                if ($product->type != '4') {
                    $price = $this->getPrice($months, $price, $priceDescription, $value, $cost, $currency, $offer, $product);
                } elseif ($cost != '0' && $product->type == '4') {
                    $price = $this->getPrice($months, $price, $priceDescription, $value, $cost, $currency, $offer, $product);
                }
                // $price = currencyFormat($cost, $code = $currency);
            }

            return $price;
        } catch (\Exception $ex) {
            app('log')->error($ex->getMessage());

            return redirect()->back()->with('fails', $ex->getMessage());
        }
    }

    public function getOfferprice(int $productid)
    {
        $plan = Plan::where('product', $productid)->value('id');
        $offerprice = PlanPrice::where('plan_id', $plan)->value('offer_price');

        return $offerprice;
    }

public function YearlyAmount($id)
{
    $countryCheck = true;
    try {
        $cost = 'Free';
        $plans = Plan::where('product', $id)->get();

        $prices = [];
        if ($plans->count() > 0) {
            foreach ($plans as $plan) {
                $planDetails = userCurrencyAndPrice('', $plan);
                $prices[] = $planDetails['plan']->add_price;
                $prices[] .= $planDetails['symbol'];
                $prices[] .= $planDetails['currency'];
            }
            $prices[0] = $prices[0] * 12;
            $format = currencyFormat(min([$prices[0]]), $code = $prices[2]);
            $finalPrice = str_replace($prices[1], '', $format);
            $cost = '<span class="price-unit">'.$prices[1].'</span>'.$finalPrice;
        }

        return $cost;
    } catch (\Exception $ex) {
        return redirect()->back()->with('fails', $ex->getMessage());
    }
}

    /**
     * Get Price Description(eg: Per Year,Per Month ,One-Time) for a Product.
     *
     * @author Ashutosh Pathak <ashutosh.pathak@ladybirdweb.com>
     *
     * @date   2019-01-09T00:20:09+0530
     *
     * @param  int  $productid  Id of the Product
     * @return string $priceDescription        The Description of the Price
     */
    public function getPriceDescription(int $productid)
    {
        try {
            $plan = Product::find($productid)->plan();
            $description = $plan ? $plan->planPrice->first() : '';
            $priceDescription = $description ? $description->price_description : '';
            return  $priceDescription;
        } catch (\Exception $ex) {
            app('log')->error($ex->getMessage());

            return redirect()->back()->with('fails', $ex->getMessage());
        }
    }

    public function checkConfigKey($config, $transform)
    {
        $result = [];
        if ($config) {
            foreach ($config as $key => $value) {
                if (array_key_exists($key, $transform)) {
                    $result[$value] = $transform[$key];
                }
            }
        }

        return $result;
    }

    public function keyArray($array)
    {
        $result = [];
        foreach ($array as $key => $value) {
            $result[] = $key;
        }

        return $result;
    }

    public function valueArray($array)
    {
        $result = [];
        foreach ($array as $key => $value) {
            $result[] = $value;
        }

        return $result;
    }

    public function postContactUs(Request $request)
    {
        $this->validate($request, [
            'name'    => 'required',
            'email'   => 'required|email',
            'message' => 'required',
        ]);

        $set = new \App\Model\Common\Setting();
        $set = $set->findOrFail(1);

        try {
            $data = '';
            $data .= 'Name: '.strip_tags($request->input('name')).'<br/>';
            $data .= 'Email: '.strip_tags($request->input('email')).'<br/>';
            $data .= 'Message: '.strip_tags($request->input('message')).'<br/>';
            $data .= 'Mobile: '.strip_tags($request->input('country_code').' '.$request->input('Mobile')).'<br/>';
            if (emailSendingStatus()) {
                $mail = new \App\Http\Controllers\Common\PhpMailController();
                $mail->SendEmail($set->email, $to, $data, 'Faveo billing enquiry');
            }

            //$this->templateController->SendEmail($from, $to, $data, $subject);
            return redirect()->back()->with('success', 'Your message was sent successfully. Thanks.');
        } catch (\Exception $ex) {
            return redirect()->back()->with('fails', $ex->getMessage());
        }
    }

    public function viewDemoReq()
    {
        try {
            return view('themes.default1.front.demoForm');
        } catch (\Exception $ex) {
            return redirect()->back()->with('fails', $ex->getMessage());
        }
    }

    public function postDemoReq(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'demoemail' => 'required|email',
        ]);

        $set = new \App\Model\Common\Setting();
        $set = $set->findOrFail(1);
        $mail = new \App\Http\Controllers\Common\PhpMailController();

        try {
            $product = $request->input('product') != 'online' ? $request->input('product') : 'our product ';
            $data = '';
            $data .= 'Name: '.strip_tags($request->input('name')).'<br/>';
            $data .= 'Email: '.strip_tags($request->input('demoemail')).'<br/>';
            $data .= 'Message: '.strip_tags($request->input('message')).'<br/>';
            $data .= 'Mobile: '.strip_tags($request->input('country_code').' '.$request->input('Mobile')).'<br/>';
            if (emailSendingStatus()) {
                $mail->SendEmail($set->email, $set->company_email, $data, 'Requesting for Demo for'.'  '.$product);
            }

            return redirect()->back()->with('success', 'Your Request for booking demo was sent successfully. Thanks.');
        } catch (\Exception $ex) {
            return redirect()->back()->with('fails', $ex->getMessage());
        }
    }

    public function VewDemoPage()
    {
        try {
            $Demo_page = Demo_page::first();

            return view('themes.default1.common.setting.demo-page', compact('Demo_page'));
        } catch (\Exception $ex) {
            return redirect()->back()->with('fails', $ex->getMessage());
        }
    }

    public function saveDemoPage(Request $request)
    {
        $data = $request->validate([
            'status' => 'required',
        ]);
        $data = [
            'status' => $request->input('status') === 'true' ? 1 : 0,
        ];

        $existingData = Demo_page::first();
        $existingData ? $existingData->update($data) : Demo_page::create($data);

        $message = $existingData ? 'Data updated successfully.' : 'Data created successfully.';

        return redirect()->back()->with('success', $message);
    }
}
