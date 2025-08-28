<?php
// public/api.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'auth_check.php'; // This includes auth and database setup
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
                    requirePermission('log.read');
                    $notification_id = $_GET['notification_id'] ?? null;
                    $limit = $_GET['limit'] ?? 100;
                    echo json_encode($notificationManager->getMessageLogs($notification_id, $limit));
                    break;
                    
                case 'users':
                    requirePermission('user.read');
                    echo json_encode($auth->getAllUsers());
                    break;
                    
                case 'user':
                    if (isset($request[1]) && $request[1] === 'activity') {
                        requirePermission('log.read');
                        $userId = $request[0] ?? null;
                        echo json_encode($auth->getUserActivityLogs($userId));
                    } else {
                        requirePermission('user.read');
                        echo json_encode($auth->getAllUsers());
                    }
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
                    requirePermission('notification.create');
                    
                    // Validasi input
                    if (empty($input['title']) || empty($input['message']) || empty($input['send_to_type'])) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Missing required fields']);
                        break;
                    }

                    if (!empty($input['template_vars']) && is_array($input['template_vars'])) {
                        // Store template variables for processing during send
                        $input['template_variables'] = json_encode($input['template_vars']);
                        
                        // For immediate sending, process the template now
                        if (isset($input['action']) && $input['action'] === 'send_now') {
                            $processedMessage = $notificationManager->processTemplate($input['message'], $input['template_vars']);
                            $input['message'] = $processedMessage;
                        }
                    }
                    
                    // Add user_id to notification
                    $input['user_id'] = $currentUser['id'];
                    
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
                    requirePermission('notification.create');
                    
                    if (empty($input['notification_id'])) {
                        http_response_code(400);
                        echo json_encode(['error' => 'notification_id required']);
                        break;
                    }
                    
                    $result = $notificationManager->sendNotification($input['notification_id']);
                    echo json_encode($result);
                    break;
                    
                case 'contact':
                    requirePermission('contact.create');
                    
                    if (empty($input['name']) || empty($input['phone'])) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Name and phone required']);
                        break;
                    }
                    
                    $result = $notificationManager->addContact($input['name'], $input['phone'], $currentUser['id']);
                    echo json_encode(['success' => $result]);
                    break;
                    
                case 'group':
                    requirePermission('group.create');
                    
                    if (empty($input['name']) || empty($input['group_id'])) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Name and group_id required']);
                        break;
                    }
                    
                    $result = $notificationManager->addGroup($input['name'], $input['group_id'], $input['description'] ?? '', $currentUser['id']);
                    echo json_encode(['success' => $result]);
                    break;
                    
                case 'user':
                    requirePermission('user.create');
                    
                    if (empty($input['username']) || empty($input['email']) || empty($input['password']) || empty($input['full_name'])) {
                        http_response_code(400);
                        echo json_encode(['error' => 'All fields required']);
                        break;
                    }
                    
                    $result = $auth->createUser($input);
                    echo json_encode($result);
                    break;
                    
                case 'process-template':
                    if (empty($input['template']) || empty($input['variables'])) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Template and variables required']);
                        break;
                    }
                    
                    $processed = $notificationManager->processTemplate($input['template'], $input['variables']);
                    echo json_encode(['processed_message' => $processed]);
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

                // In the notification creation case, update to handle template variables:
                case 'notification':
                    requirePermission('notification.create');
                    
                    // Validation
                    if (empty($input['title']) || empty($input['message']) || empty($input['send_to_type'])) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Missing required fields']);
                        break;
                    }
                    
                    // Add user_id to notification
                    $input['user_id'] = $currentUser['id'];
                    $input['created_by'] = $currentUser['username'];
                    
                    // Handle template variables
                    if (!empty($input['template_vars']) && is_array($input['template_vars'])) {
                        // Clean empty variables
                        $templateVars = array_filter($input['template_vars'], function($value) {
                            return !empty(trim($value));
                        });
                        
                        if (!empty($templateVars)) {
                            $input['template_variables'] = json_encode($templateVars);
                        }
                    }
                    
                    // Create scheduled_datetime
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
                    
                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Endpoint not found']);
            }
            break;
            
        case 'PUT':
            $input = json_decode(file_get_contents('php://input'), true);
            
            switch ($endpoint) {
                case 'contact':
                    requirePermission('contact.update');
                    
                    if (empty($input['id']) || empty($input['name']) || empty($input['phone'])) {
                        http_response_code(400);
                        echo json_encode(['error' => 'ID, name and phone required']);
                        break;
                    }
                    
                    // Update contact (implementation needed in NotificationManager)
                    echo json_encode(['success' => true, 'message' => 'Update contact functionality to be implemented']);
                    break;
                    
                case 'group':
                    requirePermission('group.update');
                    
                    if (empty($input['id']) || empty($input['name']) || empty($input['group_id'])) {
                        http_response_code(400);
                        echo json_encode(['error' => 'ID, name and group_id required']);
                        break;
                    }
                    
                    // Update group (implementation needed in NotificationManager)
                    echo json_encode(['success' => true, 'message' => 'Update group functionality to be implemented']);
                    break;
                    
                case 'user':
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
                    requirePermission('contact.delete');
                    
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
                    requirePermission('group.delete');
                    
                    $id = $request[1] ?? null;
                    if (!$id) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Group ID required']);
                        break;
                    }
                    
                    $result = $notificationManager->deleteGroup($id);
                    echo json_encode(['success' => $result]);
                    break;
                    
                case 'user':
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
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
// public/api.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../classes/NotificationManager.php';

$database = new Database();
$db = $database->getConnection();
$notificationManager = new NotificationManager($db);

$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/'));
$endpoint = $request[0] ?? '';

try {
    switch ($method) {
        case 'GET':
            switch ($endpoint) {
                case 'templates':
                    echo json_encode($notificationManager->getTemplates());
                    break;
                    
                case 'contacts':
                    echo json_encode($notificationManager->getContacts());
                    break;
                    
                case 'groups':
                    echo json_encode($notificationManager->getGroups());
                    break;
                    
                case 'notifications':
                    $limit = $_GET['limit'] ?? 50;
                    $offset = $_GET['offset'] ?? 0;
                    echo json_encode($notificationManager->getNotifications($limit, $offset));
                    break;
                    
                case 'logs':
                    $notification_id = $_GET['notification_id'] ?? null;
                    $limit = $_GET['limit'] ?? 100;
                    echo json_encode($notificationManager->getMessageLogs($notification_id, $limit));
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
                    
                    $result = $notificationManager->addContact($input['name'], $input['phone']);
                    echo json_encode(['success' => $result]);
                    break;
                    
                case 'group':
                    if (empty($input['name']) || empty($input['group_id'])) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Name and group_id required']);
                        break;
                    }
                    
                    $result = $notificationManager->addGroup($input['name'], $input['group_id'], $input['description'] ?? '');
                    echo json_encode(['success' => $result]);
                    break;
                    
                case 'process-template':
                    if (empty($input['template']) || empty($input['variables'])) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Template and variables required']);
                        break;
                    }
                    
                    $processed = $notificationManager->processTemplate($input['template'], $input['variables']);
                    echo json_encode(['processed_message' => $processed]);
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
                    
                    // Update contact (implementation needed in NotificationManager)
                    echo json_encode(['success' => true, 'message' => 'Update contact functionality to be implemented']);
                    break;
                    
                case 'group':
                    if (empty($input['id']) || empty($input['name']) || empty($input['group_id'])) {
                        http_response_code(400);
                        echo json_encode(['error' => 'ID, name and group_id required']);
                        break;
                    }
                    
                    // Update group (implementation needed in NotificationManager)
                    echo json_encode(['success' => true, 'message' => 'Update group functionality to be implemented']);
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
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>