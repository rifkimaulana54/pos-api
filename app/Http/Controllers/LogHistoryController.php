<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\User;
use App\UserMeta;
use App\Helpers\GeneralApiHelper;

use Illuminate\Support\Facades\Auth;
use App\Models\LogHistory;

class LogHistoryController extends Controller
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

    /**
     * Get all User.
     *
     * @return Response
     */
    public function index(Request $request)
    {
        try
        {
            $login = Auth::user();

            // if(!$login->can('read-branch-agency'))
            //     return errorCustomStatus(403);

            $return = array(
                'total_records' => 0,
                'log_histories' => null
            );

            $logModel = new LogHistory;
            $searchables = $logModel->getSearchable();

            $page = !empty($request->input('page')) ? ($request->input('page')-1) : 0;
            $per_page = !empty($request->input('per_page')) ? $request->input('per_page') : null;
            $keyword = !empty($request->input('keyword')) ? $request->input('keyword') : null;
            $user_id = !empty($request->input('user_id')) ? $request->input('user_id') : null;
            $filters = !empty($request->input('filter')) ? json_decode($request->input('filter')) : null;
            $date_filters = (!empty($request->input('date_filter'))) ? json_decode($request->input('date_filter'), true) : null;
            $sort_by = !empty($request->input('sort_by')) ? $request->input('sort_by') : 'created_at';
            $sort = !empty($request->input('sort')) ? $request->input('sort') : 'desc';

            $logHistoryQ = LogHistory::select('*');

            if(!empty($filters))
            {
                foreach($filters as $where => $in)
                {
                    if(is_array($in))
                        $logHistoryQ->whereIn($where,$in);
                    else
                        $logHistoryQ->where($where,$in);
                }
            }

            if(!empty($keyword))
            {
                $logHistoryQ->where(function($query) use ($keyword, $searchables){
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
                        $logHistoryQ->whereBetween($key,[$date['start'], $date['end']]);
                }
            }

            $return['total_records'] = $logHistoryQ->count();

            if(!empty($return['total_records']))
            {
                if(!empty($per_page))
                {
                    $logHistoryQ->offset(($page*$per_page))
                            ->limit($per_page);
                }

                $logHistories = $logHistoryQ->orderBy($sort_by,$sort)
                            // ->offset($page)
                            // ->paginate($per_page);
                            ->get();

               if(!empty($logHistories))
               {
                    foreach($logHistories as $history)
                    {
                        $history->action = maybe_unserialize($history->action);
                        $return['log_histories'][] = $history;
                    }
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

    public function filter(Request $request)
    {
        try
        {
            $login = Auth::user();

            $return = array(
                'total_records' => 0,
                'filters' => null
            );

            $historyQ = logHistory::select('*');

            if($historyQ->count())
            {
                $return['filters']['user_names'] = $historyQ->select('user_name')
                                        ->orderBy('user_name')
                                        ->groupBy('user_name')
                                        ->pluck('user_name');

                $historyQ->getQuery()->groups = [];
                $historyQ->getQuery()->orders = [];

                $return['filters']['action_type'] = $historyQ->select('action_type')
                                        ->orderBy('action_type')
                                        ->groupBy('action_type')
                                        ->pluck('action_type');

                $historyQ->getQuery()->groups = [];
                $historyQ->getQuery()->orders = [];

                $return['filters']['ip_address'] = $historyQ->select('ip_address')
                                        ->orderBy('ip_address')
                                        ->groupBy('ip_address')
                                        ->pluck('ip_address');

                $historyQ->getQuery()->groups = [];
                $historyQ->getQuery()->orders = [];

                $return['filters']['service'] = $historyQ->select('service')
                                        ->orderBy('service')
                                        ->groupBy('service')
                                        ->pluck('service');

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
}
