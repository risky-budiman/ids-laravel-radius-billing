<?php

namespace App\Services\Network;

use Exception;
use Illuminate\Support\Facades\Log;

class CustomTelnetClient
{
    protected $socket;
    protected $host;
    protected $port;
    protected $timeout;
    protected $debug = true;

    public function __construct($host, $port = 23, $timeout = 10)
    {
        $this->host = $host;
        $this->port = $port;
        $this->timeout = $timeout;
    }

    /**
     * Connect to the host
     */
    public function connect()
    {
        $this->socket = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);
        
        if (!$this->socket) {
            throw new Exception("Could not connect to {$this->host}:{$this->port}. Error: {$errstr} ({$errno})");
        }

        // Set stream timeout for reading
        stream_set_timeout($this->socket, $this->timeout);
        
        return true;
    }

    /**
     * Login to the OLT
     */
    public function login($username, $password)
    {
        // Wait for username prompt
        $this->read('/(Username:|login:)/i');
        $this->write($username . "\n");
        
        // Wait for password prompt
        $this->read('/Password:/i');
        $this->write($password . "\n");
        
        return true;
    }

    /**
     * Write data to the socket
     */
    public function write($data)
    {
        if (!$this->socket) return false;
        if ($this->debug) Log::debug("Telnet Writing: " . trim($data));
        return fwrite($this->socket, $data);
    }

    /**
     * Read from the socket until a regex is matched or timeout
     */
    public function read($regex = null)
    {
        if (!$this->socket) return "";
        
        // Wait a tiny bit before reading to let OLT process
        usleep(500000); 

        $buffer = "";
        $startTime = microtime(true);
        
        // Set to non-blocking for better control
        stream_set_blocking($this->socket, false);

        while (true) {
            // Check for total timeout
            if (microtime(true) - $startTime > $this->timeout) {
                if ($this->debug && strlen($buffer) > 0) {
                    Log::debug("Telnet Read Timeout after {$this->timeout}s. Buffer: " . $buffer);
                }
                break;
            }

            $data = fread($this->socket, 2048);
            
            if ($data === false) break; 

            if (strlen($data) > 0) {
                // Handle Telnet Negotiation
                $cleanData = "";
                for ($i = 0; $i < strlen($data); $i++) {
                    $char = $data[$i];
                    $cOrd = ord($char);

                    // Telnet IAC (Interpret As Command)
                    if ($cOrd == 255) {
                        $i += 2; continue;
                    }
                    $cleanData .= $char;
                }
                
                // Strip ANSI escape codes
                $cleanData = preg_replace('/\x1b[\[()][0-9;]*[a-zA-Z]/', '', $cleanData);
                
                $buffer .= $cleanData;

                // Handle Pagination (--More--)
                if (str_contains($buffer, '--More--')) {
                    $buffer = str_replace('--More--', '', $buffer);
                    $this->write(" "); 
                }

                if ($regex) {
                    // Normalize regex to handle trailing spaces in prompt
                    if (str_contains($regex, 'ZXAN')) {
                        if (preg_match('/ZXAN[>#]\s*$/m', $buffer)) break;
                    }
                    if (is_array($regex)) {
                        foreach ($regex as $r) { if (preg_match($r, $buffer)) break 2; }
                    } else {
                        if (preg_match($regex, $buffer)) break;
                    }
                }
            } else {
                usleep(50000); // 50ms
            }
        }
        
        stream_set_blocking($this->socket, true);
        return $buffer;
    }

    /**
     * Close the connection
     */
    public function disconnect()
    {
        if ($this->socket) {
            fclose($this->socket);
            $this->socket = null;
        }
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}
