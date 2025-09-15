<?php
// public/api.php - FIXED VERSION with Template Management
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'auth.php'; // This includes auth and database setup
require_once '../classes/NotificationManager.php';

$notificationManager = new NotificationManager($db);

$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/'));
$endpoint = $request[0] ?? '';

try {
    switch ($method) {
        case 'GET':
            switch ($endpoint) {
                case 'templates':
                    echo json_encode($notificationManager->getTemplates($currentUser['id']));
                    break;
                    
                case 'contacts':
                    echo json_encode($notificationManager->getContacts($currentUser['id']));
                    break;
                    
                case 'groups':
                    echo json_encode($notificationManager->getGroups($currentUser['id']));
                    break;
                    
                case 'notifications':
                    $limit = $_GET['limit'] ?? 50;
                    $offset = $_GET['offset'] ?? 0;
                    echo json_encode($notificationManager->getNotifications($limit, $offset, $currentUser['id']));
                    break;
                    
                case 'logs':
                    if (!hasPermission('log.read')) {
                        http_response_code(403);
                        echo json_encode(['error' => 'Permission denied']);
                        break;
                    }
                    $notification_id = $_GET['notification_id'] ?? null;
                    $limit = $_GET['limit'] ?? 100;
                    echo json_encode($notificationManager->getMessageLogs($notification_id, $limit));
                    break;
                    
                case 'users':
                    if (!hasPermission('user.read')) {
                        http_response_code(403);
                        echo json_encode(['error' => 'Permission denied']);
                        break;
                    }
                    echo json_encode($auth->getAllUsers());
                    break;
                    
                case 'user':
                    if (isset($request[1]) && $request[1] === 'activity') {
                        if (!hasPermission('log.read')) {
                            http_response_code(403);
                            echo json_encode(['error' => 'Permission denied']);
                            break;
                        }
                        $userId = $request[0] ?? null;
                        echo json_encode($auth->getUserActivityLogs($userId));
                    } else {
                        if (!hasPermission('user.read')) {
                            http_response_code(403);
                            echo json_encode(['error' => 'Permission denied']);
                            break;
                        }
                        echo json_encode($auth->getAllUsers());
                    }
                    break;
                    
                case 'categories':
                    // Get notification categories
                    $query = "SELECT * FROM notification_categories ORDER BY name";
                    $stmt = $db->prepare($query);
                    $stmt->execute();
                    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
                    break;
                    
                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Endpoint not found']);
            }
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            switch ($endpoint) {
                case 'notification':
                    // Validasi input
                    if (empty($input['title']) || empty($input['message']) || empty($input['send_to_type'])) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Missing required fields']);
                        break;
                    }

                    // Handle template variables
                    if (!empty($input['template_vars']) && is_array($input['template_vars'])) {
                        // Clean empty variables
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
                    
                    // Add user_id to notification
                    $input['user_id'] = $currentUser['id'];
                    $input['created_by'] = $currentUser['username'];
                    
                    // Buat scheduled_datetime
                    if (!empty($input['scheduled_date']) && !empty($input['scheduled_time'])) {
                        $input['scheduled_datetime'] = $input['scheduled_date'] . ' ' . $input['scheduled_time'];
                    } else {
                        $input['scheduled_datetime'] = date('Y-m-d H:i:s');
                    }
                    
                    $result = $notificationManager->createNotification($input);
                    
                    // Jika action adalah send_now, kirim sekarang
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
                    
                    $result = $notificationManager->sendNotification($input['notification_id']);
                    echo json_encode($result);
                    break;
                    
                case 'contact':
                    if (empty($input['name']) || empty($input['phone'])) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Name and phone required']);
                        break;
                    }
                    
                    $result = $notificationManager->addContact($input['name'], $input['phone'], $currentUser['id']);
                    echo json_encode(['success' => $result]);
                    break;
                    
                case 'group':
                    if (empty($input['name']) || empty($input['group_id'])) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Name and group_id required']);
                        break;
                    }
                    
                    $result = $notificationManager->addGroup($input['name'], $input['group_id'], $input['description'] ?? '', $currentUser['id']);
                    echo json_encode(['success' => $result]);
                    break;
                    
                case 'template':
                    if (empty($input['title']) || empty($input['message_template'])) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Title and message_template required']);
                        break;
                    }
                    
                    try {
                        $query = "INSERT INTO message_templates (title, message_template, category_id, user_id) VALUES (?, ?, ?, ?)";
                        $stmt = $db->prepare($query);
                        $result = $stmt->execute([
                            $input['title'],
                            $input['message_template'],
                            !empty($input['category_id']) ? $input['category_id'] : null,
                            $currentUser['id']
                        ]);
                        
                        if ($result) {
                            echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
                        } else {
                            echo json_encode(['success' => false, 'error' => 'Failed to create template']);
                        }
                    } catch (Exception $e) {
                        http_response_code(500);
                        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                    }
                    break;
                    
                case 'user':
                    if (!hasPermission('user.create')) {
                        http_response_code(403);
                        echo json_encode(['error' => 'Permission denied']);
                        break;
                    }
                    
                    if (empty($input['username']) || empty($input['email']) || empty($input['password']) || empty($input['full_name'])) {
                        http_response_code(400);
                        echo json_encode(['error' => 'All fields required']);
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
                    
                    // Process the template
                    $processed = $notificationManager->previewTemplate($message, $variables, $contactName);
                    
                    // Get list of variables found in template
                    $foundVariables = $notificationManager->getTemplateVariables($message);
                    
                    echo json_encode([
                        'success' => true,
                        'processed_message' => $processed,
                        'variables_found' => $foundVariables,
                        'variables_used' => $variables
                    ]);
                    break;

                case 'get-template-variables':
                    if (empty($input['message'])) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Message required']);
                        break;
                    }
                    
                    $variables = $notificationManager->getTemplateVariables($input['message']);
                    echo json_encode([
                        'success' => true,
                        'variables' => $variables
                    ]);
                    break;
                    
                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Endpoint not found']);
            }
            break;
            
        case 'PUT':
            $input = json_decode(file_get_contents('php://input'), true);
            
            switch ($endpoint) {
                case 'contact':
                    if (empty($input['id']) || empty($input['name']) || empty($input['phone'])) {
                        http_response_code(400);
                        echo json_encode(['error' => 'ID, name and phone required']);
                        break;
                    }
                    
                    try {
                        $query = "UPDATE contacts SET name = ?, phone = ? WHERE id = ? AND (user_id = ? OR user_id IS NULL)";
                        $stmt = $db->prepare($query);
                        $result = $stmt->execute([$input['name'], $input['phone'], $input['id'], $currentUser['id']]);
                        echo json_encode(['success' => $result]);
                    } catch (Exception $e) {
                        http_response_code(500);
                        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                    }
                    break;
                    
                case 'group':
                    if (empty($input['id']) || empty($input['name']) || empty($input['group_id'])) {
                        http_response_code(400);
                        echo json_encode(['error' => 'ID, name and group_id required']);
                        break;
                    }
                    
                    try {
                        $query = "UPDATE `wa_groups` SET name = ?, group_id = ?, description = ? WHERE id = ? AND (user_id = ? OR user_id IS NULL)";
                        $stmt = $db->prepare($query);
                        $result = $stmt->execute([
                            $input['name'], 
                            $input['group_id'], 
                            $input['description'] ?? '', 
                            $input['id'], 
                            $currentUser['id']
                        ]);
                        echo json_encode(['success' => $result]);
                    } catch (Exception $e) {
                        http_response_code(500);
                        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                    }
                    break;
                    
                case 'template':
                    if (empty($input['id']) || empty($input['title']) || empty($input['message_template'])) {
                        http_response_code(400);
                        echo json_encode(['error' => 'ID, title and message_template required']);
                        break;
                    }
                    
                    try {
                        // Check if user owns this template or has permission
                        $checkQuery = "SELECT user_id FROM message_templates WHERE id = ?";
                        $checkStmt = $db->prepare($checkQuery);
                        $checkStmt->execute([$input['id']]);
                        $template = $checkStmt->fetch(PDO::FETCH_ASSOC);
                        
                        if (!$template || ($template['user_id'] != $currentUser['id'] && !hasPermission('template.update'))) {
                            http_response_code(403);
                            echo json_encode(['error' => 'Permission denied']);
                            break;
                        }
                        
                        $query = "UPDATE message_templates SET title = ?, message_template = ?, category_id = ? WHERE id = ?";
                        $stmt = $db->prepare($query);
                        $result = $stmt->execute([
                            $input['title'],
                            $input['message_template'],
                            !empty($input['category_id']) ? $input['category_id'] : null,
                            $input['id']
                        ]);
                        echo json_encode(['success' => $result]);
                    } catch (Exception $e) {
                        http_response_code(500);
                        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                    }
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
            break;
            
        case 'DELETE':
            switch ($endpoint) {
                case 'contact':
                    $id = $request[1] ?? null;
                    if (!$id) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Contact ID required']);
                        break;
                    }
                    
                    $result = $notificationManager->deleteContact($id);
                    echo json_encode(['success' => $result]);
                    break;
                    
                case 'group':
                    $id = $request[1] ?? null;
                    if (!$id) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Group ID required']);
                        break;
                    }
                    
                    $result = $notificationManager->deleteGroup($id);
                    echo json_encode(['success' => $result]);
                    break;
                    
                case 'template':
                    $id = $request[1] ?? null;
                    if (!$id) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Template ID required']);
                        break;
                    }
                    
                    try {
                        // Check if user owns this template or has permission
                        $checkQuery = "SELECT user_id FROM message_templates WHERE id = ?";
                        $checkStmt = $db->prepare($checkQuery);
                        $checkStmt->execute([$id]);
                        $template = $checkStmt->fetch(PDO::FETCH_ASSOC);
                        
                        if (!$template || ($template['user_id'] != $currentUser['id'] && !hasPermission('template.delete'))) {
                            http_response_code(403);
                            echo json_encode(['error' => 'Permission denied']);
                            break;
                        }
                        
                        $query = "UPDATE message_templates SET is_active = 0 WHERE id = ?";
                        $stmt = $db->prepare($query);
                        $result = $stmt->execute([$id]);
                        echo json_encode(['success' => $result]);
                    } catch (Exception $e) {
                        http_response_code(500);
                        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                    }
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
                    
                    $result = $auth->deleteUser($id);
                    echo json_encode($result);
                    break;
                    
                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Endpoint not found']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }

} catch (Exception $e) {
    error_log('API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}
?>