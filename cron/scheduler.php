<?php
// cron/scheduler.php
// File untuk dijadwalkan di cron job
// Jalankan setiap menit: * * * * * php /path/to/your/project/cron/scheduler.php

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/NotificationManager.php';

// Only run in CLI mode
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line');
}

try {
    // Initialize database and notification manager
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    $notificationManager = new NotificationManager($db);
    
    // Log start
    $logMessage = "[" . date('Y-m-d H:i:s') . "] Starting scheduled notification check...\n";
    echo $logMessage;
    file_put_contents(__DIR__ . '/scheduler.log', $logMessage, FILE_APPEND);
    
    // Process scheduled notifications
    $results = $notificationManager->sendScheduledNotifications();
    
    if (empty($results)) {
        $logMessage = "[" . date('Y-m-d H:i:s') . "] No scheduled notifications found\n";
    } else {
        $totalSent = 0;
        $totalFailed = 0;
        $processedCount = count($results);
        
        foreach ($results as $result) {
            if ($result['success']) {
                $totalSent += $result['sent'];
                $totalFailed += $result['failed'];
            } else {
                $totalFailed++;
            }
        }
        
        $logMessage = "[" . date('Y-m-d H:i:s') . "] Processed {$processedCount} notifications. Sent: {$totalSent}, Failed: {$totalFailed}\n";
    }
    
    echo $logMessage;
    file_put_contents(__DIR__ . '/scheduler.log', $logMessage, FILE_APPEND);
    
    // Clean up old logs (keep last 30 days)
    cleanupLogs();
    
} catch (Exception $e) {
    $errorMessage = "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . "\n";
    echo $errorMessage;
    file_put_contents(__DIR__ . '/scheduler.log', $errorMessage, FILE_APPEND);
    file_put_contents(__DIR__ . '/error.log', $errorMessage, FILE_APPEND);
}

function cleanupLogs() {
    $logFile = __DIR__ . '/scheduler.log';
    $errorLogFile = __DIR__ . '/error.log';
    
    // Keep logs for 30 days
    $cutoffDate = strtotime('-30 days');
    
    foreach ([$logFile, $errorLogFile] as $file) {
        if (file_exists($file)) {
            $lines = file($file, FILE_IGNORE_NEW_LINES);
            $newLines = [];
            
            foreach ($lines as $line) {
                // Extract date from log line
                if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
                    $lineDate = strtotime($matches[1]);
                    if ($lineDate >= $cutoffDate) {
                        $newLines[] = $line;
                    }
                } else {
                    // Keep lines without dates (shouldn't happen, but just in case)
                    $newLines[] = $line;
                }
            }
            
            // Write back cleaned logs
            file_put_contents($file, implode("\n", $newLines) . "\n");
        }
    }
}

// Helper function to send email alerts (optional)
function sendAlertEmail($subject, $message) {
    $adminEmail = 'admin@example.com'; // Change this to your admin email
    
    if (!empty($adminEmail)) {
        $headers = [
            'From: noreply@' . $_SERVER['HTTP_HOST'],
            'Content-Type: text/plain; charset=UTF-8'
        ];
        
        mail($adminEmail, $subject, $message, implode("\r\n", $headers));
    }
}

// Check for too many failures (optional monitoring)
$errorLogFile = __DIR__ . '/error.log';
if (file_exists($errorLogFile)) {
    $errors = file($errorLogFile, FILE_IGNORE_NEW_LINES);
    $recentErrors = array_filter($errors, function($line) {
        if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
            $lineDate = strtotime($matches[1]);
            return $lineDate >= strtotime('-1 hour'); // Last hour
        }
        return false;
    });
    
    // If more than 10 errors in the last hour, send alert
    if (count($recentErrors) > 10) {
        $alertMessage = "WhatsApp Notification System Alert\n\n";
        $alertMessage .= "Too many errors detected in the last hour (" . count($recentErrors) . " errors).\n\n";
        $alertMessage .= "Recent errors:\n" . implode("\n", array_slice($recentErrors, -5));
        
        sendAlertEmail("WA Notification System - High Error Rate", $alertMessage);
        
        // Log the alert
        $alertLog = "[" . date('Y-m-d H:i:s') . "] ALERT: High error rate detected, email sent\n";
        file_put_contents(__DIR__ . '/scheduler.log', $alertLog, FILE_APPEND);
    }
}
?>