<?php

namespace App\Http\Controllers\User\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use App\Models\Company;
use App\Models\CompanyMeta;

class CompanyController extends Controller
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
            if(!$login->isAbleTo('read-company'))
                return errorCustomStatus(403);

            $return = array(
                'total_records' => 0,
                'companies' => null
            );

            $companyModel = new Company;
            $searchables = $companyModel->getSearchable();

            $page = !empty($request->input('page')) ? ($request->input('page')-1) : 0;
            $per_page = !empty($request->input('per_page')) ? $request->input('per_page') : null;
            $keyword = !empty($request->input('keyword')) ? $request->input('keyword') : null;
            $filters = !empty($request->input('filter')) ? json_decode($request->input('filter')) : null;
            $date_filters = (!empty($request->input('date_filter'))) ? json_decode($request->input('date_filter'), true) : null;
            $sort_by = !empty($request->input('sort_by')) ? $request->input('sort_by') : 'created_at';
            $sort = !empty($request->input('sort')) ? $request->input('sort') : 'desc';

            $companyQ = Company::select('*');

            if(!empty($filters))
            {
                foreach($filters as $where => $in)
                    if(is_array($in))
                        $companyQ->whereIn($where,$in);
                    else
                        $companyQ->where($where,$in);
            }

            if(!empty($keyword))
            {
                $companyQ->where(function($query) use ($keyword, $searchables){
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
                        $companyQ->whereBetween($key,[$date['start'], $date['end']]);
                }
            }

            $return['total_records'] = $companyQ->count();

            if(!empty($return['total_records']))
            {
                $companyQ->orderBy($sort_by,$sort);

                if(!empty($per_page))
                {
                    $companyQ->offset(($page*$per_page))
                        ->limit($per_page);
                }

                $companies = $companyQ->with(['metas'])->get();

                if(!empty($companies))
                    $return['companies'] = $companies;
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
        // dd(Auth::user());
        try
        {
            $login = Auth::user();
            if(!$login->isAbleTo('create-company'))
                return errorCustomStatus(403);

            $name = $request->input('company_name');
            if(empty($name))
                return errorCustomStatus(400,'Nama Company tidak boleh kosong.');

            $exist = Company::where('name',$name)->first();
            if(!empty($exist))
                return errorCustomStatus(400,'Company '.$name.' sudah ada.',array('brand' => $exist));

            if(!empty($request->parent_id))
            {
                $parent = Company::find($request->parent_id);
                if(empty($parent))
                    return errorCustomStatus(400,'Parent Company #'.$request->parent_id.' tidak ditemukan.');
            }

            \DB::beginTransaction();

            $company = new Company;
            if(!empty($request->parent_id)) 
                $company->parent_id = $request->parent_id;
            $company->name = str_replace(" ", "-", strtolower($name));
            $company->display_name = $name;
            // if(!empty($request->description))
            //     $company->description = $request->description;

            insert_log_user($company, $login);

            $company->save();

            if(!empty($metas = $request->meta))
            {
                foreach($metas as $meta_key => $meta_value)
                {
                    $meta = new CompanyMeta;
                    $meta->company_id = $company->id;
                    $meta->meta_key = $meta_key;
                    $meta->meta_value = $meta_value;
                    $meta->save();
                }
            }

            \DB::commit();
            $company = Company::with(['metas'])->find($company->id);

            $history = array(
                'predicate' => 'Create {object}',
                'object' => array(
                    'company'   => $company,
                    'request' => ''
                )
            );
            insertLogAction($login, $history, 'update_company', 'company');

            return responses(array('company' => $company),array('message' => 'Company '.$name.' berhasil dibuat.'));
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
            if(!$login->isAbleTo('read-company'))
                return errorCustomStatus(403);

            $company = Company::with(['metas'])->find($id);
            if(empty($company))
                return errorCustomStatus(400,'Company not found.');

            return responses(array('company' => $company));
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
            if(!$login->isAbleTo('update-company'))
                return errorCustomStatus(403);

            $company = Company::find($id);
            if(empty($company))
                return errorCustomStatus(400,'Company #'.$id.' tidak ditemukan.');

            $name = $request->input('company_name');

            if(!empty($name)) 
            {
                $exist = Company::where('name',$name)->where('id','!=',$id)->first();
                if(!empty($exist))
                    return errorCustomStatus(400,'Company '.$name.' sudah ada.',array('company' => $exist));

                $company->name = Str::slug($name);
                $company->display_name = $name;
            }

            if(isset($request->parent_id))
            {
                if(!empty($request->parent_id))
                {
                    $parent = Company::find($request->parent_id);
                    if(empty($parent))
                        return errorCustomStatus(400,'Parent Company #'.$request->parent_id.' tidak ditemukan.');
                }
                $company->parent_id = $request->parent_id;
            }

            // if(isset($request->description))
            //     $company->description = $request->description;
            if($request->input('status') !== null) 
                $company->status = $request->input('status');

            insert_log_user($company, $login);

            $company->save();

            if(!empty($metas = $request->meta))
            {
                foreach($metas as $meta_key => $meta_value)
                {
                    $meta = CompanyMeta::where('meta_key',$meta_key)
                            ->where('company_id',$company->id)->first();
                    if(empty($meta))
                    {
                        $meta = new CompanyMeta;
                        $meta->company_id = $company->id;
                        $meta->meta_key = $meta_key;
                    }

                    $meta->meta_value = $meta_value;
                    $meta->save();
                }
            }

            \DB::commit();
            $company = Company::with(['metas'])->find($id);

            $history = array(
                'predicate' => 'Update {object}',
                'object' => array(
                    'company'   => $company,
                    'request' => ''
                )
            );
            insertLogAction($login, $history, 'update_company', 'company');
            
            return responses(['company' => $company], ['message' => 'Edit Company '.$name.' berhasil!']);

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
            if(!$login->isAbleTo('delete-company'))
                return errorCustomStatus(403);

            $company = Company::find($id);
            if(empty($company))
                return errorCustomStatus(404, 'Company ID #' . $id . ' tidak ditemukan!');
            
            Company::destroy($id);

            $history = array(
                'predicate' => 'Delete {object}',
                'object' => array(
                    'company'   => $company,
                    'request' => ''
                )
            );
            insertLogAction($login, $history, 'update_company', 'company');

            return responses(['company' => $company], ['message' => 'Company '.$company->name.' berhasil di delete!']);

        }
        catch (\Exception $e) 
        {

            return errorCustomStatus(404,'Company tidak ditemukan!');
            // return response()->json(['message' => 'user not found!'], 404);
        }
    }
}
