<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;
use App\Models\UserMeta;
use App\Models\Role;
// use App\Models\Company;
use App\UserAddress;
use App\Models\MappingDeskUser;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
   /**
    * Instantiate a new UserController instance.
    *
    * @return void
    */

   public $limit = 25;

   public function __construct(Request $request)
   {
      $this->middleware('auth');
   }

   public function profile()
   {
      try
      {
         $user = Auth::user();

         if((!$user->isAbleTo('read-profile')) || ($user->hasRole(['user']) && $user->status == 2) )
               return errorCustomStatus(403);

         // if($user->hasRole(['user','customer']) && $user->status == 2)
         //     return errorCustomStatus(401,'Akun anda sudah tidak aktif, Silahkan hubungi administrator.');

         $roles = array_column(json_decode($user->roles), 'name');
         unset($user->roles);
         $locations = $user->locations;

         $user_metas = $user->metas;
         $user->acls = array_column(json_decode($user->allPermissions()), 'name');

         $user_company = $user->company->metas;
         $user_companies = $user->company;
         // $user_carts = $user_orders->carts;

         return responses(['user' => $user]);
      }
      catch(\Exception $e)
      {
         $message = $e->getMessage();
         $developer = $e->getFile().' Line: '.$e->getLine();
         return errorQuery($message,$developer);
      }
   }

   public function allUsers(Request $request)
   {
      try
      {
         $login = Auth::user();

         if(!$login->isAbleTo('read-users'))
               return errorCustomStatus(403);

         $return = array(
            'total_records' => 0,
            'users' => null
         );

         $UserModel = new User;
         $searchables = $UserModel->getSearchable();

         $page = !empty($request->input('page')) ? ($request->input('page')-1) : 0;
         $per_page = !empty($request->input('per_page')) ? $request->input('per_page') : null;
         $keyword = !empty($request->input('keyword')) ? $request->input('keyword') : null;
         $filters = !empty($request->input('filter')) ? json_decode($request->input('filter')) : null;
         $filter_nots = !empty($request->input('filter_not')) ? json_decode($request->input('filter_not')) : null;
         $date_filters = (!empty($request->input('date_filter'))) ? json_decode($request->input('date_filter'), true) : null;
         $sort_by = !empty($request->input('sort_by')) ? $request->input('sort_by') : 'created_at';
         $sort = !empty($request->input('sort')) ? $request->input('sort') : 'desc';

         $userQ = User::select('user_tm_users.*','roles.display_name as role_name')
                  ->join('role_user','role_user.user_id','=','user_tm_users.id')
                  ->join('roles','role_user.role_id','=','roles.id');

         switch ($sort_by) 
         {
            case 'role_name':
               $sort_by = 'roles.name';
               break;
            default:
               $sort_by = 'user_tm_users.'.$sort_by;
               break;
         }

         $location_filters = [];
         if(!empty($filters))
         {
            foreach($filters as $where => $in)
            {
               // $continue = true;

               // if(strpos($where,'location_') !== FALSE)
               // {
               //    $location_filters[$where] = $in;
               //    $continue = false;
               // }
               // else
               // {
               switch ($where) 
               {
                  case 'role_name':
                     $where = 'roles.name';
                     break;
                  default:
                     $where = 'user_tm_users.'.$where;
                     break;
               }
               // }

               // if($continue)
               // {
                  if(is_array($in))
                        $userQ->whereIn($where,$in);
                  else
                        $userQ->where($where,$in);
               // }
            }
         }

         if(!empty($filter_nots))
         {
            foreach($filter_nots as $where => $not_in)
            {
               switch ($where) 
               {
                  case 'role_name':
                        $where = 'roles.name';
                        break;
                  default:
                        $where = 'user_tm_users.'.$where;
                        break;
               }

               if(is_array($not_in))
                  $userQ->whereNotIn($where,$not_in);
               else
                  $userQ->where($where,'!=',$not_in);
            }
         }

         if(!empty($keyword))
         {
            $userQ->where(function($query) use ($keyword, $searchables){
               foreach($searchables as $src){
                  $query->orWhere('user_tm_users.'.$src, 'LIKE', '%'.$keyword.'%');
               } 
            });
         }

         if(!empty($date_filters))
         {
            foreach($date_filters as $key => $date)
            {
               if(!empty($date['start']) && !empty($date['end']))
               {
                  switch ($key) 
                  {
                     default:
                        $key = 'user_tm_users.'.$key;
                        break;
                  }
                  $userQ->whereBetween($key,[$date['start'], $date['end']]);
               }
            }
         }

         // if(!empty($location_filters))
         // {
         //    $userQ->whereHas('locations', function (Builder $queryw) use ($location_filters) 
         //    {
         //       foreach($location_filters as $where => $in)
         //       {
         //          // $where = str_replace('tax_','',$where);
         //          // DB::connection()->enableQueryLog();
         //          if($where == 'location_id') $where = 'id';
         //          // $where = 'product_tm_warehouses.'.$where;
                  
         //          if(is_array($in))
         //                $queryw->whereIn($where,$in);
         //          else
         //                $queryw->where($where,$in);
         //       }
         //    });
         // }

         $return['total_records'] = $userQ->count();
         if(!empty($return['total_records']))
         {
            $userQ->groupBy('user_tm_users.id')->orderBy($sort_by,$sort);

            if(!empty($per_page))
               $userQ->offset(($page*$per_page))->limit($per_page);

            $users = $userQ->with(['metas','roles', 'company'])
                           ->get();

            if(!empty($users))
            {
               foreach($users as $user)
               {
                  $roles = array_column($user->toArray()['roles'], 'name');
                  unset($user->roles);
                  $user->roles = $roles;
               }

               $return['users'] = $users;
            }
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

   public function singleUser($id)
   {
      try 
      {
         $login = Auth::user();
         // dd($login->is);
         if(!$login->isAbleTo('read-users'))
               return errorCustomStatus(403);

         $user = User::with(['metas','roles'])->find($id);
         if(empty($user))
            return errorCustomStatus(404, 'User ID #'.$id.' tidak ditemukan');
         $user->acls = array_column(json_decode($user->allPermissions()), 'name');
         // dd($user->roles->toArray());
         $roles = array_column($user->roles->toArray(), 'name');
         unset($user->roles);
         $user->roles = $roles;
         // $user->jurnal_access_token->access_token = $user->jurnal_access_token->access_token;

         return responses(['user' => $user]);

      } 
      catch (\Exception $e) 
      {
         $message = $e->getMessage();
         $developer = $e->getFile().' Line: '.$e->getLine();
         return errorQuery($message,$developer);
      }

   }

   public function updateUser(Request $request, $id)
   {
      try
      {            
         $login = Auth::user();
         if(!$login->isAbleTo(['update-users', 'update-profile']))
               return errorCustomStatus(403);

         $user = User::find($id);
         if(empty($user))
            return errorCustomStatus(404, 'User ID #'.$id.' tidak ditemukan');
         $current_status = $user->status;

         // if(!$login->hasRole(['superadmin','admin']))
         // {
         //     if($user->id != $login->id)
         //         return errorCustomStatus(400,'ID #'.$id.' tidak sesuai dengan login Anda. Hubungi Administrator');
         // }

         \DB::beginTransaction();
         if(!empty($request->email) || !empty($request->phone))
         {
            $phone = !empty($request->phone) ? $request->phone : $user->phone;
            $email = !empty($request->email) ? $request->email : $user->email;

            $cekUser = User::select('*')
                        ->where(function($query) use($phone, $email)
                        {
                           if(!empty($phone) && !empty($email) && !empty($login_code))
                           {
                              $query->where('phone',$phone)
                                    ->orWhere('email',$email);
                           }
                           elseif(!empty($phone))
                              $query->where('phone',$phone);
                           elseif(!empty($email))
                              $query->where('email',$email);

                        })
                        ->where('id','!=',$id)->first();

            if(!empty($cekUser))
               return errorCustomStatus(400,'User dengan Email '.$email.' / Phone '.$phone.' sudah ada.',array('user' => $cekUser));

            $user->email = $email;
            $user->phone = $phone;
         }
         
         // if(!empty($location_ids = $request->location_ids))
         // {
         //    $location_ids = maybe_unserialize($location_ids);
         //    $locations = Location::whereIn('id', $location_ids)->where('status', 1)->get()->toArray();
         //    $locations = array_column($locations, 'id');
         //    foreach ($location_ids as $id) 
         //       if(!in_array($id, $locations))
         //          return errorCustomStatus(400,'Location #'.$id.' tidak ditemukan.');
         // }
         // elseif(isset($request->location_id))
         //    $location_ids = [];

         if(!empty($request->company_id))
         {
            $company = Company::find($request->company_id);
            if(empty($company))
               return errorCustomStatus(400,'Company #'.$request->company_id.' tidak ditemukan.');
            $user->company_id = $request->company_id;
         }

         $user_current_metas = $user->metas;   
         if(!empty($user_current_metas))
         {
            $new_meta = [];
            foreach($user_current_metas as $met)
               $new_meta[$met->meta_key] = $met->meta_value;        
         }

         if(!empty($request->input('fullname'))) 
            $user->fullname = $request->input('fullname');
         // if(!empty($request->old_customer_code)) 
         //     $user->old_customer_code = $request->old_customer_code;

         if(!empty($request->type))
            $user->type = $request->type;
         
         if($request->input('status') !== null) 
            $user->status = $request->input('status');

         if(!empty($plainPassword = $request->input('password')))
            $user->password = app('hash')->make($plainPassword);

         insert_log_user($user, $login, 1);

         if(!empty($metas = $request->meta))
         {
            foreach($metas as $meta_key => $meta_value)
            {
               $meta = UserMeta::where('meta_key',$meta_key)
                        ->where('user_id',$user->id)->first();
               if(empty($meta))
               {
                  $meta = new UserMeta;
                  $meta->user_id = $user->id;
                  $meta->meta_key = $meta_key;
               }

               $meta->meta_value = $meta_value;
               $meta->save();
            }
         }

         // dd($user->toArray());

         $user->save();

         \DB::commit();

         if(!empty($role = $request->input('role')))
         {
            // if(!empty($company->id))
            //    $user->syncRoles([$role], $company->id);
            // else
               $user->syncRoles([$role]);
         }

         // if(!empty($location_ids))
         //    $user->locations()->sync($location_ids);
         
         $user = User::find($user->id);

         $roles = array_column(json_decode($user->roles), 'name');
         unset($user->roles);
         $user->roles = $roles;
         // $user_devices = $user->devices;
         $history = array(
            'predicate' => 'Update {object}',
            'object' => array(
               'user'   => $user,
               'request' => $request->all()
            )
         );
         // dd($jurnal_category->toArray());
         insertLogAction($login, $history, 'update_user', 'user');

         return responses(['user' => $user], ['message' => 'Edit User berhasil!']);

      }
      catch(\Exception $e)
      {
         \DB::rollback();
         $message = $e->getMessage();
         $developer = $e->getFile().' Line: '.$e->getLine();
         return errorQuery($message,$developer);
      }
   }

   public function deleteUser($id)
   {
      try
      {
         $login = Auth::user();
         if(!$login->isAbleTo('delete-users'))
               return errorCustomStatus(403);

         $user = User::findOrFail($id);
         
         User::destroy($id);

         $history = array(
            'predicate' => 'Delete {object}',
            'object' => array(
               'user'   => $user
            )
         );

         insertLogAction($login,$history,'delete_user', 'user');

         return responses(['user' => $user], ['message' => 'User '.$user->fullname.' berhasil di delete!']);

      }
      catch (\Exception $e) 
      {
         return errorCustomStatus(404,'User tidak ditemukan!');
         // return response()->json(['message' => 'user not found!'], 404);
      }
   }

   public function import(Request $request)
   {
      try {
         $login = Auth::user();
         if(!$login->isAbleTo('create-users'))
               return errorCustomStatus(403);

         if(empty($request->users))
               return errorCustomStatus(400,'Param [users] tidak boleh kosong.');

         $users_decode = json_decode($request->users);
         
         $exist_users = User::whereIn('email', array_column($users_decode, 'email'))->orWhereIn('phone', array_column($users_decode, 'phone'))->orWhereIn('login_code', array_column($users_decode, 'login_code'))->whereRoleIs(['customer'])->get()->toArray();
         $user_roles = Role::get()->toArray();
         $user_roles = array_column($user_roles, 'name', 'id');
         $exist_users_email = array_column($exist_users, 'email', 'id');
         $exist_users_phone = array_column($exist_users, 'phone', 'id');
         $exist_users_login_code = array_column($exist_users, 'phone', 'id');
         $maxQ = User::max('customer_number');
         $max_number = $maxQ+1;
         $customer = User::whereRoleIs(['customer'])->get()->toArray();
         $cust_codes = array_column($customer, 'customer_code');
         $old_cust_codes = array_column($customer, 'old_customer_code');
         $return['error'] = array();
         $e = 1;

         foreach ($users_decode as $user_dec) 
         {
               if(in_array($user_dec->email, $exist_users_email))
               {
                  $return['error'][$e][] = "Email sudah ada di database";
                  $e++;
                  continue;
               }

               if(in_array($user_dec->phone, $exist_users_phone))
               {
                  $return['error'][$e][] = "Phone sudah ada di database";
                  $e++;
                  continue;
               }

               if(in_array($user_dec->login_code, $exist_users_login_code))
               {
                  $return['error'][$e][] = "Login code sudah ada di database";
                  $e++;
                  continue;
               }
               if(!empty($user_dec->old_customer_code) && in_array($user_dec->old_customer_code, $old_cust_codes))
               {
                  // $return['error'][$e][] = "Old Customer Code sudah ada di database";
                  // $e++;
                  continue;
               }
               \DB::beginTransaction();
               $user = new User;
               $user->fullname = $user_dec->fullname;
               $user->email = $user_dec->email;
               if(!empty($user_dec->phone))
                  $user->phone = $user_dec->phone;
               if(!empty($user_dec->login_code))
                  $user->login_code = $user_dec->login_code;

               $user->password = app('hash')->make($user_dec->password);
               $user->status = 1;
               if(!empty($request->company_id)) $user->company_id = $request->company_id;
               $user->created_id = $login->id;
               $user->created_name = $login->fullname;
               $user->updated_id = $login->id;
               $user->updated_name = $login->fullname;
               if($user_dec->role == 'customer')
               {
                  $user->customer_number = $max_number;
                  $user->customer_code = 'CERP'.str_pad($max_number,10,'0',STR_PAD_LEFT);
                  if(!empty($user_dec->old_customer_code))
                     $user->old_customer_code = $user_dec->old_customer_code;

                  $max_number++;
               }
               try
               {
                  $user->save();
               }
               catch(\Exception $i)
               {
                  \DB::rollback();
                  $return['error'][$e][] = "User Gagal disimpan";
               }
               if(!empty($user_dec->role) && !empty(array_search($user_dec->role, $user_roles)))
               {
                  $user->syncRoles([array_search($user_dec->role, $user_roles)]);
                  if($user_dec->role == 'customer')
                     addJurnalUser($user, $login);
               }
               else
                  $user->syncRoles([array_search('user', $user_roles)]);

               if(!empty($user_dec->addresses))
               {
                  $user_add = $user_dec->addresses;
                  $address = new UserAddress;
                  $address->user_id = $user->id;
                  $address->address_name = $user_add->address_name;
                  $address->address = $user_add->address;
                  if(!empty($user_add->province_id)) $address->province_id = $user_add->province_id;
                  $address->province_name = $user_add->province_name;
                  if(!empty($user_add->city_id)) $address->city_id = $user_add->city_id;
                  $address->city_name = $user_add->city_name;
                  $address->kecamatan = $user_add->kecamatan;
                  if(!empty($user_add->kelurahan)) $address->kelurahan = $user_add->kelurahan;
                  $address->negara = !empty($user_add->negara) ? $user_add->negara : 'Indonesia';
                  $address->postal_code = $user_add->postal_code;
                  $address->default = 1;
                  $address->created_id = $login->id;
                  $address->created_name = $login->fullname;
                  $address->updated_id = $login->id;
                  $address->updated_name = $login->fullname;
                  try
                  {
                     $address->save();
                  }
                  catch(\Exception $i)
                  {
                     \DB::rollback();
                     $return['error'][$e][] = "User Address Gagal disimpan";
                  }
               }

               if(!empty($user_dec->meta))
               {
                  foreach ($user_dec->meta as $meta_key => $meta_value) 
                  {
                     $meta = new UserMeta;
                     $meta->user_id = $user->id;
                     $meta->meta_key = $meta_key;
                     $meta->meta_value = $meta_value;
                     $meta->created_id = $login->id;
                     $meta->created_name = $login->fullname;
                     $meta->updated_id = $login->id;
                     $meta->updated_name = $login->fullname;
                     try
                     {
                           $meta->save();
                     }
                     catch(\Exception $i)
                     {
                           \DB::rollback();
                           $return['error'][$e][] = "User Gagal disimpan";
                     }
                     
                     if($meta_key == 'order_amount')
                           update_user_level($request, $meta, $user);
                  }
               }
               \DB::commit();
               $e++;
         }

         if(!empty($return['error']) && $e == count($return['error'])+1)
            $msg = 'Semua data gagal diimport.';
         elseif(!empty($return['error']))
            $msg = 'Sebagian data gagal diimport.';
         else
            $msg = 'Semua data berhasil diimport.';

         return responses($return,array('message' => $msg));

      } catch (\Exception $e) {
         $message = $e->getMessage();
         $developer = $e->getFile().' Line: '.$e->getLine();
         return errorQuery($message,$developer);
      }
   }
}
