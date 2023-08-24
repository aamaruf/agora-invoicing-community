<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\License\LicensePermissionsController;
use App\Model\Common\StatusSetting;
use App\Model\Common\TemplateType;
use App\Model\Order\Invoice;
use App\Model\Order\Order;
use App\Model\Payment\Plan;
use App\Model\Product\Product;
use App\Model\Product\Subscription;
use App\Plugins\Stripe\Controllers\SettingsController;
use App\Traits\Order\UpdateDates;
use App\User;
use Carbon\Carbon;
use Crypt;

class BaseOrderController extends ExtendedOrderController
{
    protected $sendMail;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');

        $this->order = new Order();

        $this->product = new Product();

        $this->subscription = new Subscription();

        $this->plan = new Plan();
    }

    use UpdateDates;

    public function getUrl($model, $status, $subscriptionId, $agents = null)
    {
        $url = '';
        if($model->order_status !='Terminated') {
            if ($status == 'success') {
                if ($subscriptionId) {
                    if (!is_null($agents)) {
                        $url = '<a href=' . url('renew/' . $subscriptionId . '/' . $agents) . " 
                class='btn btn-sm btn-secondary btn-xs'" . tooltip('Renew') . "<i class='fas fa-credit-card'
                 style='color:white;'> </i></a>";
                    } else {
                        $url = '<a href=' . url('renew/' . $subscriptionId) . " 
                class='btn btn-sm btn-secondary btn-xs'" . tooltip('Renew') . "<i class='fas fa-credit-card'
                 style='color:white;'> </i></a>";
                    }
                }
            }
        }

        return '<p><a href='.url('orders/'.$model->id)." 
        class='btn btn-sm btn-secondary btn-xs'".tooltip('View')."<i class='fas fa-eye'
         style='color:white;'> </i></a> $url</p>";
    }

    /**
     * inserting the values to orders table.
     *
     * @param  type  $invoiceid
     * @param  type  $order_status
     * @return string
     *
     * @throws \Exception
     */
    public function executeOrder($invoiceid, $order_status = 'executed')
    {
        try {
            $invoice_items = $this->invoice_items->where('invoice_id', $invoiceid)->get();
            $user_id = $this->invoice->find($invoiceid)->user_id;
            if (count($invoice_items) > 0) {
                foreach ($invoice_items as $item) {
                    if ($item) {
                        $items = $this->getIfItemPresent($item, $invoiceid, $user_id, $order_status);
                    }
                }
            }

            return 'success';
        } catch (\Exception $ex) {
            app('log')->error($ex->getMessage());

            throw new \Exception($ex->getMessage());
        }
    }

    public function getIfItemPresent($item, $invoiceid, $user_id, $order_status)
    {
        try {
            $product = $this->product->where('name', $item->product_name)->first()->id;
            $version = $this->product->where('name', $item->product_name)->first()->version;
            if ($version == null) {
                //Get Version from Product Upload Table
                $version = $this->product_upload->where('product_id', $product)->pluck('version')->first();
            }
            $serial_key = $this->generateSerialKey($product, $item->agents); //Send Product Id and Agents to generate Serial Key
            \Session::put('upgradeSerialKey', $serial_key);
            $domain = $item->domain;
            $plan_id = $this->plan($item->id);

            $order = $this->order->create([
                'invoice_id' => $invoiceid,
                'invoice_item_id' => $item->id,
                'client' => $user_id,
                'order_status' => $order_status,
                'serial_key' => Crypt::encrypt($serial_key),
                'product' => $product,
                'price_override' => $item->subtotal,
                'qty' => $item->quantity,
                'domain' => $domain,
                'number' => $this->generateNumber(),
                'created_at' => Carbon::now(),
            ]);
            $this->updateInvoiceDate($invoiceid);
            \Session::put('upgradeNewActiveOrder',$order->id);

            $this->addOrderInvoiceRelation($invoiceid, $order->id);
            if ($plan_id != 0) {
                $this->addSubscription($order->id, $plan_id, $version, $product, $serial_key);
            }

            if (emailSendingStatus()) {
                $this->sendOrderMail($user_id, $order->id, $item->id);
            }
            //Update Subscriber To Mailchimp
            $mailchimpStatus = StatusSetting::pluck('mailchimp_status')->first();
            if ($mailchimpStatus) {
                $this->addtoMailchimp($product, $user_id, $item);
            }
        } catch (\Exception $ex) {
            app('log')->error($ex->getMessage());

            throw new \Exception($ex->getMessage());
        }
    }

    protected function updateInvoiceDate($invoiceId)
    {
        $invoice = Invoice::find($invoiceId);
        $invoice->update(['date' => Carbon::now()]);
    }

    public function addToMailchimp($product, $user_id, $item)
    {
        try {
            $mailchimp = new \App\Http\Controllers\Common\MailChimpController();
            $email = User::where('id', $user_id)->pluck('email')->first();
            if ($item->subtotal > 0) {
                $r = $mailchimp->updateSubscriberForPaidProduct($email, $product);
            } else {
                $r = $mailchimp->updateSubscriberForFreeProduct($email, $product);
            }
        } catch (\Exception $ex) {
            return;
        }
    }

    /**
     * inserting the values to subscription table.
     *
     * @param  int  $orderid
     * @param  int  $planid
     * @param  string  $version
     * @param  int  $product
     * @param  string  $serial_key
     *
     * @throws \Exception
     *
     * @author Ashutosh Pathak <ashutosh.pathak@ladybirdweb.com>
     */
    public function addSubscription($orderid, $planid, $version, $product, $serial_key)
    {
        try {
            $permissions = LicensePermissionsController::getPermissionsForProduct($product);
            if ($version == null) {
                $version = '';
            }
            $days = null;
            $status = Product::find($product);
            if ($status->status) {
                if (\Session::get('planDays') == 'monthly') {
                    $days = $this->plan->where('product', $product)->whereIn('days', [30, 31])->first();
                } elseif (\Session::get('planDays') == 'free-trial') {
                    $days = $this->plan->where('product', $product)->where('days', '<', 30)->first();
                } elseif (\Session::get('planDays') == 'yearly' || \Session::get('planDays') == null) {
                    $days = $this->plan->where('product', $product)->whereIn('days', [365, 366])->first();
                }
            }

            if ($days === null) {
                if (\Session::has('plan_id')) {
                    $planid = \Session::get('plan_id');
                }
                $days = $this->plan->where('id', $planid)->first();
            }
            $licenseExpiry = $this->getLicenseExpiryDate($permissions['generateLicenseExpiryDate'], $days->days);
            $updatesExpiry = $this->getUpdatesExpiryDate($permissions['generateUpdatesxpiryDate'], $days->days);
            $supportExpiry = $this->getSupportExpiryDate($permissions['generateSupportExpiryDate'], $days->days);
            $user_id = $this->order->find($orderid)->client;
            $this->subscription->create(['user_id' => $user_id,
                'plan_id' => $days->id, 'order_id' => $orderid, 'update_ends_at' => $updatesExpiry, 'ends_at' => $licenseExpiry, 'support_ends_at' => $supportExpiry, 'version' => $version, 'product_id' => $product, 'is_subscribed' => '0']);

            $licenseStatus = StatusSetting::pluck('license_status')->first();
            if ($licenseStatus == 1) {
                $cont = new \App\Http\Controllers\License\LicenseController();
                $createNewLicense = $cont->createNewLicene($orderid, $product, $user_id, $licenseExpiry, $updatesExpiry, $supportExpiry, $serial_key);
            }
        } catch (\Exception $ex) {
            app('log')->error($ex->getMessage());

            throw new \Exception('Cannot generate Subscription'.'.'.$ex->getMessage());
        }
    }

    /**
     *  Get the Expiry Date for License.
     *
     * @param  bool  $permissions  [Whether Permissons for generating License Expiry Date are there or not]
     * @param  int  $days  [No of days that would get addeed to the current date ]
     * @return string [The final License Expiry date that is generated]
     */
    protected function getLicenseExpiryDate(bool $permissions, $days)
    {
        $ends_at = '';
        if ($days > 0 && $permissions == 1) {
            $dt = \Carbon\Carbon::now();
            $ends_at = $dt->addDays($days);
        }

        return $ends_at;
    }

    /**
     *  Get the Expiry Date for Updates.
     *
     * @param  bool  $permissions  [Whether Permissons for generating Updates Expiry Date are there or not]
     * @param  int  $days  [No of days that would get added to the current date ]
     * @return string [The final Updates Expiry date that is generated]
     */
    protected function getUpdatesExpiryDate(bool $permissions, $days)
    {
        $update_ends_at = '';
        if ($days > 0 && $permissions == 1) {
            $dt = \Carbon\Carbon::now();
            $update_ends_at = $dt->addDays($days);
        }

        return $update_ends_at;
    }

    /**
     *  Get the Expiry Date for Support.
     *
     * @param  bool  $permissions  [Whether Permissons for generating Updates Expiry Date are there or not]
     * @param  int  $days  [No of days that would get added to the current date ]
     * @return string [The final Suport Expiry date that is generated]
     */
    protected function getSupportExpiryDate(bool $permissions, $days)
    {
        $support_ends_at = '';
        if ($days > 0 && $permissions == 1) {
            $dt = \Carbon\Carbon::now();
            $support_ends_at = $dt->addDays($days);
        }

        return $support_ends_at;
    }

    public function addOrderInvoiceRelation($invoiceid, $orderid)
    {
        try {
            $relation = new \App\Model\Order\OrderInvoiceRelation();
            $relation->create(['order_id' => $orderid, 'invoice_id' => $invoiceid]);
        } catch (\Exception $ex) {
            throw new \Exception($ex->getMessage());
        }
    }

    public function sendOrderMail($userid, $orderid, $itemid)
    {
        //order
        $order = $this->order->find($orderid);
        //product
        $product = $this->product($itemid);
        //user
        $productId = Product::where('name', $product)->pluck('id')->first();
        $users = new User();
        $user = $users->find($userid);
        //check in the settings
        $settings = new \App\Model\Common\Setting();
        $setting = $settings::find(1);
        $orders = new Order();
        $order = $orders->where('id', $orderid)->first();
        $invoice = $this->invoice->find($order->invoice_id);
        $number = $invoice->number;
        $downloadurl = '';
        if ($user && $order->order_status == 'Executed') {
            $downloadurl = url('product/'.'download'.'/'.$productId.'/'.$number);
        }
        // $downloadurl = $this->downloadUrl($userid, $orderid,$productId);
        $myaccounturl = url('my-order/'.$orderid);
        $invoiceurl = $this->invoiceUrl($orderid);
        //template
        $mail = $this->getMail($setting, $user, $downloadurl, $invoiceurl, $order, $product, $orderid, $myaccounturl);
    }

    public function getMail($setting, $user, $downloadurl, $invoiceurl, $order, $product, $orderid, $myaccounturl)
    {
        $contact = getContactData();
        $value = Product::where('name', $product)->value('type');

        $templates = new \App\Model\Common\Template();
        $temp_id = TemplateType::where('name', 'order_mail')->value('id');

        $template = $templates->where('type', $temp_id)->first();

        $knowledgeBaseUrl = $setting->company_url;

        $orderHeading = ($value != '4') ? 'Download' : 'Deploy';
        $orderUrl = ($value != '4') ? $downloadurl : url('my-orders');
        $end = app(\App\Http\Controllers\Order\OrderController::class)->expiry($orderid);
        $date = date_create($end);
        $end = date_format($date, 'l, F j, Y');

        $replace = [
            'orderHeading' => $orderHeading,
            'name' => $user->first_name.' '.$user->last_name,
            'serialkeyurl' => $myaccounturl,
            'downloadurl' => $orderUrl,
            'invoiceurl' => $invoiceurl,
            'product' => $product,
            'number' => $order->number,
            'expiry' => $end,
            'url' => app(\App\Http\Controllers\Order\OrderController::class)->renew($orderid),
            'knowledge_base' => $knowledgeBaseUrl,
            'contact' => $contact['contact'],
            'logo' => $contact['logo'], ];

        $type = '';
        if ($template) {
            $type_id = $template->type;
            $temp_type = new \App\Model\Common\TemplateType();
            $type = $temp_type->where('id', $type_id)->first()->name;
        }
        $mail = new \App\Http\Controllers\Common\PhpMailController();
        $mail->SendEmail($setting->email, $user->email, $template->data, $template->name, $replace, $type);

        if ($order->invoice->grand_total) {
            SettingsController::sendPaymentSuccessMailtoAdmin($order->invoice, $order->invoice->grand_total, $user, $product);
        }
    }

    public function invoiceUrl($orderid)
    {
        $orders = new Order();
        $order = $orders->where('id', $orderid)->first();
        $invoiceid = $order->invoice_id;
        $url = url('my-invoice/'.$invoiceid);

        return $url;
    }

    /**
     * get the price of a product by id.
     *
     * @param  type  $product_id
     * @return type collection
     *
     * @throws \Exception
     */
    public function getPrice($product_id)
    {
        try {
            return $this->price->where('product_id', $product_id)->first();
        } catch (\Exception $ex) {
            throw new \Exception($ex->getMessage());
        }
    }

    public function downloadUrl($userid, $orderid)
    {
        $orders = new Order();
        $order = $orders->where('id', $orderid)->first();
        $invoice = $this->invoice->find($order->invoice_id);
        $number = $invoice->number;
        $url = url('download/'.$userid.'/'.$number);

        return $url;
    }
}
