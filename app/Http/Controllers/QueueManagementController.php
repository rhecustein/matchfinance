<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\QueueMonitorService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * QueueManagementController
 * 
 * Controller untuk monitoring dan management queue system
 * 
 * ==========================================
 * AKSES KONTROL
 * ==========================================
 * 
 * MANAGER+ (manager, admin, owner):
 * ✅ View queue dashboard (index)
 * ✅ View pending jobs (pendingJobs)
 * ✅ View failed jobs (failedJobs)
 * ✅ View job details (showJob)
 * ✅ Retry single failed job (retryJob)
 * ✅ Retry all failed jobs (retryAll)
 * ✅ Retry failed jobs by queue (retryQueue)
 * ✅ Delete single failed job (deleteJob)
 * ✅ Clear all failed jobs (clearFailed)
 * ✅ Clear pending jobs by queue (clearQueue)
 * ✅ Get queue stats via AJAX (getStats)
 * 
 * CATATAN:
 * - Middleware: company.manager (ada di routes)
 * - Tenant isolation: jobs filtered by company_id in payload
 * - Real-time updates via AJAX polling
 * 
 * @package App\Http\Controllers
 */
class QueueManagementController extends Controller
{
    protected $queueService;

    public function __construct(QueueMonitorService $queueService)
    {
        $this->queueService = $queueService;
    }

    /**
     * Display queue management dashboard
     * 
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $queueName = $request->get('queue');
        $companyId = auth()->user()->company_id;
        
        // Get queue statistics
        $stats = $this->queueService->getQueueStats($queueName, $companyId);
        
        // Get available queues
        $queues = $this->queueService->getAvailableQueues();
        
        // Get recent activity
        $recentJobs = $this->queueService->getRecentJobs($queueName, $companyId, 10);
        $recentFailed = $this->queueService->getRecentFailedJobs($queueName, $companyId, 5);
        
        return view('queue-management.index', compact(
            'stats',
            'queues',
            'queueName',
            'recentJobs',
            'recentFailed'
        ));
    }

    /**
     * Get queue stats via AJAX
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStats(Request $request)
    {
        try {
            $queueName = $request->get('queue');
            $companyId = auth()->user()->company_id;
            
            $stats = $this->queueService->getQueueStats($queueName, $companyId);
            
            return response()->json([
                'success' => true,
                'stats' => $stats,
                'timestamp' => now()->toIso8601String()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get queue stats', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'company_id' => auth()->user()->company_id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch queue statistics'
            ], 500);
        }
    }

    /**
     * Display pending jobs list
     * 
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function pendingJobs(Request $request)
    {
        $queueName = $request->get('queue');
        $companyId = auth()->user()->company_id;
        $perPage = $request->get('per_page', 20);
        
        $jobs = $this->queueService->getPendingJobs($queueName, $companyId, $perPage);
        
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'jobs' => $jobs
            ]);
        }
        
        $queues = $this->queueService->getAvailableQueues();
        
        return view('queue-management.pending-jobs', compact('jobs', 'queueName', 'queues'));
    }

    /**
     * Display failed jobs list
     * 
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function failedJobs(Request $request)
    {
        $queueName = $request->get('queue');
        $companyId = auth()->user()->company_id;
        $perPage = $request->get('per_page', 20);
        
        $failedJobs = $this->queueService->getFailedJobs($queueName, $companyId, $perPage);
        
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'failed_jobs' => $failedJobs
            ]);
        }
        
        $queues = $this->queueService->getAvailableQueues();
        
        return view('queue-management.failed-jobs', compact('failedJobs', 'queueName', 'queues'));
    }

    /**
     * Show single job detail
     * 
     * @param string $uuid
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function showJob(Request $request, $uuid)
    {
        try {
            $companyId = auth()->user()->company_id;
            $job = $this->queueService->getJobDetail($uuid, $companyId);
            
            if (!$job) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Job not found'
                    ], 404);
                }
                
                return redirect()->route('queue-management.index')
                    ->with('error', 'Job not found');
            }
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'job' => $job
                ]);
            }
            
            return view('queue-management.job-detail', compact('job'));
        } catch (\Exception $e) {
            Log::error('Failed to get job detail', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch job details'
                ], 500);
            }
            
            return redirect()->route('queue-management.index')
                ->with('error', 'Failed to fetch job details');
        }
    }

    /**
     * Retry single failed job
     * 
     * @param string $uuid
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function retryJob(Request $request, $uuid)
    {
        try {
            $companyId = auth()->user()->company_id;
            $result = $this->queueService->retryFailedJob($uuid, $companyId);
            
            if (!$result) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to retry job. Job not found or does not belong to your company.'
                    ], 404);
                }
                
                return back()->with('error', 'Failed to retry job. Job not found or does not belong to your company.');
            }
            
            Log::info('Job retried successfully', [
                'uuid' => $uuid,
                'user_id' => auth()->id(),
                'company_id' => $companyId
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Job has been queued for retry'
                ]);
            }
            
            return back()->with('success', 'Job has been queued for retry');
        } catch (\Exception $e) {
            Log::error('Failed to retry job', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to retry job: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to retry job: ' . $e->getMessage());
        }
    }

    /**
     * Retry all failed jobs
     * 
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function retryAll(Request $request)
    {
        try {
            $companyId = auth()->user()->company_id;
            $queueName = $request->get('queue');
            
            $count = $this->queueService->retryAllFailedJobs($queueName, $companyId);
            
            Log::info('Retry all failed jobs executed', [
                'queue' => $queueName,
                'count' => $count,
                'user_id' => auth()->id(),
                'company_id' => $companyId
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "{$count} jobs have been queued for retry",
                    'count' => $count
                ]);
            }
            
            return back()->with('success', "{$count} jobs have been queued for retry");
        } catch (\Exception $e) {
            Log::error('Failed to retry all jobs', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to retry jobs: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to retry jobs: ' . $e->getMessage());
        }
    }

    /**
     * Retry failed jobs by queue name
     * 
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function retryQueue(Request $request)
    {
        $request->validate([
            'queue' => 'required|string'
        ]);
        
        try {
            $companyId = auth()->user()->company_id;
            $queueName = $request->queue;
            
            $count = $this->queueService->retryAllFailedJobs($queueName, $companyId);
            
            Log::info('Retry queue executed', [
                'queue' => $queueName,
                'count' => $count,
                'user_id' => auth()->id(),
                'company_id' => $companyId
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "{$count} jobs from queue '{$queueName}' have been queued for retry",
                    'count' => $count
                ]);
            }
            
            return back()->with('success', "{$count} jobs from queue '{$queueName}' have been queued for retry");
        } catch (\Exception $e) {
            Log::error('Failed to retry queue', [
                'queue' => $request->queue,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to retry queue: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to retry queue: ' . $e->getMessage());
        }
    }

    /**
     * Delete single failed job
     * 
     * @param string $uuid
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function deleteJob(Request $request, $uuid)
    {
        try {
            $companyId = auth()->user()->company_id;
            $result = $this->queueService->deleteFailedJob($uuid, $companyId);
            
            if (!$result) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to delete job. Job not found or does not belong to your company.'
                    ], 404);
                }
                
                return back()->with('error', 'Failed to delete job. Job not found or does not belong to your company.');
            }
            
            Log::info('Failed job deleted', [
                'uuid' => $uuid,
                'user_id' => auth()->id(),
                'company_id' => $companyId
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Job has been deleted'
                ]);
            }
            
            return back()->with('success', 'Job has been deleted');
        } catch (\Exception $e) {
            Log::error('Failed to delete job', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete job: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to delete job: ' . $e->getMessage());
        }
    }

    /**
     * Clear all failed jobs
     * 
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function clearFailed(Request $request)
    {
        try {
            $companyId = auth()->user()->company_id;
            $queueName = $request->get('queue');
            
            $count = $this->queueService->clearAllFailedJobs($queueName, $companyId);
            
            Log::warning('All failed jobs cleared', [
                'queue' => $queueName,
                'count' => $count,
                'user_id' => auth()->id(),
                'company_id' => $companyId
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "{$count} failed jobs have been deleted",
                    'count' => $count
                ]);
            }
            
            return back()->with('success', "{$count} failed jobs have been deleted");
        } catch (\Exception $e) {
            Log::error('Failed to clear failed jobs', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to clear failed jobs: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to clear failed jobs: ' . $e->getMessage());
        }
    }

    /**
     * Clear pending jobs by queue
     * 
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function clearQueue(Request $request)
    {
        $request->validate([
            'queue' => 'required|string'
        ]);
        
        try {
            $companyId = auth()->user()->company_id;
            $queueName = $request->queue;
            
            $count = $this->queueService->clearPendingJobs($queueName, $companyId);
            
            Log::warning('Pending jobs cleared', [
                'queue' => $queueName,
                'count' => $count,
                'user_id' => auth()->id(),
                'company_id' => $companyId
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "{$count} pending jobs from queue '{$queueName}' have been deleted",
                    'count' => $count
                ]);
            }
            
            return back()->with('success', "{$count} pending jobs from queue '{$queueName}' have been deleted");
        } catch (\Exception $e) {
            Log::error('Failed to clear queue', [
                'queue' => $request->queue,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to clear queue: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to clear queue: ' . $e->getMessage());
        }
    }
}