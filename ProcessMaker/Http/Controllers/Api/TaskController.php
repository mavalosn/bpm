<?php
namespace ProcessMaker\Http\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\Task as Resource;
use ProcessMaker\Http\Resources\TaskCollection;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Notifications\TaskReassignmentNotification;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $query = ProcessRequestToken
            ::join('process_requests as request', 'request.id', '=', 'process_request_tokens.process_request_id')
            ->join('users as user', 'user.id', '=', 'process_request_tokens.user_id')
            ->select('process_request_tokens.*');
        $include  = $request->input('include') ? explode(',',$request->input('include')) : [];
        $query->with($include);
        $filter = $request->input('filter', '');
        if (!empty($filter)) {
            $filter = '%' . $filter . '%';
            $query->where(function ($query) use ($filter) {
                $query->Where('element_name', 'like', $filter)
                    ->orWhere('process_request_tokens.status', 'like', $filter)
                    ->orWhere('request.name', 'like', $filter)
                    ->orWhere('user.firstname', 'like', $filter)
                    ->orWhere('user.lastname', 'like', $filter);
            });
        }
        $filterByFields = ['process_id', 'user_id', 'process_request_tokens.status' => 'status', 'element_id', 'element_name', 'process_request_id'];
        $parameters = $request->all();
        foreach ($parameters as $column => $filter) {
            if (in_array($column, $filterByFields)) {
                $key = array_search($column, $filterByFields);
                $query->where(is_string($key) ? $key : $column, 'like', $filter);
            }
        }
        //list only display elements type task
        $query->where('element_type', '=', 'task');
        $query->orderBy(
            $request->input('order_by', 'updated_at'), $request->input('order_direction', 'asc')
        );

        // only show tasks that the user is assigned to
        $query->where('process_request_tokens.user_id', Auth::user()->id);

        $inOverdueQuery = ProcessRequestToken::where('user_id', Auth::user()->id)
            ->where('status', 'ACTIVE')
            ->where('due_at', '<', Carbon::now());

        $inOverdue = $inOverdueQuery->count();

        $response = $query->get();

        $response = $response->filter(function($processRequestToken) {
            return Auth::user()->can('view', $processRequestToken);
        })->values();

        $response->inOverdue = $inOverdue;

        return new TaskCollection($response);
    }

    /**
     * Display the specified resource.
     *
     * @param ProcessRequestToken $task
     *
     * @return Resource
     */
    public function show(ProcessRequestToken $task)
    {
        return new Resource($task);
    }

    /**
     * Updates the current element
     *
     * @param Request $request
     * @param ProcessRequestToken $task
     *
     * @return Resource
     * @throws \Throwable
     */
    public function update(Request $request, ProcessRequestToken $task)
    {
        $this->authorize('update', $task);
        if ($request->input('status') === 'COMPLETED') {
            if ($task->status === 'CLOSED') {
                return abort(422, __('Task already closed'));
            }
            $data = $request->input();
            //Call the manager to trigger the start event
            $process = $task->process;
            $instance = $task->processRequest;
            WorkflowManager::completeTask($process, $instance, $task, $data);
            return new Resource($task->refresh());
        } elseif (!empty($request->input('user_id'))) {
            // Validate if user can reassign
            $task->authorizeReassignment(Auth::user());

            // Reassign user
            $task->user_id = $request->input('user_id');
            $task->save();
            
            // Send a notification to the user
            $notification = new TaskReassignmentNotification($task);
            $task->user->notify($notification);
            return new Resource($task->refresh());
        } else {
            return abort(422);
        }
    }
}
