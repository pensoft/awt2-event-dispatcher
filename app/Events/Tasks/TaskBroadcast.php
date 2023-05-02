<?php


namespace App\Events\Tasks;


use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use VladimirYuldashev\LaravelQueueRabbitMQ\DTOs\TaskData;

class TaskBroadcast implements ShouldBroadcastNow
{
    use InteractsWithSockets;

    public TaskData $task;
    public $userId;

    /**
     * @inheritDoc
     */
    public function broadcastOn()
    {
        return new PrivateChannel('tasks.'.$this->userId);
    }

    public function broadcastWith()
    {
        return [
            'task' => $this->task
        ];
    }
}
