<?php

namespace App\Services;

use App\Models\AcsServer;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GenieACSService
{
    protected ?string $baseUrl;

    public function __construct(?string $baseUrl = null)
    {
        $this->baseUrl = $baseUrl;
    }

    /**
     * Set the target ACS server model.
     */
    public static function forServer(AcsServer $server): self
    {
        return new self($server->url);
    }

    /**
     * Set the target URL directly.
     */
    public static function forUrl(string $url): self
    {
        return new self($url);
    }

    /**
     * Get devices with optional query and projection.
     * Query can be like: ["_id" => "OUI-Product-SN"]
     * Projection can be like: ["_id", "_lastInform", "Device.DeviceInfo.ProductClass"]
     */
    public function getDevices(array $query = [], array $projection = [], int $skip = 0, int $limit = 25)
    {
        $params = [
            'query' => json_encode($query),
            'skip' => $skip,
            'limit' => $limit,
            'total' => 'true' // Add total=true here specifically for collection
        ];
        
        if (!empty($projection)) {
            $params['projection'] = implode(',', $projection);
        }
        
        $queryString = http_build_query($params);
        return $this->request('GET', "devices?{$queryString}");
    }

    /**
     * Get a specific device details.
     */
    public function getDevice(string $deviceId)
    {
        // Use query instead of direct ID path to avoid 405 errors on some GenieACS setups
        $query = ['_id' => $deviceId];
        $params = [
            'query' => json_encode($query),
        ];
        $queryString = http_build_query($params);
        $response = $this->request('GET', "devices?{$queryString}");
        
        // Return the first device from data array if found
        if (!empty($response['data']) && is_array($response['data'])) {
            $response['data'] = $response['data'][0];
        } else {
            $response['data'] = null;
        }
        
        return $response;
    }

    /**
     * Get pending tasks for a device.
     */
    public function getTasks(string $deviceId)
    {
        $query = ['device' => $deviceId];
        $queryString = http_build_query(['query' => json_encode($query)]);
        return $this->request('GET', "tasks?{$queryString}");
    }

    /**
     * Set TR-069 parameters on a device.
     */
    public function setParameters(string $deviceId, array $parameters)
    {
        $values = [];
        foreach ($parameters as $name => $value) {
            $values[] = [$name, $value];
        }

        return $this->pushTask($deviceId, [
            'name' => 'setParameterValues',
            'parameterValues' => $values
        ]);
    }

    /**
     * Send a reboot command.
     */
    public function reboot(string $deviceId)
    {
        return $this->pushTask($deviceId, [
            'name' => 'reboot'
        ]);
    }

    /**
     * Send a factory reset command.
     */
    public function factoryReset(string $deviceId)
    {
        return $this->pushTask($deviceId, [
            'name' => 'factoryReset'
        ]);
    }

    /**
     * Generic method to push a task to a device.
     * 
     * @param string $deviceId  The device ID
     * @param array  $task      The task payload (e.g. ['name' => 'setParameterValues', ...])
     * @param int    $phpTimeout  PHP HTTP client timeout in seconds
     * @param bool   $connectionRequest  Whether to also send a connection request to wake the device
     * @param int    $genieTimeoutMs  How long GenieACS should wait for device response (in milliseconds). 0 = don't wait.
     */
    public function pushTask(string $deviceId, array $task, int $phpTimeout = 30, bool $connectionRequest = true, int $genieTimeoutMs = 5000)
    {
        $endpoint = "devices/" . urlencode($deviceId) . "/tasks";
        
        // Build GenieACS query parameters
        $queryParts = [];
        if ($genieTimeoutMs > 0) {
            $queryParts[] = "timeout=" . $genieTimeoutMs;
        }
        if ($connectionRequest) {
            $queryParts[] = "connection_request";
        }
        if (!empty($queryParts)) {
            $endpoint .= '?' . implode('&', $queryParts);
        }
        
        return $this->request('POST', $endpoint, $task, $phpTimeout);
    }

    /**
     * Helper to perform HTTP requests.
     */
    protected function request(string $method, string $endpoint, array $data = [], int $timeout = 60)
    {
        if (!$this->baseUrl) {
            throw new Exception("ACS Server URL not set.");
        }

        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');

        try {
            $pending = Http::timeout($timeout)->connectTimeout(min(10, $timeout));
            
            if ($method === 'GET') {
                $response = $pending->acceptJson()->get($url);
            } elseif ($method === 'POST') {
                $response = $pending->asJson()->acceptJson()->post($url, $data);
            } elseif ($method === 'PUT') {
                $response = $pending->asJson()->acceptJson()->put($url, $data);
            } elseif ($method === 'DELETE') {
                $response = $pending->acceptJson()->delete($url);
            } else {
                $response = $pending->asJson()->acceptJson()->send($method, $url, ['json' => $data]);
            }

            // For task pushes, both 200 (executed) and 202 (queued/pending) are valid
            if ($response->successful() || $response->status() === 202) {
                $total = $response->header('total-count') ?? $response->header('X-Total-Count');
                return [
                    'data' => $response->json(),
                    'total' => $total !== null ? (int) $total : null
                ];
            }

            $errorBody = $response->body();
            Log::error("GenieACS API Error [{$url}]: " . $errorBody);
            throw new Exception("GenieACS API returned error: " . $response->status() . " - " . $errorBody);

        } catch (Exception $e) {
            Log::error("GenieACS Request Exception: " . $e->getMessage());
            throw $e;
        }
    }
}
