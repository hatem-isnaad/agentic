<?php

namespace Agentic\Workflow;

use Agentic\Contracts\Repositories\WorkflowRunRepository;
use Agentic\Tool\ToolApprovalService;

final class WorkflowExecutionService
{
    public function __construct(
        private WorkflowResolver $workflows,
        private WorkflowRunner $runner,
        private WorkflowRunRepository $runs,
        private ToolApprovalService $approvals,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function execute(string $slug, array $input = []): WorkflowResult
    {
        $workflow = $this->workflows->resolve($slug);
        $run = $this->runs->create($slug);

        $result = $this->runner->run($workflow, $input);

        return $this->persistResult($run, $workflow->slug, $result);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function resume(string $slug, string $approvalId, array $input = [], ?string $resumeKey = null): WorkflowResult
    {
        $workflow = $this->workflows->resolve($slug);
        $approval = $this->approvals->find($approvalId);

        if ($approval === null) {
            return WorkflowResult::failed('Approval not found.', [], []);
        }

        if ($approval->status !== 'approved') {
            return WorkflowResult::failed('Workflow cannot resume until the approval is approved.', [], []);
        }

        $run = $this->runs->findByApprovalUuid($approvalId);

        if ($run === null || $run->workflowSlug !== $slug) {
            return WorkflowResult::failed('Workflow run not found for this approval.', [], []);
        }

        $resumeKey ??= '_resume_approval_id';
        $variables = $run->variables;
        $inputBag = is_array($variables['input'] ?? null) ? $variables['input'] : [];
        $variables['input'] = array_merge($inputBag, $input, [$resumeKey => $approvalId]);

        $continuation = new WorkflowContinuation(
            pointer: $run->stepPointer,
            variables: $variables,
            trace: $run->trace,
        );

        $result = $this->runner->run($workflow, [], $continuation);

        return $this->persistResult($run, $workflow->slug, $result);
    }

    public function showRun(string $uuid): ?WorkflowRunRecord
    {
        return $this->runs->find($uuid);
    }

    /**
     * @return list<WorkflowRunRecord>
     */
    public function listRuns(?string $workflowSlug = null, ?string $status = null, int $limit = 50, int $offset = 0): array
    {
        return $this->runs->list($workflowSlug, $status, $limit, $offset);
    }

    public function countRuns(?string $workflowSlug = null, ?string $status = null): int
    {
        return $this->runs->count($workflowSlug, $status);
    }

    private function persistResult(WorkflowRunRecord $run, string $slug, WorkflowResult $result): WorkflowResult
    {
        if ($result->pending) {
            $this->runs->save(new WorkflowRunRecord(
                uuid: $run->uuid,
                workflowSlug: $slug,
                status: 'pending_approval',
                stepPointer: $result->stepPointer ?? $run->stepPointer,
                variables: $result->variables,
                trace: $result->trace,
                approvalUuid: $result->approvalId,
            ));

            return $result->withRunId($run->uuid);
        }

        if ($result->success) {
            $this->runs->save(new WorkflowRunRecord(
                uuid: $run->uuid,
                workflowSlug: $slug,
                status: 'completed',
                stepPointer: $run->stepPointer,
                variables: $result->variables,
                trace: $result->trace,
                output: $result->output,
            ));

            return $result->withRunId($run->uuid);
        }

        $this->runs->save(new WorkflowRunRecord(
            uuid: $run->uuid,
            workflowSlug: $slug,
            status: 'failed',
            stepPointer: $run->stepPointer,
            variables: $result->variables,
            trace: $result->trace,
            error: $result->error,
        ));

        return $result->withRunId($run->uuid);
    }
}
