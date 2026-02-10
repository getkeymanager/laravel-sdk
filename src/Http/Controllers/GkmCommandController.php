<?php

declare(strict_types=1);

namespace GetKeyManager\Laravel\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use GetKeyManager\Laravel\GetKeyManagerClient;

/**
 * GKM Command Controller
 * 
 * Handles incoming commands from the GetKeyManager server,
 * such as remote kill switches or configuration updates.
 */
class GkmCommandController extends Controller
{
    public function __construct(
        protected GetKeyManagerClient $client
    ) {}

    /**
     * Handle incoming command
     */
    public function handle(Request $request)
    {
        // 1. Validate signature to ensure it's from GKM
        if (!$this->verifySignature($request)) {
            return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 401);
        }

        $payload = $request->input('payload', []);
        $command = $payload['command'] ?? null;

        if ($command === 'kill_switch') {
            $this->client->handleKillSwitch($payload);
            return response()->json(['status' => 'success', 'message' => 'Kill switch activated']);
        }

        if ($command === 'stat_request') {
            $response = $this->client->sendStealthStat();
            return response()->json(['status' => 'success', 'data' => $response]);
        }

        return response()->json(['status' => 'error', 'message' => 'Unknown command'], 400);
    }

    /**
     * Verify that the request came from GetKeyManager
     */
    protected function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-GKM-Signature');
        if (empty($signature)) return false;

        $payload = $request->input('payload');
        if (empty($payload)) return false;

        $jsonData = is_array($payload) ? json_encode($payload) : $payload;

        try {
            return $this->client->verifySignature($jsonData, $signature);
        } catch (\Exception $e) {
            return false;
        }
    }
}
