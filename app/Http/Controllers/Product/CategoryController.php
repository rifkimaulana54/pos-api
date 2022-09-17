<?php

namespace App\Http\Controllers\Product;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Company;

class CategoryController extends Controller
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
            if(!$login->isAbleTo('read-category'))
                return errorCustomStatus(403);

            $return = array(
                'total_records' => 0,
                'categories' => null
            );

            $CategoryModel = new Category;
            $searchables = $CategoryModel->getSearchable();

            $page = !empty($request->input('page')) ? ($request->input('page')-1) : 0;
            $per_page = !empty($request->input('per_page')) ? $request->input('per_page') : null;
            $keyword = !empty($request->input('keyword')) ? $request->input('keyword') : null;
            $filters = !empty($request->input('filter')) ? json_decode($request->input('filter')) : null;
            $date_filters = (!empty($request->input('date_filter'))) ? json_decode($request->input('date_filter'), true) : null;
            $sort_by = !empty($request->input('sort_by')) ? $request->input('sort_by') : 'created_at';
            $sort = !empty($request->input('sort')) ? $request->input('sort') : 'desc';

            $categoryQ = Category::select('*');

            if(!empty($filters))
            {
                foreach($filters as $where => $in)
                    if(is_array($in))
                        $categoryQ->whereIn($where,$in);
                    else
                        $categoryQ->where($where,$in);
            }

            if(!empty($keyword))
            {
                $categoryQ->where(function($query) use ($keyword, $searchables){
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
                        $categoryQ->whereBetween($key,[$date['start'], $date['end']]);
                }
            }

            $return['total_records'] = $categoryQ->count();

            if(!empty($return['total_records']))
            {
                $categoryQ->orderBy($sort_by,$sort);

                if(!empty($per_page))
                {
                    $categoryQ->offset(($page*$per_page))
                        ->limit($per_page);
                }

                $categories = $categoryQ->with(['company'])->get();

                if(!empty($categories))
                    $return['categories'] = $categories;
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
            if(!$login->isAbleTo('create-category'))
                return errorCustomStatus(403);

            $name = $request->input('category_name');
            if(empty($name))
                return errorCustomStatus(400,'Nama Category tidak boleh kosong.');

            $exist = Category::where('category_display_name',$name)->first();
            if(!empty($exist))
                return errorCustomStatus(400,'Category '.$name.' sudah ada.',array('category' => $exist));

            $company_id = 1;
            if(!empty($request->company_id))
            {
                $company = Company::find($request->company_id);
                if(empty($company))
                    return errorCustomStatus(400,'Company #'.$request->company_id.' tidak ditemukan');

                $company_id = $company->id;
            }

            if(!empty($request->parent_id))
            {
                $exist_parent = Category::find($request->parent_id);
                if(empty($exist_parent))
                    return errorCustomStatus(400,'Kategori #'.$request->parent_id.' tidak ditemukan.');
            }

            \DB::beginTransaction();

            $new_category = new Category;
            $new_category->company_id = $company_id;
            $new_category->category_name = str_replace(" ","-",strtolower($name));
            $new_category->category_display_name = $name;
            if(!empty($request->category_description))
                $new_category->category_description = $request->category_description;
            if(!empty($request->parent_id))
                $new_category->parent_id = $request->parent_id;
            insert_log_user($new_category, $login);
            
            try 
            {
                $new_category->save();
            } 
            catch (\Exception $e) 
            {
                \DB::rollback();
                $message = $e->getMessage();
                $developer = $e->getFile().' Line: '.$e->getLine();
                Log::error($e->getMessage().': '.$e->getFile().' Line: '.$e->getLine());
                return errorQuery($message,$developer);
            }

            \DB::commit();

            $category = Category::with(['company'])->find($new_category->id);

            $history = array(
                'predicate' => 'Create {object}',
                'object' => array(
                    'category'   => $category,
                    'request' => $request->all()
                )
            );
            // dd($jurnal_category->toArray());
            insertLogAction($login, $history, 'create_category', 'category');

            return responses(array('category' => $category),array('message' => 'Category '.$name.' berhasil dibuat.'));
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
            if(!$login->isAbleTo('read-category'))
                return errorCustomStatus(403);

            $category = Category::with(['company'])->find($id);
            if(empty($category))
                return errorCustomStatus(400,'Category ID #'.$id.' tidak ditemukan.');

            return responses(array('category' => $category));
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
            if(!$login->isAbleTo('update-category'))
                return errorCustomStatus(403);

            $category = Category::findOrFail($id);

            $name = $request->input('category_name');
            if(!empty($name))
            {
                // return errorCustomStatus(400,'Nama Kategori tidak boleh kosong.');
                $exist = Category::where('category_name',$name)->where('id','!=',$id)->where('company_id', $category->company_id)->first();
                if(!empty($exist))
                    return errorCustomStatus(400,'Kategori '.$name.' sudah ada.',array('category' => $exist));

                $category->category_name = !empty($request->slug) ? $request->slug : Str::slug($name);
                $category->category_display_name = $name;
            }

            if(!empty($request->parent_id))
            {
                $exist_parent = Category::find($request->parent_id);
                if(empty($exist_parent))
                    return errorCustomStatus(400,'Kategori #'.$request->parent_id.' tidak ditemukan.');
            }

            if(!empty($request->description))
                $category->description = $request->description;
        
            if($request->input('status') !== null) 
                $category->status = $request->input('status');

            $category->company_id = !empty($request->company_id) ? $request->company_id : 1;

            try 
            {
                $category->save();
            } 
            catch (\Exception $e) 
            {
                \DB::rollback();
                $message = $e->getMessage();
                $developer = $e->getFile().' Line: '.$e->getLine();
                Log::error($e->getMessage().': '.$e->getFile().' Line: '.$e->getLine());
                return errorQuery($message,$developer);
            }

            \DB::commit();

            $history = array(
                'predicate' => 'Update {object}',
                'object' => array(
                    'category'   => $category,
                    'request' => $request->all()
                )
            );
            insertLogAction($login, $history, 'update_category', 'category');
            
            return responses(['category' => $category], ['message' => 'Edit Category berhasil!']);

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
            if(!$login->isAbleTo('delete-category'))
                return errorCustomStatus(403);

            $category = Category::findOrFail($id);
            
            Category::destroy($id);

            $history = array(
                'predicate' => 'Delete {object}',
                'object' => array(
                    'category'   => $category,
                    'request' => ''
                )
            );
            insertLogAction($login, $history, 'update_category', 'category');

            return responses(['category' => $category], ['message' => 'Category '.$category->display_name.' berhasil di delete!']);

        }
        catch (\Exception $e) 
        {

            return errorCustomStatus(404,'Category tidak ditemukan!');
            // return response()->json(['message' => 'user not found!'], 404);
        }
    }
}
