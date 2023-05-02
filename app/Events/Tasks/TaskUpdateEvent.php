<?php

namespace App\Events\Tasks;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use VladimirYuldashev\LaravelQueueRabbitMQ\DTOs\TaskData;

class TaskUpdateEvent extends TaskBroadcast
{
    use Dispatchable, SerializesModels;

    public TaskData $task;
    public $userId;

    public function __construct(TaskData $task, $userId){
        $this->task = $task;
        $this->userId = $userId;
    }

    public function broadcastAs()
    {
        return 'TaskUpdateEvent';
    }
}
