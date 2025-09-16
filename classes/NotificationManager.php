<?php
// classes/NotificationManager.php
require_once __DIR__ . '/WhatsAppAPI.php';

class NotificationManager {
    private $conn;
    private $whatsapp;

    public function __construct($database) {
        $this->conn = $database;
        $this->whatsapp = new WhatsAppAPI($database);
    }

    // Mengirim notifikasi yang sudah terjadwal
    public function sendScheduledNotifications() {
        $query = "SELECT * FROM scheduled_notifications 
                  WHERE status = 'pending' AND is_active = 1 
                  AND scheduled_datetime <= NOW()";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $results = [];
        foreach ($notifications as $notification) {
            $results[] = $this->sendNotification($notification['id']);
        }

        return $results;
    }

    private function getNotificationContacts($notification_id) {
        $query = "SELECT c.* FROM contacts c 
                  JOIN notification_contacts nc ON c.id = nc.contact_id 
                  WHERE nc.notification_id = ? AND c.is_active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$notification_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getNotificationGroups($notification_id) {
        $query = "SELECT g.* FROM `wa_groups` g 
                  JOIN notification_groups ng ON g.id = ng.group_id 
                  WHERE ng.notification_id = ? AND g.is_active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$notification_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function logMessage($notification_id, $recipient_type, $recipient_id, 
                               $phone_number, $message, $response_data, $status) {
        $query = "INSERT INTO message_logs 
                  (notification_id, recipient_type, recipient_id, phone_number, 
                   message, response_data, status) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $notification_id, $recipient_type, $recipient_id, $phone_number,
            $message, json_encode($response_data), $status
        ]);
    }

    private function updateNotificationStatus($notification_id, $status) {
        $query = "UPDATE scheduled_notifications SET status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$status, $notification_id]);
    }

    private function scheduleNextRepeat($notification) {
        $current_datetime = new DateTime($notification['scheduled_datetime']);
        
        switch ($notification['repeat_type']) {
            case 'daily':
                $current_datetime->add(new DateInterval('P' . $notification['repeat_interval'] . 'D'));
                break;
            case 'weekly':
                $current_datetime->add(new DateInterval('P' . ($notification['repeat_interval'] * 7) . 'D'));
                break;
            case 'monthly':
                $current_datetime->add(new DateInterval('P' . $notification['repeat_interval'] . 'M'));
                break;
        }

        // Cek apakah masih dalam batas waktu pengulangan
        if ($notification['repeat_until'] && $current_datetime->format('Y-m-d') > $notification['repeat_until']) {
            return; // Tidak membuat pengulangan lagi
        }

        // Buat notifikasi baru untuk pengulangan
        $query = "INSERT INTO scheduled_notifications 
                  (title, message, template_id, send_to_type, scheduled_datetime, 
                   repeat_type, repeat_interval, repeat_until, created_by) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $notification['title'],
            $notification['message'],
            $notification['template_id'],
            $notification['send_to_type'],
            $current_datetime->format('Y-m-d H:i:s'),
            $notification['repeat_type'],
            $notification['repeat_interval'],
            $notification['repeat_until'],
            $notification['created_by']
        ]);

        $new_notification_id = $this->conn->lastInsertId();

        // Copy kontak dan grup untuk notifikasi baru
        $this->copyNotificationRecipients($notification['id'], $new_notification_id);
    }

    private function copyNotificationRecipients($old_id, $new_id) {
        // Copy kontak
        $query = "INSERT INTO notification_contacts (notification_id, contact_id)
                  SELECT ?, contact_id FROM notification_contacts WHERE notification_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$new_id, $old_id]);

        // Copy grup
        $query = "INSERT INTO notification_groups (notification_id, group_id)
                  SELECT ?, group_id FROM notification_groups WHERE notification_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$new_id, $old_id]);
    }

    // Get all notifications with user filtering
    public function getNotifications($limit = 50, $offset = 0, $userId = null) {
        // Ensure limit and offset are integers
        $limit = (int)$limit;
        $offset = (int)$offset;
        
        if ($userId && !$this->hasAdminPermission()) {
            // Regular user - only see their own notifications
            $query = "SELECT sn.*, 
                             COUNT(DISTINCT nc.id) as contact_count,
                             COUNT(DISTINCT ng.id) as group_count,
                             u.full_name as created_by_name
                      FROM scheduled_notifications sn 
                      LEFT JOIN notification_contacts nc ON sn.id = nc.notification_id
                      LEFT JOIN notification_groups ng ON sn.id = ng.notification_id
                      LEFT JOIN users u ON sn.user_id = u.id
                      WHERE sn.user_id = ?
                      GROUP BY sn.id
                      ORDER BY sn.created_at DESC 
                      LIMIT {$limit} OFFSET {$offset}";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$userId]);
        } else {
            // Admin - see all notifications
            $query = "SELECT sn.*, 
                             COUNT(DISTINCT nc.id) as contact_count,
                             COUNT(DISTINCT ng.id) as group_count,
                             u.full_name as created_by_name
                      FROM scheduled_notifications sn 
                      LEFT JOIN notification_contacts nc ON sn.id = nc.notification_id
                      LEFT JOIN notification_groups ng ON sn.id = ng.notification_id
                      LEFT JOIN users u ON sn.user_id = u.id
                      GROUP BY sn.id
                      ORDER BY sn.created_at DESC 
                      LIMIT {$limit} OFFSET {$offset}";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get templates with user filtering  
    public function getTemplates($userId = null) {
        $query = "SELECT mt.*, nc.name as category_name 
                  FROM message_templates mt 
                  LEFT JOIN notification_categories nc ON mt.category_id = nc.id 
                  WHERE mt.is_active = 1 AND (mt.user_id IS NULL OR mt.user_id = ?)
                  ORDER BY nc.name, mt.title";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get contacts with user filtering
    public function getContacts($userId = null) {
        if ($userId && !$this->hasAdminPermission()) {
            // Regular user - only see their own contacts and shared ones
            $query = "SELECT * FROM contacts WHERE (user_id IS NULL OR user_id = ?) AND is_active = 1 ORDER BY name";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$userId]);
        } else {
            // Admin - see all contacts
            $query = "SELECT * FROM contacts WHERE is_active = 1 ORDER BY name";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get groups with user filtering
    public function getGroups($userId = null) {
        if ($userId && !$this->hasAdminPermission()) {
            // Regular user - only see their own groups and shared ones
            $query = "SELECT * FROM `wa_groups` WHERE (user_id IS NULL OR user_id = ?) AND is_active = 1 ORDER BY name";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$userId]);
        } else {
            // Admin - see all groups
            $query = "SELECT * FROM `wa_groups` WHERE is_active = 1 ORDER BY name";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Add contact with user ownership
    public function addContact($name, $phone, $userId = null) {
        $query = "INSERT INTO contacts (name, phone, user_id) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$name, $phone, $userId]);
    }

    // Add group with user ownership
    public function addGroup($name, $group_id, $description = '', $userId = null) {
        $query = "INSERT INTO `wa_groups` (name, group_id, description, user_id) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$name, $group_id, $description, $userId]);
    }
    
    // Helper method to check admin permissions
    private function hasAdminPermission() {
        // This would be implemented based on your session management
        // For now, we'll assume if user_id is 1, they're admin
        return isset($_SESSION['user_id']) && ($_SESSION['role_name'] === 'Super Admin' || $_SESSION['role_name'] === 'Admin');
    }

    // Set user context for operations
    public function setUserId($userId) {
        $this->userId = $userId;
    }

    // Delete contact
    public function deleteContact($id) {
        $query = "UPDATE contacts SET is_active = 0 WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }

    // Delete group
    public function deleteGroup($id) {
        $query = "UPDATE `wa_groups` SET is_active = 0 WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }

    // Get message logs
    public function getMessageLogs($notification_id = null, $limit = 100) {
        if ($notification_id) {
            $query = "SELECT ml.*, sn.title as notification_title 
                      FROM message_logs ml
                      JOIN scheduled_notifications sn ON ml.notification_id = sn.id
                      WHERE ml.notification_id = ?
                      ORDER BY ml.sent_at DESC 
                      LIMIT ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$notification_id, $limit]);
        } else {
            $query = "SELECT ml.*, sn.title as notification_title 
                      FROM message_logs ml
                      JOIN scheduled_notifications sn ON ml.notification_id = sn.id
                      ORDER BY ml.sent_at DESC 
                      LIMIT ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$limit]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createNotification($data) {
        try {
            $this->conn->beginTransaction();

            $query = "INSERT INTO scheduled_notifications 
                    (title, message, template_id, template_variables, send_to_type, scheduled_datetime, 
                    repeat_type, repeat_interval, repeat_until, created_by, user_id) 
                    VALUES (:title, :message, :template_id, :template_variables, :send_to_type, :scheduled_datetime,
                            :repeat_type, :repeat_interval, :repeat_until, :created_by, :user_id)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':title' => $data['title'],
                ':message' => $data['message'],
                ':template_id' => $data['template_id'] ?? null,
                ':template_variables' => $data['template_variables'] ?? null, // Store as JSON
                ':send_to_type' => $data['send_to_type'],
                ':scheduled_datetime' => $data['scheduled_datetime'],
                ':repeat_type' => $data['repeat_type'] ?? 'once',
                ':repeat_interval' => $data['repeat_interval'] ?? 1,
                ':repeat_until' => $data['repeat_until'] ?? null,
                ':created_by' => $data['created_by'] ?? 'system',
                ':user_id' => $data['user_id'] ?? null
            ]);

            $notification_id = $this->conn->lastInsertId();

            // Add contacts and groups (existing code)
            if (!empty($data['contacts'])) {
                foreach ($data['contacts'] as $contact_id) {
                    $query = "INSERT INTO notification_contacts (notification_id, contact_id) VALUES (?, ?)";
                    $stmt = $this->conn->prepare($query);
                    $stmt->execute([$notification_id, $contact_id]);
                }
            }

            if (!empty($data['groups'])) {
                foreach ($data['groups'] as $group_id) {
                    $query = "INSERT INTO notification_groups (notification_id, group_id) VALUES (?, ?)";
                    $stmt = $this->conn->prepare($query);
                    $stmt->execute([$notification_id, $group_id]);
                }
            }

            $this->conn->commit();
            return ['success' => true, 'id' => $notification_id];

        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function sendNotification($notification_id) {
        try {
            // Get notification data
            $query = "SELECT * FROM scheduled_notifications WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$notification_id]);
            $notification = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$notification) {
                throw new Exception("Notification not found");
            }

            $success_count = 0;
            $fail_count = 0;

            // Parse template variables if they exist
            $templateVars = [];
            if (!empty($notification['template_variables'])) {
                $templateVars = json_decode($notification['template_variables'], true) ?? [];
            }

            // Send to contacts
            if ($notification['send_to_type'] == 'contact' || $notification['send_to_type'] == 'both') {
                $contacts = $this->getNotificationContacts($notification_id);
                foreach ($contacts as $contact) {
                    // Process template for each contact
                    $personalizedMessage = $this->processTemplateForContact($notification['message'], $templateVars, $contact);
                    
                    $result = $this->whatsapp->sendMessage($contact['phone'], $personalizedMessage);
                    
                    $this->logMessage($notification_id, 'contact', $contact['id'], 
                                    $contact['phone'], $personalizedMessage, 
                                    $result['response'], $result['success'] ? 'success' : 'failed');
                    
                    if ($result['success']) {
                        $success_count++;
                    } else {
                        $fail_count++;
                    }
                }
            }

            // Send to groups
            if ($notification['send_to_type'] == 'group' || $notification['send_to_type'] == 'both') {
                $groups = $this->getNotificationGroups($notification_id);
                foreach ($groups as $group) {
                    // Process template for group (no personalization for groups)
                    $processedMessage = $this->processTemplate($notification['message'], $templateVars);
                    
                    $result = $this->whatsapp->sendMessage($group['group_id'], $processedMessage, true);
                    
                    $this->logMessage($notification_id, 'group', $group['id'], 
                                    $group['group_id'], $processedMessage, 
                                    $result['response'], $result['success'] ? 'success' : 'failed');
                    
                    if ($result['success']) {
                        $success_count++;
                    } else {
                        $fail_count++;
                    }
                }
            }

            // Update notification status
            $status = $fail_count == 0 ? 'sent' : ($success_count == 0 ? 'failed' : 'sent');
            $this->updateNotificationStatus($notification_id, $status);

            // Handle repeating notifications
            if ($notification['repeat_type'] != 'once') {
                $this->scheduleNextRepeat($notification);
            }

            return [
                'success' => true, 
                'sent' => $success_count, 
                'failed' => $fail_count,
                'notification_id' => $notification_id
            ];

        } catch (Exception $e) {
            $this->updateNotificationStatus($notification_id, 'failed');
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // Enhanced template processing with contact personalization
    public function processTemplateForContact($template, $variables, $contact) {
        // Add contact-specific variables
        $contactVars = array_merge($variables, [
            'name' => $contact['name'],
            // Add other contact-specific data if available
        ]);
        
        return $this->processTemplate($template, $contactVars);
    }

    // Enhanced template processing
    public function processTemplate($template, $variables) {
        // Add default variables if not provided
        $defaultVars = [
            'date' => date('d/m/Y'),
            'time' => date('H:i'),
        ];
        
        $allVariables = array_merge($defaultVars, $variables);
        
        foreach ($allVariables as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }
        
        return $template;
    }

    // Test template processing (for API endpoint)
    public function previewTemplate($template, $variables, $contactName = 'John Doe') {
        $testVars = array_merge($variables, ['name' => $contactName]);
        return $this->processTemplate($template, $testVars);
    }

    // Get available template variables from a message
    public function getTemplateVariables($message) {
        preg_match_all('/\{(\w+)\}/', $message, $matches);
        return array_unique($matches[1]);
    }

    // Get notification by ID with user filtering
    public function getNotificationById($id, $userId = null) {
        if ($userId && !$this->hasAdminPermission()) {
            // Regular user - only see their own notifications
            $query = "SELECT sn.*, 
                            COUNT(DISTINCT nc.id) as contact_count,
                            COUNT(DISTINCT ng.id) as group_count,
                            u.full_name as created_by_name
                    FROM scheduled_notifications sn 
                    LEFT JOIN notification_contacts nc ON sn.id = nc.notification_id
                    LEFT JOIN notification_groups ng ON sn.id = ng.notification_id
                    LEFT JOIN users u ON sn.user_id = u.id
                    WHERE sn.id = ? AND sn.user_id = ?
                    GROUP BY sn.id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id, $userId]);
        } else {
            // Admin - see all notifications
            $query = "SELECT sn.*, 
                            COUNT(DISTINCT nc.id) as contact_count,
                            COUNT(DISTINCT ng.id) as group_count,
                            u.full_name as created_by_name
                    FROM scheduled_notifications sn 
                    LEFT JOIN notification_contacts nc ON sn.id = nc.notification_id
                    LEFT JOIN notification_groups ng ON sn.id = ng.notification_id
                    LEFT JOIN users u ON sn.user_id = u.id
                    WHERE sn.id = ?
                    GROUP BY sn.id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
        }
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If notification found, also get the associated contacts and groups
        if ($result) {
            $result['contacts'] = $this->getNotificationContacts($id);
            $result['groups'] = $this->getNotificationGroups($id);
        }
        
        return $result;
    }

    // Delete notification with user permission check
    public function deleteNotification($id, $userId = null) {
        try {
            // First check if notification exists and user has permission to delete it
            if ($userId && !$this->hasAdminPermission()) {
                // Regular user - only delete their own notifications
                $checkQuery = "SELECT id FROM scheduled_notifications WHERE id = ? AND user_id = ?";
                $checkStmt = $this->conn->prepare($checkQuery);
                $checkStmt->execute([$id, $userId]);
                
                if (!$checkStmt->fetch()) {
                    return false; // Notification not found or access denied
                }
            } else {
                // Admin - can delete any notification, but still check if it exists
                $checkQuery = "SELECT id FROM scheduled_notifications WHERE id = ?";
                $checkStmt = $this->conn->prepare($checkQuery);
                $checkStmt->execute([$id]);
                
                if (!$checkStmt->fetch()) {
                    return false; // Notification not found
                }
            }

            $this->conn->beginTransaction();

            // Soft delete the notification
            $query = "DELETE FROM scheduled_notifications WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$id]);

            if ($result) {
                // Also soft delete related notification_contacts and notification_groups
                // This prevents orphaned references
                $deleteContactsQuery = "DELETE FROM notification_contacts WHERE notification_id = ?";
                $stmt = $this->conn->prepare($deleteContactsQuery);
                $stmt->execute([$id]);

                $deleteGroupsQuery = "DELETE FROM notification_groups WHERE notification_id = ?";
                $stmt = $this->conn->prepare($deleteGroupsQuery);
                $stmt->execute([$id]);

                $this->conn->commit();
                return true;
            } else {
                $this->conn->rollback();
                return false;
            }

        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error deleting notification: " . $e->getMessage());
            return false;
        }
    }
}
?>