<?php

namespace App\Http\Controllers\User\ACL;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\Permission;

class PermissionController extends Controller
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
                'permissions' => null
            );

            $RoleModel = new Permission;
            $searchables = $RoleModel->getSearchable();

            $page = !empty($request->input('page')) ? ($request->input('page')-1) : 0;
            $per_page = !empty($request->input('per_page')) ? $request->input('per_page') : null;
            $keyword = !empty($request->input('keyword')) ? $request->input('keyword') : null;
            $filters = !empty($request->input('filter')) ? json_decode($request->input('filter')) : null;
            $date_filters = (!empty($request->input('date_filter'))) ? json_decode($request->input('date_filter'), true) : null;
            $sort_by = !empty($request->input('sort_by')) ? $request->input('sort_by') : 'created_at';
            $sort = !empty($request->input('sort')) ? $request->input('sort') : 'desc';

            $permQ = Permission::select('*');

            if(!empty($filters))
            {
                foreach($filters as $where => $in)
                    if(is_array($in))
                        $permQ->whereIn($where,$in);
                    else
                        $permQ->where($where,$in);
            }

            if(!empty($keyword))
            {
                $permQ->where(function($query) use ($keyword, $searchables){
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
                        $permQ->whereBetween($key,[$date['start'], $date['end']]);
                }
            }

            $return['total_records'] = $permQ->count();

            if(!empty($return['total_records']))
            {
                $permQ->orderBy($sort_by,$sort);

                if(!empty($per_page))
                {
                    $permQ->offset(($page*$per_page))
                        ->limit($per_page);
                }

                $permissions = $permQ->get();

                if(!empty($permissions))
                {                    
                    foreach($permissions as $perm)
                    {
                        $perm->display_name = ucwords(str_replace("-"," ",$perm->display_name));
                    }

                    $return['permissions'] = $permissions;
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

    public function groups(Request $request)
    {
        try
        {   
            $login = Auth::user();
            if(!$login->isAbleTo('read-acl'))
                return errorCustomStatus(403);

            $return = array(
                'total_records' => 0,
                'permissions' => null
            );

            $RoleModel = new Permission;
            $searchables = $RoleModel->getSearchable();

            $page = !empty($request->input('page')) ? ($request->input('page')-1) : 0;
            $per_page = !empty($request->input('per_page')) ? $request->input('per_page') : null;
            $keyword = !empty($request->input('keyword')) ? $request->input('keyword') : null;
            $filters = !empty($request->input('filter')) ? json_decode($request->input('filter')) : null;
            $date_filters = (!empty($request->input('date_filter'))) ? json_decode($request->input('date_filter'), true) : null;
            $sort_by = !empty($request->input('sort_by')) ? $request->input('sort_by') : 'name';
            $sort = !empty($request->input('sort')) ? $request->input('sort') : 'asc';

            $permQ = Permission::select('*');

            if(!empty($filters))
            {
                foreach($filters as $where => $in)
                    if(is_array($in))
                        $permQ->whereIn($where,$in);
                    else
                        $permQ->where($where,$in);
            }

            if(!empty($keyword))
            {
                $permQ->where(function($query) use ($keyword, $searchables){
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
                        $permQ->whereBetween($key,[$date['start'], $date['end']]);
                }
            }

            $return['total_records'] = $permQ->count();

            if(!empty($return['total_records']))
            {
                if(!empty($per_page))
                {
                    $permQ->offset($page)
                    ->limit($per_page);
                }

                $permissionQ = $permQ->orderBy($sort_by,$sort)->get();

                if(!empty($permissionQ))
                {
                    $permissions = array();
                    
                    foreach($permissionQ as $perm)
                    {
                        $spl = explode("-",$perm->name);
                        $perm_name = trim(strtoupper(str_replace(array($spl[0],'-'), array('',' '), $perm->name)));
                        if(empty($permissions[$perm_name]))
                        {
                            $permissions[$perm_name][0] = array(
                                'id'            => $perm->id,
                                'name'          => ucfirst($spl[0]),
                                'status'        => $perm->status,
                                'status_label'  => $perm->status_label,
                            );
                        }
                        else
                        {
                            $permissions[$perm_name][] = array(
                                'id'    => $perm->id,
                                'name'  => ucfirst($spl[0]),
                                'status'        => $perm->status,
                                'status_label'  => $perm->status_label,
                            );
                        }
                    }

                    $return['permissions'] = $permissions;
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
                return errorCustomStatus(400,'Nama Permission tidak boleh kosong.');

            $exist = Permission::where('display_name',$name)->first();
            if(!empty($exist))
                return errorCustomStatus(400,'Permission '.$name.' sudah ada.',array('permission' => $exist));

            $new = new Permission;
            $new->name = str_replace(" ","-",strtolower($name));
            $new->display_name = $name;
            $new->status = 1;
            $new->protected = 0;
            $new->save();

            $permission = Permission::find($new->id);
            return responses(array('permission' => $permission),array('message' => 'Permission '.$name.' berhasil dibuat.'));
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
            if(!$login->isAbleTo('read-acl'))
                return errorCustomStatus(403);

            $permission = Permission::find($id);
            if(empty($permission))
                return errorCustomStatus(400,'Permission not found.');

            return responses(array('permission' => $permission));
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

            $permission = Permission::findOrFail($id);

            $name = $request->input('name');
            $exist = Permission::where('display_name',$name)
                    ->where('id','!=',$id)->first();
            if(!empty($exist))
                return errorCustomStatus(400,'Permission '.$name.' sudah ada.',array('permission' => $exist));

            if(!empty($name)) 
            {
                $permission->name = str_replace(" ","-",strtolower($name));
                $permission->display_name = $name;
            }

            if($request->input('status') !== null) 
                $permission->status = $request->input('status');

            $permission->save();
            
            return responses(['permission' => $permission], ['message' => 'Edit Permission berhasil!']);

        }
        catch (\Exception $e) 
        {

            return errorCustomStatus(404,'Permission tidak ditemukan!');
            // return response()->json(['message' => 'user not found!'], 404);
        }
    }

    public function destroy($id)
    {
        try
        {
            $login = Auth::user();
            if(!$login->isAbleTo('delete-acl'))
                return errorCustomStatus(403);

            $permission = Permission::findOrFail($id);
            
            Permission::destroy($id);

            return responses(['permission' => $permission], ['message' => 'Permission '.$permission->display_name.' berhasil di delete!']);

        }
        catch (\Exception $e) 
        {

            return errorCustomStatus(404,'Role tidak ditemukan!');
            // return response()->json(['message' => 'user not found!'], 404);
        }
    }
}
