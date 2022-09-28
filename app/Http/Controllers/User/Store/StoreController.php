<?php

namespace App\Http\Controllers\User\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Store;
use App\Models\StoreMeta;

class StoreController extends Controller
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
            if(!$login->isAbleTo('read-store'))
                return errorCustomStatus(403);

            $return = array(
                'total_records' => 0,
                'stores' => null
            );

            $StoreModel = new Store;
            $searchables = $StoreModel->getSearchable();

            $page = !empty($request->input('page')) ? ($request->input('page')-1) : 0;
            $per_page = !empty($request->input('per_page')) ? $request->input('per_page') : null;
            $keyword = !empty($request->input('keyword')) ? $request->input('keyword') : null;
            $filters = !empty($request->input('filter')) ? json_decode($request->input('filter')) : null;
            $date_filters = (!empty($request->input('date_filter'))) ? json_decode($request->input('date_filter'), true) : null;
            $sort_by = !empty($request->input('sort_by')) ? $request->input('sort_by') : 'created_at';
            $sort = !empty($request->input('sort')) ? $request->input('sort') : 'desc';

            $storeQ = Store::select('*');

            if(!empty($filters))
            {
                foreach($filters as $where => $in)
                    if(is_array($in))
                        $storeQ->whereIn($where,$in);
                    else
                        $storeQ->where($where,$in);
            }

            if(!empty($keyword))
            {
                $storeQ->where(function($query) use ($keyword, $searchables){
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
                        $storeQ->whereBetween($key,[$date['start'], $date['end']]);
                }
            }

            $return['total_records'] = $storeQ->count();

            if(!empty($return['total_records']))
            {
                $storeQ->orderBy($sort_by,$sort);

                if(!empty($per_page))
                {
                    $storeQ->offset(($page*$per_page))
                        ->limit($per_page);
                }

                $stores = $storeQ->with(['metas'])->get();

                if(!empty($stores))
                    $return['stores'] = $stores;
            }

            return responses($return);

            // return response()->json(['users' =>  User::all()], 200);
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
            if(!$login->isAbleTo('create-store'))
                return errorCustomStatus(403);

            $checks = array(
                'store_name',
                'store_address',
            );

            foreach ($checks as $check) 
            {
                if(empty($request->$check))
                return errorCustomStatus(400,'Param ['.$check.'] tidak boleh kosong.');
            }

            $name = $request->input('store_name');

            $exist = Store::where('store_name',$name)->first();
            if(!empty($exist))
                return errorCustomStatus(400,'Store '.$name.' sudah ada.',array('brand' => $exist));

            if(!empty($request->company_id))
            {
                $company = Company::find($request->company_id);
                if(empty($company))
                    return errorCustomStatus(400,'Company ID #'.$request->company_id.' tidak ditemukan.');
            }

            \DB::beginTransaction();

            $store = new Store;
            if(!empty($request->company_id)) 
                $store->company_id = $request->company_id;
            $store->store_name = $name;
            $store->store_address = !empty($request->store_address) ? $request->store_address : '';
            $store->no_telepone = !empty($request->no_telepone) ? $request->no_telepone : '';
            if(!empty($request->store_description))
                $store->store_description = $request->store_description;

            insert_log_user($store, $login);

            $store->save();

            if(!empty($metas = $request->meta))
            {
                foreach($metas as $meta_key => $meta_value)
                {
                    $meta = new StoreMeta;
                    $meta->store_id = $store->id;
                    $meta->meta_key = $meta_key;
                    $meta->meta_value = $meta_value;
                    $meta->save();
                }
            }

            \DB::commit();

            $store = Store::with(['metas'])->find($store->id);

            return responses(array('store' => $store),array('message' => 'Store '.$name.' berhasil dibuat.'));
        }
        catch(\Exception $e)
        {
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
            if(!$login->isAbleTo('read-store'))
                return errorCustomStatus(403);

            $store = Store::with(['metas'])->find($id);
            if(empty($store))
                return errorCustomStatus(400,'Store not found.');

            return responses(array('store' => $store));
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
            if(!$login->isAbleTo('update-store'))
                return errorCustomStatus(403);

            $store = Store::find($id);
            if(empty($store))
                return errorCustomStatus(400,'Store #'.$id.' tidak ditemukan.');

            $name = $request->input('store_name');

            if(!empty($name)) 
            {
                $exist = Store::where('store_name',$name)->where('id','!=',$id)->first();
                if(!empty($exist))
                    return errorCustomStatus(400,'Store '.$name.' sudah ada.',array('store' => $exist));

                $store->store_name = $name;
            }

            if(isset($request->company_id))
            {
                if(!empty($request->company_id))
                {
                    $company = Company::find($request->company_id);
                    if(empty($company))
                        return errorCustomStatus(400,'Company ID #'.$request->company_id.' tidak ditemukan.');
                }

                $store->company_id = $request->company_id;
            }

            if(isset($request->store_address))
                $store->store_address = $request->store_address;
            if(isset($request->no_telepone))
                $store->no_telepone = $request->no_telepone;
            if(isset($request->store_description))
                $store->store_description = $request->store_description;
            if($request->input('status') !== null) 
                $store->status = $request->input('status');
            insert_log_user($store, $login);

            $store->save();

            if(!empty($metas = $request->meta))
            {
                foreach($metas as $meta_key => $meta_value)
                {
                    $meta = StoreMeta::where('meta_key',$meta_key)
                            ->where('store_id',$store->id)->first();
                    if(empty($meta))
                    {
                        $meta = new StoreMeta;
                        $meta->store_id = $store->id;
                        $meta->meta_key = $meta_key;
                    }

                    $meta->meta_value = $meta_value;
                    $meta->save();
                }
            }

            \DB::commit();
            $store = Store::with(['metas'])->find($id);

            $history = array(
                'predicate' => 'Update {object}',
                'object' => array(
                    'store'   => $store,
                    'request' => ''
                )
            );
            insertLogAction($login, $history, 'update_store', 'store');
            
            return responses(['store' => $store], ['message' => 'Edit Store '.$name.' berhasil!']);

        }
        catch (\Exception $e) 
        {
            $message = $e->getMessage();
            $developer = $e->getFile().' Line: '.$e->getLine();
            return errorQuery($message,$developer);
        }
    }

    public function destroy($id)
    {
        try
        {
            $login = Auth::user();
            if(!$login->isAbleTo('delete-store'))
                return errorCustomStatus(403);

            $store = Store::find($id);
            if(empty($store))
                return errorCustomStatus(404, 'Store ID #'.$id.' tidak ditemukan!');

            Store::destroy($id);

            $history = array(
                'predicate' => 'Delete {object}',
                'object' => array(
                    'store'   => $store,
                    'request' => ''
                )
            );
            insertLogAction($login, $history, 'update_store', 'store');

            return responses(['store' => $store], ['message' => 'Store '.$store->store_name.' berhasil di delete!']);

        }
        catch (\Exception $e) 
        {

            return errorCustomStatus(404,'Store tidak ditemukan!');
            // return response()->json(['message' => 'user not found!'], 404);
        }
    }
}
