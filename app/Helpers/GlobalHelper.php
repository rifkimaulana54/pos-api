<?php

use App\Mail\Mailer;
use App\Models\LogHistory;
use Illuminate\Support\Facades\Mail;

if(!function_exists('responses')) 
{
    function responses($data = null, $custom = array())
    {
        $status_code = 200;
        $results = array(
            'code' => $status_code,
            'data' => !empty($data) ? $data : null,
            'spent'    => microtime(true) - LUMEN_START
        );

        if (!empty($custom))
            $results = array_merge($results, $custom);

        return response()->json($results, $status_code);
    }
}

if(!function_exists('errorCustomStatus')) 
{

    function errorCustomStatus($status, $message = '', $custom = array())
    {
        $results = array(
            'code' => $status,
            'message' => $message,
            'spent'    => microtime(true) - LUMEN_START
        );

        switch ($status) {
            case 400:
                if (empty($message))
                    $results['message'] = "Server tidak dapat mengenali permintaan anda. Silakan cek kembali URL yang ingin dituju!";
                break;
            case 401:
                if (empty($message))
                    $results['message'] = "Token expired.";
                break;
            case 403:
                if (empty($message))
                    $results['message'] = "Anda tidak memiliki izin untuk mengakses halaman ini!";
                break;
            case 404:
                if (empty($message))
                    $results['message'] = "Halaman tidak ditemukan!";
                break;
            case 405:
                if (empty($message))
                    $results['message'] = "Terjadi kesalahan dalam mengakses URL ini!";
                break;
            case 408:
                if (empty($message))
                    $results['message'] = "Waktu memuat server telah habis!";
                break;
            case 415:
                if (empty($message))
                    $results['message'] = "Jenis file yang anda unggah tidak didukung/diizinkan oleh server!";
                break;
            case 422:
                if (empty($message))
                    $results['message'] = "Terjadi kesalahan dalam menginput data. Silahkan anda cek kembali!";
                break;
            case 499:
                if (empty($message))
                    $results['message'] = "Koneksi ditutup secara mendadak saat server memproses permintaan!";
                break;
            case 503:
                if (empty($message))
                    $results['message'] = "Layanan server tidak tersedia untuk saat ini!";
                break;
            case 504:
                if (empty($message))
                    $results['message'] = "Server sedang sibuk!";
                break;
            default:
                if (empty($message))
                    $results['message'] = "Terjadi kesalahan silahkan hubungi Administrator.";
                break;
        }

        if (!empty($custom)) {
            $results = array_merge($results, $custom);
        }

        return response()->json($results)->setStatusCode($status);
    }
}

if(!function_exists('handleResponses')) 
{

    function handleResponses($status, $message)
    {
        $results = [];
        $results['error'] = true;
        $results['code'] = $status;
        $results['message'] = $message;

        return response()->json($results)->setStatusCode(200);
    }
}


if(!function_exists('errorQuery')) 
{

    function errorQuery($message, $developer = '', $severity = '')
    {
        $results = array(
            'code' => 500,
            'message' => $message,
            'spent'    => microtime(true) - LUMEN_START
        );

        if (!empty($developer))
            $results['developer'] = $developer;

        if (!empty($severity))
            $results['severity'] = $severity;

        return response()->json($results)->setStatusCode(500);
    }
}


if(!function_exists('insert_log_user'))
{
    function insert_log_user($model, $login, $update=0)
    {
        if(empty($update))
        {
            $model->created_id = $login->id;
            $model->created_name = $login->fullname;
        }
        $model->updated_id = $login->id;
        $model->updated_name = $login->fullname;
    }
}

if(!function_exists('insertLogAction'))
{
    function insertLogAction($user,$data=array(),$type='',$service='')
    {
        $request = request();
        if(!empty($data))
        {
            $history = new LogHistory;
            $history->user_name = $user->fullname;
            $history->ip_address = $request->ip();
            $history->action = json_encode($data);
            if(!empty($type)) $history->action_type = $type;
            if(!empty($service)) $history->service = $service;
            $history->save();
        }
    }
}

if(!function_exists('sendEmail'))
{
    function sendEmail($tos, $mail, $attachments, $blade, $datas, $metas, $login)
    {
        $sendmail = Mail::to($tos);
        if(!empty($metas['cc']))
            $sendmail->cc($metas['cc']);

        // $menus = !empty($request->menus) ? $request->menus : array();

        // dd($menus);
        $sendmail->send(new Mailer($mail->subject, $mail->body, $attachments, $blade, $datas));
        $history = array(
                'predicate' => 'Send New {object}',
                'object' => array('email' => $mail)
        );

        insertLogAction($login,$history,'send_new_email','messaging');
    }
}


if (!function_exists('maybe_unserialize'))
{
    function maybe_unserialize( $original, $array = true ) 
    {
        // if ( self::is_serialized( $original ) ) // don't attempt to unserialize data that wasn't serialized going in
        if(@json_decode($original))
            return @json_decode( $original, $array );

        return $original;
    }
}

if (!function_exists('maybe_serialize'))
{
    function maybe_serialize( $data ) 
    {
        if ( is_array( $data ) || is_object( $data ) )
            // return serialize( $data );
            return json_encode($data);

        // Double serialization is required for backward compatibility.
        // See https://core.trac.wordpress.org/ticket/12930
        // Also the world will end. See WP 3.6.1.
        // if ( self::is_serialized( $data, false ) )
        //     return serialize( $data );

        return $data;
    }
}

if (!function_exists('cdn'))
{
    function cdn()
    {
        $cdn_url = env('CDN_USER',get_url());
        if(env('BRANCH') && (env('APP_ENV') == 'development' || env('APP_ENV') == 'testing'))
            $cdn_url .= env('BRANCH').'/';
        
        return $cdn_url;
    }
}

if (!function_exists('get_url')) {

    function get_url()
    {
        $url = env('APP_URL');
        return $url;
    }
}
