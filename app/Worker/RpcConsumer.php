<?php

namespace App\Worker;

use App\Events\Tasks\TaskUpdateEvent;
use Carbon\Carbon;
use Exception;
use Illuminate\Container\Container;
use Illuminate\Queue\Worker;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Support\Facades\Redis;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;
use VladimirYuldashev\LaravelQueueRabbitMQ\DTOs\TaskData;
use VladimirYuldashev\LaravelQueueRabbitMQ\Queue\RabbitMQQueue;

class RpcConsumer extends Worker
{
    /** @var Container */
    protected $container;

    /** @var string */
    protected $consumerTag;

    /** @var int */
    protected $prefetchSize;

    /** @var int */
    protected $maxPriority;

    /** @var int */
    protected $prefetchCount;

    /** @var AMQPChannel */
    protected $channel;

    /** @var object|null */
    protected $currentJob;

    public function setContainer(Container $value): void
    {
        $this->container = $value;
    }

    public function setConsumerTag(string $value): void
    {
        $this->consumerTag = $value;
    }

    public function setMaxPriority(int $value): void
    {
        $this->maxPriority = $value;
    }

    public function setPrefetchSize(int $value): void
    {
        $this->prefetchSize = $value;
    }

    public function setPrefetchCount(int $value): void
    {
        $this->prefetchCount = $value;
    }

    /**
     * Listen to the given queue in a loop.
     *
     * @param string $connectionName
     * @param string $queue
     * @param WorkerOptions $options
     * @return int
     * @throws Throwable
     */
    public function daemon($connectionName, $queue, WorkerOptions $options)
    {

        if ($this->supportsAsyncSignals()) {
            $this->listenForSignals();
        }

        $lastRestart = $this->getTimestampOfLastQueueRestart();

        [$startTime, $jobsProcessed] = [hrtime(true) / 1e9, 0];

        /** @var RabbitMQQueue $connection */
        $connection = $this->manager->connection($connectionName);

        $this->channel = $connection->getChannel();

        $this->channel->basic_qos(
            $this->prefetchSize,
            $this->prefetchCount,
            null
        );

        $arguments = [];
        if ($this->maxPriority) {
            $arguments['priority'] = ['I', $this->maxPriority];
        }


        $this->channel->basic_consume(
            $queue,
            '',
            false,
            true,
            false,
            false,
            function (AMQPMessage $message) use ($connection, $options, $connectionName, $queue, &$jobsProcessed): void {
                $correlationId = $message->get('correlation_id');
                $userId = Redis::get('task:'.$correlationId);
                logger('task:'.$correlationId);
                logger($message->getBody());

                if($userId){
                    $data = Redis::get('user:'.$userId);
                    if($data) {
                        $data = $this->unserialize($data);
                        collect($data)->each(function($item, $key) use (&$data) {
                            if(!Redis::exists('task:'.$item->task_id)){
                                unset($data[$key]);
                            }
                        });
                        $key=array_search($correlationId, array_column(json_decode(json_encode($data),TRUE), 'task_id'));
                        if($key !== false){
                            $oldData = $data[$key]->toArray();
                            logger('IN', $oldData);
                            $msg = $message->getBody();
                            $body = (object)[];
                            if($this->isJson($msg)){
                                $body = json_decode($msg);
                                if($body->status) {
                                    $msg = $body->status;
                                }
                            }

                            $newData['status'] = $msg;
                            $newData['data'] = (object) $body;
                            $newData['updated_at'] = Carbon::now();
                            $data[$key] = TaskData::update($oldData, $newData);
                            Redis::set('user:'.$userId, $this->serialize($data));

                            broadcast(new TaskUpdateEvent($data[$key], $userId));
                        }
                    }
                }

                $jobsProcessed++;

                if ($this->supportsAsyncSignals()) {
                    $this->resetTimeoutHandler();
                }
            },
            null,
            $arguments
        );

        while ($this->channel->is_consuming()) {
            // Before reserving any jobs, we will make sure this queue is not paused and
            // if it is we will just pause this worker for a given amount of time and
            // make sure we do not need to kill this worker process off completely.
            if (! $this->daemonShouldRun($options, $connectionName, $queue)) {
                $this->pauseWorker($options, $lastRestart);

                continue;
            }

            // If the daemon should run (not in maintenance mode, etc.), then we can wait for a job.
            try {
                $this->channel->wait(null, true, (int) $options->timeout);
            } catch (AMQPRuntimeException $exception) {
                $this->exceptions->report($exception);

                $this->kill(1);
            } catch (Exception | Throwable $exception) {
                $this->exceptions->report($exception);

                $this->stopWorkerIfLostConnection($exception);
            }

            // If no job is got off the queue, we will need to sleep the worker.
            if ($this->currentJob === null) {
                $this->sleep($options->sleep);
            }

            // Finally, we will check to see if we have exceeded our memory limits or if
            // the queue should restart based on other indications. If so, we'll stop
            // this worker and let whatever is "monitoring" it restart the process.
            $status = $this->stopIfNecessary(
                $options,
                $lastRestart,
                $startTime,
                $jobsProcessed,
                $this->currentJob
            );

            if (! is_null($status)) {
                return $this->stop($status);
            }

            $this->currentJob = null;
        }
    }

    protected function isJson($string) {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    protected function serialize($value)
    {
        return is_numeric($value) && ! in_array($value, [INF, -INF]) && ! is_nan($value) ? $value : serialize($value);
    }

    /**
     * Unserialize the value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function unserialize($value)
    {
        return is_numeric($value) ? $value : unserialize($value);
    }
    /**
     * Determine if the daemon should process on this iteration.
     *
     * @param WorkerOptions $options
     * @param string $connectionName
     * @param string $queue
     * @return bool
     */
    protected function daemonShouldRun(WorkerOptions $options, $connectionName, $queue): bool
    {
        return ! ((($this->isDownForMaintenance)() && ! $options->force) || $this->paused);
    }

    /**
     * Stop listening and bail out of the script.
     *
     * @param  int  $status
     * @return int
     */
    public function stop($status = 0): int
    {
        // Tell the server you are going to stop consuming.
        // It will finish up the last message and not send you any more.
        $this->channel->basic_cancel($this->consumerTag, false, true);

        return parent::stop($status);
    }
}
