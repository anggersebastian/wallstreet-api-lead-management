<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lead;
use App\Models\ErrorLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class LeadController extends Controller
{
    // POST /api/leads
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'source' => 'required|string|max:255',
            'message' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Create lead
            $lead = Lead::create($validator->validated());

            // Send Slack notification
            $this->sendSlackNotification($lead);

            return response()->json($lead, 201);
        } catch (\Exception $e) {
            $this->logError($e, '/api/leads', 500);
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    // GET /api/leads
    public function index()
    {
        try {
            $leads = Cache::remember('all_leads', now()->addMinutes(30), function () {
                return Lead::all();
            });

            return response()->json($leads);
        } catch (\Exception $e) {
            $this->logError($e, '/api/leads', 500);
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    // GET /api/leads/{id}
    public function show($id)
    {
        try {
            $lead = Lead::findOrFail($id);
            return response()->json($lead);
        } catch (\Exception $e) {
            $this->logError($e, "/api/leads/$id", 404);
            return response()->json(['error' => 'Lead not found'], 404);
        }
    }

    private function sendSlackNotification(Lead $lead)
    {
        try {
            $webhookUrl = env('SLACK_WEBHOOK_URL');

            if (!$webhookUrl) {
                Log::warning('Slack webhook URL not configured');
                return;
            }

            $message = [
                'text' => "🔥 *New Lead Submitted!* 🔥",
                'attachments' => [
                    [
                        'color' => '#36a64f',
                        'fields' => [
                            [
                                'title' => 'Name',
                                'value' => $lead->name,
                                'short' => true
                            ],
                            [
                                'title' => 'Email',
                                'value' => $lead->email,
                                'short' => true
                            ],
                            [
                                'title' => 'Phone',
                                'value' => $lead->phone,
                                'short' => true
                            ],
                            [
                                'title' => 'Source',
                                'value' => $lead->source,
                                'short' => true
                            ],
                            [
                                'title' => 'Message',
                                'value' => $lead->message ?: 'N/A',
                                'short' => false
                            ]
                        ]
                    ]
                ]
            ];

            Http::post($webhookUrl, $message);

        } catch (\Exception $e) {
            Log::error('Slack notification failed: ' . $e->getMessage());
        }
    }

    private function logError(\Exception $e, string $endpoint, int $statusCode)
    {
        ErrorLog::create([
            'error_message' => $e->getMessage(),
            'endpoint' => $endpoint,
            'status_code' => $statusCode
        ]);
    }
}
