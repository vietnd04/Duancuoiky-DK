<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Task;
use App\Models\Member;
use App\Models\TaskMember;
use App\Models\User;
use App\Models\Notification;
use App\Http\Controllers\NotificationController;

class TaskController extends Controller
{
    public function createTask(Request $request){

     return DB::transaction(function() use ($request){
        $fields = $request->all();

        $errors = Validator::make($fields, [
            'name' => 'required',
            'projectId' => 'required|numeric',
            'memberIds' => 'required|array',
            'memberIds.*'=>'numeric'
        ]);

        if ($errors->fails()) {
            return response($errors->errors()->all(), 422);
        }

        $task=Task::create([
            'projectId' => $fields['projectId'],
            'name' => $fields['name'],
            'status' => Task::NOT_STARTED,
        ]);

        $members=$fields['memberIds'];
        for($i=0; $i<count($members);$i++){
            $memberId = $members[$i];
            
            TaskMember::create([
                'projectId' => $fields['projectId'],
                'taskId' => $task->id,
                'memberId' => $memberId
            ]);
            
            // Kiểm tra xem memberId có phải là user hay không
            $user = User::where('id', $memberId)->first();
            if ($user) {
                // Tạo thông báo cho user
                NotificationController::createNotification(
                    $user->id,
                    $task->id,
                    'Công việc mới được giao',
                    'Bạn được giao công việc: ' . $task->name,
                    'task_assigned'
                );
            }
        }

        return response(['message'=>'Task created successfully !']);
     });


    }


    public function TaskToNotStartedToPending(Request $request){
       
        Task::changeTaskStatus($request->taskId,Task::PENDING);
        Task::handleProjectProgress($request->projectId);
        
        $this->notifyTaskStatusChange($request->taskId, 'đang xử lý');
        
        return response(['message'=>'task move to pending'],200);
    }
    
    public function TaskToPendingToCompleted(Request $request){
        Task::changeTaskStatus($request->taskId,Task::COMPLETED);
        Task::handleProjectProgress($request->projectId);
        
        $this->notifyTaskStatusChange($request->taskId, 'hoàn thành');
        
        return response(['message'=>'task move to completed'],200);
    }


    public function TaskToNotStartedToCompleted(Request $request){
      
        Task::changeTaskStatus($request->taskId,Task::COMPLETED);
        
        $this->notifyTaskStatusChange($request->taskId, 'hoàn thành');
        
        return response(['message'=>'task move to completed'],200);
    }

   

    public function TaskToPendingToNotStarted(Request $request){
      
        Task::changeTaskStatus($request->taskId,Task::NOT_STARTED);
        
        $this->notifyTaskStatusChange($request->taskId, 'chưa bắt đầu');
        
        return response(['message'=>'task move to not started'],200);
    }

    public function TaskToCompletedToPending(Request $request){

        Task::changeTaskStatus($request->taskId,Task::PENDING);
        Task::handleProjectProgress($request->projectId);
        
        $this->notifyTaskStatusChange($request->taskId, 'đang xử lý');
        
        return response(['message'=>'task move to Pending'],200);
    }

    public function TaskToCompletedToNotStarted(Request $request){
  
        Task::changeTaskStatus($request->taskId,Task::NOT_STARTED);
        
        $this->notifyTaskStatusChange($request->taskId, 'chưa bắt đầu');
        
        return response(['message'=>'task move to not started'],200);
    }

    private function notifyTaskStatusChange($taskId, $statusName)
    {
        $task = Task::find($taskId);
        if (!$task) return;
        
        // Lấy danh sách các user đã được gán cho task này
        $taskMembers = TaskMember::where('taskId', $taskId)->get();
        
        foreach ($taskMembers as $taskMember) {
            // Kiểm tra xem memberId có phải là user hay không
            $user = User::where('id', $taskMember->memberId)->first();
            if ($user) {
                // Tạo thông báo cho user
                NotificationController::createNotification(
                    $user->id,
                    $taskId,
                    'Trạng thái công việc đã thay đổi',
                    'Công việc "' . $task->name . '" đã chuyển sang trạng thái ' . $statusName,
                    'task_status_changed'
                );
            }
        }
    }
}
