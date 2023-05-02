<?php

namespace App\Virtual\Models;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     title="Task",
 *     description="The created task object",
 *     @OA\Xml(
 *         name="Task"
 *     )
 * )
 */
class Task extends BaseModels
{
    /**
     * @OA\Property(
     *     title="TaskId",
     *     description="The id of the task"
     * )
     *
     * @var string
     */
    private string $task_id;

    /**
     * @OA\Property(
     *     title="Status",
     *     description="The status of the task",
     *     enum={"DONE", "PENDING", "FAILED"},
     * )
     *
     * @var string
     */
    private string $status;

    /**
     * @OA\Property(
     *     title="Type",
     *     description="The type of the task",
     *     enum={"pdf.export"},
     * )
     *
     * @var string
     */
    private string $type;

    /**
     * @OA\Property(
     *     title="Data",
     *     type="object",
     *     @OA\Schema(),
     *     description="The result of the task"
     * )
     * @var object
     */
    private object $data;

}
