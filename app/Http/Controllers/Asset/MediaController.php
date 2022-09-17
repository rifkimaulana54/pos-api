<?php

namespace App\Http\Controllers\Asset;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;
use App\Models\Media;

class MediaController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    public $limit = 25;

    public function __construct()
    {
        // $this->middleware('auth');
    }

    public function index(Request $request)
    {
        try
        {
            $login = Auth::user();

            // if(!$login->can('read-crm'))
            //     return errorCustomStatus(403);

            $return = array(
                'total_records' => 0,
                'medias' => null
            );

            $mediaModel = new Media;
            $searchables = $mediaModel->getSearchable();

            $page = !empty($request->input('page')) ? ($request->input('page')-1) : 0;
            $per_page = !empty($request->input('per_page')) ? $request->input('per_page') : null;
            $keyword = !empty($request->input('keyword')) ? $request->input('keyword') : null;
            $filters = !empty($request->input('filter')) ? json_decode($request->input('filter')) : null;
            $filter_nots = !empty($request->input('filter_not')) ? json_decode($request->input('filter_not')) : null;
            $date_filters = (!empty($request->input('date_filter'))) ? json_decode($request->input('date_filter'), true) : null;
            $sort_by = !empty($request->input('sort_by')) ? $request->input('sort_by') : 'created_at';
            $sort = !empty($request->input('sort')) ? $request->input('sort') : 'desc';

            $mediaQ = Media::select('*');

            if(!empty($filters))
            {
                foreach($filters as $where => $in)
                {
                    if(is_array($in))
                        $mediaQ->whereIn($where,$in);
                    else
                        $mediaQ->where($where,$in);
                }
            }

            if(!empty($filter_nots))
            {
                foreach($filter_nots as $where => $not_in)
                {
                    if(is_array($not_in))
                        $mediaQ->whereNotIn($where,$not_in);
                    else
                        $mediaQ->where($where, '!=', $not_in);
                }
            }

            if(!empty($keyword))
            {
                $mediaQ->where(function($query) use ($keyword, $searchables){
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
                        $mediaQ->whereBetween($key,[$date['start'], $date['end']]);
                }
            }

            $return['total_records'] = $mediaQ->count();

            if(!empty($return['total_records']))
            {
                if(!empty($per_page))
                {
                    $mediaQ->offset(($page*$per_page))
                            ->limit($per_page);
                }

                $medias = $mediaQ->orderBy($sort_by,$sort)
                            // ->offset($page)
                            // ->paginate($per_page);
                            ->get();

               if(!empty($medias))
               {
                    foreach($medias as $media)
                    {
                        $media->media_path = cdn().$media->media_path;

                        switch($media->status)
                        {
                            case 2:
                                $media->status_label = 'Inactive';
                                break;
                            case 0:
                                $media->status_label = 'Archived';
                                break;
                            default:
                                $media->status_label = 'Active';
                                break;
                        }
                    }

                    $return['medias'] = $medias;
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

    public function store(Request $request)
    {
        try
        {
            $login = Auth::user();
            if(empty($login))
            {
                $login = collect();
                $login->id = 1;
                $login->fullname = 'Superadmin';
            }

            // echo "<pre>";
            // print_r(public_path('uploads'));
            // echo "</pre>";
            // die();

            // if(!$login->can('read-crm'))
            //     return errorCustomStatus(403);

            $this->validate($request, [
                'file'      => 'required',
                'service'   => 'required'
            ]);

            $service = $request->service;
            $curdate = date('Ymd');
            $cury = date('Y');
            $curm = date('m');
            $curd = date('d');
            $path = "/uploads/".$service."/assets/".$cury."/".$curm."/".$curd;
            
            if(!File::isDirectory(public_path('uploads')))
                File::makeDirectory(public_path('uploads'));
            if(!File::isDirectory(public_path('uploads/'.$service)))
                File::makeDirectory(public_path('uploads/'.$service));

            if(!File::isDirectory(public_path('uploads/'.$service.'/assets/')))
                File::makeDirectory(public_path('uploads/'.$service.'/assets/'));
            if(!File::isDirectory(public_path('uploads/'.$service.'/assets/'.$cury)))
                File::makeDirectory(public_path('uploads/'.$service.'/assets/'.$cury));
            if(!File::isDirectory(public_path('uploads/'.$service.'/assets/'.$cury.'/'.$curm)))
                File::makeDirectory(public_path('uploads/'.$service.'/assets/'.$cury.'/'.$curm));
            if(!File::isDirectory(public_path('uploads/'.$service.'/assets/'.$cury.'/'.$curm.'/'.$curd)))
                File::makeDirectory(public_path('uploads/'.$service.'/assets/'.$cury.'/'.$curm.'/'.$curd));

            if($request->hasfile('file'))
            {
                $file = $request->file('file');
                $fileNameWithExt = $file->getClientOriginalName();
                $filename = pathinfo($fileNameWithExt, PATHINFO_FILENAME);
                $fileExt = $file->getClientOriginalExtension();

                $fileNameToStore = time().rand().'_file_.'.$fileExt;

                $file->move(public_path($path), $fileNameToStore);
                //$path2 = $request->file('file')->storeAs('public/'.$path,$fileNameToStore);

                $savePath = $path.'/'.$fileNameToStore;

                $media = new media;
                $media->user_id = $login->id;
                $media->user_name = $login->fullname;
                $media->media_path = $savePath;
                $media->media_original_name = $fileNameWithExt;
                $media->media_type = $request->file('file')->getClientMimeType();
                $media->service = $service;
                
                if($request->caption)
                    $media->media_caption = $request->caption;

                $media->save();

                $media->media_path = cdn().$media->media_path;

                $history = array(
                    'predicate' => 'Create New {object}',
                    'object' => array('media' => $media)
                );
                insertLogAction($login,$history,'create_media',$media->service);

                return responses(array('media' => $media),array('message' => 'Media berhasil di buat.'));
            }
        }
        catch(\Exception $e)
        {
            $message = $e->getMessage();
            $developer = $e->getFile().' Line: '.$e->getLine();
            return errorQuery($message,$developer);
        }
    }

    public function show(Request $request, $id)
    {
        try
        {            
            // if(!userCan($request,'read-product'))
            //     return errorCustomStatus(403);

            $media = Media::find($id);
            if(empty($media))
                return errorCustomStatus(400,'Media #'.$id.' not found.');

            $media->media_path = cdn().$media->media_path;

            return responses(array('media' => $media));
        }
        catch(\Exception $e)
        {
            $message = $e->getMessage();
            $developer = $e->getFile().' Line: '.$e->getLine();
            return errorQuery($message,$developer);
        }
    }
    
    public function destroy(Request $request, $id)
    {
        try
        {
            $login = Auth::user();
            if(empty($login))
            {
                $login = collect();
                $login->id = 1;
                $login->fullname = 'Superadmin';
            }

           // if(!userCan($request,'delete-product'))
           //      return errorCustomStatus(403);

            $media = Media::findOrFail($id);
            
            Media::destroy($id);

            $history = array(
                'predicate' => 'Delete {object}',
                'object' => array('media' => $media)
            );
            insertLogAction($login,$history,'delete_media',$media->service);

            return responses(['media' => $media], ['message' => 'Media #'.$id.' berhasil di delete!']);

        }
        catch (\Exception $e) 
        {
            return errorCustomStatus(404,'Media tidak ditemukan!');
            // return response()->json(['message' => 'user not found!'], 404);
        }
    }
}
