<?php

namespace App\Http\Controllers\Product;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Company;
use App\Models\ProductMeta;

class ProductController extends Controller
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
            if(!$login->isAbleTo('read-product'))
                return errorCustomStatus(403);

            $return = array(
                'total_records' => 0,
                'products' => null
            );

            $ProductModel = new Product;
            $searchables = $ProductModel->getSearchable();

            $page = !empty($request->input('page')) ? ($request->input('page')-1) : 0;
            $per_page = !empty($request->input('per_page')) ? $request->input('per_page') : null;
            $keyword = !empty($request->input('keyword')) ? $request->input('keyword') : null;
            $filters = !empty($request->input('filter')) ? json_decode($request->input('filter')) : null;
            $date_filters = (!empty($request->input('date_filter'))) ? json_decode($request->input('date_filter'), true) : null;
            $sort_by = !empty($request->input('sort_by')) ? $request->input('sort_by') : 'created_at';
            $sort = !empty($request->input('sort')) ? $request->input('sort') : 'desc';

            $productQ = Product::select('*');

            if(!empty($filters))
            {
                foreach($filters as $where => $in)
                    if(is_array($in))
                        $productQ->whereIn($where,$in);
                    else
                        $productQ->where($where,$in);
            }

            if(!empty($keyword))
            {
                $productQ->where(function($query) use ($keyword, $searchables){
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
                        $productQ->whereBetween($key,[$date['start'], $date['end']]);
                }
            }

            $return['total_records'] = $productQ->count();

            if(!empty($return['total_records']))
            {
                $productQ->orderBy($sort_by,$sort);

                if(!empty($per_page))
                {
                    $productQ->offset(($page*$per_page))
                        ->limit($per_page);
                }

                $products = $productQ->with(['category','company', 'metas'])->get();

                if(!empty($products))
                    $return['products'] = $products;
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
            if(!$login->isAbleTo('create-product'))
                return errorCustomStatus(403);

            $checks = array(
                'product_name',
                'product_price',
            );

            foreach ($checks as $check) 
            {
                if(empty($request->$check))
                return errorCustomStatus(400,'Param ['.$check.'] tidak boleh kosong.');
            }
            
            $name = $request->input('product_name');
            
            $exist = Product::where('product_display_name',$name)->first();
            if(!empty($exist))
                return errorCustomStatus(400,'Product '.$name.' sudah ada.',array('product' => $exist));

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

            \DB::beginTransaction();

            $new_product = new Product;
            $new_product->company_id = $company_id;
            $new_product->category_id = $request->category_id;
            $new_product->product_name = Str::slug($name);
            $new_product->product_display_name = $name;
            $new_product->product_price = $request->input('product_price');
            if(!empty($request->product_description))
                $new_product->product_description = $request->product_description;
            insert_log_user($new_product, $login);
            
            try 
            {
                $new_product->save();
            } 
            catch (\Exception $e) 
            {
                \DB::rollback();
                $message = $e->getMessage();
                $developer = $e->getFile().' Line: '.$e->getLine();
                Log::error($e->getMessage().': '.$e->getFile().' Line: '.$e->getLine());
                return errorQuery($message,$developer);
            }

            if(!empty($metas = $request->meta))
            {
                foreach($metas as $meta_key => $meta_value)
                {
                    $meta = new ProductMeta;
                    $meta->product_id = $new_product->id;
                    $meta->meta_key = $meta_key;
                    $meta->meta_value = $meta_value;
                    // insert_log_user($meta, $login);

                    $meta->save();
                }
            }

            \DB::commit();

            $product = Product::with(['company', 'category', 'metas'])->find($new_product->id);

            $history = array(
                'predicate' => 'Create {object}',
                'object' => array(
                    'product'   => $product,
                    'request' => $request->all()
                )
            );
            // dd($jurnal_product->toArray());
            insertLogAction($login, $history, 'create_product', 'product');

            return responses(array('product' => $product),array('message' => 'Product '.$name.' berhasil dibuat.'));
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
            if(!$login->isAbleTo('read-product'))
                return errorCustomStatus(403);

            $product = Product::with(['company', 'category', 'metas'])->find($id);
            if(empty($product))
                return errorCustomStatus(400,'Product ID #'.$id.' tidak ditemukan.');

            return responses(array('product' => $product));
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
            if(!$login->isAbleTo('update-product'))
                return errorCustomStatus(403);

            $product = Product::find($id);
            if(empty($product))
                return errorCustomStatus(404, 'Product ID #'.$id.' tidak ditemukan!');

            $name = $request->input('product_name');
            if(!empty($name))
            {
                // return errorCustomStatus(400,'Nama Kategori tidak boleh kosong.');
                $exist = Product::where('product_name',$name)->where('id','!=',$id)->where('company_id', $product->company_id)->first();
                if(!empty($exist))
                    return errorCustomStatus(400,'Kategori '.$name.' sudah ada.',array('product' => $exist));

                $product->product_name = !empty($request->slug) ? $request->slug : Str::slug($name);
                $product->product_display_name = $name;
            }

            if(!empty($request->category_id))
            {
                $exist_category = Category::find($request->category_id);
                if(empty($exist_category))
                    return errorCustomStatus(400,'Kategori #'.$request->category_id.' tidak ditemukan.');
                $product->category_id = $request->category_id;
                
            }

            if(!empty($request->product_description))
                $product->product_description = $request->product_description;
        
            if($request->input('status') !== null) 
                $product->status = $request->input('status');

            $price = $request->input('product_price');
            if(!empty($price))
                $product->product_price = $price;
            
            $product->company_id = !empty($request->company_id) ? $request->company_id : 1;

            try 
            {
                $product->save();
            } 
            catch (\Exception $e) 
            {
                \DB::rollback();
                $message = $e->getMessage();
                $developer = $e->getFile().' Line: '.$e->getLine();
                Log::error($e->getMessage().': '.$e->getFile().' Line: '.$e->getLine());
                return errorQuery($message,$developer);
            }

            if(!empty($metas = $request->meta))
            {
                foreach($metas as $meta_key => $meta_value)
                {
                    $meta = ProductMeta::where('meta_key',$meta_key)
                            ->where('product_id',$product->id)->first();
                    if(empty($meta))
                    {
                        $meta = new ProductMeta;
                        $meta->product_id = $product->id;
                        $meta->meta_key = $meta_key;
                        insert_log_user($meta, $login);
                    }
                    else
                        // insert_log_user($meta, $login, 1);

                    $meta->meta_value = $meta_value;
                    $meta->save();
                }
            }

            \DB::commit();
            $product = Product::with([
                'company',
                'category',
                'metas'
            ])->find($id);

            $history = array(
                'predicate' => 'Update {object}',
                'object' => array(
                    'product'   => $product,
                    'request' => $request->all()
                )
            );
            insertLogAction($login, $history, 'update_product', 'product');
            
            return responses(['product' => $product], ['message' => 'Edit Product berhasil!']);

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
            if(!$login->isAbleTo('delete-product'))
                return errorCustomStatus(403);

            $product = Product::find($id);
            if(empty($product))
                return errorCustomStatus(404, 'Product ID #'.$id.' tidak ditemukan!');
            
            Product::destroy($id);

            $history = array(
                'predicate' => 'Delete {object}',
                'object' => array(
                    'product'   => $product,
                    'request' => ''
                )
            );
            insertLogAction($login, $history, 'update_product', 'product');

            return responses(['product' => $product], ['message' => 'Product '.$product->display_name.' berhasil di delete!']);

        }
        catch (\Exception $e) 
        {
            return errorCustomStatus(404,'Product tidak ditemukan!');
        }
    }
}
