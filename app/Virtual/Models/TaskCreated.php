<?php

namespace App\Virtual\Models;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     title="TaskCreated",
 *     description="The task created sucessfully object",
 *     @OA\Xml(
 *         name="TaskCreated"
 *     )
 * )
 */
class TaskCreated
{
    /**
     * @OA\Property(
     *     title="Id",
     *     description="The id of the task"
     * )
     *
     * @var string
     */
    private string $task_id;
}
