<?php

namespace App\Http\Controllers\User\ACL;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\Role;

use function PHPUnit\Framework\returnSelf;

class RoleController extends Controller
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
            if(!$login->isAbleTo('read-acl'))
                return errorCustomStatus(403);

            $return = array(
                'total_records' => 0,
                'roles' => null
            );

            $RoleModel = new Role;
            $searchables = $RoleModel->getSearchable();

            $page = !empty($request->input('page')) ? ($request->input('page')-1) : 0;
            $per_page = !empty($request->input('per_page')) ? $request->input('per_page') : null;
            $keyword = !empty($request->input('keyword')) ? $request->input('keyword') : null;
            $filters = !empty($request->input('filter')) ? json_decode($request->input('filter')) : null;
            $date_filters = (!empty($request->input('date_filter'))) ? json_decode($request->input('date_filter'), true) : null;
            $sort_by = !empty($request->input('sort_by')) ? $request->input('sort_by') : 'created_at';
            $sort = !empty($request->input('sort')) ? $request->input('sort') : 'desc';

            $roleQ = Role::select('*');

            if(!empty($filters))
            {
                foreach($filters as $where => $in)
                    if(is_array($in))
                        $roleQ->whereIn($where,$in);
                    else
                        $roleQ->where($where,$in);
            }

            if(!empty($keyword))
            {
                $roleQ->where(function($query) use ($keyword, $searchables){
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
                        $roleQ->whereBetween($key,[$date['start'], $date['end']]);
                }
            }

            $return['total_records'] = $roleQ->count();

            if(!empty($return['total_records']))
            {
                $roleQ->orderBy($sort_by,$sort);

                if(!empty($per_page))
                {
                    $roleQ->offset(($page*$per_page))
                        ->limit($per_page);
                }

                $roles = $roleQ->get();

                if(!empty($roles))
                {
                    foreach($roles as $role)
                    {
                        $permissions = array_column(json_decode($role->permissions), 'id');
                        unset($role->permissions);
                        $role->permissions = $permissions;
                        switch($role->status)
                        {
                            case 2:
                                $role->status_label = 'Inactive';
                                break;
                            case 0:
                                $role->status_label = 'Archived';
                                break;
                            default:
                                $role->status_label = 'Active';
                                break;
                        }
                    }

                    $return['roles'] = $roles;
                }
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
            if(!$login->isAbleTo('create-acl'))
                return errorCustomStatus(403);

            $name = $request->input('name');
            if(empty($name))
                return errorCustomStatus(400,'Nama Role tidak boleh kosong.');

            $exist = Role::where('display_name',$name)->first();
            if(!empty($exist))
                return errorCustomStatus(400,'Role '.$name.' sudah ada.',array('role' => $exist));

            \DB::beginTransaction();
            $permissions = $request->input('permissions');

            $new = new Role;
            $new->name = str_replace(" ","-",strtolower($name));
            $new->display_name = $name;
            // $new->status = 1;
            try 
            {
                $new->save();
            } 
            catch (\Exception $e) 
            {
                \DB::rollback();
                $message = $e->getMessage();
                $developer = $e->getFile().' Line: '.$e->getLine();
                Log::error($e->getMessage().': '.$e->getFile().' Line: '.$e->getLine());
                return errorQuery($message,$developer);
            }

            $role = Role::find($new->id);
            if(!empty($permissions = maybe_unserialize($permissions)))
            {
                try 
                {
                    $role->syncPermissions($permissions);
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

            \DB::commit();

            $role_permissions = $role->permissions;

            return responses(array('role' => $role),array('message' => 'Role '.$name.' berhasil dibuat.'));
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
            if(!$login->isAbleTo('read-acl'))
                return errorCustomStatus(403);

            $role = Role::find($id);
            if(empty($role))
                return errorCustomStatus(400,'Role ID #'.$id.' tidak ditemukan.');

            $permissions = array_column(json_decode($role->permissions), 'id');
            unset($role->permissions);
            $role->permissions = $permissions;

            return responses(array('role' => $role));
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
            if(!$login->isAbleTo('update-acl'))
                return errorCustomStatus(403);

            $role = Role::findOrFail($id);
            if(empty($role))
                return errorCustomStatus(404, 'Role ID #'.$id.' tidak ditemukan!');
            $name = $request->input('name');
            $exist = Role::where('display_name',$name)
                    ->where('id','!=',$id)->first();
            if(!empty($exist))
                return errorCustomStatus(400,'Role '.$name.' sudah ada.',array('role' => $exist));
            \DB::beginTransaction();
            if(!empty($name)) 
            {
                $role->name = str_replace(" ","-",strtolower($name));
                $role->display_name = $name;
            }
            if($request->input('status') !== null) 
                $role->status = $request->input('status');

            try 
            {
                $role->save();
            } 
            catch (\Exception $e) 
            {
                \DB::rollback();
                $message = $e->getMessage();
                $developer = $e->getFile().' Line: '.$e->getLine();
                Log::error($e->getMessage().': '.$e->getFile().' Line: '.$e->getLine());
                return errorQuery($message,$developer);
            }

            if(!empty($permissions = maybe_unserialize($request->input('permissions'))))
            {
                try 
                {
                    $role->syncPermissions($permissions);
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

            \DB::commit();
            
            return responses(['role' => $role], ['message' => 'Edit Role berhasil!']);

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
            if(!$login->isAbleTo('delete-acl'))
                return errorCustomStatus(403);

            $role = Role::findOrFail($id);
            
            Role::destroy($id);

            return responses(['role' => $role], ['message' => 'Role '.$role->display_name.' berhasil di delete!']);

        }
        catch (\Exception $e) 
        {

            return errorCustomStatus(404,'Role tidak ditemukan!');
            // return response()->json(['message' => 'user not found!'], 404);
        }
    }
}
