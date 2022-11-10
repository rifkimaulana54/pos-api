<?php

namespace App\Http\Controllers\Order;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\Company;
use App\Models\OrderMapping;
use App\Models\OrderMeta;
use Illuminate\Support\Facades\Cache;

class OrderController extends Controller
{
    /**
     * Instantiate a new UserController instance.
     *
     * @return void
     */

    public $limit = 25;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
    	try
        {   
            $login = Auth::user();
            if(!$login->isAbleTo('read-orders'))
                return errorCustomStatus(403);

            $return = array(
                'total_records' => 0,
                'orders' => null
            );

            $OrderModel = new Order;
            $searchables = $OrderModel->getSearchable();

            $page = !empty($request->input('page')) ? ($request->input('page')-1) : 0;
            $per_page = !empty($request->input('per_page')) ? $request->input('per_page') : null;
            $keyword = !empty($request->input('keyword')) ? $request->input('keyword') : null;
            $filters = !empty($request->input('filter')) ? json_decode($request->input('filter')) : null;
            $date_filters = (!empty($request->input('date_filter'))) ? json_decode($request->input('date_filter'), true) : null;
            $sort_by = !empty($request->input('sort_by')) ? $request->input('sort_by') : 'created_at';
            $sort = !empty($request->input('sort')) ? $request->input('sort') : 'desc';

            $orderQ = Order::select('*');

            if(!empty($filters))
            {
                foreach($filters as $where => $in)
                    if(is_array($in))
                        $orderQ->whereIn($where,$in);
                    else
                        $orderQ->where($where,$in);
            }

            if(!empty($keyword))
            {
                $orderQ->where(function($query) use ($keyword, $searchables){
                    foreach($searchables as $src){
                        $query->orWhere($src, 'LIKE', '%'.$keyword.'%');
                    } 
                });
            }

            if(!empty($date_filters))
            {
                foreach($date_filters as $key => $date)
                {
                    if(!empty($date['start']) && !empty($date['end']))
                        $orderQ->whereBetween($key,[$date['start'], $date['end']]);
                }
            }

            $return['total_records'] = $orderQ->count();

            if(!empty($return['total_records']))
            {
                $orderQ->orderBy($sort_by,$sort);

                if(!empty($per_page))
                {
                    $orderQ->offset(($page*$per_page))
                        ->limit($per_page);
                }

                $orders = $orderQ->with([
                    'mapping',
                    'mapping.product',
                    'store'
                    ])->get();

                if(!empty($orders))
                    $return['orders'] = $orders;
            }

            return responses($return);
        }
        catch(\Exception $e)
        {
            $message = $e->getMessage();
            $developer = $e->getFile().' Line: '.$e->getLine();
            return errorQuery($message,$developer);
        }
    }

    public function store(Request $request)
    {
        try
        {
            $login = Auth::user();
            // dd($request->all());
            if(!$login->isAbleTo('create-orders'))
                return errorCustomStatus(403);

            $company_id = 1;
            if(!empty($request->company_id))
            {
                $company = Company::find($request->company_id);
                if(empty($company))
                    return errorCustomStatus(400,'Company #'.$request->company_id.' tidak ditemukan');

                $company_id = $company->id;
            }

            if(!empty($request->category_id))
            {
                $exist_category = Category::find($request->category_id);
                if(empty($exist_category))
                    return errorCustomStatus(400,'Kategori #'.$request->category_id.' tidak ditemukan.');
            }

            $transaction_date = date('Y-m-d H:i:s');
            $wait = 0;
            while(!empty(Cache::get('generate_co_code')))
            {
                $wait++;
            }
            // dump($wait);
            Cache::put('generate_co_code',1,600);

            \DB::beginTransaction();

            $order_number = 1;
            $last_order = Order::select('*')->where('company_id', $company_id)
                            ->whereMonth('created_at','=',date('m', strtotime($transaction_date)))
                            ->whereYear('created_at','=',date('Y', strtotime($transaction_date)))
                            ->orderBy('order_number','desc')->first();
            // dd($last_order->toArray());
            if(!empty($last_order))
            {
                // if(date('Ym',strtotime($last_order->created_at)) == date('Ym', strtotime($transaction_date)))
                // {
                do{
                    $order_number = ($last_order->order_number + 1);
                }
                while(Order::where('order_number',$order_number)->whereMonth('created_at','=',date('m', strtotime($transaction_date)))->whereYear('created_at','=',date('Y', strtotime($transaction_date)))->first());
                // }
            }

            $new_order = new Order;
            $new_order->company_id = $company_id;
            $new_order->order_code = 'CO/' . date('y/m/', strtotime($transaction_date)) . str_pad($order_number, 4, '0', STR_PAD_LEFT);
            if(!empty($request->customer_name))
                $new_order->customer_name = $request->customer_name;
            if (!empty($request->total_order))
                $new_order->total_order = $request->total_order;
            $new_order->order_number = !empty($last_order->order_number) ? ($last_order->order_number + 1) : 1;
            $new_order->store_id = $login->store_id;
            $new_order->status = $request->status;


            insert_log_user($new_order, $login);
            
            try 
            {
                $new_order->save();
            } 
            catch (\Exception $e) 
            {
                \DB::rollback();
                $message = $e->getMessage();
                $developer = $e->getFile().' Line: '.$e->getLine();
                Log::error($e->getMessage().': '.$e->getFile().' Line: '.$e->getLine());
                return errorQuery($message,$developer);
            }

            if(!empty($request->items))
            {
                $items = maybe_unserialize($request->items);
                $cek = Order::where('store_id', $login->store_id)->where('status', 5)->first();

                if(!empty($items))
                {
                    foreach($items as $item)
                    {
                        $new_item = new OrderMapping;
                        $new_item->order_id = !empty($cek) ? $cek->id : $new_order->id;
                        $new_item->product_id = !empty($item['product_id']) ? $item['product_id'] : '';
                        $new_item->order_qty = !empty($item['order_qty']) ? $item['order_qty'] : '';
                        $new_item->default_price = !empty($item['default_price']) ? $item['default_price'] : '';
                        $new_item->order_subtotal = !empty($item['order_subtotal']) ? $item['order_subtotal'] : '';

                        insert_log_user($new_item, $login);

                        try
                        {
                            $new_item->save();
                        }
                        catch (\Exception $e)
                        {
                            \DB::rollback();
                            $message = $e->getMessage();
                            $developer = $e->getFile().' Line: '.$e->getLine();
                            Log::error($e->getMessage().': '.$e->getFile().' Line: '.$e->getLine());
                            return errorQuery($message,$developer);
                        }
                    }
                }
            }

            \DB::commit();

            Cache::forget('generate_co_code');

            $order = Order::with(['mapping', 'store'])->find($new_order->id);

            $history = array(
                'predicate' => 'Create {object}',
                'object' => array(
                    'order'   => $order,
                    'request' => $request->all()
                )
            );
            // dd($jurnal_order->toArray());
            insertLogAction($login, $history, 'create_order', 'orders');

            return responses(array('order' => $order),array('message' => 'Order name #'. $new_order->customer_name.' berhasil dibuat.'));
        }
        catch(\Exception $e)
        {
            \DB::rollback();
            $message = $e->getMessage();
            $developer = $e->getFile().' Line: '.$e->getLine();
            return errorQuery($message,$developer);
        }
    }

    public function show($id)
    {
        try
        {
            $login = Auth::user();
            if(!$login->isAbleTo('read-orders'))
                return errorCustomStatus(403);

            $order = Order::with([
                'mapping',
                'mapping.product',
                'store'
                ])->find($id);
            if(empty($order))
                return errorCustomStatus(400,'Order ID #'.$id.' tidak ditemukan.');

            return responses(array('order' => $order));
        }
        catch(\Exception $e)
        {
            $message = $e->getMessage();
            $developer = $e->getFile().' Line: '.$e->getLine();
            return errorQuery($message,$developer);
        }
    }

    public function update(Request $request, $id)
    {
        try
        {            
            $login = Auth::user();
            if(!$login->isAbleTo('update-orders'))
                return errorCustomStatus(403);

            $order = Order::find($id);
            if(empty($order))
                return errorCustomStatus(404, 'Order id #'.$id.' tidak ditemukan!');

            \DB::beginTransaction();

            if(!empty($request->customer_name))
                $order->customer_name = $request->customer_name;
            if(!empty($request->total_order))
                $order->total_order = $request->total_order;
            $order->status = !empty($request->status) ? $request->status : 2;
            $order->company_id = !empty($request->company_id) ? $request->company_id : 1;

            insert_log_user($order, $login, 1);

            try
            {
                $order->save();
            }
            catch (\Exception $e)
            {
                \DB::rollback();
                $message = $e->getMessage();
                $developer = $e->getFile().' Line: '.$e->getLine();
                Log::error($e->getMessage().': '.$e->getFile().' Line: '.$e->getLine());
                return errorQuery($message, $developer);
            }

            if(!empty($request->items))
            {
                $items = maybe_unserialize($request->items);

                if(!empty($items))
                {
                    $existing_items_ids = OrderMapping::where('order_id', $order->id)
                        ->pluck('id')->toArray();

                    $new_ids = [];
                    foreach ($items as $item)
                    {
                        if(empty($item['id']))
                        {
                            $order_item = new OrderMapping;
                            $order_item->order_id = $order->id;

                            insert_log_user($order_item, $login);
                        }
                        else
                        {
                            $order_item = OrderMapping::find($item['id']);
                            if(empty($order_item))
                            {
                                $order_item = new OrderMapping;
                                $order_item->order_id = $order->id;
                                insert_log_user($order_item, $login);
                            }
                            else
                                insert_log_user($order_item, $login, 1);
                        }

                        $order_item->product_id = !empty($item['product_id']) ? $item['product_id'] : '';
                        $order_item->order_qty = !empty($item['order_qty']) ? $item['order_qty'] : '';
                        $order_item->default_price = !empty($item['default_price']) ? $item['default_price'] : '';
                        $order_item->order_subtotal = !empty($item['order_subtotal']) ? $item['order_subtotal'] : '';

                        try
                        {
                            $order_item->save();
                        }
                        catch (\Exception $e)
                        {
                            \DB::rollback();
                            $message = $e->getMessage();
                            $developer = $e->getFile().' Line: '.$e->getLine();
                            Log::error($e->getMessage().': '.$e->getFile().' Line: '.$e->getLine());
                            return errorQuery($message,$developer);
                        }

                        $new_ids[] = $order_item->id;
                    }

                    if(!empty($existing_items_ids))
                    {
                        $delete_items = [];

                        foreach ($existing_items_ids as $exist_id) {
                            if(!in_array($exist_id, $new_ids))
                                $delete_items[] = $exist_id;
                        }

                        if(!empty($delete_items))
                        {
                            try
                            {
                                OrderMapping::destroy($delete_items);
                            }
                            catch(\Exception $e)
                            {
                                \DB::rollback();
                                $message = $e->getMessage();
                                $developer = $e->getFile().' Line: '.$e->getLine();
                                Log::error($e->getMessage().': '.$e->getFile().' Line: '.$e->getLine());
                                return errorQuery($message,$developer);
                            }
                        }
                    }
                }
            }

            \DB::commit();
            $order = Order::with([
                        'mapping', 
                        'store'
                    ])->find($id);

            $history = array(
                'predicate' => 'Update {object}',
                'object' => array(
                    'order'   => $order,
                    'request' => $request->all()
                )
            );
            insertLogAction($login, $history, 'update_order', 'orders');
            
            return responses(['order' => $order], ['message' => 'Order name #'.$order->customer_name.' berhasil diedit!']);

        }
        catch (\Exception $e)
        {
            \DB::rollback();
            $message = $e->getMessage();
            $developer = $e->getFile().' Line: '.$e->getLine();
            Log::error($e->getMessage().': '.$e->getFile().' Line: '.$e->getLine());
            return errorQuery($message,$developer);
        }
    }

    public function destroy($id)
    {
        try
        {
            $login = Auth::user();
            if(!$login->isAbleTo('delete-orders'))
                return errorCustomStatus(403);

            $order = Order::find($id);
            if(empty($order))
                return errorCustomStatus(404, 'Order ID #'.$id.' tidak ditemukan!');
            
            Order::destroy($id);

            $history = array(
                'predicate' => 'Delete {object}',
                'object' => array(
                    'order'   => $order,
                    'request' => ''
                )
            );
            insertLogAction($login, $history, 'update_order', 'orders');

            return responses(['order' => $order], ['message' => 'Order '.$order->display_name.' berhasil di delete!']);

        }
        catch (\Exception $e) 
        {
            return errorCustomStatus(404,'Order tidak ditemukan!');
        }
    }
}
