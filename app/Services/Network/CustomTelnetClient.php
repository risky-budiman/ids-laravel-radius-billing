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
        return fwrite($this->socket, $data);
    }

    /**
     * Read from the socket until a regex is matched or timeout
     * Supports array of regexes
     */
    /**
     * Read from the socket until a regex is matched or timeout
     */
    public function read($regex = null)
    {
        if (!$this->socket) return "";

        $buffer = "";
        $startTime = microtime(true);
        
        // Set to non-blocking for better control
        stream_set_blocking($this->socket, false);

        while (true) {
            // Check for total timeout
            if (microtime(true) - $startTime > $this->timeout) {
                if ($this->debug) Log::debug("Telnet Read Timeout after {$this->timeout}s. Buffer: " . $buffer);
                break;
            }

            $data = fread($this->socket, 1024);
            
            if ($data === false) {
                break; 
            }

            if (strlen($data) > 0) {
                // Handle Telnet Negotiation
                $cleanData = "";
                for ($i = 0; $i < strlen($data); $i++) {
                    $char = $data[$i];
                    $cOrd = ord($char);

                    // Telnet IAC (Interpret As Command)
                    if ($cOrd == 255) {
                        $command = isset($data[$i+1]) ? ord($data[$i+1]) : 0;
                        $option = isset($data[$i+2]) ? ord($data[$i+2]) : 0;
                        
                        if ($command == 253) fwrite($this->socket, chr(255) . chr(252) . chr($option)); // DO -> WONT
                        elseif ($command == 251) fwrite($this->socket, chr(255) . chr(254) . chr($option)); // WILL -> DONT
                        elseif ($command == 254) fwrite($this->socket, chr(255) . chr(252) . chr($option)); // DONT -> WONT
                        elseif ($command == 252) fwrite($this->socket, chr(255) . chr(254) . chr($option)); // WONT -> DONT
                        
                        $i += 2;
                        continue;
                    }
                    $cleanData .= $char;
                }
                
                // Strip ANSI escape codes (important for network devices)
                $cleanData = preg_replace('/\x1b[\[()][0-9;]*[a-zA-Z]/', '', $cleanData);
                
                $buffer .= $cleanData;

                if ($regex) {
                    if (is_array($regex)) {
                        foreach ($regex as $r) {
                            if (preg_match($r, $buffer)) break 2;
                        }
                    } else {
                        if (preg_match($regex, $buffer)) break;
                    }
                }
            } else {
                // Wait a bit if no data to save CPU
                usleep(50000); // 50ms
            }
        }
        
        // Set back to blocking for safety
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
