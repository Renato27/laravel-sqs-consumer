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

        LaravelSQSQueueServiceProvider::deleteProcessedJob(new JobProcessed('sqs-consumer', $job));
    }

    public function testKeepsAMessageItsHandlerReleased()
    {
        $job = $this->job(true, false);
        $job->shouldReceive('delete')->never();

        LaravelSQSQueueServiceProvider::deleteProcessedJob(new JobProcessed('sqs-consumer', $job));
    }

    public function testDoesNotDeleteTwice()
    {
        $job = $this->job(false, true);
        $job->shouldReceive('delete')->never();

        LaravelSQSQueueServiceProvider::deleteProcessedJob(new JobProcessed('sqs-consumer', $job));
    }

    public function testIgnoresJobsOfOtherConnectionsWithoutThrowing()
    {
        $job = $this->job(false, false);
        $job->shouldReceive('delete')->never();

        $this->app['config']->set('queue.connections.sync', ['driver' => 'sync']);

        $this->app['events']->dispatch(new JobProcessed('sync', $job));
    }

    public function testDeletesThroughTheEventDispatcherForTheSqsConsumerDriver()
    {
        $job = $this->job(false, false);
        // atLeast: TestCase boots both the Laravel and Lumen providers, so the listener is registered twice.
        $job->shouldReceive('delete')->atLeast()->once();

        $this->app['events']->dispatch(new JobProcessed('sqs-consumer', $job));
    }
}
