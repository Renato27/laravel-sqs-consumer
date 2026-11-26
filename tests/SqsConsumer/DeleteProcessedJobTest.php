<?php

namespace SqsConsumer;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobProcessed;
use Mockery;
use Renatomaldonado\LaravelSqsConsume\Provider\LaravelSQSQueueServiceProvider;
use Renatomaldonado\LaravelSqsConsume\Tests\TestCase;

class DeleteProcessedJobTest extends TestCase
{
    private function job(bool $released, bool $deleted): Job
    {
        $job = Mockery::mock(Job::class);
        $job->shouldReceive('isReleased')->andReturn($released);
        $job->shouldReceive('isDeleted')->andReturn($deleted);

        return $job;
    }

    public function testDeletesAMessageItsHandlerFinished()
    {
        $job = $this->job(false, false);
        $job->shouldReceive('delete')->once();

        LaravelSQSQueueServiceProvider::deleteProcessedJob(new JobProcessed('sqs', $job));
    }

    public function testKeepsAMessageItsHandlerReleased()
    {
        $job = $this->job(true, false);
        $job->shouldReceive('delete')->never();

        LaravelSQSQueueServiceProvider::deleteProcessedJob(new JobProcessed('sqs', $job));
    }

    public function testDoesNotDeleteTwice()
    {
        $job = $this->job(false, true);
        $job->shouldReceive('delete')->never();

        LaravelSQSQueueServiceProvider::deleteProcessedJob(new JobProcessed('sqs', $job));
    }
}
