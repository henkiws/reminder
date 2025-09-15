<?php
// public/api.php - ENHANCED VERSION with Complete CRUD and Error Handling
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'auth_check.php'; // This includes auth and database setup
require_once '../classes/NotificationManager.php';

// Error handling
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    $notificationManager = new NotificationManager($db);

    $method = $_SERVER['REQUEST_METHOD'];
    $pathInfo = $_SERVER['PATH_INFO'] ?? '';
    $request = explode('/', trim($pathInfo, '/'));
    $endpoint = $request[0] ?? '';

    // Log API requests for debugging
    error_log("API Request: $method $pathInfo from " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

    switch ($method) {
        case 'GET':
            handleGetRequest($endpoint, $request, $notificationManager, $auth, $currentUser, $db);
            break;
            
        case 'POST':
            handlePostRequest($endpoint, $request, $notificationManager, $auth, $currentUser, $db);
            break;
            
        case 'PUT':
            handlePutRequest($endpoint, $request, $notificationManager, $auth, $currentUser, $db);
            break;
            
        case 'DELETE':
            handleDeleteRequest($endpoint, $request, $notificationManager, $auth, $currentUser, $db);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }

} catch (Exception $e) {
    error_log('API Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage(),
        'debug' => [
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]
    ]);
}

function handleGetRequest($endpoint, $request, $notificationManager, $auth, $currentUser, $db) {
    switch ($endpoint) {
        case 'templates':
            $category = $_GET['category'] ?? null;
            $templates = $notificationManager->getTemplates($currentUser['id'], $category);
            echo json_encode($templates);
            break;
            
        case 'contacts':
            $search = $_GET['search'] ?? null;
            $filter = $_GET['filter'] ?? 'all';
            $contacts = $notificationManager->getContacts($currentUser['id'], $search, $filter);
            echo json_encode($contacts);
            break;
            
        case 'groups':
            $search = $_GET['search'] ?? null;
            $groups = $notificationManager->getGroups($currentUser['id'], $search);
            echo json_encode($groups);
            break;
            
        case 'notifications':
            $limit = max(1, min(100, (int)($_GET['limit'] ?? 50)));
            $offset = max(0, (int)($_GET['offset'] ?? 0));
            $status = $_GET['status'] ?? null;
            $priority = $_GET['priority'] ?? null;
            $notifications = $notificationManager->getNotifications($limit, $offset, $currentUser['id'], $status, $priority);
            echo json_encode($notifications);
            break;
            
        case 'notification':
            if (!isset($request[1])) {
                http_response_code(400);
                echo json_encode(['error' => 'Notification ID required']);
                break;
            }
            $notification = $notificationManager->getNotificationById($request[1], $currentUser['id']);
            if ($notification) {
                echo json_encode($notification);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Notification not found']);
            }
            break;
            
        case 'logs':
            if (!hasPermission('log.read')) {
                http_response_code(403);
                echo json_encode(['error' => 'Permission denied']);
                break;
            }
            $notification_id = $_GET['notification_id'] ?? null;
            $limit = max(1, min(500, (int)($_GET['limit'] ?? 100)));
            $logs = $notificationManager->getMessageLogs($notification_id, $limit);
            echo json_encode($logs);
            break;
            
        case 'users':
            if (!hasPermission('user.read')) {
                http_response_code(403);
                echo json_encode(['error' => 'Permission denied']);
                break;
            }
            echo json_encode($auth->getAllUsers());
            break;
            
        case 'categories':
            $query = "SELECT * FROM notification_categories WHERE is_active = 1 ORDER BY name";
            $stmt = $db->prepare($query);
            $stmt->execute();
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;
            
        case 'stats':
            $stats = getNotificationStats($db, $currentUser['id']);
            echo json_encode($stats);
            break;
            
        case 'dashboard':
            $dashboard = getDashboardData($db, $notificationManager, $currentUser['id']);
            echo json_encode($dashboard);
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
    }
}

function handlePostRequest($endpoint, $request, $notificationManager, $auth, $currentUser, $db) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON data']);
        return;
    }
    
    switch ($endpoint) {
        case 'notification':
            // Enhanced notification creation with validation
            $validationErrors = validateNotificationData($input);
            if (!empty($validationErrors)) {
                http_response_code(400);
                echo json_encode(['error' => 'Validation failed', 'details' => $validationErrors]);
                break;
            }

            // Handle template variables
            if (!empty($input['template_vars']) && is_array($input['template_vars'])) {
                $templateVars = array_filter($input['template_vars'], function($value) {
                    return !empty(trim($value));
                });
                
                if (!empty($templateVars)) {
                    $input['template_variables'] = json_encode($templateVars);
                }
                
                // For immediate sending, process the template now
                if (isset($input['action']) && $input['action'] === 'send_now') {
                    $processedMessage = $notificationManager->processTemplate($input['message'], $templateVars);
                    $input['message'] = $processedMessage;
                }
            }
            
            // Add user context
            $input['user_id'] = $currentUser['id'];
            $input['created_by'] = $currentUser['username'];
            
            // Handle scheduling
            if (!empty($input['scheduled_date']) && !empty($input['scheduled_time'])) {
                $input['scheduled_datetime'] = $input['scheduled_date'] . ' ' . $input['scheduled_time'];
            } else {
                $input['scheduled_datetime'] = date('Y-m-d H:i:s');
            }
            
            $result = $notificationManager->createNotification($input);
            
            // If action is send_now, send immediately
            if ($result['success'] && isset($input['action']) && $input['action'] === 'send_now') {
                $sendResult = $notificationManager->sendNotification($result['id']);
                $result['send_result'] = $sendResult;
            }
            
            echo json_encode($result);
            break;
            
        case 'send':
            if (empty($input['notification_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'notification_id required']);
                break;
            }
            
            // Check ownership
            $notification = $notificationManager->getNotificationById($input['notification_id'], $currentUser['id']);
            if (!$notification) {
                http_response_code(404);
                echo json_encode(['error' => 'Notification not found or access denied']);
                break;
            }
            
            $result = $notificationManager->sendNotification($input['notification_id']);
            echo json_encode($result);
            break;
            
        case 'send-test':
            if (empty($input['phone']) || empty($input['message'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Phone and message required']);
                break;
            }
            
            $result = $notificationManager->sendTestMessage($input['phone'], $input['message']);
            echo json_encode($result);
            break;
            
        case 'contact':
            $validationErrors = validateContactData($input);
            if (!empty($validationErrors)) {
                http_response_code(400);
                echo json_encode(['error' => 'Validation failed', 'details' => $validationErrors]);
                break;
            }
            
            // Format phone number
            $input['phone'] = formatPhoneNumber($input['phone']);
            
            $result = $notificationManager->addContact($input['name'], $input['phone'], $input['notes'] ?? '', $currentUser['id']);
            echo json_encode(['success' => $result]);
            break;
            
        case 'group':
            $validationErrors = validateGroupData($input);
            if (!empty($validationErrors)) {
                http_response_code(400);
                echo json_encode(['error' => 'Validation failed', 'details' => $validationErrors]);
                break;
            }
            
            $result = $notificationManager->addGroup(
                $input['name'], 
                $input['group_id'], 
                $input['description'] ?? '', 
                $currentUser['id']
            );
            echo json_encode(['success' => $result]);
            break;
            
        case 'template':
            $validationErrors = validateTemplateData($input);
            if (!empty($validationErrors)) {
                http_response_code(400);
                echo json_encode(['error' => 'Validation failed', 'details' => $validationErrors]);
                break;
            }
            
            // Extract variables from template
            $variables = extractTemplateVariables($input['message_template']);
            
            $query = "INSERT INTO message_templates (title, message_template, category_id, user_id, variables) VALUES (?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([
                $input['title'],
                $input['message_template'],
                !empty($input['category_id']) ? $input['category_id'] : null,
                $currentUser['id'],
                !empty($variables) ? json_encode($variables) : null
            ]);
            
            if ($result) {
                echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to create template']);
            }
            break;
            
        case 'user':
            if (!hasPermission('user.create')) {
                http_response_code(403);
                echo json_encode(['error' => 'Permission denied']);
                break;
            }
            
            $validationErrors = validateUserData($input);
            if (!empty($validationErrors)) {
                http_response_code(400);
                echo json_encode(['error' => 'Validation failed', 'details' => $validationErrors]);
                break;
            }
            
            $result = $auth->register($input);
            echo json_encode($result);
            break;
            
        case 'preview-template':
            if (empty($input['message'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Message required']);
                break;
            }
            
            $message = $input['message'];
            $variables = $input['variables'] ?? [];
            $contactName = $input['contact_name'] ?? 'John Doe';
            
            $processed = $notificationManager->previewTemplate($message, $variables, $contactName);
            $foundVariables = extractTemplateVariables($message);
            
            echo json_encode([
                'success' => true,
                'processed_message' => $processed,
                'variables_found' => $foundVariables,
                'variables_used' => $variables
            ]);
            break;
            
        case 'contacts':
            if (isset($request[1])) {
                switch ($request[1]) {
                    case 'bulk-delete':
                        if (!isset($input['contact_ids']) || !is_array($input['contact_ids'])) {
                            http_response_code(400);
                            echo json_encode(['error' => 'contact_ids array required']);
                            break;
                        }
                        
                        $result = $notificationManager->bulkDeleteContacts($input['contact_ids'], $currentUser['id']);
                        echo json_encode($result);
                        break;
                        
                    case 'import':
                        // Handle CSV import
                        if (!isset($_FILES['file'])) {
                            http_response_code(400);
                            echo json_encode(['error' => 'File required']);
                            break;
                        }
                        
                        $result = $notificationManager->importContacts($_FILES['file'], $currentUser['id']);
                        echo json_encode($result);
                        break;
                        
                    default:
                        http_response_code(404);
                        echo json_encode(['error' => 'Endpoint not found']);
                }
            }
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
    }
}

function handlePutRequest($endpoint, $request, $notificationManager, $auth, $currentUser, $db) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON data']);
        return;
    }
    
    switch ($endpoint) {
        case 'contact':
            $validationErrors = validateContactData($input, true);
            if (!empty($validationErrors)) {
                http_response_code(400);
                echo json_encode(['error' => 'Validation failed', 'details' => $validationErrors]);
                break;
            }
            
            if (empty($input['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID required']);
                break;
            }
            
            // Format phone number
            $input['phone'] = formatPhoneNumber($input['phone']);
            
            $query = "UPDATE contacts SET name = ?, phone = ?, notes = ?, updated_at = NOW() 
                      WHERE id = ? AND (user_id = ? OR user_id IS NULL)";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([
                $input['name'], 
                $input['phone'], 
                $input['notes'] ?? '',
                $input['id'], 
                $currentUser['id']
            ]);
            echo json_encode(['success' => $result]);
            break;
            
        case 'group':
            $validationErrors = validateGroupData($input, true);
            if (!empty($validationErrors)) {
                http_response_code(400);
                echo json_encode(['error' => 'Validation failed', 'details' => $validationErrors]);
                break;
            }
            
            if (empty($input['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID required']);
                break;
            }
            
            $query = "UPDATE wa_groups SET name = ?, group_id = ?, description = ?, updated_at = NOW() 
                      WHERE id = ? AND (user_id = ? OR user_id IS NULL)";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([
                $input['name'], 
                $input['group_id'], 
                $input['description'] ?? '', 
                $input['id'], 
                $currentUser['id']
            ]);
            echo json_encode(['success' => $result]);
            break;
            
        case 'template':
            $validationErrors = validateTemplateData($input, true);
            if (!empty($validationErrors)) {
                http_response_code(400);
                echo json_encode(['error' => 'Validation failed', 'details' => $validationErrors]);
                break;
            }
            
            if (empty($input['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID required']);
                break;
            }
            
            // Check ownership
            $checkQuery = "SELECT user_id FROM message_templates WHERE id = ?";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute([$input['id']]);
            $template = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$template || ($template['user_id'] != $currentUser['id'] && !hasPermission('template.update'))) {
                http_response_code(403);
                echo json_encode(['error' => 'Permission denied']);
                break;
            }
            
            // Extract variables
            $variables = extractTemplateVariables($input['message_template']);
            
            $query = "UPDATE message_templates SET title = ?, message_template = ?, category_id = ?, 
                      variables = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([
                $input['title'],
                $input['message_template'],
                !empty($input['category_id']) ? $input['category_id'] : null,
                !empty($variables) ? json_encode($variables) : null,
                $input['id']
            ]);
            echo json_encode(['success' => $result]);
            break;
            
        case 'notification':
            if (empty($input['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID required']);
                break;
            }
            
            // Check ownership
            $notification = $notificationManager->getNotificationById($input['id'], $currentUser['id']);
            if (!$notification) {
                http_response_code(404);
                echo json_encode(['error' => 'Notification not found or access denied']);
                break;
            }
            
            $result = $notificationManager->updateNotification($input['id'], $input, $currentUser['id']);
            echo json_encode($result);
            break;
            
        case 'user':
            if (!hasPermission('user.update')) {
                http_response_code(403);
                echo json_encode(['error' => 'Permission denied']);
                break;
            }
            
            $userId = $request[1] ?? null;
            if (!$userId) {
                http_response_code(400);
                echo json_encode(['error' => 'User ID required']);
                break;
            }
            
            $result = $auth->updateUser($userId, $input);
            echo json_encode($result);
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
    }
}

function handleDeleteRequest($endpoint, $request, $notificationManager, $auth, $currentUser, $db) {
    switch ($endpoint) {
        case 'contact':
            $id = $request[1] ?? null;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'Contact ID required']);
                break;
            }
            
            $result = $notificationManager->deleteContact($id, $currentUser['id']);
            echo json_encode(['success' => $result]);
            break;
            
        case 'group':
            $id = $request[1] ?? null;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'Group ID required']);
                break;
            }
            
            $result = $notificationManager->deleteGroup($id, $currentUser['id']);
            echo json_encode(['success' => $result]);
            break;
            
        case 'template':
            $id = $request[1] ?? null;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'Template ID required']);
                break;
            }
            
            // Check ownership
            $checkQuery = "SELECT user_id FROM message_templates WHERE id = ?";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute([$id]);
            $template = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$template || ($template['user_id'] != $currentUser['id'] && !hasPermission('template.delete'))) {
                http_response_code(403);
                echo json_encode(['error' => 'Permission denied']);
                break;
            }
            
            $query = "UPDATE message_templates SET is_active = 0, updated_at = NOW() WHERE id = ?";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([$id]);
            echo json_encode(['success' => $result]);
            break;
            
        case 'notification':
            $id = $request[1] ?? null;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'Notification ID required']);
                break;
            }
            
            // Check ownership
            $notification = $notificationManager->getNotificationById($id, $currentUser['id']);
            if (!$notification) {
                http_response_code(404);
                echo json_encode(['error' => 'Notification not found or access denied']);
                break;
            }
            
            $result = $notificationManager->deleteNotification($id, $currentUser['id']);
            echo json_encode(['success' => $result]);
            break;
            
        case 'user':
            if (!hasPermission('user.delete')) {
                http_response_code(403);
                echo json_encode(['error' => 'Permission denied']);
                break;
            }
            
            $id = $request[1] ?? null;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'User ID required']);
                break;
            }
            
            if ($id == $currentUser['id']) {
                http_response_code(400);
                echo json_encode(['error' => 'Cannot delete your own account']);
                break;
            }
            
            $result = $auth->deleteUser($id);
            echo json_encode($result);
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
    }
}

// Validation functions
function validateNotificationData($data) {
    $errors = [];
    
    if (empty($data['title'])) {
        $errors['title'] = 'Title is required';
    }
    
    if (empty($data['message'])) {
        $errors['message'] = 'Message is required';
    }
    
    if (empty($data['send_to_type']) || !in_array($data['send_to_type'], ['contact', 'group', 'both'])) {
        $errors['send_to_type'] = 'Valid send_to_type is required';
    }
    
    if (!empty($data['priority']) && !in_array($data['priority'], ['low', 'normal', 'high', 'urgent'])) {
        $errors['priority'] = 'Invalid priority value';
    }
    
    return $errors;
}

function validateContactData($data, $isUpdate = false) {
    $errors = [];
    
    if (empty($data['name'])) {
        $errors['name'] = 'Name is required';
    }
    
    if (empty($data['phone'])) {
        $errors['phone'] = 'Phone is required';
    } elseif (!preg_match('/^628\d{8,13}$/', formatPhoneNumber($data['phone']))) {
        $errors['phone'] = 'Invalid phone format (should be 628xxxxxxxxx)';
    }
    
    if ($isUpdate && empty($data['id'])) {
        $errors['id'] = 'ID is required for update';
    }
    
    return $errors;
}

function validateGroupData($data, $isUpdate = false) {
    $errors = [];
    
    if (empty($data['name'])) {
        $errors['name'] = 'Group name is required';
    }
    
    if (empty($data['group_id'])) {
        $errors['group_id'] = 'Group ID is required';
    } elseif (!preg_match('/^.+@g\.us$/', $data['group_id'])) {
        $errors['group_id'] = 'Invalid WhatsApp group ID format';
    }
    
    if ($isUpdate && empty($data['id'])) {
        $errors['id'] = 'ID is required for update';
    }
    
    return $errors;
}

function validateTemplateData($data, $isUpdate = false) {
    $errors = [];
    
    if (empty($data['title'])) {
        $errors['title'] = 'Template title is required';
    }
    
    if (empty($data['message_template'])) {
        $errors['message_template'] = 'Template message is required';
    }
    
    if ($isUpdate && empty($data['id'])) {
        $errors['id'] = 'ID is required for update';
    }
    
    return $errors;
}

function validateUserData($data) {
    $errors = [];
    
    if (empty($data['username'])) {
        $errors['username'] = 'Username is required';
    }
    
    if (empty($data['email'])) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }
    
    if (empty($data['password'])) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($data['password']) < 6) {
        $errors['password'] = 'Password must be at least 6 characters';
    }
    
    if (empty($data['full_name'])) {
        $errors['full_name'] = 'Full name is required';
    }
    
    return $errors;
}

// Helper functions
function formatPhoneNumber($phone) {
    $cleaned = preg_replace('/\D/', '', $phone);
    
    if (substr($cleaned, 0, 1) === '0') {
        $cleaned = '62' . substr($cleaned, 1);
    } elseif (substr($cleaned, 0, 2) !== '62') {
        $cleaned = '62' . $cleaned;
    }
    
    return $cleaned;
}

function extractTemplateVariables($text) {
    preg_match_all('/\{(\w+)\}/', $text, $matches);
    return array_unique($matches[1]);
}

function getNotificationStats($db, $userId) {
    $query = "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
            SUM(CASE WHEN scheduled_datetime > NOW() THEN 1 ELSE 0 END) as scheduled,
            SUM(sent_count) as total_messages_sent,
            SUM(failed_count) as total_messages_failed
        FROM scheduled_notifications 
        WHERE user_id = ? OR ? IN (SELECT id FROM users WHERE role_id IN (1,2))
    ";
    $stmt = $db->prepare($query);
    $stmt->execute([$userId, $userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getDashboardData($db, $notificationManager, $userId) {
    $stats = getNotificationStats($db, $userId);
    
    // Recent notifications
    $recentNotifications = $notificationManager->getNotifications(5, 0, $userId);
    
    // Upcoming notifications
    $upcomingQuery = "
        SELECT id, title, scheduled_datetime, status 
        FROM scheduled_notifications 
        WHERE (user_id = ? OR ? IN (SELECT id FROM users WHERE role_id IN (1,2)))
        AND status = 'pending' 
        AND scheduled_datetime > NOW() 
        ORDER BY scheduled_datetime ASC 
        LIMIT 5
    ";
    $stmt = $db->prepare($upcomingQuery);
    $stmt->execute([$userId, $userId]);
    $upcomingNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Recent activity
    $activityQuery = "
        SELECT ml.*, sn.title as notification_title
        FROM message_logs ml
        JOIN scheduled_notifications sn ON ml.notification_id = sn.id
        WHERE sn.user_id = ? OR ? IN (SELECT id FROM users WHERE role_id IN (1,2))
        ORDER BY ml.sent_at DESC
        LIMIT 10
    ";
    $stmt = $db->prepare($activityQuery);
    $stmt->execute([$userId, $userId]);
    $recentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'stats' => $stats,
        'recent_notifications' => $recentNotifications,
        'upcoming_notifications' => $upcomingNotifications,
        'recent_activity' => $recentActivity
    ];
}
?>