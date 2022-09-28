<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Location;
use App\Models\Role;
use App\Models\Store;
use Illuminate\Http\Request;

use App\Models\User;
use App\Models\UserMeta;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
   /**
    * Create a new controller instance.
    *
    * @return void
    */
   
   /**
    * Store a new user.
    *
    * @param  Request  $request
    * @return Response
    */
   public function register(Request $request)
   {
      try 
      {
         $login = Auth::user();
         if(empty($login))
         {
            $login = collect([]);
            $login->id = 0;
            $login->fullname = 'New User';
         }

         $check_user = array(
            'name',
             // 'phone',
             // 'email'
         );

         // if(empty($request->roles))
         //     $check_user[] = 'phone';

         // dd($request->all());
         foreach ($check_user as $check) 
         {
            if(empty($request->$check))
               return errorCustomStatus(400,'Param ['.$check.'] tidak boleh kosong.');
         }

         // if(!empty($store_ids = $request->store_ids))
         // {
         //       $stores = Store::whereIn('id', $store_ids)->where('status', 1)->get()->toArray();
         //       $stores = array_column($stores, 'id');
         //       if(!empty($stores))
         //       {
         //          foreach ($request->store_ids as $id) 
         //             if(!in_array($id, $stores))
         //                   return errorCustomStatus(400,'Warehouse #'.$id.' tidak ditemukan.');
         //       }
         // }

         if(!empty($request->password))
         {
            $plainPassword = $request->input('password');
            if($plainPassword != $request->password_confirmation)
               return errorCustomStatus(400,'Param Password tidak sesuai dengan password_confirmation.');
         }
         else
         {
            $plainPassword = '12345678';
         }

         if(!empty($roles_user = $request->input('role')))
         {
            $user_role = Role::where('id', $roles_user)->where('status', 1)->first();
            if(empty($user_role))
               return errorCustomStatus(400,'Role #'.$roles_user.' tidak ditemukan.');
         }

         $existQ = User::select('*');
         if(!empty($request->email) || $request->phone)
         {
            $existQ->where(function($query) use ($request)
            {
               if(!empty($request->email))
                  $query->orWhere('email', $request->email);
               if(!empty($request->phone))
                  $query->orWhere('phone', $request->phone);
            });
         }
         $exist = $existQ->first();

         if(!empty($exist))
         {
            $exmsg = '';
            if(!empty($request->email) && $request->email == $exist->email) 
               $exmsg .= 'Email '.$request->email;
            if(!empty($request->phone) && $request->phone == $exist->phone)
            {
               if(!empty($exmsg)) $exmsg .= ' & ';
               $exmsg .= 'Phone number '.$request->phone;
            }
            $exmsg .= ' already registered. Please try again.';
            return errorCustomStatus(406,$exmsg,array('user' => $exist));
         }

         if(!empty($request->login_code))
         {
            $existCode = User::where('login_code',$request->login_code)->first();
            if(!empty($existCode))
            {
               return errorCustomStatus(406,'Login Code ['.$request->login_code.'] already exist, please choose another login_code.',array('user' => $existCode));
            }
         }

         $wait = 0;
         // dump('generate_gi_code:');
         // dump(Cache::get('generate_gi_code'));
         while(!empty(Cache::get('generate_user_code')))
         {
            $wait++;
         }
         // dump($wait);
         Cache::put('generate_user_code',1,600);

         \DB::beginTransaction();

         $maxQ = User::max('user_number');
         $max_number = !empty($maxQ) ? ($maxQ+1) : 1;
         $user = new User;

         $user->user_number = $max_number;
         try 
         {
            $user->save();
         } 
         catch (\Exception $e) 
         {
            $message = $e->getMessage();
            $developer = $e->getFile().' Line: '.$e->getLine();
            Log::error($e->getMessage().': '.$e->getFile().' Line: '.$e->getLine());
            return errorQuery($message,$developer);
         }

         \DB::commit();
         Cache::forget('generate_user_code');

         \DB::beginTransaction();

         $user->fullname = ucwords($request->name);
         if(!empty($request->email))
            $user->email = $request->email;
         if(!empty($request->phone))
            $user->phone = $request->phone;
         if(!empty($request->store_id))
            $user->store_id = $request->store_id;

         $user->password = app('hash')->make($plainPassword);
         if(!empty($request->type))
            $user->type = $request->type;
         if(!empty($request->picture))
            $user->picture = $request->picture;


         $user->status = !empty($request->status) ? $request->status : 0;

         insert_log_user($user, $login);

         // dd($user->toArray());

         try 
         {
            $user->save();
         } 
         catch (\Exception $e) 
         {
            \DB::rollback();
            if(!empty($user->id))
               User::destroy($user->id);
            $message = $e->getMessage();
            $developer = $e->getFile().' Line: '.$e->getLine();
            Log::error($e->getMessage().': '.$e->getFile().' Line: '.$e->getLine());
            return errorQuery($message,$developer);
         }

         // if (!empty($store_ids))
         //    $user->stores()->sync($store_ids);
            

         if(!empty($role = $request->input('role')))
         {
            if(!empty($company_id))
               $user->syncRoles([$role], $company_id);
            else
               $user->syncRoles([$role]);
         }

         if(!empty($metas = $request->meta))
         {
            foreach($metas as $meta_key => $meta_value)
            {
               if(!empty($meta_value))
               {
                  $meta = new UserMeta;
                  $meta->user_id = $user->id;
                  $meta->meta_key = $meta_key;
                  $meta->meta_value = $meta_value;
                  try 
                  {
                     $meta->save();
                  } 
                  catch (\Exception $e) 
                  {
                     \DB::rollback();
                     if(!empty($user->id))
                        User::destroy($user->id);
                     $message = $e->getMessage();
                     $developer = $e->getFile().' Line: '.$e->getLine();
                     Log::error($e->getMessage().': '.$e->getFile().' Line: '.$e->getLine());
                     return errorQuery($message,$developer);
                  }
               }
            }

            $user_metas = $user->metas;
         }

         \DB::commit();

         $user = User::find($user->id);

         $roles = array_column(json_decode($user->roles), 'name');
         unset($user->roles);
         $user->roles = $roles;

         $history = array(
            'predicate' => 'Send New {object}',
            'object' => array('user' => $user, 'request' => $request->all())
         );

         insertLogAction($login,$history,'create_user','user');

         //return successful response
         return responses(['user' => $user], ['message' => 'User '.$user->fullname.' registered successfully.']);

      } 
      catch (\Exception $e) 
      {
         \DB::rollback();
         if(!empty($user->id))
            User::destroy($user->id);
         $message = $e->getMessage();
         $developer = $e->getFile().' Line: '.$e->getLine();
         Log::error($e->getMessage().': '.$e->getFile().' Line: '.$e->getLine());
         return errorQuery($message,$developer);
      }
   }

   /**
    * Get a JWT via given credentials.
    *
    * @param  Request  $request
    * @return Response
    */
   public function login(Request $request)
   {
         //validate incoming request 
      $this->validate($request, [
         // 'email' => 'required|string',
         'password' => 'required|string',
      ]);

      $username = !empty($request->phone) ? 'phone' : 'email';

      $company_id = !empty($request->company_id) ? $request->company_id : 1;
      $company = Company::find($company_id);
      if(empty($company))
         return errorCustomStatus(403,"Company #".$company_id. ' not found.'); 

      $user = User::where($username,$request->$username)->first();
      if(empty($user))
         return errorCustomStatus(403,"Email not found. Please enter a valid email.");

      // $user->last_login = date('Y-m-d H:i:s');
      $user->save();

      if(in_array($user->status, [0,2]))
         return errorCustomStatus(403);

      $credentials = $request->only([$username, 'password']);

      if(!empty($request->expired_in))
         $jwt_ttl = $request->expired_in;

      if(!empty($request->remember))
      {
         // config([
         //     'flysystem.connections.'.$driver.'.driver' => $driver,
         //     'flysystem.connections.'.$driver.'.host' => $rep_service->service_url,
         //     'flysystem.connections.'.$driver.'.port' => $rep_service->metas['port'],
         //     'flysystem.connections.'.$driver.'.username' => $rep_service->service_username,
         //     'flysystem.connections.'.$driver.'.password' => $rep_service->service_password,
         // ]);

         // if(!empty($rep_service->metas['path']))
         //     config(['flysystem.connections.'.$driver.'.root' => $rep_service->metas['path']]);
         config([
               'jwt.ttl' => !empty($jwt_ttl) ? $jwt_ttl : null,
               'jwt.required_claims' => [
                  'iss',
                  'iat',
                  // 'exp',
                  'nbf',
                  'sub',
                  'jti'
               ],
         ]);
         //     $jwt_config = config('jwt');
         //     echo '<pre>';
         //     print_r($jwt_config);
         //     echo '</pre>';
         //     die();
      }

      $claims['ip'] = $request->ip();
      // if(!empty($request->header('jp-device')))
      //     $claims['aud'] = $request->header('jp-device');

      if (! $token = Auth::claims($claims)->attempt($credentials)) 
      {
         return errorCustomStatus(403,'Your username or password is incorrect. Please try again.');
         // return response()->json(['message' => 'Unauthorized'], 401);
      }

      return $this->respondWithToken($token);
   }

   public function loginCode(Request $request)
   {
         //validate incoming request 
      try
      {
         $this->validate($request, [
               'code' => 'required|string',
               // 'social'    => 'required|string',
               // 'social_id' => 'required|string',
         ]);

         $user = User::with('metas')->where('login_code',$request->code)->first();
         if(empty($user))
            return errorCustomStatus(403,"Login code not found. Please enter a valid login code.");

         if($user->status == 2)
               return errorCustomStatus(403);

         if(!empty($request->expired_in))
               $jwt_ttl = $request->expired_in;

         if(!empty($request->remember))
         {
               // config([
               //     'flysystem.connections.'.$driver.'.driver' => $driver,
               //     'flysystem.connections.'.$driver.'.host' => $rep_service->service_url,
               //     'flysystem.connections.'.$driver.'.port' => $rep_service->metas['port'],
               //     'flysystem.connections.'.$driver.'.username' => $rep_service->service_username,
               //     'flysystem.connections.'.$driver.'.password' => $rep_service->service_password,
               // ]);

               // if(!empty($rep_service->metas['path']))
               //     config(['flysystem.connections.'.$driver.'.root' => $rep_service->metas['path']]);
               config([
                  'jwt.ttl' => !empty($jwt_ttl) ? $jwt_ttl : null,
                  'jwt.required_claims' => [
                     'iss',
                     'iat',
                     // 'exp',
                     'nbf',
                     'sub',
                     'jti'
                  ],
               ]);
               //     $jwt_config = config('jwt');
               //     echo '<pre>';
               //     print_r($jwt_config);
               //     echo '</pre>';
               //     die();
         }

         $claims['ip'] = $request->ip();
         // if(!empty($request->header('jp-device')))
         //     $claims['aud'] = $request->header('jp-device');

         if (! $token = Auth::claims($claims)->login($user,$request->remember)) 
            return errorCustomStatus(400,'Login code tidak sesuai.');

         return $this->respondWithToken($token);
      }
      catch(\Exception $e)
      {
         $message = $e->getMessage();
         $developer = $e->getFile().' Line: '.$e->getLine();
         return errorQuery($message,$developer);
      }
   }

   public function forgotPassword(Request $request)
   {
      $requireds = array(
         'email',
         '_token',
      );

      foreach($requireds as $req)
      {
         if(empty($request->$req))
            return errorCustomStatus(400,'Param '.$req.' tidak boleh kosong.');
      }

      if(is_numeric($request->email))
         $field = 'phone';
      else
         $field = 'email';
      
      $user = User::where($field,$request->email)->first();
      if(empty($user))
         return errorCustomStatus(400,'Email/phone tidak ditemukan.');


      $user->reset_token = $request->_token;
      $user->save();

      $login = $user;
      
      // $reset_url = env('FCM_URL').'?apn='.env('APN_CUSTOMER').'&link='.urlencode(env('DASHBOARD_URL').'password/resets?_token='.$user->reset_token.'&email='.$user->email);
      // if(!empty($request->apn))
      //     $reset_url .= '&apn='.$request->apn;

      $reset_url = env('DASHBOARD_URL').'password/reset/'.$user->reset_token.'?email='.$user->email;

      $mail = [
         'recipient' => $user->email,
         'subject'   => 'Tugas Akhir - Reset Password Confirmation',
         'body'      => 'We have received your request to change your password. If you did not make this request, please contact our customer support.',
         'type_id'   => 1,
         'meta'      => [
            'template'  => 'forgot-password',
            'data' => //maybe_serialize(
            [
               'header'    => 'Password Reset Confirmation',
               'fullname'  => $user->fullname,
               'reset_msg'   => 'We have received your request to change your password. If you did not make this request, please contact our customer support.',
               'reset_url'  => $reset_url
            ] //)
         ]
      ];

      $login = collect([]);
      $login->id = 0;
      $login->fullname = 'forgot_password';
      try 
      {
         sendEmail($mail['recipient'], json_decode(json_encode($mail)), [], $mail['meta']['template'], $mail['meta']['data'], $mail['meta'], $login);
      } 
      catch (\Exception $e) 
      {
         $message = $e->getMessage();
         $developer = $e->getFile().' Line: '.$e->getLine();
         Log::error($e->getMessage().': '.$e->getFile().' Line: '.$e->getLine());
         return errorQuery($message,$developer);
      }

      // $request->request->add($mail);


      // $mailController = new MailController;
      // $mailDecode = json_decode(json_encode($mailController->store($request)))->original;
      // // echo 'code = '.$mailDecode->code.'<br/>';
      // // dd($mailDecode);
      // if(!empty($mailDecode->code) && $mailDecode->code != 200)
      //    return errorCustomStatus(400,$mailDecode->message);

      // $notif = array(
      //     'user_id'           => $user->id,
      //     // 'notif_cc'          => $order->agent_id,
      //     'notif_type'        => 'user_reset_password',
      //     'notif_variables'   => array(
      //         'user' => $user->name,
      //         'reset_url' => '<a href="'.$reset_url .'" title="reset password">'.$reset_url .'</a>',
      //         'reset_url_sms' => $reset_url
      //     ),
      //     // 'notif_attachments' => $attachments
      // );

      // // dd($notif);

      // NotificationHelper::addNotif($login, $notif, ['email','phone']);

      // $postParam = array(
      //     'endpoint'  => 'types',
      //     'form_params' => array(
      //         'filter'  => json_encode(array('type_name' => array('otp','account')))
      //     ),
      //     'headers' => [ 'Authorization' => 'Bearer '.$this->jwt_token ]
      // );

      // $typeApi = MessagingApiHelper::postData( $postParam );
      // $dataDecode = json_decode($typeApi);

      // if((!empty($dataDecode->code) && $dataDecode->code != 200) || (!empty($dataDecode->data) && empty($dataDecode->data->total_records)))
      //     return errorCustomStatus(400,'Type Message OTP/Account tidak ditemukan', json_decode($typeApi,true));
      
      // $types = array_column($dataDecode->data->types,'id','type_name');

      // if(empty($types['account']) && empty($types['otp']))
      //     return errorCustomStatus(400,'Type Message OTP/Account tidak ditemukan', json_decode($typeApi,true));

      // $email = true;
      // $sms = true;

      // // $url = env('DASHBOARD_URL', 'http://127.0.0.1:8000/').'password/resets?_token='.$user->reset_token.'&email='.$user->email;
      

      // if(!empty($user['email']) && !empty($types['account']))
      // {
      //     $mailParam = array(
      //         'endpoint' => 'mail/store',
      //         'form_params' => array(
      //             'type_id'   => $types['account'],
      //             'recipient' => $user['email'],
      //             'subject'   => 'Reset Password Request',
      //             'body'      => '<h2>Hi '.$user['name'].',</h2>
      //                             <p>Kami telah menerimakan permintaan reset password dari Anda</p>
      //                             <p>Silahkan klik tautan berikut untuk melanjutkan reset password:</p>
      //                             <p><a href="'.$url.'" title="reset password">'.$url.'</a></p>
      //                             <br>
      //                             <p>Jika Anda tidak merasa meminta reset password, silahkan abaikan email ini.</p>',
      //         ),
      //         'headers'   => array(
      //             'Authorization' => 'Bearer '.$this->jwt_token
      //         )
      //     );

      //     $mailApi = MessagingApiHelper::postData($mailParam);
      //     $mailDecode = json_decode($mailApi);

      //     if(!empty($mailDecode->code) && $mailDecode->code != 200)
      //         $email = true;
      //     else
      //         $email = false;

      // }

      // if(!empty($user['phone']) && !empty($types['otp']))
      // {
      //     $msg_body = 'Berikut adalah link untuk reset password anda: '.$url;
      //     $smsParam = array(
      //         'endpoint'  => 'sms/store',
      //         'form_params' => array(
      //             'type_id' => $types['otp'],
      //             'recipient' => '62'.$user['phone'],
      //             // 'subject' => 'Aktivasi User Peruri',
      //             'body' => $msg_body,
      //         ),
      //         'headers' => [ 'Authorization' => 'Bearer '.$this->jwt_token ]
      //     );

      //     $smsApi = MessagingApiHelper::postData( $smsParam );
      //     $smsDecode = json_decode($smsApi);

      //     if((!empty($smsDecode->code) && $smsDecode->code != 200))
      //         $sms = true;
      //     else
      //         $sms = false;
      // }

      // if($email && $sms)
      //     return errorCustomStatus(400,'Kirim Password Reset Link Gagal.');

      return responses(array('user' => $user),array('message' => 'Tautan Reset Password berhasil dikirim.'));

   }

   public function updatePassword(Request $request)
   {
      try
      {
         $requireds = array(
            'email',
            '_token',
            'password',
            'password_confirmation'
         );

         foreach($requireds as $req)
         {
            if(empty($request->$req))
               return errorCustomStatus(400,'Param '.$req.' tidak boleh kosong.');
         }

         $users = User::where('email',$request->email)->where('reset_token',$request->_token)->first();
         if(empty($users))
            return errorCustomStatus(400,'User tidak ditemukan.');

         if(!empty($plainPassword = $request->input('password')))
         {
            if($plainPassword == $request->password_confirmation)
               $users->password = app('hash')->make($plainPassword);
            else
               return errorCustomStatus(400,'Password tidak sesuai.');
         }

         $users->reset_token = null;
         $users->save();

         return responses(array('status' => true),array('message' => 'Reset Password berhasil.'));
      }
      catch(\Exception $e)
      {
         $message = $e->getMessage();
         $developer = $e->getFile().' Line: '.$e->getLine();
         return errorQuery($message,$developer);
      }
   }
}
